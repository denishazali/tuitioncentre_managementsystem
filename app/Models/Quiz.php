<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Quiz extends Model {
 protected $guarded=[]; protected function casts():array{return ['is_published'=>'boolean'];}
 public function subject(){return $this->belongsTo(Subject::class);} public function creator(){return $this->belongsTo(User::class,'created_by');}
 public function questions(){return $this->hasMany(QuizQuestion::class);} public function attempts(){return $this->hasMany(QuizAttempt::class);}
}
