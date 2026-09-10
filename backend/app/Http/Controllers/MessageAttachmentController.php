<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageAttachment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MessageAttachmentController extends Controller
{
    private const MAX_FILE_KB = 20480;
    private const MAX_VOICE_KB = 15360;

    public function store(Request $request, Conversation $conversation): JsonResponse
    {
        abort_unless($request->user()?->can('messages.send'), 403);
        $this->participantOrFail($conversation, $request->user()->id);
        $validated = $request->validate(['file'=>['required','file','max:'.self::MAX_FILE_KB],'kind'=>['required','in:file,image,voice'],'body'=>['nullable','string','max:5000'],'duration_ms'=>['nullable','integer','min:0','max:3600000']]);
        $file=$request->file('file'); $mime=(string)$file->getMimeType(); $kind=$validated['kind'];
        $this->validateKind($kind,$mime,(int)$file->getSize());
        $body=trim((string)($validated['body']??'')); $disk='local';
        $extension=strtolower($file->guessExtension()?:$file->getClientOriginalExtension()?:'bin');
        $storedName=Str::uuid().'.'.preg_replace('/[^a-z0-9]+/','',$extension); $relativePath='messages/'.$conversation->id.'/'.$storedName;
        try {
            Storage::disk($disk)->putFileAs('messages/'.$conversation->id,$file,$storedName);
            $checksum=hash_file('sha256',Storage::disk($disk)->path($relativePath));
            $message=DB::transaction(function()use($conversation,$request,$body,$disk,$relativePath,$file,$mime,$kind,$checksum,$validated):Message{
                $message=$conversation->messages()->create(['sender_id'=>$request->user()->id,'body'=>$body!==''?$body:null]);
                $message->attachments()->create(['uploaded_by'=>$request->user()->id,'kind'=>$kind,'disk'=>$disk,'path'=>$relativePath,'original_name'=>Str::limit(basename($file->getClientOriginalName()),255,''),'mime_type'=>$mime,'size_bytes'=>$file->getSize(),'duration_ms'=>$kind==='voice'?($validated['duration_ms']??null):null,'checksum_sha256'=>$checksum]);
                $conversation->forceFill(['last_message_at'=>$message->created_at])->save();
                $conversation->participantStates()->where('user_id',$request->user()->id)->update(['last_read_message_id'=>$message->id,'archived_at'=>null]);
                $conversation->participantStates()->where('user_id','!=',$request->user()->id)->update(['archived_at'=>null]);
                return $message;
            });
        } catch (\Throwable $e) { Storage::disk($disk)->delete($relativePath); throw $e; }
        $message->load(['sender:id,name,first_name,last_name,username,job_title','attachments']);
        return response()->json(['data'=>$this->messagePayload($message)],201);
    }

    public function download(Request $request, MessageAttachment $attachment): StreamedResponse
    {
        abort_unless($request->user()?->can('messages.view'),403); $attachment->loadMissing('message');
        $this->participantOrFail($attachment->message->conversation,$request->user()->id); abort_unless(Storage::disk($attachment->disk)->exists($attachment->path),404);
        return Storage::disk($attachment->disk)->download($attachment->path,$attachment->original_name,['Content-Type'=>$attachment->mime_type,'X-Content-Type-Options'=>'nosniff']);
    }

    public function media(Request $request, MessageAttachment $attachment): StreamedResponse
    {
        abort_unless($request->user()?->can('messages.view'),403); $attachment->loadMissing('message');
        $this->participantOrFail($attachment->message->conversation,$request->user()->id); abort_unless(in_array($attachment->kind,['image','voice'],true),404); abort_unless(Storage::disk($attachment->disk)->exists($attachment->path),404);
        return Storage::disk($attachment->disk)->response($attachment->path,null,['Content-Type'=>$attachment->mime_type,'X-Content-Type-Options'=>'nosniff','Cache-Control'=>'private, no-store']);
    }

    private function participantOrFail(Conversation $conversation,int $userId):void { abort_unless($conversation->participantStates()->where('user_id',$userId)->exists(),404); }
    private function validateKind(string $kind,string $mime,int $size):void
    {
        $allowedFiles=['application/pdf','text/plain','text/markdown','application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $allowedImages=['image/jpeg','image/png','image/webp','image/gif'];
        $allowedVoice=['audio/webm','video/webm','audio/ogg','audio/mp4','audio/mpeg','audio/wav','audio/x-wav'];
        $allowed=match($kind){'image'=>$allowedImages,'voice'=>$allowedVoice,default=>array_merge($allowedFiles,$allowedImages)};
        if(!in_array($mime,$allowed,true)||($kind==='voice'&&$size>self::MAX_VOICE_KB*1024)) throw ValidationException::withMessages(['file'=>['This file type or size is not allowed for the selected attachment type.']]);
    }
    private function messagePayload(Message $message):array
    {
        return ['id'=>$message->id,'conversation_id'=>$message->conversation_id,'body'=>$message->body,'sender_id'=>$message->sender_id,'sender'=>$message->sender?->only(['id','name','first_name','last_name','username','job_title']),'attachments'=>$message->attachments->map(fn(MessageAttachment $a)=>['id'=>$a->id,'kind'=>$a->kind,'name'=>$a->original_name,'mime_type'=>$a->mime_type,'size_bytes'=>$a->size_bytes,'duration_ms'=>$a->duration_ms,'download_url'=>'/api/messaging/attachments/'.$a->id.'/download','media_url'=>in_array($a->kind,['image','voice'],true)?'/api/messaging/attachments/'.$a->id.'/media':null])->values(),'created_at'=>optional($message->created_at)?->toISOString()];
    }
}
