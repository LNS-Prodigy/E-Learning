<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
// Searchable
use Spatie\Searchable\Search;
// Searching Datas
use App\Models\Course\Course;
use App\Models\Course\CourseCategory;
use Spatie\Searchable\ModelSearchAspect;

use DB;

class SearchController extends Controller
{
    public function searchCourses(Request $request)
    {
        $this->validate($request, [
            'q' => 'required|string'
        ]);

        $search = $request->get('q');

        $searchResults = (new Search())
            ->registerModel(Course::class, function(ModelSearchAspect $modelSearchAspect) {
                $modelSearchAspect
                    ->addSearchableAttribute('title')
                    ->addSearchableAttribute('language')
                    ->addSearchableAttribute('level')
                    ->leftJoin('course_ratings', 'course_ratings.course_id', '=', 'courses.id')
                    ->select(['courses.id', 'title', 'slug', 'courses.teacher_id', 'has_discount', 'free_course', 'price', 'discount', 'category_id', 'image', 'image_public_id', 'excerpt', 'language', 'level', DB::raw(('avg(rating) as rating_average'))])
                    ->groupBy('courses.id')
                    ->withCount(['students', 'lessons', 'ratings'])
                    ->with(['user:id,name'])
                    ->paginate(10);
            })->search($search);

        $forBeginners = Course::where('level', 'BEGINNER')
            ->where('status', 'PUBLISHED')
            ->with(['user:id,name', 'category:id,name,slug'])
            ->take(5)
            ->get(['id', 'image', 'image_public_id', 'title', 'slug', 'category_id', 'teacher_id', 'excerpt', 'free_course', 'price', 'discount', 'has_discount', 'created_at', 'updated_at', 'level']);


        return response()
            ->json([
                'searchResults' => $searchResults,
                'forBeginners' => $forBeginners
            ]);
    }

    public function getYoutubeData(Request $request)
    {
        $youtubeApiKey = env('GOOGLE_YOUTUBE_CLIENT_ID');

        preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $request->video_url, $match);
        $youtubeId = $match[1];

        $sUrl = "https://www.googleapis.com/youtube/v3/videos?id=" . $youtubeId . "&key=" . $youtubeApiKey . "&part=contentDetails";
        $sData = file_get_contents($sUrl);

        $oData = json_decode($sData);

        $sVideo = array_shift($oData->items);
        $sDuration = $sVideo->contentDetails->duration;

        $pFields = "'PT(?:([0-9]+)H)?(?:([0-9]+)M)?(?:([0-9]+)S)?'si";

        if (preg_match($pFields, $sDuration, $aMatch)) {
            // Add up hours, minutes, and seconds

            if ($aMatch[1] != "") {
                $hour = $aMatch[1];
            } else {
                $hour = "00";
            }
            

            if ($aMatch[2] != "") {
                $minute = $aMatch[2];
            } else {
                $minute = "00";
            }
            
            $response = [
                'duration' => $hour . ':' . $minute . ':' . $aMatch[3]
            ];
        }

        return response()->json($response);
    }
}
