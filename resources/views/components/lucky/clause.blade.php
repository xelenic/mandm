{{-- One numbered clause of the Terms & Conditions, e.g. "1.1  The Campaign is …". --}}
@props(['number'])

<li class="grid grid-cols-[7cqw_1fr] gap-[1cqw]">
    <span class="font-title font-bold">{{ $number }}</span>
    <p>{{ $slot }}</p>
</li>
