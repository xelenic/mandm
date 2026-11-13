<?php

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Widgets\UserStatsOverview;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

describe('panel access', function () {
    it('redirects guests to the login page', function () {
        $this->get('/admin')->assertRedirect('/admin/login');
    });

    it('shows the login page', function () {
        $this->get('/admin/login')->assertOk();
    });

    it('forbids users who are not admins', function () {
        $this->actingAs(User::factory()->create())
            ->get('/admin')
            ->assertForbidden();
    });

    it('lets admins see the dashboard and users list', function () {
        $this->actingAs(User::factory()->admin()->create());

        $this->get('/admin')->assertOk();
        $this->get('/admin/users')->assertOk();
    });

    it('shows user stats on the dashboard', function () {
        $this->actingAs(User::factory()->admin()->create());
        User::factory()->count(2)->create();

        // Dashboard widgets are lazy-loaded, so test the widget component itself.
        Livewire::test(UserStatsOverview::class)
            ->assertSeeInOrder(['Total users', '3', 'Administrators', '1']);
    });

    it('treats a newly created user as a non-admin', function () {
        $user = User::create([
            'name' => 'Jane',
            'email' => 'jane@example.com',
            'password' => 'secret-password',
        ]);

        expect($user->is_admin)->toBeFalse();
    });

    it('does not allow is_admin to be mass assigned', function () {
        $user = User::create([
            'name' => 'Mallory',
            'email' => 'mallory@example.com',
            'password' => 'secret-password',
            'is_admin' => true,
        ]);

        expect($user->fresh()->is_admin)->toBeFalse();
    });
});

describe('managing users', function () {
    beforeEach(function () {
        $this->admin = User::factory()->admin()->create();
        $this->actingAs($this->admin);
    });

    it('lists users', function () {
        $users = User::factory()->count(3)->create();

        Livewire::test(ListUsers::class)
            ->assertOk()
            ->assertCanSeeTableRecords($users);
    });

    it('creates a user, including admin access', function () {
        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'New Admin',
                'email' => 'new-admin@example.com',
                'password' => 'a-strong-password',
                'is_admin' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::where('email', 'new-admin@example.com')->firstOrFail();

        expect($user->is_admin)->toBeTrue()
            ->and(Hash::check('a-strong-password', $user->password))->toBeTrue();
    });

    it('validates new users', function () {
        $existing = User::factory()->create();

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => '',
                'email' => $existing->email,
                'password' => '',
            ])
            ->call('create')
            ->assertHasFormErrors([
                'name' => 'required',
                'email' => 'unique',
                'password' => 'required',
            ]);
    });

    it('keeps the current password when the field is left blank on edit', function () {
        $user = User::factory()->create(['password' => 'original-password']);

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm(['name' => 'Renamed', 'password' => ''])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();

        expect($user->name)->toBe('Renamed')
            ->and(Hash::check('original-password', $user->password))->toBeTrue();
    });

    it('can change another user\'s admin access', function () {
        $user = User::factory()->create();

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm(['is_admin' => true])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($user->fresh()->is_admin)->toBeTrue();
    });

    it('deletes other users', function () {
        $user = User::factory()->create();

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->callAction('delete');

        expect(User::find($user->id))->toBeNull();
    });
});

describe('self-lockout protection', function () {
    beforeEach(function () {
        $this->admin = User::factory()->admin()->create();
        $this->actingAs($this->admin);
    });

    it('hides the delete button on your own account', function () {
        Livewire::test(EditUser::class, ['record' => $this->admin->getRouteKey()])
            ->assertActionHidden('delete');
    });

    it('does not let you remove your own admin access', function () {
        Livewire::test(EditUser::class, ['record' => $this->admin->getRouteKey()])
            ->assertFormFieldDisabled('is_admin')
            ->fillForm(['is_admin' => false])
            ->call('save');

        expect($this->admin->fresh()->is_admin)->toBeTrue();
    });

    it('skips your own account during bulk delete', function () {
        $other = User::factory()->create();

        Livewire::test(ListUsers::class)
            ->selectTableRecords([$this->admin->getKey(), $other->getKey()])
            ->callAction(TestAction::make('delete')->table()->bulk());

        expect(User::find($this->admin->id))->not->toBeNull()
            ->and(User::find($other->id))->toBeNull();
    });
});

describe('app:create-admin command', function () {
    it('creates an admin who can access the panel', function () {
        $this->artisan('app:create-admin')
            ->expectsQuestion('Name', 'Ops Admin')
            ->expectsQuestion('Email address', 'ops@example.com')
            ->expectsQuestion('Password', 'a-strong-password')
            ->assertSuccessful();

        $user = User::where('email', 'ops@example.com')->firstOrFail();

        expect($user->is_admin)->toBeTrue()
            ->and($user->email_verified_at)->not->toBeNull();

        $this->actingAs($user)->get('/admin')->assertOk();
    });
});
