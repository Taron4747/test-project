<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ImportedRow extends Model
{
    use  HasFactory;

    protected $table = 'imported_rows';
    protected $fillable = ['id','name', 'date'];

}
