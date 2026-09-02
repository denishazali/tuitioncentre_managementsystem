<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class QuizAttempt extends Model {
 protected $guarded=[]; protected function casts():array{return ['score'=>'decimal:2','started_at'=>'datetime','submitted_at'=>'datetime'];}
 public function quiz(){return $this->belongsTo(Quiz::class);} public function student(){return $this->belongsTo(Student::class);}
}
