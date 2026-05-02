<?php

namespace App\Console\Commands;

use App\Platform\Models\SuperAdmin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateSuperAdmin extends Command
{
    protected $signature = 'nxpbd:create-super-admin';

    protected $description = 'Create or update the first NexproBD Gadget ERP super admin account';

    public function handle(): int
    {
        $name = trim((string) $this->ask('Super admin name'));
        $email = trim((string) $this->ask('Super admin email'));
        $password = (string) $this->secret('Super admin password');
        $passwordConfirmation = (string) $this->secret('Confirm password');

        if ($name === '') {
            $this->error('Name is required.');
            return self::FAILURE;
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('A valid email is required.');
            return self::FAILURE;
        }

        if (strlen($password) < 8) {
            $this->error('Password must be at least 8 characters.');
            return self::FAILURE;
        }

        if ($password !== $passwordConfirmation) {
            $this->error('Password confirmation does not match.');
            return self::FAILURE;
        }

        SuperAdmin::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
            ]
        );

        $this->info('Super admin account is ready.');

        return self::SUCCESS;
    }
}
