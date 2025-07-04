<?php


namespace BinshopsBlog\Models;

use Illuminate\Database\Eloquent\Model;


/**
 *
 */
class BinshopsConfiguration extends Model
{
    protected $primaryKey = 'key';

    public $fillable = [
        'key',
        'value'
    ];

    /**
     * Use a specified database connection, if one is configured
     *
     * @return string
     */
    public function getConnectionName()
    {
        return config('binshopsblog.db_connection', 'mysql');
    }

    public static function get($key){
        $obj = BinshopsConfiguration::where('key', $key)->first();
        if ($obj){
            return $obj->value;
        }
        else{
            return null;
        }
    }

    public static function set($key, $value){
        $config = new BinshopsConfiguration();
        $config->key = $key;
        $config->value = $value;
        $config->save();
    }
}
