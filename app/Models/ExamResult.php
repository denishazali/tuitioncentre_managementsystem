<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ExamResult extends Model {
 protected $guarded=[]; protected function casts():array{return ['score'=>'decimal:2'];}
 public function student(){return $this->belongsTo(Student::class);} public function subject(){return $this->belongsTo(Subject::class);}
 public function creator(){return $this->belongsTo(User::class,'created_by');}
}
