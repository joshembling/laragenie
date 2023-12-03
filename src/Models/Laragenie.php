<?php

namespace JoshEmbling\Laragenie\Models;

use Illuminate\Database\Eloquent\Model;

class Laragenie extends Model
{
    protected $table = 'laragenie_responses';

    protected $fillable = [
        'question', 'answer', 'cost', 'vectors',
    ];

    protected $casts = [
        'cost' => 'double',
        'vectors' => 'array',
    ];
}
