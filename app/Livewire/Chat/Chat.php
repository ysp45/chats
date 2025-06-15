<?php

namespace App\Livewire\Chat;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Namu\WireChat\Enums\ConversationType;
use Namu\WireChat\Enums\MessageType;
use Namu\WireChat\Events\MessageCreated;
use Namu\WireChat\Events\MessageDeleted;
use Namu\WireChat\Facades\WireChat;
use Namu\WireChat\Jobs\NotifyParticipants;
use Namu\WireChat\Livewire\Chats\Chats;
use Namu\WireChat\Livewire\Concerns\Widget;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Message;
use Namu\WireChat\Models\Participant;

/**
 * Chat Component
 *
 * Handles group, private and self conversations.
 *
 * @property \Illuminate\Contracts\Auth\Authenticatable|null $auth
 */
class Chat extends \Namu\WireChat\Livewire\Chat\Chat
{
    /**
     * Send a message  */
    public function sendMessage()
    {
        abort_unless(auth()->check(), 401);
        
        // Check if user has permission to send messages
        if (!$this->auth->can('chat-all')) {
            // For users without chat-all permission, check if they're chatting with a psychologist
            $hasPsychologist = $this->conversation->participants()
                ->whereHas('participantable', function ($query) {
                    $query->whereHas('psychologist', function ($q) {
                        $q->where('online_chat', true);
                    });
                })
                ->exists();
            
            if (!$hasPsychologist) {
                abort(403, 'You can only chat with available psychologists.');
            }
        }

        // rate limit
        $this->rateLimit();

        /* If media is empty then continue to validate body, since media can be submitted without body */
        // Combine media and files arrays

        $attachments = array_merge($this->media, $this->files);

        // If combined files array is empty, continue to validate body
        if (empty($attachments)) {
            $this->validate(['body' => 'required|string']);
        }

        if (count($attachments) != 0) {
            // Validation
            $maxUploads = config('wirechat.attachments.max_uploads');
            $fileMimes = implode(',', config('wirechat.attachments.file_mimes'));
            $fileMaxUploadSize = (int) config('wirechat.attachments.file_max_upload_size');
            $mediaMimes = implode(',', config('wirechat.attachments.media_mimes'));
            $mediaMaxUploadSize = (int) config('wirechat.attachments.media_max_upload_size');

            try {
                $this->validate([
                    'files' => "array|max:$maxUploads|nullable",
                    'files.*' => "max:$fileMaxUploadSize|mimes:$fileMimes",
                    'media' => "array|max:$maxUploads|nullable",
                    'media.*' => "max:$mediaMaxUploadSize|mimes:$mediaMimes",
                ]);
            } catch (\Illuminate\Validation\ValidationException $th) {
                $errors = $th->errors();
                foreach ($errors as $field => $messages) {
                    $this->addError($field, $messages[0]);
                }
                return $this->dispatch('wirechat-toast', type: 'warning', message: $th->getMessage());
            }

            $createdMessages = [];
            foreach ($attachments as $key => $attachment) {
                $path = $attachment->store(
                    WireChat::storageFolder(),
                    WireChat::storageDisk()
                );

                $replyId = ($key === 0 && $this->replyMessage) ? $this->replyMessage->id : null;

                $message = Message::create([
                    'reply_id' => $replyId,
                    'conversation_id' => $this->conversation->id,
                    'sendable_type' => $this->auth->getMorphClass(),
                    'sendable_id' => auth()->id(),
                    'type' => MessageType::ATTACHMENT,
                ]);

                $attachment = $message->attachment()->create([
                    'file_path' => $path,
                    'file_name' => basename($path),
                    'original_name' => $attachment->getClientOriginalName(),
                    'mime_type' => $attachment->getMimeType(),
                    'url' => Storage::disk(WireChat::storageDisk())->url($path),
                ]);

                $createdMessages[] = $message;
                $this->conversation->updated_at = now();
                $this->conversation->save();
                $this->dispatch('refresh')->to(Chats::class);
                $this->dispatchMessageCreatedEvent($message);
            }

            foreach ($createdMessages as $key => $message) {
                $this->pushMessage($message);
            }

            $this->dispatch('scroll-bottom');
        }

        if ($this->body != null) {
            $createdMessage = Message::create([
                'reply_id' => $this->replyMessage?->id,
                'conversation_id' => $this->conversation->id,
                'sendable_type' => $this->auth->getMorphClass(),
                'sendable_id' => auth()->id(),
                'body' => $this->body,
                'type' => MessageType::TEXT,
            ]);

            $this->pushMessage($createdMessage);
            $this->conversation->touch();
            $this->dispatchMessageCreatedEvent($createdMessage);
            $this->dispatch('refresh')->to(Chats::class);
        }

        $this->reset('media', 'files', 'body');
        $this->dispatch('scroll-bottom');
        $this->removeReply();
    }

    // Override the dispatchMessageCreatedEvent method to use direct broadcasting instead of queues
    protected function dispatchMessageCreatedEvent(Message $message): void
    {
        // Don't dispatch if it is a self conversation
        if ($this->conversation->isSelf()) {
            return;
        }

        try {
            // Broadcast the message directly without using a queue
            broadcast(new MessageCreated($message))->toOthers();

            // Notify participants if conversation is NOT self
            if (!$this->conversation->isSelf()) {
                // Create and handle the notification directly
                $participants = $this->conversation->participants()
                    ->withoutParticipantable($this->auth)
                    ->latest('last_active_at')
                    ->get();
                
                foreach ($participants as $participant) {
                    broadcast(new \Namu\WireChat\Events\NotifyParticipant($participant, $message));
                }
            }
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
        }
    }
}