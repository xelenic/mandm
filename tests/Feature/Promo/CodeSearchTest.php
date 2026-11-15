<?php

use App\Models\QrCode;

beforeEach(function () {
    $this->withoutVite();
    $this->qrCode = QrCode::factory()->create(['code' => 'QNTU8RJF7YCH']);
});

it('shows the code search on the home page', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee("Can't scan the QR code?", false)
        ->assertSee('Find my gift')
        ->assertSee('action="'.route('redeem.search').'"', false);
});

it('takes people to their gift, however they type the code', function (string $typed) {
    $this->from('/')
        ->post('/find', ['code' => $typed])
        ->assertRedirect('/r/QNTU8RJF7YCH');
})->with([
    'as printed' => ['MM-QNTU8RJF7YCH'],
    'without the prefix' => ['QNTU8RJF7YCH'],
    'lower case' => ['mm-qntu8rjf7ych'],
    'prefix without dash' => ['MMQNTU8RJF7YCH'],
    'with spaces' => [' MM - QNTU 8RJF 7YCH '],
]);

it('finds a code that itself starts with MM', function () {
    QrCode::factory()->create(['code' => 'MMAB23CD45EF']);

    $this->post('/find', ['code' => 'MM-MMAB23CD45EF'])->assertRedirect('/r/MMAB23CD45EF');
    $this->post('/find', ['code' => 'MMAB23CD45EF'])->assertRedirect('/r/MMAB23CD45EF');
});

it('explains when a code is not found, keeping what was typed', function () {
    $this->from('/')
        ->post('/find', ['code' => 'MM-WRONGCODE99'])
        ->assertRedirect('/')
        ->assertSessionHasErrors(['code' => "We couldn't find this code. Please check it against your card and try again."])
        ->assertSessionHasInput('code', 'MM-WRONGCODE99');
});

it('asks for a code when the box is empty', function (?string $typed) {
    $this->from('/')
        ->post('/find', ['code' => $typed])
        ->assertSessionHasErrors(['code' => 'Please enter the code printed on your card.']);
})->with([null, '']);

it('does not count a failed search as a scan', function () {
    $this->post('/find', ['code' => 'MM-WRONGCODE99']);

    expect($this->qrCode->fresh()->scan_count)->toBe(0);
});

it('stops anyone trying codes in bulk', function () {
    foreach (range(1, 10) as $attempt) {
        $this->post('/find', ['code' => "WRONG{$attempt}"]);
    }

    $this->post('/find', ['code' => 'MM-QNTU8RJF7YCH'])->assertTooManyRequests();
});

it('links to the code search from the "code not found" page', function () {
    $this->get('/r/NOTAREALCODE')
        ->assertNotFound()
        ->assertSee('Type the code from your card')
        ->assertSee('href="'.route('home').'"', false);
});
