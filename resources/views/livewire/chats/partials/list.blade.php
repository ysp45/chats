@use('Namu\WireChat\Facades\WireChat')


<ul wire:loading.delay.long.remove wire:target="search" class="space-y-1.5">
    @foreach ($conversations as $key => $conversation)
    @php
    $group = $conversation->isGroup() ? $conversation->group : null;
    $receiver = $conversation->isGroup() ? null : ($conversation->isPrivate() ?
    $conversation->peer_participant?->participantable : $this->auth);
    $lastMessage = $conversation->lastMessage;
    $isReadByAuth = $conversation?->readBy($conversation->auth_participant ?? $this->auth) || $selectedConversationId ==
    $conversation->id;
    $belongsToAuth = $lastMessage?->belongsToAuth();
    @endphp

    <li x-data="{
            conversationID: @js($conversation->id),
            showUnreadStatus: @js(!$isReadByAuth),
            handleChatOpened(event) {
                if (event.detail.conversation == this.conversationID) {
                    this.showUnreadStatus = false;
                }
                $wire.selectedConversationId = event.detail.conversation;
            },
            handleChatClosed(event) {
                $wire.selectedConversationId = null;
                selectedConversationId = null;
            }
        }" id="conversation-{{ $conversation->id }}"
        wire:key="conversation-em-{{ $conversation->id }}-{{ $conversation->updated_at->timestamp }}"
        x-on:chat-opened.window="handleChatOpened($event)"
        class="relative rounded-lg transition-colors duration-200 mx-1 "
        :class="$wire.selectedConversationId == conversationID ? 'bg-zinc-100/80 dark:bg-zinc-700/90' : 'hover:bg-zinc-50/80 dark:hover:bg-zinc-700/50'">

        <a @if ($widget) tabindex="0" role="button" dusk="openChatWidgetButton"
            @click="$dispatch('open-chat', {conversation: '@json($conversation->id)'})"
            @keydown.enter="$dispatch('open-chat', {conversation: '@json($conversation->id)'})" @else wire:navigate
            href="{{ route(WireChat::viewRouteName(), $conversation->id) }}" @endif
            class="flex items-center gap-3 p-2.5">

            <!-- Avatar -->
            <div class="shrink-0 relative">
                <x-wirechat::avatar disappearing="{{ $conversation->hasDisappearingTurnedOn() }}"
                    group="{{ $conversation->isGroup() }}"
                    src="{{ $group ? $group?->cover_url : $receiver?->cover_url ?? null }}"
                    class="w-10 h-10 rounded-full border border-zinc-200/80 dark:border-zinc-600/80" />
            </div>

            <!-- Conversation content -->
            <div class="flex-1 min-w-0">
                <!-- Name and time -->
                <div class="flex items-center justify-between w-full">
                    <h6 class="text-[0.95rem] font-medium truncate text-zinc-800 dark:text-zinc-200">
                        {{ $group ? $group?->name : $receiver?->display_name }}
                        @if ($conversation->isSelfConversation())
                        <span class="text-xs font-normal ml-1 text-zinc-500 dark:text-zinc-400">
                            ({{ __('wirechat::chats.labels.you') }})
                        </span>
                        @endif
                    </h6>

                    <div class="flex items-center gap-1.5 shrink-0">
                        @if ($lastMessage)
                        <span class="text-[0.7rem] text-zinc-400 dark:text-zinc-500">
                            {{ $lastMessage->created_at->format('H:i') }}
                        </span>
                        @endif
                    </div>
                </div>

                <!-- Message preview -->
                @if ($lastMessage)
                <div class="flex justify-between items-start w-full mt-0.5">
                    <div class="flex-1 min-w-0">
                        <p class="text-xs truncate" :class="{
                'text-zinc-500 dark:text-zinc-400': !showUnreadStatus,
                'text-zinc-700 dark:text-zinc-200 font-medium': showUnreadStatus
            }">

                            <span class="@if($belongsToAuth) text-zinc-600 dark:text-zinc-300 @endif">
                                @include('wirechat::livewire.chats.partials.message-body')
                            </span>
                        </p>
                    </div>

                    <!-- Unread indicator and timestamp -->
                    <div class="flex items-center gap-1.5 pl-2">
                        @if (!$lastMessage?->ownedBy($this->auth) && !$isReadByAuth)
                        <span x-show="showUnreadStatus"
                            class="w-2 h-2 rounded-full bg-teal-500 dark:bg-teal-400 flex-shrink-0"></span>
                        @endif
                    </div>
                </div>
                @endif
            </div>
        </a>

        <!-- Active indicator -->
        <div x-show="$wire.selectedConversationId == conversationID"
            class="absolute inset-y-2.5 left-0.5 w-0.5 bg-teal-500 dark:bg-teal-400 rounded-r-md"></div>
    </li>
    @endforeach
</ul>

@if(count($conversations) === 0)
<div class="py-8 text-center">
    <div class="mx-auto w-14 h-14 flex items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-700/50 mb-3">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-zinc-400" fill="none" viewBox="0 0 24 24"
            stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
        </svg>
    </div>
    <p class="text-sm text-zinc-500 dark:text-zinc-400">No conversations</p>
</div>
@endif
