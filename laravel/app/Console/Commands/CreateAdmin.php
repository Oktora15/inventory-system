<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateAdmin extends Command
{
    protected $signature = 'app:create-admin {name : Nama Admin} {email : Email Admin}';

    protected $description = 'Create the first Admin account';

    public function handle(): int
    {
        if (User::query()->where('role', User::ROLE_ADMIN)->where('is_active', true)->exists()) {
            $this->components->error('Admin aktif sudah tersedia. Gunakan menu manajemen pengguna.');

            return self::FAILURE;
        }

        $name = trim($this->argument('name'));
        $email = $this->argument('email');

        if ($name === '' || strlen($name) > 255 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->components->error('Nama harus diisi (maksimal 255 karakter) dan email harus valid.');

            return self::FAILURE;
        }

        if (User::query()->where('email', $email)->exists()) {
            $this->components->error('Email sudah terdaftar.');

            return self::FAILURE;
        }

        if (! $this->input->isInteractive()) {
            $this->components->error('Pembuatan Admin pertama memerlukan terminal interaktif untuk memasukkan kata sandi secara aman.');

            return self::FAILURE;
        }

        $password = $this->secret('Kata sandi Admin (minimal 8 karakter)');

        if (! is_string($password) || strlen($password) < 8) {
            $this->components->error('Kata sandi harus memiliki minimal 8 karakter.');

            return self::FAILURE;
        }

        $confirmation = $this->secret('Konfirmasi kata sandi');

        if ($password !== $confirmation) {
            $this->components->error('Konfirmasi kata sandi tidak cocok.');

            return self::FAILURE;
        }

        $user = DB::transaction(function () use ($name, $email, $password): User {
            $user = new User;
            $user->forceFill([
                'name' => $name,
                'email' => $email,
                'role' => User::ROLE_ADMIN,
                'password' => $password,
                'is_active' => true,
            ])->save();

            return $user;
        });

        $this->components->info('Admin berhasil dibuat. Silakan masuk ke aplikasi.');

        return self::SUCCESS;
    }
}
