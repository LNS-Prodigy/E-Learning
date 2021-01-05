<?php

use Illuminate\Database\Seeder;

use Faker\Factory as Faker;

use App\Models\Course\CourseRating;
use App\User;
use App\Models\Course\Course;

class CourseRatingsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();

        for ($i = 0; $i < 100; $i++) {
            CourseRating::create([
                'user_id' => User::all()->random()->id,
                'course_id' => Course::all()->random()->id,
                'teacher_id' => $faker->randomElement(['6', '14']),
                'rating' => $faker->randomElement(['1', '1.5', '2', '2.5', '3', '3.5', '4', '4.5', '5']),
                'comments' => $faker->paragraph(2)
            ]);
        }
    }
}
