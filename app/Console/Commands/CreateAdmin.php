<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class CreateAdmin extends Command
{
    protected $signature = 'app:create-admin';

    protected $description = 'Create an administrator who can sign in to the admin panel';

    public function handle(): int
    {
        $name = text(
            label: 'Name',
            required: true,
            validate: ['name' => 'max:255'],
        );

        $email = text(
            label: 'Email address',
            required: true,
            validate: ['email' => 'email|max:255|unique:users,email'],
        );

        $password = password(
            label: 'Password',
            required: true,
            validate: ['password' => Password::default()],
        );

        $user = new User(compact('name', 'email', 'password'));
        $user->is_admin = true;
        $user->email_verified_at = now();
        $user->save();

        $this->components->info("Admin [{$user->email}] created. Sign in at ".url('/admin'));

        return self::SUCCESS;
    }
}
