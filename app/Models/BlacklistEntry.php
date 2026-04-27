<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class BlacklistEntry extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'first_name','last_name','birth_date','id_document_number','nationality',
        'record_type','incident_date','incident_reference',
        'reason','official_warning_given','official_warning_given_at',
        'gas_fine_recommended','gas_fine_status','gas_fine_reference',
        'location','source','status','notes','scanned_payload_hash','created_by','last_checked_at'
    ];
    protected function casts(): array { return ['birth_date'=>'date','incident_date'=>'date','official_warning_given'=>'boolean','official_warning_given_at'=>'datetime','gas_fine_recommended'=>'boolean','last_checked_at'=>'datetime']; }
    public function creator(){ return $this->belongsTo(User::class, 'created_by'); }
    public function auditLogs(){ return $this->hasMany(AuditLog::class); }
    protected function fullName(): Attribute { return Attribute::get(fn()=>trim($this->first_name.' '.$this->last_name)); }
}
