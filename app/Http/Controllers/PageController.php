<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

// Authenticated User
use Auth;

// Courses
use App\Models\Course\Course;
use App\Models\Course\CourseCategory;
use App\Models\Course\CourseSection;
use App\Models\Course\CourseSectionLesson;
use App\Models\Course\CourseSectionQuiz;
use App\Models\Course\CourseUserProgress;
use App\Models\Course\CourseRating;

// Instructor
use App\User;

// Cart
use App\Models\Cart\Cart;
use App\Models\Cart\CartItem;
use App\Models\Course\CourseQuizBank;
use DB;

class PageController extends Controller
{
    public function welcome()
    {
        $courses = Course::where('status', 'PUBLISHED')
            ->has('lessons')
            ->with(['user:id,name'])
            ->select('courses.id', 'courses.title', 'courses.slug', 'courses.teacher_id', 'courses.price', 'courses.free_course', 'courses.discount', 'courses.price', 'courses.has_discount', 'courses.image', 'courses.image_public_id', 'courses.rating_average')
            ->groupBy('id')
            ->withCount('ratings')
            ->get();

        $enrolled_courses = null;

        if (Auth::check()) {
            $enrolled_courses = Course::whereHas('students', function($query) {
                $query->where('user_id', Auth::user()->id)
                    ->where('remaining_lessons', '!=', '0');
            })
            ->with(['user:id,name', 'firstLesson:id,course_id', 'firstProgress' => function ($query) {
                $query->where('user_id', Auth::user()->id);
            }, 'hasFinishedLesson' => function ($query) {
                $query->where('user_id', Auth::user()->id)
                    ->where('status', 1)->count();
            }, 'lessons' => function ($query) {
                $query->where('duration', '!=', null);
            }, 'courseDuration' => function ($query) {
                $query->where('user_id', Auth::id());
            }])
            ->withCount(['progress' => function ($query) {
                $query->where('user_id', \Auth::user()->id)
                    ->where('status', 1);
            }, 'lessons'])
            ->get();
        }

        return response()
            ->json([
                'enrolled_courses' => $enrolled_courses,
                'courses' => $courses
            ]);
    }

    public function showCourse($slug, Request $request)
    {
        $course = Course::where('status', 'PUBLISHED')
            ->has('lessons')
            ->with(['category:id,name,slug', 'requirements:course_id,description', 'outcomes:course_id,description', 'whos:course_id,description', 'firstLesson:id,course_id'])
            ->withCount('ratings')
            ->where('slug', $slug)
            ->firstOrFail();

        $avgRating = round($course->ratings()->avg('rating'), 1);

        $fiveRating = CourseRating::where('course_id', $course->id)
            ->where('rating', 5)
            ->count();

        $fourRating = CourseRating::where('course_id', $course->id)
            ->whereIn('rating', [4, 4.5])
            ->count();

        $threeRating = CourseRating::where('course_id', $course->id)
            ->whereIn('rating', [3, 3.5])
            ->count();

        $twoRating = CourseRating::where('course_id', $course->id)
            ->whereIn('rating', [2, 2.5])
            ->count();

        $oneRating = CourseRating::where('course_id', $course->id)
            ->whereIn('rating', [0.5, 1, 1.5])
            ->count();

        $enrolled_course = Auth::check() && $course->students()->where('user_id', \Auth::id())->count() > 0;
        $enrolled_students = $course->students()->count();

        $enrolled_at = null;

        if ($enrolled_course == true) {
            $enrolled_at = $course->students()->where('user_id', \Auth::id())
                ->where('course_id', $course->id)
                ->select('users.id', 'name')
                ->first();
        }

        // add delay on course view
        // $visitorIp = trim(shell_exec("dig +short myip.opendns.com @resolver1.opendns.com"));
        $visitorIp = $request->ip();

        views($course)
            ->collection('course')
            ->overrideVisitor($visitorIp)
            ->record();

        $sections = CourseSection::where('course_id', $course->id)
            ->with(['lessons:id,course_section_id'])
            ->orderBy('order_index', 'asc')
            ->get(['id', 'title']);

        $lessons = CourseSectionLesson::where('course_id', $course->id)
            ->where('duration', '!=', null)
            ->get(['course_id', 'course_section_id', 'title', 'duration', 'order_index']);

        $quizBank = CourseQuizBank::where('course_id', $course->id)
            ->with(['quizzes'])
            ->get();
        // Course Complete Lesson Duration
        $lessonDurations = CourseSectionLesson::where('course_id', $course->id)
            ->where('duration', '!=', null)
            ->pluck('duration');

        // Calculate total hours
        $hrs = 0;
        $mins = 0;
        $secs = 0;

        foreach ($lessonDurations as $time) {
            list ($hours, $minutes, $seconds) = explode(':', $time);

            $hrs += (int) $hours;
            $mins += (int) $minutes;
            $secs += (int) $seconds;

            // Convert each 60 minutes to an hour
            if ($mins >= 60) {
                $hrs++;
                $mins -= 60;
            }

            // Convert each 60 seconds to a minute
            if ($secs >= 60) {
                $mins++;
                $secs -= 60;
            }
        }

        $totalDuration = sprintf('%d:%d:%d', $hrs, $mins, $secs);

        $instructor = User::where('id', $course->teacher_id)
            ->withCount(['courses', 'myReviews'])
            ->firstOrFail(['id', 'name', 'avatar', 'avatar_public_id', 'introduction', 'biography']);

        $instructorDatas = Course::where('courses.teacher_id', $instructor->id)
            ->leftJoin('course_ratings', 'course_ratings.teacher_id', '=', 'courses.teacher_id')
            ->select(['courses.id', 'courses.teacher_id', DB::raw(('AVG(rating) as rating_average')) ])
            ->withCount(['students'])
            ->first();

        $countLessons = CourseSectionLesson::where('course_id', $course->id)
            ->count();

        $mightLikes = Course::where('slug', '!=', $slug)
            ->has('lessons')
            ->with(['user:id,name'])
            ->where('status', 'PUBLISHED')
            ->where('category_id', $course->category_id)
            ->inRandomOrder()
            ->take(3)
            ->select('courses.id', 'title', 'slug', 'courses.teacher_id', 'has_discount', 'free_course', 'price', 'discount', 'category_id', 'image', 'image_public_id', 'excerpt', 'language', 'level', 'courses.updated_at', 'courses.created_at', 'courses.rating_average')
            ->groupBy('id')
            ->withCount('ratings', 'students')
            ->get();

        return response()
            ->json([
                'course' => $course,
                'mightLikes' => $mightLikes,
                'sections' => $sections,
                'countLessons' => $countLessons,
                'totalDuration' => $totalDuration,
                'lessons' => $lessons,
                'quizzes' => $quizBank,
                // 'addedToCart' => $addedToCart,
                'enrolled_course' => $enrolled_course,
                'enrolled_at' => $enrolled_at,
                'enrolled_students' => $enrolled_students,
                'instructor' => $instructor,
                'avgRating' => $avgRating,
                'fiveRating' => $fiveRating,
                'fourRating' => $fourRating,
                'threeRating' => $threeRating,
                'twoRating' => $twoRating,
                'oneRating' => $oneRating,
                'instructorDatas' => $instructorDatas,
            ]);
    }

