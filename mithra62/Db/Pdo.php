<?php
/**
 * mithra62
 *
 * @copyright	Copyright (c) 2015, mithra62, Eric Lamb.
 * @link		http://mithra62.com/
 * @version		1.0
 * @filesource 	./mithra62/Db/Pdo.php
 */
 
namespace mithra62\Db;

use Aura\Sql\ExtendedPdo;
use Aura\SqlQuery\QueryFactory;

/**
 * mithra62 - PDO Database Object
 *
 * Wrapper for a simple PDO abstraction
 *
 * @package Database
 * @author Eric Lamb <eric@mithra62.com>
 */
class Pdo extends ExtendedPdo implements DbInterface
{
    /**
     * The SQL string to execute
     * @var string
     */
    protected $sql = null; 
    
    /**
     * (non-PHPdoc)
     * @see \mithra62\Db\DbInterface::select()
     */
    public function select($table, $where)
    {
        $this->table = $table;
        $this->where = $where;
        return $this;
    }
    
    /**
     * (non-PHPdoc)
     * @see \mithra62\Db\DbInterface::insert()
     */
    public function insert($table, array $data = array())
    {
        $query_factory = new QueryFactory('mysql');
        $insert = $query_factory->newInsert();
        $insert->into($table);
        $cols = $bind = array();
        foreach($data AS $key => $value)
        {
            $cols[] = $key;
            $bind[$key] = $value;
        }
        
        $insert->cols($cols)->bindValues($bind);
        $sth = $this->prepare($insert->getStatement());
        $sth->execute($insert->getBindValues());
        
        $name = $insert->getLastInsertIdName('id');
        return $this->lastInsertId($name);
    }
    
    /**
     * (non-PHPdoc)
     * @see \mithra62\Db\DbInterface::update()
     */
    public function update($table, $data, $where)
    {
        $query_factory = new QueryFactory('mysql');
        $update = $query_factory->newUpdate()->table($table);
        foreach($data AS $key => $value)
        {
            $cols[] = $key;
            $bind[$key] = $value;
        }
        
        if (is_string($where)) {
            $where = $this->escape($where, false, false);
        } elseif (is_array($where)) {
            $where = $this->parseArrayPair($where, 'AND');
        } else {
            $where = '';
        }        
        
        $update->cols($cols)->where($where)->bindValues($bind);
        $sth = $this->prepare($update->getStatement());
        return $sth->execute($update->getBindValues());
    }
    
    
    public function query($sql = '', $params = false)
    {
        return $this->fetchAll($sql);
    }
    
    public function escape($string)
    {
        return $this->quote($string);
    }
    
    public function getAllTables()
    {
        $sql = 'SHOW TABLES';
        return $this->fetchAll($sql);
    }
    
    public function getTableStatus()
    {
        $sql = 'SHOW TABLE STATUS';
        return $this->fetchAll($sql);  
    }
    
    public function getCreateTable($table, $if_not_exists = false)
    {
        $sql = sprintf('SHOW CREATE TABLE `%s` ;', $table);
        $statement = $this->query($sql, true);
        $string = false;
        if (! empty($statement['0']['Create Table'])) {
            $string = $statement['0']['Create Table'];
        }
        
        if ($if_not_exists) {
            $replace = substr($string, 0, 12);
            if ($replace == 'CREATE TABLE') {
                $string = str_replace('CREATE TABLE', 'CREATE TABLE IF NOT EXISTS ', $string);
            }
        }
        
        return $string;
    }
    
    public function clear()
    {
        
    }
    
    public function totalRows($table)
    {
        $sql = sprintf('SELECT COUNT(*) AS count FROM `%s`', $table);
        $statement = $this->query($sql, true);
        if ($statement) {
            if (isset($statement['0']['count'])) {
                return $statement['0']['count'];
            }
        }
        
        return '0';
    }
    
    public function getColumns($table)
    {
        $sql = sprintf('SHOW COLUMNS FROM `%s`', $table);
        $statement = $this->query($sql, true);
        if ($statement) {
            return $statement;
        }
        return array();
    }
    
