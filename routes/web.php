<?php

use Illuminate\Support\Facades\Route;
use Namu\WireChat\Livewire\Pages\Chat;
use Namu\WireChat\Livewire\Pages\Chats;
use App\Models\User;
use App\Http\Controllers\ConversationController;

Route::middleware(config('wirechat.routes.middleware'))
    ->prefix(config('wirechat.routes.prefix'))
    ->group(function () {
        Route::get('/', Chats::class)->name('chats');
        Route::get('/{conversation}', Chat::class)->middleware('belongsToConversation')->name('chat');
    });

// Route to create a conversation with a psychologist
Route::get('/create-conversation/{psychologist}', function (User $psychologist) {
    // Check if the user has the required permission
    if (!auth()->user()->can('chat-specific')) {
        abort(403, 'Unauthorized action.');
    }
    
    // Check if the user is a psychologist with online_chat enabled
    if (!$psychologist->psychologist || !$psychologist->psychologist->online_chat) {
        abort(404, 'Psychologist not available for chat.');
    }
    
    // Create or get existing conversation
    $conversation = auth()->user()->createConversationWith($psychologist);
    
    // Redirect to the chat
    return redirect()->route('chat', $conversation->id);
})->middleware(['auth'])->name('create-conversation');