<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Programa extends Model
{
    protected $table = 'programa';
    protected $primaryKey = 'cod';
    public $incrementing = false;
    public $timestamps = false;

    protected $guarded = [];
}
