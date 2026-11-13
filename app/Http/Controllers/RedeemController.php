<?php

namespace App\Http\Controllers;

use App\Actions\ClaimGift;
use App\Exceptions\GiftAlreadyClaimed;
use App\Http\Requests\StoreClaimRequest;
use App\Models\QrCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * The pages people land on after scanning the QR code in their package.
 */
class RedeemController extends Controller
{
    /**
     * Gift packages: each has its own unique QR code.
     */
    public function show(Request $request, string $code): View|Response
    {
        $qrCode = $this->find($code);

        if ($qrCode === null) {
            return response()->view('redeem.invalid', status: 404);
        }

        $claim = $qrCode->claim;

        // This browser just claimed the gift: keep showing the reference.
        if ($claim !== null && $request->session()->get($this->sessionKey($qrCode)) === $claim->reference) {
            return view('redeem.claimed', ['claim' => $claim]);
        }

        $qrCode->recordScan();

        if ($claim !== null) {
            // Never show the winner's personal details to someone else.
            return view('redeem.already-claimed', ['claimedAt' => $claim->created_at]);
        }

        return view('redeem.claim', ['qrCode' => $qrCode]);
    }

    /**
     * Packages without a gift: all share this one QR code.
     */
    public function tryAgain(): View
    {
        return view('redeem.try-again');
    }

    public function store(StoreClaimRequest $request, ClaimGift $claimGift, string $code): RedirectResponse
    {
        $qrCode = $this->find($code);

        abort_if($qrCode === null, 404);

        try {
            $claim = $claimGift->handle(
                $qrCode,
                $request->claimDetails(),
                $request->ip(),
                $request->userAgent(),
            );
        } catch (GiftAlreadyClaimed) {
            return redirect()->route('redeem.show', $qrCode->code);
        }

        $request->session()->put($this->sessionKey($qrCode), $claim->reference);

        return redirect()->route('redeem.show', $qrCode->code);
    }

    private function find(string $code): ?QrCode
    {
        return QrCode::query()->where('code', strtoupper($code))->first();
    }

    private function sessionKey(QrCode $qrCode): string
    {
        return "claimed_gifts.{$qrCode->getKey()}";
    }
}
