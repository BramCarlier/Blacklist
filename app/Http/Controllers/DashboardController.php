<?php
namespace App\Http\Controllers;
use App\Models\BlacklistEntry;
use Inertia\Inertia;
class DashboardController extends Controller
{
    public function __invoke(){
        return Inertia::render('Dashboard', [
            'stats' => [
                'active' => BlacklistEntry::where('status','active')->count(),
                'expired' => BlacklistEntry::where('status','expired')->count(),
                'checksToday' => BlacklistEntry::whereDate('last_checked_at', now()->toDateString())->count(),
            ],
            'recent' => BlacklistEntry::latest()->limit(8)->get(['id','first_name','last_name','status','location','reason','created_at'])
        ]);
    }
}
