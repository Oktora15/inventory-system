<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:promote-user-to-admin {email : Email akun yang akan diberi role Admin}')]
#[Description('Promote an existing user to the Admin role')]
class PromoteUserToAdmin extends Command
{
    public function handle(): int
    {
        $user = User::query()->where('email', $this->argument('email'))->first();

        if ($user === null) {
            $this->components->error('Akun dengan email tersebut tidak ditemukan.');

            return self::FAILURE;
        }

        if ($user->isAdmin()) {
            $this->components->info('Akun tersebut sudah memiliki role Admin.');

            return self::SUCCESS;
        }

        $user->forceFill(['role' => User::ROLE_ADMIN])->save();

        $this->components->info("Role Admin diberikan kepada {$user->email}.");

        return self::SUCCESS;
    }
}
