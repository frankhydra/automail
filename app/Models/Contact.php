<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'first_name',
        'last_name',
        'email',
        'status',
    ];

    /**
     * Get the organization that owns the contact.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * The contact lists that this contact belongs to.
     */
    public function lists(): BelongsToMany
    {
        return $this->belongsToMany(ContactList::class, 'contact_list_members')
            ->withTimestamps();
    }
}