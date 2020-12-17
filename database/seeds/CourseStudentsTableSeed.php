<?php

use Illuminate\Database\Seeder;

use Faker\Factory as Faker;
use App\User;
use App\Models\Course\Course;
use App\Models\Cart\Subscription\CourseStudent;

class CourseStudentsTableSeed extends Seeder
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
            CourseStudent::create([
                'course_id' => Course::all()->random()->id,
                'user_id' => User::all()->random()->id,
                'total_time' => $faker->time
            ]);
        }
    }
}
