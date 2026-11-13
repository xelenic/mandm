<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Total users', User::query()->count())
                ->icon(Heroicon::OutlinedUsers),
            Stat::make('Administrators', User::query()->where('is_admin', true)->count())
                ->icon(Heroicon::OutlinedShieldCheck),
            Stat::make('New in last 7 days', User::query()->where('created_at', '>=', now()->subDays(7))->count())
                ->icon(Heroicon::OutlinedUserPlus),
        ];
    }
}
