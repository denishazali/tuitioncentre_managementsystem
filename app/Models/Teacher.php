<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Teacher extends Model {
 protected $guarded=[]; protected function casts():array{return ['is_active'=>'boolean'];}
 public function subjects(){return $this->belongsToMany(Subject::class)->withTimestamps();}
 public function timetables(){return $this->hasMany(Timetable::class);}
}
