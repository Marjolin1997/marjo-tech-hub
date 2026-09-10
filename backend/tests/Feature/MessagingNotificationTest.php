<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\User;
use App\Notifications\UnreadConversationNotification;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MessagingNotificationTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp():void{parent::setUp();config(['access_control.owner_email'=>null,'app.frontend_url'=>'https://hub.example.test']);app(PermissionRegistrar::class)->forgetCachedPermissions();$this->seed(AccessControlSeeder::class);Notification::fake();}
    private function user():User{$u=User::factory()->create();$u->assignRole('Viewer');return $u;}
    private function conversation(User $a,User $b):Conversation{$id=$this->actingAs($a)->postJson('/api/messaging/conversations',['recipient_id'=>$b->id])->json('data.id');return Conversation::findOrFail($id);}
    private function ageMessage(Conversation $conversation,$message):void{$old=now()->subMinutes(6);$message->forceFill(['created_at'=>$old,'updated_at'=>$old])->save();$conversation->forceFill(['last_message_at'=>$old])->save();}

    public function test_email_is_not_sent_before_five_minutes_but_is_sent_after_threshold():void
    {
        $a=$this->user();$b=$this->user();$c=$this->conversation($a,$b);$m=$c->messages()->create(['sender_id'=>$a->id,'body'=>'private']);$c->update(['last_message_at'=>$m->created_at]);
        $this->artisan('messaging:notify-unread')->assertSuccessful();Notification::assertNothingSent();$this->ageMessage($c,$m);
        $this->artisan('messaging:notify-unread')->assertSuccessful();Notification::assertSentTo($b,UnreadConversationNotification::class,function($notification)use($b){$mail=$notification->toMail($b);return str_contains($mail->actionUrl,'/messages?conversation=')&&!str_contains(implode(' ',(array)$mail->introLines),'private');});
    }

    public function test_notification_is_deduplicated_until_a_new_unread_message_ages():void
    {
        $a=$this->user();$b=$this->user();$c=$this->conversation($a,$b);$first=$c->messages()->create(['sender_id'=>$a->id,'body'=>'one']);$this->ageMessage($c,$first);
        $this->artisan('messaging:notify-unread')->assertSuccessful();$this->artisan('messaging:notify-unread')->assertSuccessful();Notification::assertSentToTimes($b,UnreadConversationNotification::class,1);
        $second=$c->messages()->create(['sender_id'=>$a->id,'body'=>'two']);$c->forceFill(['last_message_at'=>$second->created_at])->save();$this->artisan('messaging:notify-unread')->assertSuccessful();Notification::assertSentToTimes($b,UnreadConversationNotification::class,1);
        $this->ageMessage($c,$second);$this->artisan('messaging:notify-unread')->assertSuccessful();Notification::assertSentToTimes($b,UnreadConversationNotification::class,2);
    }

    public function test_read_before_threshold_prevents_notification():void
    {
        $a=$this->user();$b=$this->user();$c=$this->conversation($a,$b);$m=$c->messages()->create(['sender_id'=>$a->id,'body'=>'read']);$this->ageMessage($c,$m);
        ConversationParticipant::query()->where('conversation_id',$c->id)->where('user_id',$b->id)->update(['last_read_message_id'=>$m->id]);$this->artisan('messaging:notify-unread')->assertSuccessful();Notification::assertNothingSent();
    }
}
