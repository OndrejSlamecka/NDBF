<?php
/**
 * This file is a part of the NDBF library
 *
 * @copyright (c) 2011 Ondrej Slamecka (http://www.slamecka.cz)
 *
 * License can be found within the file license.txt in the root folder.
 *
 */

namespace NDBF;

/**
 * Repository with basic functionality such as find() function.
 */
class Repository extends \Nette\Object
{
    /* ---------------------------- VARIABLES ------------------------------- */

    /** @var \NDBF\RepositoryManager */
    protected $parent;

    /** @var \Nette\Database\Connection */
    protected $connection;

    /** @var string Associated table name */
    protected $table_name;


    /* ------------------------ CONSTRUCTOR, DESIGN ------------------------- */

    public function __construct(RepositoryManager $parent, \Nette\Database\Connection $connection, $table_name = null)
    {
        $this->parent = $parent;
        $this->connection = $connection;

        // DATABASE TABLE NAME
        if ($table_name === null) {
            $table_name = get_class($this);
            $table_name = substr($table_name, strrpos($table_name, '\\') + 1);
        }
        $this->table_name = strtolower($table_name); // Lowercase convention!
    }

    /**
     * Allows access to other repositories
     * @return \NDBF\RepositoryManager
     */
    final protected function getParent()
    {
        return $this->parent;
    }

    /**
     * @return \Nette\Database\Connection
     */
    final public function getDb()
    {
        return $this->connection;
    }

    /* -------------------- Nette\Database extension ------------------- */

    /**
     * @return \Nette\Database\Table\Selection
     */
    final public function table()
    {
        return $this->db->table($this->table_name);
    }

    /**
     * @return \Nette\Database\Table\Selection
     */
    final public function select($columns)
    {
        return $this->db->table($this->table_name)->select($columns);
    }

    /**
     * Returns all rows as an associative array.
     * @param  string
     * @param  string column name used for an array value or an empty string for the whole row
     * @return array
     */
    public function fetchPairs($key, $val = '')
    {
        return $this->table($this->table_name)->fetchPairs($key, $val);
    }

    /**
     * Counts table's rows.
     * @param array,null $conditions
     * @return int
     */
    public function count($conditions = null)
    {
        $query = $this->table();

        if (count($conditions) > 0)
            foreach ($conditions as $column => $value)
                $query->where($column, $value);

        return $query->count('*');
    }

    /**
     * Removes entity from db. (ID required)
     * @param Entity $entity
     * @throws LogicException, InvalidArgumentException
     */
    public function remove($conditions)
    {
        $this->db->exec('DELETE FROM `' . $this->table_name . '` WHERE ', $conditions);
    }

    /**
     * Saves record
     * @param type $record
     * @param type $table_id
     */
    public function save(&$record, $table_id)
    {
        // If there is no ID, we MUST insert
        if (!isset($record[$table_id])) {
            $insert = true;
        } else {
            // There is an ID
            // Following condition allows restoring deleted items
            if ($this->select($table_id)->where($table_id, $record[$table_id])->fetch()) // Is this entity already stored?
                $insert = false; // Yes it is, so we'll update it
            else
                $insert = true; // No it isn't so insert
        }


        if ($insert) {
            $this->db
                    ->exec('INSERT INTO `' . $this->table_name . '`', $record);

            // Set last inserted item id
            $record[$table_id] = $this->db->lastInsertId();
        }else
            $this->db
                    ->exec('UPDATE `' . $this->table_name . '` SET ? WHERE `' . $table_id . '` = ?', $record, $record[$table_id]);
    }

    /* --- DEPRECATED --- */

    /*
     *
     * @param array $conditions (column=>value)
     * @param string $order
     * @param int $limit
     * @param int $offset
     * @return array, null
     */

    /** @deprecated */
    public function find($conditions = null, $order = null, $limit = null, $offset = null)
    {
        // Start basic command
        $query = $this->db->table($this->table_name);

        // Apply conditions
        if (count($conditions) > 0)
            foreach ($conditions as $column => $value)
                $query->where($column, $value);

        // Apply order
        if (isset($order))
            $query = $query->order($order);

        if (isset($limit)) {
            if ($offset !== null)
                $query = $query->limit($limit, $offset);
            else
                $query = $query->limit($limit);
        }

        return $query;
    }

}
