<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            AdminTablesSeeder::class,
            UsersSeeder::class,
            UserAddressesSeeder::class,
            CategoriesSeeder::class,
            ProductsSeeder::class,
            CouponCodesSeeder::class,
            OrdersSeeder::class
        ]);
    }
}
