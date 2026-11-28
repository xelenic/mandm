{{--
    "How to Join" pop-up (opened by "Campaign Details" on the menu). A native
    <dialog>: Esc and the × button close it, focus stays inside while open.
--}}
@props(['open' => false])

@php
    use App\Support\LuckyDrawImage;
@endphp

<dialog id="how-to-join" class="lucky-dialog m-auto w-full max-w-[480px] overflow-visible bg-transparent p-0" aria-labelledby="how-to-join-title">
    <div class="@container w-full px-[9cqw] py-[6cqw]">
        <h2 id="how-to-join-title" class="text-center font-title text-[9.6cqw] leading-none font-bold text-candy uppercase [paint-order:stroke_fill] [-webkit-text-stroke:1.6cqw_white]">
            How to Join
        </h2>

        <div class="relative mt-[22cqw]">
            {{-- Party poppers and a gift sitting on the top edge of the card. --}}
            <img src="{{ LuckyDrawImage::url('popper-left') }}" alt="" class="pointer-events-none absolute -top-[24cqw] -left-[8cqw] w-[30cqw]" aria-hidden="true">
            <img src="{{ LuckyDrawImage::url('gift-box') }}" alt="" class="pointer-events-none absolute -top-[19cqw] left-1/2 w-[24cqw] -translate-x-1/2 drop-shadow-[0_1.5cqw_2cqw_rgba(0,0,0,0.25)]" aria-hidden="true">
            <img src="{{ LuckyDrawImage::url('popper-right') }}" alt="" class="pointer-events-none absolute -top-[24cqw] -right-[8cqw] w-[30cqw]" aria-hidden="true">

            <form method="dialog" class="absolute -top-[30cqw] -right-[2cqw] z-10">
                <button class="flex size-[8cqw] items-center justify-center rounded-full bg-white text-[5cqw] leading-none text-stone-800 shadow-md transition hover:scale-110 active:scale-95 focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-cocoa" aria-label="Close">
                    &#10005;
                </button>
            </form>

            <div class="rounded-[6cqw] bg-[linear-gradient(180deg,#00a831_0%,#007a24_55%,#005a19_100%)] px-[5cqw] pt-[11cqw] pb-[7cqw] text-center text-white shadow-[0_3cqw_6cqw_rgba(0,0,0,0.2)]">
                <p class="font-title text-[5.4cqw] font-bold text-sunny">Step 1 :-</p>
                <p class="mt-[1cqw] text-[5.6cqw] leading-snug">
                    To participate, purchase a M&amp;M'S<sup class="text-[0.6em]">®</sup> Limited Edition Christmas Advent Calendar Box
                </p>

                <img src="{{ LuckyDrawImage::url('snow-globe') }}" alt="" width="530" height="622" class="mx-auto my-[4cqw] h-auto w-[34cqw] drop-shadow-[0_2cqw_3cqw_rgba(0,0,0,0.35)]">

                <p class="font-title text-[5.4cqw] font-bold text-sunny">Step 2 :-</p>
                <p class="mt-[1cqw] text-[5.6cqw] leading-snug">
                    Locate the QR code inside your box, scan it, and enter your details on this site to redeem your prize instantly.
                </p>
            </div>
        </div>
    </div>
</dialog>

<script>
    (() => {
        const dialog = document.getElementById('how-to-join');

        document.querySelectorAll('[data-how-to-join]').forEach((link) => link.addEventListener('click', (event) => {
            if (event.metaKey || event.ctrlKey || event.shiftKey) return;
            event.preventDefault();
            dialog.showModal();
        }));

        // Clicking the faded page behind the pop-up closes it.
        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) dialog.close();
        });

        @if ($open)
            dialog.showModal();
        @endif
    })();
</script>
