<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'message',
        'ip',
        'salesdrive_status',
        'salesdrive_order_id',
        'dilovod_status',
        'dilovod_person_id',
        'last_error',
    ];

    public function fullName(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }
}
