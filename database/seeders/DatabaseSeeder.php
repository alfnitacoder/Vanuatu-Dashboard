<?php

namespace Database\Seeders;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $shop = Shop::query()->create([
            'name' => 'Port Vila Demo',
            'location' => 'Port Vila',
        ]);

        User::query()->create([
            'shop_id' => $shop->id,
            'name' => 'Demo Shop',
            'email' => 'shop@stua.vu',
            'password' => 'stua-demo',
        ]);
    }
}
