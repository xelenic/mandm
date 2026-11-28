{{-- Campaign Terms & Conditions (text supplied by the client; keep it word for word). --}}
<x-layouts.lucky-draw title="Terms & Conditions">
    <div class="mt-[8cqw] w-[82cqw] animate-rise-in [animation-delay:0.2s]">
        <x-lucky.button :href="$menuUrl" variant="outline" size="xs">
            <span aria-hidden="true">&#8249;</span> Back
        </x-lucky.button>
    </div>

    <x-lucky.panel class="mt-[4cqw] w-[82cqw] animate-pop-in px-[5cqw] pt-[6cqw] pb-[2cqw] text-cocoa [animation-delay:0.3s]">
        <h1 class="font-title text-[4cqw] leading-tight font-bold">M&amp;M’S Limited Edition<br>Christmas Advent Calendar</h1>
        <p class="mt-[1cqw] text-[4.6cqw]">Terms &amp; Conditions</p>

        {{-- Only this text scrolls; the heading and Back stay in view. --}}
        <div class="lucky-scroll mt-[4cqw] w-full" tabindex="0" role="region" aria-label="Terms and Conditions text">
            <div class="space-y-[5cqw] pr-[2.5cqw] pb-[10cqw] text-left text-[3.7cqw] leading-[1.4]">
                <section>
                    <h2 class="font-title text-[3.5cqw] font-bold uppercase">1. Organiser and eligibility</h2>
                    <ol class="mt-[2cqw] space-y-[2.5cqw]">
                        <x-lucky.clause number="1.1">
                            The M&amp;M’S 2026 Christmas Lucky Draw (the “Campaign”) is organised by Mars International India Private Limited (the “Organiser”).
                        </x-lucky.clause>
                        <x-lucky.clause number="1.2">
                            The Campaign is open to residents of Sri Lanka aged fourteen (14) years and above at the time of entry and who possess a valid National Identity Card (NIC), Foreign Identification Number (FIN) or passport. Participants below the age of eighteen (18) are required to obtain written parental/guardian approval to take part in the Contest, and are to provide that to the Organizer upon request.
                        </x-lucky.clause>
                    </ol>
                </section>

                <section>
                    <h2 class="font-title text-[3.5cqw] font-bold uppercase">2. Campaign Period</h2>
                    <ol class="mt-[2cqw] space-y-[2.5cqw]">
                        <x-lucky.clause number="2.1">
                            The Campaign opens on 01 December 2026 and closes on 01 January 2027, Sri Lanka Standard Time (UTC+05:30). Entries must be submitted through the Campaign portal before the closing time. The Organizer shall reserve the right to revise the Campaign Period at any time without prior notice or assigning any reason whatsoever. All submissions received after the Campaign Period will be null and void.
                        </x-lucky.clause>
                    </ol>
                </section>

                <section>
                    <h2 class="font-title text-[3.5cqw] font-bold uppercase">3. How to Enter</h2>
                    <ol class="mt-[2cqw] space-y-[2.5cqw]">
                        <x-lucky.clause number="3.1">
                            During the Campaign Period, purchase an eligible M&amp;M’S Limited Edition Christmas Advent Calendar in Sri Lanka. Scan the QR code on the entry card included in the pack and follow the instructions on the Campaign portal.
                        </x-lucky.clause>
                        <x-lucky.clause number="3.2">
                            Only one entry per card is permitted. Keep the original entry card for verification if you win.
                        </x-lucky.clause>
                    </ol>
                </section>

                <section>
                    <h2 class="font-title text-[3.5cqw] font-bold uppercase">4. Results and Prize Claims</h2>
                    <ol class="mt-[2cqw] space-y-[2.5cqw]">
                        <x-lucky.clause number="4.1">
                            The portal displays your result immediately after you enter. Each valid entry offers a chance to win a gift voucher. If the portal displays “Try Again/ This entry didn't win a voucher.” no prize was awarded for that entry. The same card cannot be used for another entry.
                        </x-lucky.clause>
                        <x-lucky.clause number="4.2">
                            If the portal displays a winning result, it will show the voucher you won before asking for your first name, last name and mobile number. Submit accurate details to claim your voucher. The Organiser may contact you to verify your win and ask you to present the original entry card.
                        </x-lucky.clause>
                        <x-lucky.clause number="4.3">
                            Gift vouchers will be dispatched to verified winners within seven working days of winner confirmation. Voucher values are stated in Sri Lankan rupees (LKR). Redemption is subject to the voucher issuer’s terms.
                        </x-lucky.clause>
                    </ol>
                </section>

                <section>
                    <h2 class="font-title text-[3.5cqw] font-bold uppercase">5. Personal Information and Enquiries</h2>
                    <ol class="mt-[2cqw] space-y-[2.5cqw]">
                        <x-lucky.clause number="5.1">
                            The Organiser uses the contact details submitted by winners to verify claims, contact winners, and arrange voucher delivery. For details on how your information is handled, see
                            <a href="https://www.mars.com/privacy" target="_blank" rel="noopener" class="break-all text-candy underline underline-offset-2">https://www.mars.com/privacy</a>.
                        </x-lucky.clause>
                        <x-lucky.clause number="5.2">
                            For Campaign enquiries, contact
                            <a href="tel:+94706335650" class="whitespace-nowrap text-candy underline underline-offset-2">+94 70 63 35 650</a>.
                        </x-lucky.clause>
                    </ol>
                </section>
            </div>
        </div>
    </x-lucky.panel>
</x-layouts.lucky-draw>
