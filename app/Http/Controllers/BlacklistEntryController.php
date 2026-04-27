<?php
namespace App\Http\Controllers;
use App\Models\AuditLog;
use App\Models\BlacklistEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
class BlacklistEntryController extends Controller
{
    public function index(Request $request){
        $q = trim((string)$request->query('q',''));
        $entries = BlacklistEntry::query()
            ->when($q, fn($query)=>$query->where(fn($w)=>$w->where('first_name','like',"%$q%")->orWhere('last_name','like',"%$q%")->orWhere('id_document_number','like',"%$q%")->orWhere('location','like',"%$q%")))
            ->latest()->paginate(15)->withQueryString();
        return Inertia::render('Blacklist/Index', ['entries'=>$entries, 'filters'=>['q'=>$q]]);
    }
    public function create(){ return Inertia::render('Blacklist/Create'); }
    public function store(Request $request){
        $data = $this->validated($request);
        $entry = DB::transaction(function() use ($request,$data){
            $payload = (string)($data['raw_payload'] ?? ''); unset($data['raw_payload']);
            $data['created_by'] = $request->user()->id;
            $data['scanned_payload_hash'] = $payload ? hash('sha256', $payload) : null;
            $entry = BlacklistEntry::create($data);
            $this->audit($request, $entry, 'created', ['source'=>$entry->source]);
            return $entry;
        });
        return redirect()->route('blacklist.index')->with('success', $entry->full_name.' added to blacklist.');
    }
    public function edit(BlacklistEntry $blacklistEntry){ return Inertia::render('Blacklist/Edit', ['entry'=>$blacklistEntry]); }
    public function update(Request $request, BlacklistEntry $blacklistEntry){
        $data = $this->validated($request, updating: true); unset($data['raw_payload']);
        $blacklistEntry->update($data); $this->audit($request, $blacklistEntry, 'updated');
        return redirect()->route('blacklist.index')->with('success','Blacklist entry updated.');
    }
    public function destroy(Request $request, BlacklistEntry $blacklistEntry){
        $this->audit($request, $blacklistEntry, 'deleted'); $blacklistEntry->delete();
        return back()->with('success','Entry removed.');
    }
    public function status(Request $request, BlacklistEntry $blacklistEntry){
        $data = $request->validate(['status'=>['required','in:active,expired,appealed']]);
        $blacklistEntry->update(['status'=>$data['status']]); $this->audit($request, $blacklistEntry, 'status_changed', $data);
        return back()->with('success','Status updated.');
    }
    public function lookup(Request $request): JsonResponse{
        $data = $request->validate([
            'id_document_number'=>['nullable','string','max:120'],
            'raw_payload'=>['nullable','string','max:8000'],
            'first_name'=>['nullable','string','max:120'],
            'last_name'=>['nullable','string','max:120'],
            'birth_date'=>['nullable','date'],
        ]);
        $document = trim((string)($data['id_document_number'] ?? ''));
        $raw = trim((string)($data['raw_payload'] ?? ''));
        $first = trim((string)($data['first_name'] ?? ''));
        $last = trim((string)($data['last_name'] ?? ''));
        $birth = trim((string)($data['birth_date'] ?? ''));
        $hash = $raw !== '' ? hash('sha256', $raw) : null;
        $matches = BlacklistEntry::query()
            ->where(function($q) use ($document, $hash, $first, $last, $birth) {
                if ($document !== '') $q->orWhere('id_document_number', $document);
                if ($hash) $q->orWhere('scanned_payload_hash', $hash);
                if ($first !== '' && $last !== '') {
                    $q->orWhere(function($name) use ($first, $last, $birth) {
                        $name->where('first_name', 'like', $first)->where('last_name', 'like', $last);
                        if ($birth !== '') $name->whereDate('birth_date', $birth);
                    });
                }
            })
            ->latest()
            ->limit(10)
            ->get(['id','first_name','last_name','birth_date','id_document_number','nationality','reason','location','status','last_checked_at','created_at']);
        $matches->each(function(BlacklistEntry $entry) use ($request) {
            $entry->forceFill(['last_checked_at'=>now()])->save();
            $this->audit($request, $entry, 'nfc_lookup');
        });
        return response()->json([
            'found' => $matches->isNotEmpty(),
            'active_found' => $matches->contains(fn($entry) => $entry->status === 'active'),
            'matches' => $matches,
        ]);
    }
    public function check(Request $request){
        $data = $request->validate(['query'=>['required','string','max:255']]);
        $q = $data['query'];
        $matches = BlacklistEntry::where('status','active')->where(fn($w)=>$w->where('id_document_number',$q)->orWhere('scanned_payload_hash', hash('sha256',$q))->orWhere('last_name','like',"%$q%"))->limit(10)->get();
        foreach ($matches as $entry) { $entry->forceFill(['last_checked_at'=>now()])->save(); $this->audit($request, $entry, 'checked', ['query'=>Str::limit($q, 24)]); }
        return back()->with('success', $matches->count() ? 'Match found: deny access.' : 'No active match found.');
    }
    private function validated(Request $request, bool $updating=false): array{
        return $request->validate([
            'first_name'=>['required','string','max:120'], 'last_name'=>['required','string','max:120'], 'birth_date'=>['nullable','date'],
            'id_document_number'=>['required','string','max:120'], 'nationality'=>['nullable','string','max:80'],
            'reason'=>['required','string','max:255'], 'location'=>['required','string','max:120'], 'source'=>['required','in:manual,camera,nfc'],
            'status'=>['required','in:active,expired,appealed'], 'notes'=>['nullable','string','max:4000'], 'raw_payload'=>['nullable','string','max:8000'],
        ]);
    }
    private function audit(Request $request, BlacklistEntry $entry, string $action, array $metadata=[]): void{
        AuditLog::create(['user_id'=>$request->user()?->id,'blacklist_entry_id'=>$entry->id,'action'=>$action,'metadata'=>$metadata,'ip_address'=>$request->ip(),'user_agent'=>Str::limit((string)$request->userAgent(), 255),'created_at'=>now()]);
    }
}
