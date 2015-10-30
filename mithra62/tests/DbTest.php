<?php
/**
 * mithra62 - Unit Test
 *
 * @copyright	Copyright (c) 2015, mithra62, Eric Lamb.
 * @link		http://mithra62.com/
 * @version		1.0
 * @filesource 	./mithra62/tests/DbTest.php
 */
 
namespace mithra62\tests;

use mithra62\tests\TestFixture;
use mithra62\Db;

/**
 * mithra62 - Db object Unit Tests
 *
 * Contains all the unit tests for the \mithra62\Db object
 *
 * @package 	mithra62\Tests
 * @author		Eric Lamb <eric@mithra62.com>
 */
class DbTest extends TestFixture
{
    protected $test_table_name = 'm62_test_table';
    
    public function testInit()
    {
        $db = new Db;
        $this->assertObjectHasAttribute('db', $db);
        $this->assertObjectHasAttribute('credentials', $db);
        $this->assertObjectHasAttribute('columns', $db);
        $this->assertObjectHasAttribute('where', $db);
    }
    
    public function testTable()
    {
        $db = new Db;
        $this->assertFalse($db->getTable());
        $this->assertInstanceOf('mithra62\Db', $db->from($this->test_table_name));
        $this->assertEquals($this->test_table_name, $db->getTable());
    }
    
    public function testWhere()
    {
        $db = new Db;
        $this->assertEquals('1=1', $db->getWhere());
        $this->assertInstanceOf('mithra62\Db', $db->where(array('id' => 1)));
        $this->assertEquals(array('id' => 1), $db->getWhere());
    }
    
    public function testCredentials()
    {
        $db = new Db;
        $this->assertEquals(array(), $db->getCredentials());
        $this->assertInstanceOf('mithra62\Db', $db->setCredentials( $this->getDbCreds() ) );
        $this->assertEquals($this->getDbCreds(), $db->getCredentials());
    }
    
    public function testDbObject()
    {
        $db = new Db;
        $db->setCredentials( $this->getDbCreds() );
        $this->assertInstanceOf('voku\db\DB', $db->getDb());
    }
    
    public function testGetCreateTable()
    {
        $db = new Db;
        //$sql = $this->getSettingsTableCreateSql();
        //$db->query($sql);
        $string = $db->setCredentials( $this->getDbCreds() )->getCreateTable($this->test_table_name);
        $this->assertEquals(substr($string, 0, 29), 'CREATE TABLE `'.$this->test_table_name.'`');
        
        $string = $db->getCreateTable($this->test_table_name, true);
        $this->assertEquals(substr($string, 0, 26), 'CREATE TABLE IF NOT EXISTS');
    }
    
    public function testGetTableStatus()
    {
        $db = new Db;
        $table_status = $db->setCredentials( $this->getDbCreds() )->getTableStatus($this->test_table_name);
       
        $this->assertTrue(is_array($table_status));
        $this->assertCount(1, $table_status);
        foreach($table_status AS $table)
        {
            $this->assertArrayHasKey('Name', $table);
            $this->assertArrayHasKey('Engine', $table);
            $this->assertArrayHasKey('Version', $table);
            $this->assertArrayHasKey('Rows', $table);
            $this->assertArrayHasKey('Row_format', $table);
            $this->assertArrayHasKey('Avg_row_length', $table);
            $this->assertArrayHasKey('Max_data_length', $table);
            $this->assertArrayHasKey('Index_length', $table);
            break;
        }
    }
    
    public function testGetTables()
    {
        $db = new Db;
        $tables = $db->setCredentials( $this->getDbCreds() )->getTables();
        $this->assertTrue(is_array($tables));
        $this->assertCount(1, $tables);
        $this->assertArrayHasKey($this->test_table_name, $tables);
    }
    
    public function testSetDbName()
    {
        $db = new Db;
        $creds = $this->getDbCreds();
        $db->setCredentials( $creds )->setDbName($creds['database_switch']);
        $tables = $db->setCredentials( $this->getDbCreds() )->getTables();
        $this->assertTrue(is_array($tables));
        $this->assertCount(0, $tables);
    }
    
    public function testEscape()
    {
        $db = new Db;
        $string = $db->escape("My String isn't really here. \"I hope this works!\"");
        $this->assertEquals($string, "My String isn\'t really here. \\\"I hope this works!\\\"");
    }
}