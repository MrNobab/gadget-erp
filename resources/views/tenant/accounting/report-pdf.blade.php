<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $report['title'] }}</title>

    <style>
        @page {
            margin: 22px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #111;
            font-size: 10px;
        }

        .header {
            width: 100%;
            border-bottom: 1px solid #333;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }

        .header td {
            vertical-align: top;
        }

        .logo {
            max-width: 120px;
            max-height: 65px;
        }

        .company {
            text-align: right;
            line-height: 1.4;
        }

        .company-name {
            font-size: 17px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin: 8px 0 2px;
        }

        .subtitle {
            text-align: center;
            font-size: 11px;
            margin-bottom: 10px;
        }

        table.report {
            width: 100%;
            border-collapse: collapse;
        }

        table.report th,
        table.report td {
            border: 1px solid #333;
            padding: 5px;
        }

        table.report th {
            background: #f3f4f6;
            font-weight: bold;
            text-align: left;
        }

        .totals {
            margin-top: 12px;
            width: 45%;
            margin-left: auto;
            border-collapse: collapse;
        }

        .totals td {
            border: 1px solid #333;
            padding: 5px;
        }

        .totals td:first-child {
            font-weight: bold;
        }

        .footer {
            position: fixed;
            bottom: 10px;
            left: 22px;
            right: 22px;
            text-align: center;
            font-size: 9px;
            color: #444;
        }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td style="width: 35%;">
                @if($logoDataUri)
                    <img src="{{ $logoDataUri }}" class="logo" alt="Logo">
                @endif
            </td>

            <td class="company">
                <div class="company-name">{{ $tenant->name }}</div>

                @if(!empty($settings['shop_address']))
                    <div>{!! nl2br(e($settings['shop_address'])) !!}</div>
                @endif

                @if(!empty($settings['shop_phone']))
                    <div>Phone: {{ $settings['shop_phone'] }}</div>
                @endif

                @if(!empty($settings['shop_email']))
                    <div>Email: {{ $settings['shop_email'] }}</div>
                @endif
            </td>
        </tr>
    </table>

    <div class="title">{{ $report['title'] }}</div>
    <div class="subtitle">
        {{ $report['subtitle'] ?? '' }}
        <br>
        Generated: {{ $generatedAt->format('d M Y h:i A') }}
    </div>

    <table class="report">
        <thead>
            <tr>
                @foreach($report['headers'] as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>

        <tbody>
            @forelse($report['rows'] as $row)
                <tr>
                    @foreach($row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($report['headers']) }}" style="text-align: center;">No data found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if(!empty($report['totals']))
        <table class="totals">
            @foreach($report['totals'] as $label => $value)
                <tr>
                    <td>{{ $label }}</td>
                    <td style="text-align: right;">{{ $value }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <div class="footer">
        Software Developed & Maintained by NexproBD
    </div>
</body>
</html>
