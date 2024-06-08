<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    use HasFactory;

    protected $fillable = [
        'idusuario1',
        'idusuario2',
    ];

    public function user1()
    {
        return $this->belongsTo(User::class, 'idusuario1');
    }

    public function user2()
    {
        return $this->belongsTo(User::class, 'idusuario2');
    }

    public function messages()
    {
        return $this->hasMany(Message::class, 'IDChat');
    }
}
