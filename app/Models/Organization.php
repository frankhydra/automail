<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

    /**
     * The users that belong to the organization.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Get all contacts belonging to this organization.
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    /**
     * Get all contact lists belonging to this organization.
     */
    public function contactLists(): HasMany
    {
        return $this->hasMany(ContactList::class);
    }

    /**
     * Get all sending identities belonging to this organization.
     */
    public function sendingIdentities(): HasMany
    {
        return $this->hasMany(SendingIdentity::class);
    }

    /**
     * Get all email templates belonging to this organization.
     */
    public function templates(): HasMany
    {
        return $this->hasMany(Template::class);
    }

    /**
     * Get all email campaigns created within this organization.
     */
    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }
}