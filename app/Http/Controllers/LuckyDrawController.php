<?php

namespace App\Http\Controllers;

use App\Actions\ClaimGift;
use App\Exceptions\GiftAlreadyClaimed;
use App\Http\Requests\ClaimDetailsRequest;
use App\Models\QrCode;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The lucky draw screens people see after scanning the QR card in their pack
 * (design: public/m&m.pdf).
 *
 *   1 menu → 2 enter lucky draw → 3/4 congratulations → 5 details → 6 review → 8 received
 *                               ↘ 7 thanks for joining in (cards without a gift)
 *
 * Gift cards: /r/{code}/…  ·  Try-again card: /try-again/…  ·  No card: / (type the code)
 */
class LuckyDrawController extends Controller
{
    private const MENU = 'lucky_draw.menu';

    // ---- Home (no card scanned) ---------------------------------------------

    public function home(Request $request): View
    {
        return $this->menu($request, route('lucky.enter-code'));
    }

    /** Screen 2 without a code: people type the code printed on their card. */
    public function enterCode(): View
    {
        return view('lucky-draw.draw', ['resultUrl' => null]);
    }

    public function search(Request $request): RedirectResponse
    {
        $request->validate(
            ['code' => ['required', 'string', 'max:40']],
            ['code.required' => 'Please enter the code printed on your card.'],
        );

        if (QrCode::isTypedTryAgainCode($request->string('code'))) {
            $request->session()->put(self::MENU, route('redeem.try-again'));

            return redirect()->route('lucky.try-again.result');
        }

        $qrCode = QrCode::findTyped($request->string('code'));

        if ($qrCode === null) {
            return back()
                ->withInput()
                ->withErrors(['code' => "We couldn't find this code. Please check it against your card and try again."]);
        }

        $qrCode->recordScan();
        $request->session()->put(self::MENU, route('redeem.show', $qrCode->code));

        return redirect()->route('lucky.result', $qrCode->code);
    }

    // ---- Try-again card (every pack without a gift) -------------------------

    public function tryAgainMenu(Request $request): View
    {
        return $this->menu($request, route('lucky.try-again.draw'));
    }

    public function tryAgainDraw(): View
    {
        return view('lucky-draw.draw', ['resultUrl' => route('lucky.try-again.result')]);
    }

    /** Screen 7. */
    public function tryAgainResult(): View
    {
        return view('lucky-draw.not-won', ['menuUrl' => route('redeem.try-again')]);
    }

    // ---- Gift cards (each has its own code) ---------------------------------

    /** Screen 1 for a gift card: where its QR code points. */
    public function giftMenu(Request $request, string $code): View
    {
        $qrCode = $this->qrCode($code);

        // Reopening the menu for the How to Join pop-up is not a new scan.
        if (! $this->claimedHere($request, $qrCode) && ! $request->boolean('how-to-join')) {
            $qrCode->recordScan();
        }

        return $this->menu($request, route('lucky.draw', $qrCode->code));
    }

    /** Screen 2. */
    public function giftDraw(string $code): View
    {
        return view('lucky-draw.draw', ['resultUrl' => route('lucky.result', $this->qrCode($code)->code)]);
    }

    /** Screens 3 and 4, or the outcome if this gift was already claimed. */
    public function giftResult(Request $request, string $code): View|RedirectResponse
    {
        $qrCode = $this->qrCode($code);

        if ($this->claimedHere($request, $qrCode)) {
            return redirect()->route('lucky.received', $qrCode->code);
        }

        if ($qrCode->claim !== null) {
            return $this->alreadyClaimed($qrCode);
        }

        return view('lucky-draw.prize', ['gift' => $qrCode->gift, 'code' => $qrCode->code]);
    }

    /** Screen 5. */
    public function details(Request $request, string $code): View|RedirectResponse
    {
        $qrCode = $this->qrCode($code);

        if ($qrCode->claim !== null) {
            return redirect()->route('lucky.result', $qrCode->code);
        }

        return view('lucky-draw.details', [
            'code' => $qrCode->code,
            'draft' => $this->draft($request, $qrCode),
        ]);
    }

    public function saveDetails(ClaimDetailsRequest $request, string $code): RedirectResponse
    {
        $qrCode = $this->qrCode($code);

        if ($qrCode->claim !== null) {
            return redirect()->route('lucky.result', $qrCode->code);
        }

        $request->session()->put($this->draftKey($qrCode), $request->details());

        return redirect()->route('lucky.review', $qrCode->code);
    }

    /** Screen 6. */
    public function review(Request $request, string $code): View|RedirectResponse
    {
        $qrCode = $this->qrCode($code);
        $draft = $this->draft($request, $qrCode);

        if ($qrCode->claim !== null || $draft === null) {
            return redirect()->route($qrCode->claim !== null ? 'lucky.result' : 'lucky.details', $qrCode->code);
        }

        return view('lucky-draw.review', [
            'code' => $qrCode->code,
            'draft' => $draft,
            'phone' => ClaimDetailsRequest::localPhone($draft['phone']),
        ]);
    }

