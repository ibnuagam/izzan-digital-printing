<?php

namespace Database\Seeders;

use App\Models\Material;
use App\Models\Service;
use Illuminate\Database\Seeder;

class MasterDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            throw new \RuntimeException('Data dummy hanya untuk lingkungan local.');
        }
        foreach ([
            ['code' => 'DEMO-LYN-01', 'name' => 'Spanduk (simulasi)', 'unit' => 'm2', 'base_price' => 25000, 'description' => 'Data dummy untuk pengembangan. Ukuran dihitung dalam luas meter persegi.'],
            ['code' => 'DEMO-LYN-02', 'name' => 'Brosur (simulasi)', 'unit' => 'lembar', 'base_price' => 1500, 'description' => 'Data dummy; spesifikasi dan harga belum dikonfirmasi dengan toko.'],
            ['code' => 'DEMO-LYN-03', 'name' => 'Kartu nama (simulasi)', 'unit' => 'pcs', 'base_price' => 500, 'description' => 'Data dummy untuk mencoba katalog dan pengelolaan layanan.'],
        ] as $row) {
            Service::firstOrCreate(['code' => $row['code']], $row + ['is_active' => true]);
        }
        foreach ([
            ['code' => 'DEMO-BHN-01', 'name' => 'Flexi (simulasi)', 'unit' => 'm2'],
            ['code' => 'DEMO-BHN-02', 'name' => 'Art paper (simulasi)', 'unit' => 'lembar'],
            ['code' => 'DEMO-BHN-03', 'name' => 'Art carton (simulasi)', 'unit' => 'lembar'],
        ] as $row) {
            Material::firstOrCreate(['code' => $row['code']], $row + ['description' => 'Data dummy pengembangan; bukan data nyata toko.', 'is_active' => true]);
        }
    }
}
