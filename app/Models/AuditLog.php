<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AuditLog extends Model
{
    public $timestamps = false;
    protected $fillable = ['user_id','blacklist_entry_id','action','metadata','ip_address','user_agent','created_at'];
    protected function casts(): array { return ['metadata'=>'array','created_at'=>'datetime']; }
}
