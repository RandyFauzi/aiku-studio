<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CreatePanelAdmin extends Command
{
    protected $signature = 'panel:admin {email} {--generate : Store a generated password in private initial-admin.txt}';

    protected $description = 'Create or update an administrator login';

    public function handle(): int
    {
        $email = $this->argument('email');
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email');

            return self::FAILURE;
        }
        $password = $this->option('generate') ? Str::password(24) : $this->secret('Kata sandi baru (minimal 12 karakter)');
        if (strlen($password ?? '') < 12) {
            $this->error('Minimal 12 karakter.');

            return self::FAILURE;
        }
        User::updateOrCreate(['email' => $email], ['name' => 'AIKu Administrator', 'password' => $password]);
        if ($this->option('generate')) {
            Storage::disk('local')->put('initial-admin.txt', 'Email: '.$email."\nPassword: ".$password."\n");
            $this->info('Credentials saved in storage/app/private/initial-admin.txt. Keep private.');
        }
        $this->info('Administrator ready.');

        return self::SUCCESS;
    }
}
