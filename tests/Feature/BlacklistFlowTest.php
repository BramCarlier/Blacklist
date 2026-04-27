<?php
namespace Tests\Feature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class BlacklistFlowTest extends TestCase
{
    use RefreshDatabase;
    public function test_admin_can_create_blacklist_entry(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/blacklist', ['first_name'=>'Ada','last_name'=>'Lovelace','birth_date'=>'1815-12-10','id_document_number'=>'ABC123','nationality'=>'GB','reason'=>'Test ban','location'=>'Front door','source'=>'manual','status'=>'active','notes'=>''])->assertRedirect('/blacklist');
        $this->assertDatabaseHas('blacklist_entries', ['id_document_number'=>'ABC123','location'=>'Front door']);
    }
}
