{{-- Inline styles: Filament's prebuilt CSS doesn't include custom utility classes. --}}
<div style="display: flex; flex-direction: column; align-items: center; gap: 0.75rem; text-align: center;">
    <div style="width: 14rem; padding: 0.5rem; border-radius: 0.5rem; background: #fff;">
        <div style="line-height: 0;">{!! str_replace('<svg ', '<svg style="width:100%;height:auto" ', $svg) !!}</div>
    </div>

    <p style="font-size: 0.875rem; word-break: break-all; opacity: 0.75;">{{ $url }}</p>

    @if (filled($note ?? null))
        <p style="font-size: 0.75rem; opacity: 0.6;">{{ $note }}</p>
    @endif
</div>
