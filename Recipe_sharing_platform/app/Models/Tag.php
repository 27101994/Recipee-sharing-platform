<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'tag_name', 'recipe_id',
    ];

    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }
}
