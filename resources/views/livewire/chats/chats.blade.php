@use('Namu\WireChat\Facades\WireChat')


<div x-data="{ selectedConversationId: '{{ request()->conversation ?? $selectedConversationId }}' }"
    x-on:open-chat.window="selectedConversationId= $event.detail.conversation; $wire.selectedConversationId= $event.detail.conversation;"
    x-init=" setTimeout(() => {
         conversationElement = document.getElementById('conversation-' + selectedConversationId);

         // Scroll to the conversation element
         if (conversationElement) {
             conversationElement.scrollIntoView({ behavior: 'smooth' });
         }
     }, 200);"
    class="flex flex-col bg-[var(--wc-light-primary)] dark:bg-[var(--wc-dark-primary)] transition-all h-full overflow-hidden w-full sm:p-3">

    @php
    /* Show header if any of these conditions are true */
    $showHeader = $showNewChatModalButton || $allowChatsSearch || $showHomeRouteButton || !empty($title);
    @endphp

    {{-- include header --}}
    @includeWhen($showHeader, 'wirechat::livewire.chats.partials.header')

    @can('chat-specific')
    <section class="my-4 mx-1">
        <h2 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200 mb-4">Psikolog Tersedia</h2>
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm overflow-hidden">
            <ul class="divide-y divide-zinc-200 dark:divide-zinc-700 max-h-96 overflow-y-auto">
                @foreach ($psychologist as $key => $user)
                <a wire:key="user-{{ $key }}" href="{{ route('create-conversation', $user->id) }}" wire:navigate
                    class="flex items-center gap-4 p-4 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors duration-200 cursor-pointer">
                    <!-- Avatar -->
                    <x-wirechat::avatar src="{{ $user->cover_url }}"
                        class="w-12 h-12 rounded-full border-2 border-zinc-200 dark:border-zinc-600 object-cover" />

                    <!-- Informasi Pengguna -->
                    <div class="flex-1">
                        <p
                            class="text-base font-medium text-zinc-800 dark:text-zinc-100 group-hover:underline transition-all">
                            {{ $user->display_name }}
                        </p>
                    </div>

                    {{--
                    <!-- Ikon Aksi -->
                    <div class="text-zinc-400 dark:text-zinc-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7">
                            </path>
                        </svg>
                    </div> --}}
                </a>
                @endforeach
            </ul>
        </div>
        <flux:separator class="mt-3" />
    </section>
    @endcan

    <main x-data @scroll.self.debounce="
           {{-- Detect when scrolled to the bottom --}}
            // Calculate scroll values
            scrollTop = $el.scrollTop;
            scrollHeight = $el.scrollHeight;
            clientHeight = $el.clientHeight;

            // Check if the user is at the bottom of the scrollable element
            if ((scrollTop + clientHeight) >= (scrollHeight - 1) && $wire.canLoadMore) {
                // Trigger load more if we're at the bottom
                await $nextTick();
                $wire.loadMore();
            }
            " class=" overflow-y-auto py-2 grow h-full relative " style="contain:content">

        {{-- loading indicator --}}

        @if (count($conversations) > 0)
        {{-- include list item --}}
        @include('wirechat::livewire.chats.partials.list')

        {{-- include load more if true --}}
        @includeWhen($canLoadMore, 'wirechat::livewire.chats.partials.load-more-button')
        @else
        <div class="w-full flex items-center h-full justify-center">
            <h6 class=" font-bold text-gray-700 dark:text-white">{{ __('wirechat::chats.labels.no_conversations_yet') }}
            </h6>
        </div>
        @endif
    </main>

</div>