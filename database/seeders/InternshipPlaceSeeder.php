<?php

namespace Database\Seeders;

use App\Models\InternshipPlace;
use Illuminate\Database\Seeder;

class InternshipPlaceSeeder extends Seeder
{
    public function run(): void
    {
        $places = [
            [
                'name' => 'Dinas Komunikasi Informatika dan Statistik Provinsi Lampung',
                'address' => 'Jl. Wolter Monginsidi No. 69, Bandar Lampung, Lampung',
                'latitude' => -5.4292400,
                'longitude' => 105.2629400,
            ],
            [
                'name' => 'Dinas Perhubungan Provinsi Lampung',
                'address' => 'Jl. Cut Mutia No. 76, Teluk Betung, Bandar Lampung, Lampung',
                'latitude' => -5.4463200,
                'longitude' => 105.2661700,
            ],
            [
                'name' => 'Dinas Perhubungan Kota Bandar Lampung',
                'address' => 'Bandar Lampung, Lampung',
                'latitude' => -5.4216400,
                'longitude' => 105.2629500,
            ],
            [
                'name' => 'Badan Pusat Statistik Provinsi Lampung',
                'address' => 'Jl. Basuki Rahmat No. 54, Bandar Lampung, Lampung',
                'latitude' => -5.4295000,
                'longitude' => 105.2605000,
            ],
            [
                'name' => 'Badan Pusat Statistik Kota Bandar Lampung',
                'address' => 'Bandar Lampung, Lampung',
                'latitude' => -5.3971400,
                'longitude' => 105.2667900,
            ],
            [
                'name' => 'Balai Besar Perikanan Budidaya Laut Lampung',
                'address' => 'Lampung',
                'latitude' => -5.5123600,
                'longitude' => 105.2442500,
            ],
            [
                'name' => 'PT PLN (Persero) UID Lampung',
                'address' => 'Jl. ZA Pagar Alam, Bandar Lampung, Lampung',
                'latitude' => -5.3825800,
                'longitude' => 105.2585700,
            ],
            [
                'name' => 'PT Telkom Indonesia Witel Lampung',
                'address' => 'Bandar Lampung, Lampung',
                'latitude' => -5.4252300,
                'longitude' => 105.2581800,
            ],
            [
                'name' => 'Bank Indonesia Perwakilan Provinsi Lampung',
                'address' => 'Bandar Lampung, Lampung',
                'latitude' => -5.4307500,
                'longitude' => 105.2602700,
            ],
            [
                'name' => 'Kantor Wilayah Direktorat Jenderal Pajak Bengkulu dan Lampung',
                'address' => 'Bandar Lampung, Lampung',
                'latitude' => -5.3818600,
                'longitude' => 105.2566200,
            ],
            [
                'name' => 'Kantor Pelayanan Pajak Pratama Tanjung Karang',
                'address' => 'Bandar Lampung, Lampung',
                'latitude' => -5.4211700,
                'longitude' => 105.2575800,
            ],
            [
                'name' => 'Kantor Pelayanan Pajak Pratama Kedaton',
                'address' => 'Bandar Lampung, Lampung',
                'latitude' => -5.3829300,
                'longitude' => 105.2577900,
            ],
            [
                'name' => 'Pemerintah Kota Bandar Lampung',
                'address' => 'Jl. Dr. Susilo, Bandar Lampung, Lampung',
                'latitude' => -5.4261200,
                'longitude' => 105.2597700,
            ],
            [
                'name' => 'Pemerintah Provinsi Lampung',
                'address' => 'Jl. Wolter Monginsidi, Bandar Lampung, Lampung',
                'latitude' => -5.4287200,
                'longitude' => 105.2614400,
            ],
            [
                'name' => 'Rumah Sakit Umum Daerah Dr. H. Abdul Moeloek',
                'address' => 'Jl. Dr. Rivai, Bandar Lampung, Lampung',
                'latitude' => -5.4265600,
                'longitude' => 105.2589900,
            ],
            [
                'name' => 'Universitas Lampung',
                'address' => 'Jl. Prof. Dr. Ir. Sumantri Brojonegoro No. 1, Bandar Lampung, Lampung',
                'latitude' => -5.3644100,
                'longitude' => 105.2439600,
            ],
            [
                'name' => 'UPT TIK Universitas Lampung',
                'address' => 'Universitas Lampung, Bandar Lampung, Lampung',
                'latitude' => -5.3649800,
                'longitude' => 105.2435600,
            ],
            [
                'name' => 'Jurusan Ilmu Komputer FMIPA Universitas Lampung',
                'address' => 'FMIPA Universitas Lampung, Bandar Lampung, Lampung',
                'latitude' => -5.3655200,
                'longitude' => 105.2432500,
            ],
            [
                'name' => 'Radar Lampung',
                'address' => 'Bandar Lampung, Lampung',
                'latitude' => -5.3928400,
                'longitude' => 105.2791100,
            ],
            [
                'name' => 'Lampung Post',
                'address' => 'Bandar Lampung, Lampung',
                'latitude' => -5.4103900,
                'longitude' => 105.2696700,
            ],
        ];

        foreach ($places as $place) {
            InternshipPlace::query()->updateOrCreate(
                ['name' => $place['name']],
                $place + [
                    'is_active' => true,
                    'visited' => false,
                ],
            );
        }
    }
}
