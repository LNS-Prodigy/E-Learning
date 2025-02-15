<?php

namespace App\Models\Course;

use Illuminate\Database\Eloquent\Model;

// Search Model
use Spatie\Searchable\Searchable;
use Spatie\Searchable\SearchResult;

class CourseQANDA extends Model implements Searchable
{
    protected $table = 'course_questions_and_answers';

    protected $fillable = ['parent_id', 'user_id', 'course_id', 'title', 'details'];

    /**
     * @return array
     */
    public function user()
    {
    	return $this->belongsTo('App\User', 'user_id');
    }

    /**
     * @return array
     */
    public function course()
    {
    	return $this->belongsTo('App\Models\Course\Course');
    }

    public function parentId()
    {
        return $this->belongsTo(self::class);
    }

    public function replies()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    
    /**
     * @return array
     */
    public function getSearchResult(): \Spatie\Searchable\SearchResult
    {
        // $url = url('course/' . $this->slug);
        return new SearchResult(
            $this,
            $this->title,
        );
    }

}
