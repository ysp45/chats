<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Message;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Define a gate for viewing messages in a conversation
        Gate::define('view-conversation-messages', function (User $user, Conversation $conversation) {
            // Users with chat-all permission can view all conversations
            if ($user->can('chat-all')) {
                return true;
            }
            
            // Users without special permissions can only view conversations with psychologists
            // Check if the conversation involves a psychologist with online_chat=true
            $hasPsychologist = $conversation->participants()
                ->whereHas('participantable', function ($query) {
                    $query->whereHas('psychologist', function ($q) {
                        $q->where('online_chat', true);
                    });
                })
                ->exists();
                
            // Check if the user is a participant in the conversation
            $isParticipant = $conversation->participants()
                ->where('participantable_id', $user->id)
                ->where('participantable_type', $user->getMorphClass())
                ->exists();
            
            return $hasPsychologist && $isParticipant;
        });
    }
}