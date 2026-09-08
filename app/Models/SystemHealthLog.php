<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemHealthLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'component',
        'status',
        'message',
        'metrics',
        'logged_at',
    ];

    protected function casts(): array
    {
        return [
            'metrics' => 'array',
            'logged_at' => 'datetime',
        ];
    }
}
