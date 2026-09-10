<?php

namespace Tests\Feature;

use App\Models\MessageAttachment;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MessagingRichMediaTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp():void{parent::setUp();config(['access_control.owner_email'=>null]);app(PermissionRegistrar::class)->forgetCachedPermissions();$this->seed(AccessControlSeeder::class);Storage::fake('local');}
    private function user(string $role='Viewer'):User{$u=User::factory()->create();$u->assignRole($role);return $u;}
    private function conversation(User $a,User $b):int{return (int)$this->actingAs($a)->postJson('/api/messaging/conversations',['recipient_id'=>$b->id])->json('data.id');}
    public function test_participant_can_upload_private_attachment_without_storage_metadata_leaking():void{$a=$this->user();$b=$this->user();$id=$this->conversation($a,$b);$r=$this->actingAs($a)->post("/api/messaging/conversations/{$id}/attachments",['kind'=>'file','file'=>UploadedFile::fake()->createWithContent('notes.txt','private notes')],['Accept'=>'application/json'])->assertCreated();$r->assertJsonMissingPath('data.attachments.0.disk')->assertJsonMissingPath('data.attachments.0.path')->assertJsonMissingPath('data.attachments.0.checksum_sha256');$attachment=MessageAttachment::query()->firstOrFail();Storage::disk('local')->assertExists($attachment->path);$this->actingAs($b)->get($r->json('data.attachments.0.download_url'))->assertOk();}
    public function test_nonparticipant_cannot_read_attachment_even_with_owner_role():void{$a=$this->user();$b=$this->user();$owner=$this->user('Owner');$id=$this->conversation($a,$b);$r=$this->actingAs($a)->post("/api/messaging/conversations/{$id}/attachments",['kind'=>'image','file'=>UploadedFile::fake()->create('photo.jpg',10,'image/jpeg')],['Accept'=>'application/json'])->assertCreated();$this->actingAs($owner)->get($r->json('data.attachments.0.download_url'))->assertNotFound();$this->actingAs($owner)->get($r->json('data.attachments.0.media_url'))->assertNotFound();}
    public function test_disallowed_attachment_type_is_rejected():void{$a=$this->user();$b=$this->user();$id=$this->conversation($a,$b);$this->actingAs($a)->post("/api/messaging/conversations/{$id}/attachments",['kind'=>'file','file'=>UploadedFile::fake()->create('archive.zip',10,'application/zip')],['Accept'=>'application/json'])->assertUnprocessable()->assertJsonValidationErrors('file');}
    public function test_attachment_only_and_voice_messages_are_supported():void{$a=$this->user();$b=$this->user();$id=$this->conversation($a,$b);$this->actingAs($a)->post("/api/messaging/conversations/{$id}/attachments",['kind'=>'file','file'=>UploadedFile::fake()->createWithContent('readme.txt','hello')],['Accept'=>'application/json'])->assertCreated()->assertJsonPath('data.body',null);$voice=$this->actingAs($a)->post("/api/messaging/conversations/{$id}/attachments",['kind'=>'voice','duration_ms'=>1500,'file'=>UploadedFile::fake()->create('voice.webm',20,'audio/webm')],['Accept'=>'application/json'])->assertCreated();$voice->assertJsonPath('data.attachments.0.kind','voice')->assertJsonPath('data.attachments.0.duration_ms',1500);$this->actingAs($b)->get($voice->json('data.attachments.0.media_url'))->assertOk();}
}
