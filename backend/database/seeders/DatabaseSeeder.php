<?php

namespace Database\Seeders;

use App\Models\Equipment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Akun demo untuk login. Password ini HANYA untuk lingkungan
        // demo lokal/dosen; ganti lewat tinker/seed sendiri bila dipakai
        // di tempat lain. Bukan kredensial yang ditulis di Dockerfile.
        User::updateOrCreate(
            ['email' => 'admin@lab.test'],
            [
                'name' => 'Admin Lab',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );

        $samples = [
            ['name' => 'Laptop Praktikum', 'code' => 'LAB-001', 'category' => 'Komputer', 'quantity' => 20, 'condition' => 'baik', 'location' => 'Lab A', 'description' => 'Laptop untuk praktikum pemrograman.'],
            ['name' => 'Switch Jaringan 24 Port', 'code' => 'LAB-002', 'category' => 'Jaringan', 'quantity' => 4, 'condition' => 'baik', 'location' => 'Lab B', 'description' => 'Switch managed untuk praktikum jaringan.'],
            ['name' => 'Proyektor', 'code' => 'LAB-003', 'category' => 'Audio Visual', 'quantity' => 2, 'condition' => 'perawatan', 'location' => 'Lab A', 'description' => 'Satu unit sedang perawatan berkala.'],
        ];

        foreach ($samples as $sample) {
            Equipment::updateOrCreate(['code' => $sample['code']], $sample);
        }
    }
}
