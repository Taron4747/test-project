<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportedRow extends Model
{
    protected $table = 'imported_rows';
    protected $fillable = ['id','name', 'date'];

}
