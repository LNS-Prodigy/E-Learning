<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCourseCurriculumSectionLessonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('course_curriculum_section_lessons', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Course ID Relationship
            $table->bigInteger('course_id')->unsigned();
            // $table->foreign('course_id')->references('id')->on('courses')->onDelete('cascade');

            $table->bigInteger('course_section_id')->unsigned();
            // $table->foreign('course_section_id')->references('id')->on('course_sections')->onDelete('cascade');

            // Media
            $table->string('lesson_image')->nullable();

            // Title and Slug
            $table->string('title');
            $table->string('slug');

            // Lesson Type
            $table->enum('lesson_type', ['NULL', 'VIDEO', 'TFILE', 'PDF', 'DF', 'IFILE'])->default('NULL');

            // If type of File
            $table->longText('text_file')->nullable();

            // Lesson Provider if lesson_type = VIDEO
            $table->enum('lesson_provider', ['Youtube', 'Vimeo', 'HTML5', 'NULL'])->default('NULL')->nullable();
            $table->string('thumbnail')->nullable();
            // Video
            $table->string('video_url')->nullable();
            $table->string('video_html5_public_id')->nullable();

            $table->time('duration')->nullable();

            // Lesson Provider if lesson_type = ['TFILE', 'PDF', 'DF', 'IFILE']
            $table->string('lesson_attachment')->nullable();

            $table->text('summary')->nullable();

            // Section Order
            $table->integer('order_index')->unsigned()->default(1);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('course_curriculum_section_lessons');
    }
}
