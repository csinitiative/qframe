<?php
/*
 * The locks table provides the facility to coordinate long-running 
 * application-level locks on response 
 * elements to preclude inadvertant data-loss through
 * conflicting updates. Table is names "locks" as "lock" is a SQL2 
 * reserved word.
 */

class CreateTableLocks extends Migration {

  public function up() {
    $this->createTable('locks', array('primary' => 'lockID'), array(
      array('lockID', 'integer'),
      array('dbUserID', 'integer'),
      array('className', 'string'),
      array('objectID', 'string'),
      array('obtained', 'timestamp', array('null' => true)),
      array('expiration', 'datetime')
    ));
    $this->createIndex('locks', array('objectID'));
  }

  public function down() {
    $this->dropTable('locks');
  }
}
