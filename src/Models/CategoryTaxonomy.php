<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CategoryTaxonomy extends Model
{
    //

    /**
     * Use a specified database connection, if one is configured
     *
     * @return string
     */
    public function getConnectionName()
    {
        return config('binshopsblog.db_connection', 'mysql');
    }
}
