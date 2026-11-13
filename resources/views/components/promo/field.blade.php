@props(['name', 'label', 'type' => 'text', 'hint' => null])

<div>
    <label for="{{ $name }}" class="block text-sm font-medium text-stone-800">{{ $label }}</label>

    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ old($name) }}"
        @if ($hint) aria-describedby="{{ $name }}-hint" @endif
        @error($name) aria-invalid="true" aria-errormessage="{{ $name }}-error" @enderror
        {{ $attributes->class([
            'mt-1.5 block w-full rounded-xl border bg-white px-4 py-3 text-base text-stone-900 shadow-sm placeholder:text-stone-400 focus:outline-none focus:ring-4',
            'border-stone-300 focus:border-red-600 focus:ring-red-600/15' => ! $errors->has($name),
            'border-red-500 focus:border-red-600 focus:ring-red-600/20' => $errors->has($name),
        ]) }}
    >

    @if ($hint)
        <p id="{{ $name }}-hint" class="mt-1.5 text-xs text-stone-500">{{ $hint }}</p>
    @endif

    @error($name)
        <p id="{{ $name }}-error" class="mt-1.5 text-sm font-medium text-red-700">{{ $message }}</p>
    @enderror
</div>
