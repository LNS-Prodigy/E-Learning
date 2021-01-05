<?php

namespace App\Http\Controllers\Instructor\Courses;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// Course Model
use App\Models\Course\Course;
use App\Models\Course\CourseSection;
use App\Models\Course\CourseUserProgress;
use App\Models\Course\CourseSectionLesson;
use App\Models\Course\CourseSectionQuiz;
use App\Models\Cart\Subscription\CourseStudent;

class CourseSectionController extends Controller
{
    /**
     * Create new section in courses
     *
     * @param int id // Course ID
     * @return \Illuminate\Http\Response
     * @return \Illuminate\Http\Request
     */
    public function store($id, Request $request)
    {
        $this->validate($request, [
            'title' => 'required|max:255'
        ]);

        $course = Course::where('id', $id)
            ->firstOrFail();

        $section = new CourseSection($request->all());

        $section->slug = str_slug($request->title, '-');
        $section->course_id = $course->id;
        $section->order_index++;

        $section->save();

        return response()
            ->json([
                'saved' => true,
                'id' => $section->id,
                'title' => $section->title,
                'slug' => $section->slug,
                'message' => "Section succesfully saved!"
            ]);
    }

    /**
     * Get data for editting
     *
     * @return \Illuminate\Http\Response
    */
    public function edit($id)
    {
        $section = CourseSection::find($id);

        return response()
            ->json([
                'section' => $section
            ]);
    }

    /**
     * Update section
     *
     * @return \Illuminate\Http\Response
     * @return \Illuminate\Http\Request
    */
    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'title' => 'required|max:255'
        ]);

        $section = CourseSection::where('id', $id)
            ->firstOrFail();

        $input = $request->all();
        $section->fill($input);

        $section->save();

        return response()
            ->json([
                'saved' => true,
                'id' => $section->id,
                'message' => "Section #$section->id, updated succesfully",
                'section' => $section
            ]);
    }

    /**
     * Delete Section
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $section = CourseSection::where('id', $id)
            ->firstOrFail();

        $course = Course::where('id', $section->course_id)->first();

        CourseUserProgress::where('section_id', $id)->delete();
        CourseSectionQuiz::where('section_id', $id)->delete();

        $students = CourseStudent::where('course_id', $course->id)->get();

        foreach ($students as $student) {
            $lessons = CourseSectionLesson::where('course_section_id', $section->id)->get();

            foreach ($lessons as $lesson) {
                if ($student->total_time != '00:00:00') {
                    $time = $student->total_time;
                    $time2 = $lesson->duration;

                    $secs = strtotime($time2) - strtotime("00:00:00");
                    $result = date("H:i:s", strtotime($time) - $secs);

                    $student->total_time = $result;
                    $student->remaining_lessons -= 1;
                    $student->save();
                }
            }
        }

        $section->delete();

        return response()
            ->json([
                'deleted' => true,
                'message' => 'Section successfully deleted.'
            ]);
    }

    /**
     *  Update order_index of Section
     *
     * @return \Illuminate\Http\Response
     * @return \Illuminate\Http\Request
     */
    public function updateOrderIndex(Request $request, $id)
    {
        $this->validate($request, [
            'sections.*.order_index' => 'required'
        ]);

        $sections = CourseSection::all();

        foreach($sections as $section) {
            $id = $section->id;
            foreach ($request->sections as $section) {
                CourseSection::find($section['id'])->update(['order_index' => $section['order_index']]);
            }
        }

        return response()
            ->json([
                'saved' => true,
                'message' => 'Order changed'
            ], 200);

    }
}
