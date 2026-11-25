{{--
    A one-time confetti burst for winning moments. Positions, colours and
    timings are spread out evenly (not random) so every visit looks the same.
--}}
@props(['pieces' => 36])

@php
    $colors = ['#e31b23', '#ffd200', '#00a651', '#0072bc', '#f7941d', '#6d3b1f', '#ffffff'];
@endphp

<div class="lucky-confetti" aria-hidden="true">
    @for ($i = 0; $i < $pieces; $i++)
        <i style="
            --x: {{ ($i * 37 + 5) % 100 }}%;
            --c: {{ $colors[$i % count($colors)] }};
            --delay: {{ number_format(($i * 0.07) % 1.1, 2) }}s;
            --d: {{ number_format(2.6 + ($i % 5) * 0.35, 2) }}s;
            --drift: {{ (($i * 53) % 21) - 10 }}cqw;
            --spin: {{ (($i % 2) ? 1 : -1) * (360 + ($i * 47) % 540) }}deg;
        "></i>
    @endfor
</div>
