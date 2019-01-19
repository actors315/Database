<?php
namespace Twinkle\Database;

/**
 * Statement
 *
 * @author Sasa
 * 
 * @lastmodifed Python Luo <laifaluo@126.com>
 */
class Statement extends \PDOStatement
{

    public $delimiter = ".";

    /**
     * Instance of DB class
     *
     * @var DB
     */
    protected $db;

    /**
     * Last fetched row
     *
     * @var array
     */
    public $last_row;

    /**
     * @param DB $db
     */
    protected function __construct(DB $db)
    {
        $this->db = $db;
    }


    /**
     * Fetch data into object's properties.
     * If $from_table is defined, only data from that table will be assigned
     *
     * Note: After value is assigned to property, it will be unset from last_row
     *
     * @param object $object
     * @param string $from_table If isn't used, method will return all data in one object
     * @param int $fetch_from Fetch data from next or last fetched row. DB::FETCH_FROM_NEXT_ROW or DB::FETCH_FROM_LAST_ROW
     * @return object|NULL
     */
    function fetchInto()
    {
        return $this->fetch(DB::FETCH_ASSOC);
        
    }

    /**
     * Fetch data into object from last fetched row.
     * This is shortcut for fetchInto($object, $from_table, DB::FETCH_FROM_LAST_ROW);
     *
     * @param object $object
     * @param string $from_table
     * @return object|NULL
     */
    function fetchIntoFromLastRow($object, $from_table)
    {
        return $this->fetchInto($object, $from_table, DB::FETCH_FROM_LAST_ROW);
    }

    /**
     * Fetch collection of objects (do the some thing as fetchAll)
     *
     * @param string|object $class_name
     * @return array
     */
    function fetchCollection()
    {
        return $this->fetchAll(DB::FETCH_ASSOC);
    }

    /**
     * Get value from column, from last row
     *
     * @param string $column_name
     * @return mixed|NULL
     */
    function getColumnValue($column_name)
    {
        return isset($this->last_row[$column_name]) ? $this->last_row[$column_name] : null;
    }

    function closeCursor()
    {
        $this->last_row = null;
        return parent::closeCursor();
    }

}
