<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContactMessage extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['access_token' => 'encrypted'];
    protected $hidden = ['access_token', 'access_token_hash', 'submission_token'];

    public function replies(): HasMany
    {
        return $this->hasMany(ContactReply::class)->orderBy('id');
    }

    public function privateUrl(): string
    {
        return route('messages.show', ['reference' => $this->reference, 'token' => $this->access_token]);
    }
}
