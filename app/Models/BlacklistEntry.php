<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class BlacklistEntry extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = ['first_name','last_name','birth_date','id_document_number','nationality','reason','location','source','status','notes','scanned_payload_hash','created_by','last_checked_at'];
    protected function casts(): array { return ['birth_date'=>'date','last_checked_at'=>'datetime']; }
    public function creator(){ return $this->belongsTo(User::class, 'created_by'); }
    public function auditLogs(){ return $this->hasMany(AuditLog::class); }
    protected function fullName(): Attribute { return Attribute::get(fn()=>trim($this->first_name.' '.$this->last_name)); }
}
