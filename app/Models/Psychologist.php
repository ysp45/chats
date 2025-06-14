<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Psychologist extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'nip',
        'specialization',
        'description',
        'image',
        'online_chat',
        'user_input',
        'user_update',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'online_chat' => 'boolean',
    ];

    /**
     * Get the user that owns the psychologist profile.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}