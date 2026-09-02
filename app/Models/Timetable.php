<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Timetable extends Model { protected $guarded=[]; public function subject(){return $this->belongsTo(Subject::class);} public function teacher(){return $this->belongsTo(Teacher::class);} }
