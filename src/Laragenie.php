<?php

namespace JoshEmbling\Laragenie;

class Laragenie
{
    protected $fillable = [
        'question', 'answer', 'cost', 'question_embedding',
    ];

    protected $casts = [
        'cost' => 'double',
    ];
}
