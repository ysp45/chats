<?php

namespace Namu\WireChat\Middleware;

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
        
        // Check if user has chat-specific permission and the conversation involves a psychologist
        if ($user && $user->can('chat-specific')) {
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
        }
        
        // Default check if user belongs to conversation
        if ($user && $user->belongsToConversation($conversation)) {
            return $next($request);
        }

        abort(403, 'Forbidden');
    }
}