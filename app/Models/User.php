<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Namu\WireChat\Traits\Chatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, Chatable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'nip',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Get the psychologist associated with the user.
     */
    public function psychologist()
    {
        return $this->hasOne(Psychologist::class);
    }

    /**
     * Determine if the user can create new groups.
     */
    public function canCreateGroups(): bool
    {
        return $this->can('chat-all');
    }

    /**
     * Determine if the user can create new chats with other users.
     */
    public function canCreateChats(): bool
    {
        return $this->can('chat-all') || $this->can('chat-specific');
    }

    /**
     * Get the display name for the user.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name;
    }

    /**
     * Get the cover URL for the user.
     */
    public function getCoverUrlAttribute(): ?string
    {
        if ($this->psychologist && $this->psychologist->image) {
            return asset('storage/' . $this->psychologist->image);
        }
        
        return null;
    }

    /**
     * Get the profile URL for the user.
     */
    public function getProfileUrlAttribute(): ?string
    {
        return null;
    }

    /**
     * Check if the user belongs to a conversation.
     * Override to implement permission-based access.
     */
    public function belongsToConversation(\Namu\WireChat\Models\Conversation $conversation, bool $withoutGlobalScopes = false): bool
    {
        // If user has chat-all permission, they can access all conversations
        if ($this->can('chat-all')) {
            return true;
        }
        
        // For users without special permissions, check if they're part of the conversation
        // and if it's with a psychologist who has online_chat=true
        $isParticipant = parent::belongsToConversation($conversation, $withoutGlobalScopes);
        
        if ($isParticipant) {
            return true;
        }
        
        // Check if the conversation involves a psychologist with online_chat=true
        return $conversation->participants()
            ->whereHas('participantable', function ($query) {
                $query->whereHas('psychologist', function ($q) {
                    $q->where('online_chat', true);
                });
            })
            ->exists();
    }
}