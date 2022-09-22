<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Flashcard extends Model
{
    use HasFactory;

    protected $fillable = [
        'question', 'answer'
    ];

    public function flashcards()
    {
        return $this->belongsToMany(Flashcard::class)->withPivot(['status']);
    }
}
