<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccessToken extends Model
{
    use HasFactory;
    protected $fillable = ["project_id", "name", "token", "active"];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
