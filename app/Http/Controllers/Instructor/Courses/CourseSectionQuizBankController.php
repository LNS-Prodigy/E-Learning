<?php

namespace App\Http\Controllers\Instructor\Courses;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// Auth User
use Auth;

// Courses
use App\Models\Course\Course;
use App\Models\Course\CourseQuizBank;
use App\Models\Course\CourseSectionQuiz;
use App\Models\Course\CourseSection;

class CourseSectionQuizBankController extends Controller
{
    /**
     * Store new Quiz Bank
     */
    public function store(Request $request) {
        $quizBankExists = CourseQuizBank::where('section_id', $request->section_id)
        	->first();

        $this->validate($request, [
        	'section_id' => 'required',
        	'number_of_questions' => 'required'	
        ]);

        $quizBank = new CourseQuizBank($request->all());
       	
       	if (!$quizBankExists) {
       		$quizBank->save();
       	} else {
       		return response()
       			->json([
       				'saved' => false,
       				'message' => "There's already an existing quiz bank on that section."
       			]);
       	}

        return response()
        	->json([
        		'saved' => true,
        		'bank' => $quizBank,
        		'message' => 'Quiz bank is created'
        	]);
	}

	/**
	 * Get data for editting
	 * 
	 * @return \Illuminate\Http\Response
	 */
	public function edit($id) {
		$quizBank = CourseQuizBank::find($id);

		return response()
			->json([
				'quizbank' => $quizBank
			]);
	}
	
	/**
	 * Get QuizBank Data
	 */
	public function update(Request $request, $quizbank_id) {
		$quizBankExists = CourseQuizBank::where('id', '!=', $quizbank_id)
			->where('section_id', $request->section_id)
        	->first();

		$quizBank = CourseQuizBank::where('id', $quizbank_id)
			->first();

		$this->validate($request, [
			'section_id' => 'required',
			'number_of_questions' => 'required'
		]);

		$quizBank->section_id = $request->section_id;
		$quizBank->number_of_questions = $request->number_of_questions;

		if (!$quizBankExists) {
			$quizBank->update();
		} else {
			return response()
       			->json([
       				'saved' => false,
       				'message' => "There's already an existing quiz bank on that section."
       			]);
		}

		return response()->json([
			'saved' => true,
			'message' => "Quiz bank #$quizBank->id, is updated successfully",
			'quizbank' => $quizBank
		]);
	}

	/**
	 * Delete Quizbank
	 * 
	 * @return \Illuminate\Http\Response
	 * @return \Illuminate\Http\Request
	 */
	public function destroy($id)
	{
		$quizbank = CourseQuizBank::findOrFail($id);
		$quizBank->delete();

		return response()
			->json([
				'deleted' => true,
				'message' => 'Quiz bank successfully deleted.'
			]);
	}

}
