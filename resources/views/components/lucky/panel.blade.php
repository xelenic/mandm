{{-- The cream card with a gold frame used on screens 5–8 of the design. --}}
<div {{ $attributes->class([
    'w-[82cqw] rounded-[5cqw] bg-[linear-gradient(180deg,#fffaf0_0%,#f8ecd4_100%)] p-[1.2cqw]',
    'border-[0.9cqw] border-gold shadow-[0_0_0_0.5cqw_rgba(255,220,140,0.35),0_10px_30px_rgba(0,0,0,0.45)]',
]) }}>
    <div class="flex flex-col items-center rounded-[3.8cqw] border-[0.35cqw] border-sand/80 px-[4cqw] py-[6cqw] text-center">
        {{ $slot }}
    </div>
</div>
