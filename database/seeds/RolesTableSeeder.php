<?php

use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Spatie\Permission\Models\Role;
use App\User;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Role::create([
            'name' => 'admin'
        ]);

        Role::create([
            'name' => 'student'
        ]);

        Role::create([
            'name' => 'instructor'
        ]);

        $faker = Faker::create();

        User::create([
            'role_id' => 1,
            'name' => 'Administrator',
            'username' => 'admin',
            'email' => 'admin@heroacademy.online',
            'avatar' => $faker->image("public/uploads/faker/", 446, 251, null, false),
            'avatar_public_id' => $faker->image("public/uploads/faker/", 446, 251, null, false),
            'email_verified_at' => \Carbon\Carbon::now(),
            'password' => bcrypt('password'),
        ]);
    }
}
