<?php declare(strict_types=1);

namespace Limepie\Model;

class Properties
{
    protected $table_name;

    protected $primary_key_name = 'seq';

    protected $timestamp_fields = [];

    public $fields = [];

    public function getTableName() : string
    {
        return $this->table_name;
    }

    public function getPrimaryKeyName() : string
    {
        return $this->primary_key_name;
    }

    public function getTimestampFields() : array
    {
        return $this->timestamp_fields;
    }

    public function getFields() : array
    {
        return $this->fields;
    }
}
