<?php
/**
 * This file is part of the CSI QFrame.
 *
 * The CSI QFrame is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The CSI QFrame is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category   QFrame
 * @package    QFrame_Test
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */


/**
 * PHPUnit_Framework
 */
require_once 'PHPUnit/Framework.php';


/**
 * @category   QFrame
 * @package    QFrame_Test
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */
class QFrame_Test_Unit extends PHPUnit_Framework_TestCase {
  
  private $_fixtures = array();
  
  function __construct() {
    $defaultFixtureFilename = preg_replace('/^Test_Unit_|Test$/', '', get_class($this)) . '.yml';
    if(file_exists(TEST_PATH . '/fixtures/' . $defaultFixtureFilename))
      $this->_fixtures[] = substr($defaultFixtureFilename, 0, -4);
  }

  public function fixture($fixtures) {
    if(!is_array($fixtures)) $fixtures = array($fixtures);
    foreach($fixtures as $fixture) {
      if(!file_exists(TEST_PATH . '/fixtures/' . $fixture . ".yml"))
        throw new Exception('Non-existent fixture requested.');
      $this->_fixtures[] = $fixture;
    }
  }
  
  public function setUp() {
    if(method_exists($this, 'start')) call_user_func(array($this, 'start'));
    $this->loadFixtures();
  }

  public function tearDown() {    
    if(method_exists($this, 'end')) call_user_func(array($this, 'end'));
    $this->resetDatabase();
  }
  
  private function loadFixtures() {
    $db = Zend_Db_Table_Abstract::getDefaultAdapter();
    foreach($this->_fixtures as $fixture) {
      $fixture = Spyc::YAMLLoad(TEST_PATH . '/fixtures/' . $fixture . '.yml');
      $table = $fixture['table'];
      $data = $fixture['data'];
      foreach($data as $datum) {
        $db->insert($table, $datum);
      }
    }
  }
  
  private function resetDatabase() {
    $db = Zend_Db_Table_Abstract::getDefaultAdapter();
    foreach($db->listTables() as $table) {
      $db->getConnection()->exec("TRUNCATE TABLE " . $table);
      QFrame_Db_Table::reset($table);
    }
  }
} 
