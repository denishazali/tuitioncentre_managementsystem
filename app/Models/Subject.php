<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Subject extends Model {
 protected $guarded=[]; protected function casts():array{return ['price'=>'decimal:2','is_active'=>'boolean'];}
 public function teachers(){return $this->belongsToMany(Teacher::class)->withTimestamps();}
 public function enrollments(){return $this->belongsToMany(Enrollment::class)->withPivot('price_at_enrollment')->withTimestamps();}
 public function timetables(){return $this->hasMany(Timetable::class);} public function quizzes(){return $this->hasMany(Quiz::class);}
}
