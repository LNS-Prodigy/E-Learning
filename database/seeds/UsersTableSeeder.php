<?php

use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use App\User;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();
        for ($i = 0; $i < 20000; $i++) {
            User::create([
                'role_id' => '2',
                'name' => $faker->name,
                'username' => $faker->username,
                'email' => $faker->unique()->email,
                'password' => bcrypt('password'),
            ]);
        }
    }
}
