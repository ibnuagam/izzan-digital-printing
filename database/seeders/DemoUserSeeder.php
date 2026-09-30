<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            throw new \RuntimeException('Akun demo hanya untuk lingkungan local.');
        }
        foreach (['admin', 'manajer', 'pelanggan'] as $role) {
            $email = $role.'@izzan.test';
            if (! User::where('email', $email)->exists()) {
                $user = new User;
                $user->name = ucfirst($role).' Demo';
                $user->email = $email;
                $user->password = Hash::make('IzzanDemo2026!');
                $user->role = $role;
                $user->save();
            }
        }
    }
}
