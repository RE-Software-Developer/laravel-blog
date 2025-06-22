<?php

namespace BinshopsBlog\Laravel\Fulltext;

use Illuminate\Database\Eloquent\Model;

class IndexedRecord extends Model
{
    protected $table = 'laravel_fulltext';

    public function __construct(array $attributes = [])
    {
        $this->connection = config('binshopsblog.search.db_connection');

        parent::__construct($attributes);
    }

    /**
     * Use a specified database connection, if one is configured
     *
     * @return string
     */
    public function getConnectionName()
    {
        return config('binshopsblog.db_connection', 'mysql');
    }

    public function indexable()
    {
        return $this->morphTo();
    }

    public function updateIndex()
    {
        $this->setAttribute('indexed_title', $this->indexable->getIndexTitle());
        $this->setAttribute('indexed_content', $this->indexable->getIndexContent());
        $this->save();
    }
}
