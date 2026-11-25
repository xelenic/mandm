{{-- One numbered clause of the Terms & Conditions, e.g. "1.1  The Campaign is …". --}}
@props(['number'])

<li class="grid grid-cols-[9cqw_1fr] gap-[1.5cqw]">
    <span class="font-title font-bold text-berry">{{ $number }}</span>
    <p>{{ $slot }}</p>
</li>
