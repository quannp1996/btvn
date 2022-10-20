<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected $table = 'purchase';
    public $timestamps = false;
    protected $primaryKey = 'pur_id';
}
