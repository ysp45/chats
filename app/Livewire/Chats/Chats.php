<?php

namespace App\Livewire\Chats;

use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Namu\WireChat\Facades\WireChat;
use Namu\WireChat\Helpers\MorphClassResolver;
use Namu\WireChat\Livewire\Concerns\Widget;
use Namu\WireChat\Models\Conversation;

/**
 * Chats Component
 *
 * Handles chat conversations, search, and real-time updates.
 *
 * @property \Illuminate\Contracts\Auth\Authenticatable|null $auth
 */
class Chats extends \Namu\WireChat\Livewire\Chats\Chats
{
    public $psychologist;

    /**
     * Loads conversations based on the current page and search filters.
     * Applies search filters and updates the conversations collection.
     *
     * @return void
     */
    protected function loadConversations()
    {
        $perPage = 10;
        $offset = ($this->page - 1) * $perPage;

        // Check if user has chat-all permission
        if ($this->auth->can('chat-all')) {
            $additionalConversations = $this->auth->conversations()
                ->with([
                    'lastMessage.sendable',
                    'group.cover' => fn($query) => $query->select('id', 'url', 'attachable_type', 'attachable_id', 'file_path'),
                ])
                ->when(trim($this->search ?? '') != '', fn($query) => $this->applySearchConditions($query))
                ->when(trim($this->search ?? '') == '', function ($query) {
                    /** @phpstan-ignore-next-line */
                    return $query->withoutDeleted()->withoutBlanks();
                })
                ->latest('updated_at')
                ->skip($offset)
                ->take($perPage)
                ->get();
        } else {
            // User can only chat with psychologists who have online_chat=true
            $additionalConversations = $this->auth->conversations()
                ->with([
                    'lastMessage.sendable',
                    'group.cover' => fn($query) => $query->select('id', 'url', 'attachable_type', 'attachable_id', 'file_path'),
                ])
                ->when(trim($this->search ?? '') != '', fn($query) => $this->applySearchConditions($query))
                ->when(trim($this->search ?? '') == '', function ($query) {
                    /** @phpstan-ignore-next-line */
                    return $query->withoutDeleted()->withoutBlanks();
                })
                ->whereHas('participants', function ($query) {
                    $query->where(function ($q) {
                        $q->where('participantable_id', $this->auth->id)
                          ->where('participantable_type', $this->auth->getMorphClass());
                    })
                    ->orWhereHas('participantable', function ($q) {
                        $q->whereHas('psychologist', function ($q) {
                            $q->where('online_chat', true);
                        });
                    });
                })
                ->latest('updated_at')
                ->skip($offset)
                ->take($perPage)
                ->get();
        }

        // Set participants manually where needed
        $additionalConversations->each(function ($conversation) {
            if ($conversation->isPrivate() || $conversation->isSelf()) {
                // Manually load participants (only 2 expected in private/self)
                $participants = $conversation->participants()->select('id', 'participantable_id', 'participantable_type', 'conversation_id', 'conversation_read_at')->with('participantable')->get();
                $conversation->setRelation('participants', $participants);

                // Set peer and auth participants
                $conversation->auth_participant = $conversation->participant($this->auth);
                $conversation->peer_participant = $conversation->peerParticipant($this->auth);
            }
        });

        $this->canLoadMore = $additionalConversations->count() === $perPage;

        $this->conversations = collect($this->conversations)
            ->concat($additionalConversations)
            ->unique('id')
            ->sortByDesc('updated_at')
            ->values();
    }

    /**
     * Mounts the component and initializes conversations.
     *
     * @return void
     */
    public function mount(
        $showNewChatModalButton = null,
        $allowChatsSearch = null,
        $showHomeRouteButton = null,
        ?string $title = null,
    ) {
        // If a value is passed, use it; otherwise fallback to WireChat defaults.
        $this->showNewChatModalButton = isset($showNewChatModalButton) ? $showNewChatModalButton : WireChat::showNewChatModalButton();
        $this->allowChatsSearch = isset($allowChatsSearch) ? $allowChatsSearch : WireChat::allowChatsSearch();
        $this->showHomeRouteButton = isset($showHomeRouteButton) ? $showHomeRouteButton : ! $this->widget;
        $this->title = isset($title) ? $title : __('wirechat::chats.labels.heading');

        abort_unless(auth()->check(), 401);
        $this->selectedConversationId = request()->conversation;
        $this->conversations = collect();

        // Load available psychologists for regular users
        if (!$this->auth->can('chat-all')) {
            $this->psychologist = User::with('psychologist')
                ->whereHas('psychologist', function ($query) {
                    $query->where('online_chat', '=', 1);
                })
                ->get();

            if (request()->is('chats') && count($this->psychologist) == 1) {
                header('Location: ' . route('create-conversation', $this->psychologist[0]->id));
                exit;
            }
        }
    }
}