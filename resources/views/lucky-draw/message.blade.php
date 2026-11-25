{{--
    A simple panel screen in the same style, for states the design doesn't
    show: code not found, already claimed, and the menu's information pages.

    $title       heading
    $paragraphs  list of plain-text paragraphs
    $menuUrl     where "Back to Menu" goes
--}}
<x-layouts.lucky-draw :title="$title">
    <x-lucky.panel class="mt-[2cqw] animate-pop-in [animation-delay:0.2s]">
        <h1 class="font-heading text-[7.6cqw] leading-[1.15] text-balance">{{ $title }}</h1>
        <x-lucky.divider />

        <div class="space-y-[3cqw] text-[5.8cqw] leading-snug text-berry">
            @foreach ($paragraphs as $paragraph)
                <p>{{ $paragraph }}</p>
            @endforeach
        </div>

        <x-lucky.button :href="$menuUrl" image="btn-back-to-menu" label="Back to Menu" primary class="mt-[6cqw] w-[60cqw] animate-rise-in [animation-delay:0.6s]" />
    </x-lucky.panel>
</x-layouts.lucky-draw>
