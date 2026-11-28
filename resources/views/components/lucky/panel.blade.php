{{-- A rounded card: white (screens 3, 4, 7, 8) or green (the contact form, screens 5 and 6). --}}
@props(['variant' => 'white'])

<div {{ $attributes->class([
    'flex w-[80cqw] flex-col items-center rounded-[6cqw] px-[6cqw] py-[6cqw] text-center shadow-[0_2cqw_5cqw_rgba(0,0,0,0.12)]',
    'bg-white text-candy-text' => $variant === 'white',
    'bg-mint text-white' => $variant === 'green',
]) }}>
    {{ $slot }}
</div>
