<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HeadCategory extends Model
{
    use HasFactory;

    protected $table = 'head_category'; // Define table name if different from Laravel convention

    protected $fillable = [
        'name', // Add other columns if needed
    ];

    /**
     * Relationship: A head category has many categories.
     */
    public function categories()
    {
        return $this->hasMany(Category::class, 'head_category_id');
    }
}
