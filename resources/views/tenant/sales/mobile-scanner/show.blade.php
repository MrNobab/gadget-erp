<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Mobile Scanner - {{ $tenant->name }}</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/@zxing/library@0.21.3/umd/index.min.js" defer></script>
</head>
<body class="min-h-screen bg-slate-950 text-white">
    <main class="min-h-screen flex flex-col">
        <header class="px-4 py-4 border-b border-white/10 bg-slate-900">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h1 class="text-lg font-bold">{{ $tenant->name }}</h1>
                    <p class="text-sm text-slate-300">{{ $scannerSession->name ?: 'POS scanner' }} / Code {{ $scannerSession->pair_code }}</p>
                </div>

                <a href="{{ route('tenant.mobile-scanner.index', $tenant) }}" class="px-3 py-2 rounded-lg bg-white/10 text-sm font-semibold">
                    Change
                </a>
            </div>
        </header>

        <section class="flex-1 p-4 space-y-4">
            <div class="relative overflow-hidden rounded-xl border border-white/10 bg-black aspect-[3/4] max-h-[62vh]">
                <video id="preview" class="h-full w-full object-cover" autoplay muted playsinline></video>
                <div class="pointer-events-none absolute inset-x-8 top-1/2 h-24 -translate-y-1/2 rounded-xl border-2 border-emerald-400 shadow-[0_0_0_999px_rgba(2,6,23,.45)]"></div>
            </div>

            <div id="scanStatus" class="rounded-xl border border-white/10 bg-white/10 px-4 py-3 text-sm text-slate-200">
                Press Start Camera, then point the phone at a product barcode.
            </div>

            <div class="grid grid-cols-2 gap-3">
                <button type="button" id="startCameraBtn" class="rounded-xl bg-emerald-500 px-4 py-3 text-sm font-bold text-slate-950">
                    Start Camera
                </button>

                <button type="button" id="stopCameraBtn" class="rounded-xl bg-white/10 px-4 py-3 text-sm font-bold text-white">
                    Stop
                </button>
            </div>

            <form id="manualScanForm" class="rounded-xl border border-white/10 bg-white/10 p-4 space-y-3">
                <label class="block text-sm font-semibold text-slate-200">Manual Barcode</label>
                <input type="text" id="manualCodeInput" autocomplete="off" placeholder="Type code if camera cannot read" class="w-full rounded-lg border border-white/10 bg-slate-900 px-4 py-3 text-white">
                <button type="submit" class="w-full rounded-lg bg-white px-4 py-3 text-sm font-bold text-slate-950">
                    Send Code
                </button>
            </form>
        </section>
    </main>

    <script>
        const scanStoreUrl = @json(route('tenant.mobile-scanner.scans.store', [$tenant, $scannerSession->token]));
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const video = document.getElementById('preview');
        const statusBox = document.getElementById('scanStatus');
        const startCameraBtn = document.getElementById('startCameraBtn');
        const stopCameraBtn = document.getElementById('stopCameraBtn');
        const manualScanForm = document.getElementById('manualScanForm');
        const manualCodeInput = document.getElementById('manualCodeInput');

        let stream = null;
        let scanning = false;
        let nativeDetector = null;
        let zxingReader = null;
        let lastSubmittedCode = '';
        let lastSubmittedAt = 0;

        function setStatus(message, tone = 'neutral') {
            statusBox.textContent = message;
            statusBox.classList.remove('bg-white/10', 'text-slate-200', 'bg-emerald-500/20', 'text-emerald-100', 'bg-red-500/20', 'text-red-100');

            if (tone === 'success') {
                statusBox.classList.add('bg-emerald-500/20', 'text-emerald-100');
                return;
            }

            if (tone === 'error') {
                statusBox.classList.add('bg-red-500/20', 'text-red-100');
                return;
            }

            statusBox.classList.add('bg-white/10', 'text-slate-200');
        }

        function canSubmit(code) {
            const now = Date.now();

            if (!code || (code === lastSubmittedCode && now - lastSubmittedAt < 1400)) {
                return false;
            }

            lastSubmittedCode = code;
            lastSubmittedAt = now;

            return true;
        }

        async function submitScan(rawCode) {
            const code = String(rawCode || '').trim();

            if (!canSubmit(code)) {
                return;
            }

            setStatus(`Sending ${code}...`);

            try {
                const response = await fetch(scanStoreUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ code }),
                });

                const payload = await response.json();

                if (!response.ok || !payload.ok) {
                    setStatus(payload.message || 'Could not send this scan.', 'error');
                    return;
                }

                setStatus(payload.message || `Sent ${code}`, payload.found ? 'success' : 'error');

                if (navigator.vibrate) {
                    navigator.vibrate(payload.found ? 80 : [80, 80, 80]);
                }
            } catch (error) {
                setStatus('Network error while sending scan.', 'error');
            }
        }

        async function startCamera() {
            if (scanning) {
                return;
            }

            setStatus('Starting camera...');

            try {
                if ('BarcodeDetector' in window) {
                    await startNativeScanner();
                    return;
                }

                if (window.ZXing?.BrowserMultiFormatReader) {
                    await startZxingScanner();
                    return;
                }

                setStatus('Camera barcode scanning is not supported in this browser. Use manual barcode entry below.', 'error');
            } catch (error) {
                setStatus('Could not start camera. Allow camera permission and try again.', 'error');
            }
        }

        async function startNativeScanner() {
            nativeDetector = new BarcodeDetector({
                formats: ['code_128', 'code_39', 'ean_13', 'ean_8', 'upc_a', 'upc_e', 'qr_code'],
            });

            stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: { ideal: 'environment' },
                    width: { ideal: 1280 },
                    height: { ideal: 720 },
                },
                audio: false,
            });

            video.srcObject = stream;
            await video.play();
            scanning = true;
            setStatus('Scanner running. Point at a barcode.');
            requestAnimationFrame(nativeScanLoop);
        }

        async function nativeScanLoop() {
            if (!scanning || !nativeDetector) {
                return;
            }

            try {
                const barcodes = await nativeDetector.detect(video);

                if (barcodes.length > 0) {
                    await submitScan(barcodes[0].rawValue);
                }
            } catch (error) {
                // Some devices throw while the video is still warming up. Keep scanning.
            }

            requestAnimationFrame(nativeScanLoop);
        }

        async function startZxingScanner() {
            const hints = new Map();
            const formats = [
                ZXing.BarcodeFormat.CODE_128,
                ZXing.BarcodeFormat.CODE_39,
                ZXing.BarcodeFormat.EAN_13,
                ZXing.BarcodeFormat.EAN_8,
                ZXing.BarcodeFormat.UPC_A,
                ZXing.BarcodeFormat.UPC_E,
                ZXing.BarcodeFormat.QR_CODE,
            ].filter(Boolean);

            hints.set(ZXing.DecodeHintType.POSSIBLE_FORMATS, formats);
            zxingReader = new ZXing.BrowserMultiFormatReader(hints);
            scanning = true;
            setStatus('Scanner running. Point at a barcode.');

            await zxingReader.decodeFromVideoDevice(null, 'preview', function (result) {
                if (result) {
                    submitScan(result.getText());
                }
            });
        }

        function stopCamera() {
            scanning = false;
            nativeDetector = null;

            if (zxingReader) {
                zxingReader.reset();
                zxingReader = null;
            }

            if (stream) {
                stream.getTracks().forEach(track => track.stop());
                stream = null;
            }

            video.srcObject = null;
            setStatus('Camera stopped.');
        }

        startCameraBtn.addEventListener('click', startCamera);
        stopCameraBtn.addEventListener('click', stopCamera);

        manualScanForm.addEventListener('submit', function (event) {
            event.preventDefault();
            submitScan(manualCodeInput.value);
            manualCodeInput.value = '';
            manualCodeInput.focus();
        });

        window.addEventListener('beforeunload', stopCamera);
    </script>
</body>
</html>
