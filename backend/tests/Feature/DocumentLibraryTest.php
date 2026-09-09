<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Document;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentLibraryTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_routes_require_authentication(): void
    {
        $this->getJson('/api/documents')->assertUnauthorized();
        $this->postJson('/api/documents')->assertUnauthorized();
    }

    public function test_user_can_upload_private_document_with_metadata_and_checksum(): void
    {
        Storage::fake('documents'); $user=User::factory()->create();
        $category=$user->categories()->create(['name'=>'Architecture','slug'=>'architecture']);
        $tag=$user->tags()->create(['name'=>'Laravel','slug'=>'laravel']);
        $response=$this->actingAs($user)->post('/api/documents', [
            'file'=>UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'), 'title'=>'Architecture Notes',
            'category_id'=>$category->id,'tag_ids'=>[$tag->id],'is_sensitive'=>true,
        ]);
        $response->assertCreated()->assertJsonPath('data.title','Architecture Notes')->assertJsonPath('data.original_name','notes.pdf')->assertJsonPath('data.is_sensitive',true);
        $document=Document::firstOrFail();
        $this->assertNotSame('notes.pdf',$document->stored_name); $this->assertSame(64,strlen($document->checksum));
        Storage::disk('documents')->assertExists($document->path);
    }

    public function test_user_cannot_assign_another_users_category_or_tags(): void
    {
        Storage::fake('documents'); $owner=User::factory()->create(); $other=User::factory()->create();
        $category=$other->categories()->create(['name'=>'Private','slug'=>'private']); $tag=$other->tags()->create(['name'=>'Private','slug'=>'private']);
        $payload=['file'=>UploadedFile::fake()->create('notes.pdf',10,'application/pdf'),'title'=>'Blocked','category_id'=>$category->id,'tag_ids'=>[$tag->id]];
        $this->actingAs($owner)->post('/api/documents',$payload)->assertStatus(422);
        $this->assertDatabaseCount('documents',0);
    }

    public function test_cross_user_document_access_is_hidden(): void
    {
        Storage::fake('documents'); $owner=User::factory()->create(); $other=User::factory()->create();
        $document=$owner->documents()->create(['title'=>'Secret','slug'=>'secret','original_name'=>'secret.pdf','stored_name'=>'uuid.pdf','disk'=>'documents','path'=>$owner->id.'/uuid.pdf','mime_type'=>'application/pdf','extension'=>'pdf','size'=>3,'checksum'=>str_repeat('a',64)]);
        Storage::disk('documents')->put($document->path,'pdf');
        $this->actingAs($other)->getJson('/api/documents/'.$document->id)->assertNotFound();
        $this->actingAs($other)->get('/api/documents/'.$document->id.'/preview')->assertNotFound();
        $this->actingAs($other)->get('/api/documents/'.$document->id.'/download')->assertNotFound();
        $this->actingAs($other)->deleteJson('/api/documents/'.$document->id)->assertNotFound();
    }

    public function test_owner_can_update_metadata_and_delete_binary(): void
    {
        Storage::fake('documents'); $user=User::factory()->create();
        $document=$user->documents()->create(['title'=>'Old','slug'=>'old','original_name'=>'old.pdf','stored_name'=>'uuid.pdf','disk'=>'documents','path'=>$user->id.'/uuid.pdf','mime_type'=>'application/pdf','extension'=>'pdf','size'=>3,'checksum'=>str_repeat('b',64)]);
        Storage::disk('documents')->put($document->path,'pdf');
        $this->actingAs($user)->putJson('/api/documents/'.$document->id,['title'=>'New','description'=>'Updated','tag_ids'=>[],'is_sensitive'=>false])->assertOk()->assertJsonPath('data.title','New');
        $this->actingAs($user)->deleteJson('/api/documents/'.$document->id)->assertNoContent();
        $this->assertDatabaseMissing('documents',['id'=>$document->id]); Storage::disk('documents')->assertMissing($document->path);
    }

    public function test_category_deletion_nulls_document_category(): void
    {
        $user=User::factory()->create(); $category=$user->categories()->create(['name'=>'Temp','slug'=>'temp']);
        $document=$user->documents()->create(['category_id'=>$category->id,'title'=>'Doc','slug'=>'doc','original_name'=>'d.pdf','stored_name'=>'u.pdf','disk'=>'documents','path'=>'1/u.pdf','mime_type'=>'application/pdf','extension'=>'pdf','size'=>1,'checksum'=>str_repeat('c',64)]);
        $category->delete(); $this->assertNull($document->fresh()->category_id);
    }
}
