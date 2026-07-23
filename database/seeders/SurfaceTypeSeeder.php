<?php

namespace Database\Seeders;

use App\Models\SurfaceType;
use Illuminate\Database\Seeder;

class SurfaceTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'ASFALT (SICAK KARIŞIM)', 'price_per_m2' => 1265],
            ['name' => 'ASFALT (SOĞUK ASFALT)',  'price_per_m2' => 994],
            ['name' => 'PARKE',                   'price_per_m2' => 904],
            ['name' => 'BETON',                   'price_per_m2' => 813],
            ['name' => 'STABİLİZE',               'price_per_m2' => 363],
            ['name' => 'TRETUAR (PARKE-PRİZMA)',  'price_per_m2' => 813],
            ['name' => 'TRETUAR (KARO)',          'price_per_m2' => 632],
            ['name' => 'TRETUAR (MERMER)',        'price_per_m2' => 2438],
            ['name' => 'TRETUAR (BAZALT)',        'price_per_m2' => 1987],
            ['name' => 'BORDÜR (BETON)',          'price_per_m2' => 723],
            ['name' => 'BORDÜR (BAZALT)',         'price_per_m2' => 904],
            ['name' => 'ÇİM',                     'price_per_m2' => 452],
            ['name' => 'TOPRAK',                  'price_per_m2' => 63],
            ['name' => 'BETON YOL OLUĞU',         'price_per_m2' => 904],
            ['name' => 'GÖRME ENGELLİ KARO',      'price_per_m2' => 452],
        ];

        foreach ($types as $t) {
            SurfaceType::updateOrCreate(
                ['name' => $t['name']],
                [
                    'price_per_m2' => $t['price_per_m2'],
                    'active' => true,
                ]
            );
        }

        // Deactivate old types not in the list
        $names = array_column($types, 'name');
        SurfaceType::whereNotIn('name', $names)->update(['active' => false]);
    }
}
