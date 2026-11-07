<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ShopMakeAdmin extends Command
{
    protected $signature = 'shop:make-admin {email} {--name=Super Admin}';
    protected $description = 'Create (or promote) a super admin and print a one-time random password';

    public function handle(): int
    {
        $email = strtolower($this->argument('email'));
        $password = Str::password(18, symbols: false);
        $user = User::firstOrNew(['email' => $email]);
        $user->forceFill(['name' => $user->name ?: $this->option('name'), 'password' => bcrypt($password), 'is_admin' => true, 'email_verified_at' => $user->email_verified_at ?? now()])->save();
        $this->line("Admin: {$email}");
        $this->line("Password: {$password}");

        return self::SUCCESS;
    }
}