    /**
     * (non-PHPdoc)
     * @see \mithra62\Db\DbInterface::get()
     */
    public function get()
    {
        $query_factory = new QueryFactory('mysql');
        $select = $query_factory->newSelect();
        $select->cols(array('*'))->from($this->table);
        
        if (is_string($this->where)) {
            $where = $this->escape($this->where, false, false);
        } elseif (is_array($this->where)) {
            $where = $this->parseArrayPair($this->where, 'AND');
        } else {
            $where = '';
        }
        
        $select->where($where);
        
        $sql = $select->getStatement();
        $return = $this->fetchAll($sql);
        if($return)
        {
            return $return;
        }
        return array();    
    }
    
    /**
     * Takes the WHERE array clause and prepairs it for use
     * @param array $arrayPair
     * @param string $glue
     */
    protected function parseArrayPair($arrayPair, $glue = ',')
    {
        // init
        $sql = '';
        $pairs = array();
    
        if (!empty($arrayPair)) {
    
            foreach ($arrayPair as $_key => $_value) {
                $_connector = '=';
                $_key_upper = strtoupper($_key);
    
                if (strpos($_key_upper, ' NOT') !== false) {
                    $_connector = 'NOT';
                }
    
                if (strpos($_key_upper, ' IS') !== false) {
                    $_connector = 'IS';
                }
    
                if (strpos($_key_upper, ' IS NOT') !== false) {
                    $_connector = 'IS NOT';
                }
    
                if (strpos($_key_upper, ' IN') !== false) {
                    $_connector = 'IN';
                }
    
                if (strpos($_key_upper, ' NOT IN') !== false) {
                    $_connector = 'NOT IN';
                }
    
                if (strpos($_key_upper, ' BETWEEN') !== false) {
                    $_connector = 'BETWEEN';
                }
    
                if (strpos($_key_upper, ' NOT BETWEEN') !== false) {
                    $_connector = 'NOT BETWEEN';
                }
    
                if (strpos($_key_upper, ' LIKE') !== false) {
                    $_connector = 'LIKE';
                }
    
                if (strpos($_key_upper, ' NOT LIKE') !== false) {
                    $_connector = 'NOT LIKE';
                }
    
                if (strpos($_key_upper, ' >') !== false && strpos($_key_upper, ' =') === false) {
                    $_connector = '>';
                }
    
                if (strpos($_key_upper, ' <') !== false && strpos($_key_upper, ' =') === false) {
                    $_connector = '<';
                }
    
                if (strpos($_key_upper, ' >=') !== false) {
                    $_connector = '>=';
                }
    
                if (strpos($_key_upper, ' <=') !== false) {
                    $_connector = '<=';
                }
    
                if (strpos($_key_upper, ' <>') !== false) {
                    $_connector = '<>';
                }
    
                if (
                    is_array($_value)
                    &&
                    (
                        $_connector == 'NOT IN'
                        ||
                        $_connector == 'IN'
                    )
                ) {
                    foreach ($_value as $oldKey => $oldValue) {
                        /** @noinspection AlterInForeachInspection */
                        $_value[$oldKey] = $this->escape($oldValue);
                    }
                    $_value = '(' . implode(',', $_value) . ')';
                } elseif (
                    is_array($_value)
                    &&
                    (
                        $_connector == 'NOT BETWEEN'
                        ||
                        $_connector == 'BETWEEN'
                    )
                ) {
                    foreach ($_value as $oldKey => $oldValue) {
                        /** @noinspection AlterInForeachInspection */
                        $_value[$oldKey] = $this->secure($oldValue);
                    }
                    $_value = '(' . implode(' AND ', $_value) . ')';
                } else {
                    $_value = $this->escape($_value);
                }
    
                $quoteString = $_key;//$this->escape(trim(str_ireplace($_connector, '', $_key)));
                $pairs[] = ' ' . $quoteString . ' ' . $_connector . ' ' . $_value . " \n";
            }
    
            $sql = implode($glue, $pairs);
        }
    
        return $sql;
    }    
}