    public function getCourseFeedbacks($id)
    {
        $feedBacks = CourseRating::where('course_id', $id)
            ->with(['user:id,name,email,avatar,avatar_public_id'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()
            ->json([
                'feedbacks' => $feedBacks
            ]);
    }

    public function getCourseOverviewUrl($slug)
    {
        $courseOverviewURL = Course::where('status', 'PUBLISHED')
            ->where('slug', $slug)
            ->firstOrFail(['course_overview_url', 'course_overview_provider', 'image']);

        return response()
            ->json([
                'courseOverviewURL' => $courseOverviewURL
            ]);
    }

    public function showInstructor($username)
    {
        $instructor = User::where('username', $username)
            ->firstOrFail();

        $courses = Course::where('teacher_id', $instructor->id)
            ->where('status', 'PUBLISHED')
            ->with(['category', 'user'])
            ->get(['image', 'title', 'excerpt', 'price', 'discount', 'has_discount', 'id', 'teacher_id', 'category_id', 'slug']);

        return response()
            ->json([
                'instructor' => $instructor,
                'courses' => $courses
            ]);
    }

    public function showCategory($slug)
    {
        $category = CourseCategory::where('slug', $slug)
            ->first();

        $countCourses = 0;

        $courses = Course::where('category_id', $category->id)->get('id');

        if ($courses->count() != 0) {
            $countCourses = Course::where('category_id', $category->id)->whereNotIn('id', [$courses->pluck('id')])->count();
        }

        $featuredCourse = Course::where('category_id', $category->id)
            ->select('courses.id', 'courses.title', 'courses.slug', 'courses.teacher_id', 'courses.has_discount', 'courses.free_course', 'courses.price', 'courses.discount', 'courses.category_id', 'courses.image', 'courses.image_public_id', 'courses.excerpt', 'courses.language', 'courses.level', 'courses.rating_average')
            ->groupBy('id')
            ->where('featured', 1)
            ->with(['user:id,name'])
            ->withCount(['lessons', 'ratings', 'students'])
            ->get();

        $mostPopular = Course::has('lessons')
            ->select('courses.id', 'courses.title', 'courses.slug', 'courses.teacher_id', 'courses.has_discount', 'courses.free_course', 'courses.price', 'courses.discount', 'courses.category_id', 'courses.image', 'courses.image_public_id', 'courses.excerpt', 'courses.language', 'courses.level', 'courses.rating_average')
            ->groupBy('id')
            ->take(10)
            ->where('category_id', $category->id)
            ->with(['user:id,name'])
            ->withCount('ratings')
            ->orderBy('rating_average', 'desc')
            ->get();

        return response()
            ->json([
                'category' => $category,
                'mostPopular' => $mostPopular,
                'featuredCourse' => $featuredCourse,
                'courses' => $courses,
                'countCourses' => $countCourses
            ]);
    }

    public function getCategoryCourses($category_id)
    {
        $courses = Course::has('lessons')
            ->select('courses.id', 'courses.title', 'courses.slug', 'courses.teacher_id', 'courses.has_discount', 'courses.free_course', 'courses.price', 'courses.discount', 'courses.category_id', 'courses.image', 'courses.image_public_id', 'courses.excerpt', 'courses.language', 'courses.level', 'courses.rating_average')
            ->where('category_id', $category_id)
            ->with(['user:id,name'])
            ->withCount(['ratings', 'lessons', 'students'])
            ->orderBy('rating_average', 'desc')
            ->paginate(10);

        return response()
            ->json([
                'courses' => $courses
            ]);
    }

    public function navCategories()
    {
        $categories = CourseCategory::get(['id', 'name', 'slug', 'icon']);

        return response()
            ->json([
                'categories' => $categories
            ]);
    }

}
