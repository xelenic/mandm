@props([
    // Image file in public/images/lucky-draw (without ".webp"); its text is part of the image.
    'image',
    // The text on the image, for screen readers and if the image can't load.
    'label',
    // A link when set; otherwise a button (submit by default).
    'href' => null,
    'type' => 'submit',
    // Main call to action: glows softly and shines now and then, to draw the eye.
    'primary' => false,
])

@php
    $src = asset("images/lucky-draw/{$image}.webp");

    // Hover (on devices that can hover): lift, grow a little, brighten, gold shine sweeps across.
    // Tap / click: press in.
    $classes = 'group relative inline-block shrink-0 rounded-full transition duration-200 ease-out '
        .'hover:-translate-y-[0.8cqw] hover:scale-[1.04] hover:brightness-110 active:translate-y-0 active:scale-[0.95] active:brightness-95 '
        .'focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-white '
        .'disabled:pointer-events-none disabled:opacity-70'
        .($primary ? ' lucky-shine-idle' : '');

    $imageClasses = 'block h-auto w-full transition-[filter] duration-200 '
        .($primary ? 'animate-glow' : 'drop-shadow-[0_3px_4px_rgba(0,0,0,0.3)] group-hover:drop-shadow-[0_8px_10px_rgba(0,0,0,0.35)]');
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>
@endif
    <img src="{{ $src }}" alt="{{ $label }}" class="{{ $imageClasses }}" draggable="false">
    <span class="lucky-shine" style="--lucky-mask: url('{{ $src }}')" aria-hidden="true"></span>
@if ($href)
    </a>
@else
    </button>
@endif