    /** "Confirm & Submit" on screen 6. */
    public function store(Request $request, ClaimGift $claimGift, string $code): RedirectResponse
    {
        $qrCode = $this->qrCode($code);
        $draft = $this->draft($request, $qrCode);

        if ($draft === null) {
            return redirect()->route('lucky.details', $qrCode->code);
        }

        try {
            $claim = $claimGift->handle(
                $qrCode,
                [
                    'name' => $draft['first_name'].' '.$draft['last_name'],
                    'phone' => $draft['phone'],
                    'email' => null,
                    'nic' => null,
                ],
                $request->ip(),
                $request->userAgent(),
            );
        } catch (GiftAlreadyClaimed) {
            return redirect()->route('lucky.result', $qrCode->code);
        }

        $request->session()->forget($this->draftKey($qrCode));
        $request->session()->put($this->claimKey($qrCode), $claim->reference);

        return redirect()->route('lucky.received', $qrCode->code);
    }

    /** Screen 8. */
    public function received(Request $request, string $code): View|RedirectResponse
    {
        $qrCode = $this->qrCode($code);

        if (! $this->claimedHere($request, $qrCode)) {
            return redirect()->route('lucky.result', $qrCode->code);
        }

        return view('lucky-draw.received', ['menuUrl' => route('redeem.show', $qrCode->code)]);
    }

    // ---- Menu information pages ---------------------------------------------

    /**
     * "Campaign Details" is the How to Join pop-up on the menu. The menu opens
     * it in place; this address (for links and browsers without JavaScript)
     * shows the menu the person came from with the pop-up already open.
     */
    public function campaign(Request $request): RedirectResponse
    {
        $menu = $request->session()->get(self::MENU, route('home'));

        return redirect()->to($menu.'?how-to-join=1');
    }

    public function terms(Request $request): View
    {
        return view('lucky-draw.terms', [
            'menuUrl' => $request->session()->get(self::MENU, route('home')),
        ]);
    }

    public function winners(Request $request): View
    {
        return $this->message($request, 'Winners Gallery', [
            'Our winners will be celebrated here after the campaign. Check back soon!',
        ]);
    }

    // ---- Helpers --------------------------------------------------------------

    /** Screen 1; remembers it so "Back to Menu" on later screens returns here. */
    private function menu(Request $request, string $claimUrl): View
    {
        $request->session()->put(self::MENU, $request->url());

        return view('lucky-draw.menu', ['claimUrl' => $claimUrl]);
    }

    /**
     * @param  list<string>  $paragraphs
     */
    private function message(Request $request, string $title, array $paragraphs): View
    {
        return view('lucky-draw.message', [
            'title' => $title,
            'paragraphs' => $paragraphs,
            'menuUrl' => $request->session()->get(self::MENU, route('home')),
        ]);
    }

    private function alreadyClaimed(QrCode $qrCode): View
    {
        // Never show the winner's details to anyone else.
        return view('lucky-draw.message', [
            'title' => 'Already Claimed',
            'paragraphs' => [
                'The prize on this card was claimed on '.$qrCode->claim->created_at->format('j F Y').'.',
                'Each card can only be used once. If you claimed it yourself, our team will contact you.',
            ],
            'menuUrl' => route('redeem.show', $qrCode->code),
        ]);
    }

    /** The gift card for this code, or the "code not found" screen. */
    private function qrCode(string $code): QrCode
    {
        $qrCode = QrCode::query()->where('code', strtoupper($code))->first();

        if ($qrCode === null) {
            throw new HttpResponseException(response()->view('lucky-draw.message', [
                'title' => "We couldn't find this code",
                'paragraphs' => [
                    'Please scan the QR card inside your Christmas pack again.',
                    'If it still doesn\'t work, tap "Claim Your Prize" on the menu and type the code printed on your card.',
                ],
                'menuUrl' => route('home'),
            ], 404));
        }

        return $qrCode;
    }

    private function claimedHere(Request $request, QrCode $qrCode): bool
    {
        $claim = $qrCode->claim;

        return $claim !== null && $request->session()->get($this->claimKey($qrCode)) === $claim->reference;
    }

    /**
     * @return array{first_name: string, last_name: string, phone: string}|null
     */
    private function draft(Request $request, QrCode $qrCode): ?array
    {
        return $request->session()->get($this->draftKey($qrCode));
    }

    private function draftKey(QrCode $qrCode): string
    {
        return "lucky_draw.details.{$qrCode->getKey()}";
    }

    private function claimKey(QrCode $qrCode): string
    {
        return "claimed_gifts.{$qrCode->getKey()}";
    }
}
