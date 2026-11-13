<?php

namespace App\Filament\Resources\Claims\Pages;

use App\Filament\Resources\Claims\ClaimResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditClaim extends EditRecord
{
    protected static string $resource = ClaimResource::class;

    /**
     * Status and notes aren't mass assignable on Claim (the public form must
     * never set them), so set the admin form's validated fields directly.
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->forceFill($data)->save();

        return $record;
    }
}
