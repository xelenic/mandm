@props([
    // A link when set; otherwise a button (submit by default).
    'href' => null,
    'type' => 'submit',
    // green: main action · red: other actions · white: on a green panel · outline: small "Back" link.
    'variant' => 'red',
    // Size: lg (menu's main button), md (most buttons), sm (small buttons inside panels).
    'size' => 'md',
    // Main call to action: glows softly and shines now and then, to draw the eye.
    'primary' => false,
])

@php
    $colors = [
        'green' => 'bg-mint text-white shadow-[0_1cqw_2cqw_rgba(0,0,0,0.18)] hover:bg-[#00b836]',
        'red' => 'bg-candy text-white shadow-[0_1cqw_2cqw_rgba(0,0,0,0.18)] hover:bg-[#e80a05]',
        'white' => 'bg-white text-candy shadow-[0_1cqw_2cqw_rgba(0,0,0,0.15)] hover:bg-[#fff6f0]',
        'outline' => 'border-[0.4cqw] border-cocoa/70 bg-transparent text-cocoa hover:bg-white/40',
    ][$variant];

    $sizes = [
        'lg' => 'px-[5cqw] py-[3cqw] text-[5.7cqw]',
        'md' => 'px-[5cqw] py-[2.2cqw] text-[4.5cqw]',
        'sm' => 'px-[5cqw] py-[1.9cqw] text-[4.1cqw]',
        'xs' => 'px-[3cqw] py-[0.6cqw] text-[3.6cqw]',
    ][$size];

    // Hover (on devices that can hover): lift, grow a little, shine sweeps across. Tap: press in.
    // Labels stay on one line, as in the design.
    $classes = "group relative inline-flex shrink-0 items-center justify-center gap-[1.5cqw] overflow-hidden rounded-full text-center font-title leading-none font-bold whitespace-nowrap {$colors} {$sizes} "
        .'transition duration-200 ease-out hover:-translate-y-[0.8cqw] hover:scale-[1.03] active:translate-y-0 active:scale-[0.95] '
        .'focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-cocoa '
        .'disabled:pointer-events-none disabled:opacity-70'
        .($primary ? ' lucky-shine-idle animate-glow' : '');
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>
@endif
    <span class="relative">{{ $slot }}</span>
    <span class="lucky-shine" aria-hidden="true"></span>
@if ($href)
    </a>
@else
    </button>
@endif
