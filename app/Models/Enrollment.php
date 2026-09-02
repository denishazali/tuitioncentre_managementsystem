<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Enrollment extends Model {
 protected $guarded=[]; protected function casts():array{return ['total_amount'=>'decimal:2','submitted_at'=>'datetime','approved_at'=>'datetime'];}
 public function student(){return $this->belongsTo(Student::class);} public function parent(){return $this->belongsTo(User::class,'parent_id');}
 public function package(){return $this->belongsTo(Package::class);} public function payment(){return $this->hasOne(Payment::class);}
 public function subjects(){return $this->belongsToMany(Subject::class)->withPivot('price_at_enrollment')->withTimestamps();}
}
