<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Direktur Utama',
            'email' => 'direktur@sriayu.test',
            'password' => bcrypt('password'),
            'role' => 'direktur_utama',
        ]);

        User::create([
            'name' => 'Admin Penjualan',
            'email' => 'penjualan@sriayu.test',
            'password' => bcrypt('password'),
            'role' => 'admin_penjualan',
        ]);

        User::create([
            'name' => 'Admin Gudang',
            'email' => 'gudang@sriayu.test',
            'password' => bcrypt('password'),
            'role' => 'admin_gudang',
        ]);
    }
}
