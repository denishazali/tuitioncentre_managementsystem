<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Payment extends Model {
 protected $guarded=[]; protected function casts():array{return ['amount'=>'decimal:2','paid_at'=>'datetime','approved_at'=>'datetime'];}
 public function enrollment(){return $this->belongsTo(Enrollment::class);} public function approver(){return $this->belongsTo(User::class,'approved_by');}
}
