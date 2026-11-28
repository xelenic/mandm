{{--
    A simple panel screen in the same style, for states the design doesn't
    show: code not found, already claimed, and the Winners Gallery.

    $title       heading
    $paragraphs  list of plain-text paragraphs
    $menuUrl     where "Back to Menu" goes
--}}
<x-layouts.lucky-draw :title="$title" center>
    <x-lucky.panel class="animate-pop-in [animation-delay:0.2s]">
        <h1 class="font-title text-[7.6cqw] leading-[1.1] font-bold text-balance text-candy">{{ $title }}</h1>

        <div class="mt-[4cqw] space-y-[3cqw] text-[5.2cqw] leading-snug">
            @foreach ($paragraphs as $paragraph)
                <p>{{ $paragraph }}</p>
            @endforeach
        </div>

        <x-lucky.button :href="$menuUrl" size="sm" primary class="mt-[7cqw] animate-rise-in [animation-delay:0.6s]">Back to Menu</x-lucky.button>
    </x-lucky.panel>
</x-layouts.lucky-draw>
