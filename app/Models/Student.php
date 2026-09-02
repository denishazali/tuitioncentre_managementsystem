<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Student extends Model {
 protected $guarded=[]; protected function casts():array{return ['date_of_birth'=>'date'];}
 public function user(){return $this->belongsTo(User::class);} public function parent(){return $this->belongsTo(User::class,'parent_id');}
 public function enrollments(){return $this->hasMany(Enrollment::class);} public function quizAttempts(){return $this->hasMany(QuizAttempt::class);}
 public function examResults(){return $this->hasMany(ExamResult::class);}
}
