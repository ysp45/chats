<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Namu\WireChat\Models\Conversation;
use Symfony\Component\HttpFoundation\Response;

class BelongsToConversation
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $conversationId = $request->route('conversation');

        $conversation = Conversation::findOrFail($conversationId);

        // Check if user has chat-all permission
        if ($user && $user->can('chat-all')) {
            return $next($request);
        }
        
        // Check if the conversation involves a psychologist with online_chat=true
        $hasPsychologist = $conversation->participants()
            ->whereHas('participantable', function ($query) {
                $query->whereHas('psychologist', function ($q) {
                    $q->where('online_chat', true);
                });
            })
            ->exists();
            
        $isParticipant = $conversation->participants()
            ->where('participantable_id', $user->id)
            ->where('participantable_type', $user->getMorphClass())
            ->exists();
            
        if ($hasPsychologist && $isParticipant) {
            return $next($request);
        }
        
        abort(403, 'Forbidden');
    }
}