<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['project_id', 'name', 'payload', 'last_result', 'last_run_at'])]
class DiagnosticScenario extends Model
{
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'last_result' => 'array',
            'last_run_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
