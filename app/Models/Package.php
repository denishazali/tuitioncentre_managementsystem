<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Package extends Model {
 protected $guarded=[]; protected function casts():array{return ['price'=>'decimal:2','is_active'=>'boolean'];}
 public function subjects(){return $this->belongsToMany(Subject::class)->withPivot('is_compulsory')->withTimestamps();}
 public function enrollments(){return $this->hasMany(Enrollment::class);}
}
