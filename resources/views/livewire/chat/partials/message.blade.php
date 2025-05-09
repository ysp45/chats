@use('Namu\WireChat\Facades\WireChat')

@php
$isSameAsNext = ($message?->sendable_id === $nextMessage?->sendable_id) && ($message?->sendable_type === $nextMessage?->sendable_type);
$isNotSameAsNext = !$isSameAsNext;
$isSameAsPrevious = ($message?->sendable_id === $previousMessage?->sendable_id) && ($message?->sendable_type === $previousMessage?->sendable_type);
$isNotSameAsPrevious = !$isSameAsPrevious;

// Check if the message is short and single-line (≤50 characters and no line breaks)
$isShortSingleLine = $message?->body && strlen($message?->body) <= 50 && !str_contains($message?->body, "\n");
@endphp

<div
    {{-- We use style here to make it easy for dynamic and safe injection --}}
    @style([
        'background-color:var(--wc-brand-primary)' => $belongsToAuth == true
    ])

    @class([
        'flex flex-wrap max-w-fit border border-gray-200/40 dark:border-none rounded-xl p-2.5 flex flex-col text-black bg-[#f6f6f8fb]',
        'text-white' => $belongsToAuth,
        'bg-gray-200 dark:bg-zinc-700 text-gray-800 dark:text-zinc-200' => !$belongsToAuth,

        // Message styles based on position and ownership
        // RIGHT
        'rounded-br-md rounded-tr-2xl' => ($isSameAsNext && $isNotSameAsPrevious && $belongsToAuth),
        'rounded-r-md' => ($isSameAsPrevious && $belongsToAuth),
        'rounded-br-xl rounded-r-xl' => ($isNotSameAsPrevious && $isNotSameAsNext && $belongsToAuth),
        'rounded-br-2xl' => ($isNotSameAsNext && $belongsToAuth),

        // LEFT
        'rounded-bl-md rounded-tl-2xl' => ($isSameAsNext && $isNotSameAsPrevious && !$belongsToAuth),
        'rounded-l-md' => ($isSameAsPrevious && !$belongsToAuth),
        'rounded-bl-xl rounded-l-xl' => ($isNotSameAsPrevious && $isNotSameAsNext && !$belongsToAuth),
        'rounded-bl-2xl' => ($isNotSameAsNext && !$belongsToAuth),
    ])
>
    @if (!$belongsToAuth && $isGroup)
        <div
            @class([
                'shrink-0 font-medium text-purple-500',
                'hidden' => $isSameAsPrevious
            ])
        >
            {{ $message?->sendable?->display_name }}
        </div>
    @endif

    <div class="flex w-full">
        <div @class([
            'flex items-end' => $isShortSingleLine,
            'flex flex-col items-end' => !$isShortSingleLine,
        ])>
            <div class="flex-1">
                <pre
                    class="whitespace-pre-line text-sm tracking-normal dark:text-white"
                    style="font-family: inherit;"
                >{{ $message?->body }}</pre>
            </div>

            <span
                @class([
                    'text-[11px] shrink-0',
                    'ml-2' => $isShortSingleLine, // margin kiri saat pendek
                    'mt-1' => !$isShortSingleLine, // margin atas saat panjang
                    'text-gray-700 dark:text-gray-300' => !$belongsToAuth,
                    'text-gray-100' => $belongsToAuth,
                ])
            >
                {{ $message?->created_at->format('H:i') }}
            </span>
        </div>
    </div>

</div>
