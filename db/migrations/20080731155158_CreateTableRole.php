<?php
/*
 * The role table describes the relationship of a dbUser to a question and its
 * responses
 *
 * An assignment is the object that grants a dbUser a role for a question. 
 * As of this time assignments are made at the question level, the application
 * may provide a means to manipulate those assignments at larger levels of
 * granularity but they are applied at this level.  assignment serves as the
 * owner of both requirement (the link table to questions) and action (the
 * link table to responses.) As such, it a a workflow element as well.
 */

class CreateTableRole extends Migration {

  public function up() {
    $this->createTable('role', array('primary' => 'roleID'), array(
      array('roleID', 'integer'),
      array('roleDescription', 'string', array('limit' => 128, 'null' => true)),
      array('ACLstring', 'text', array('null' => true)),
    ));
    
    $this->createTable('assignment', array('primary' => 'assignmentID'), array(
      array('dbUserID', 'integer'),
      array('roleID', 'integer'),
      array('assignmentID', 'integer'),
      array('comments', 'text', array('null' => true))
    ));
    
    $this->createIndex('assignment', array('dbUserID', 'roleID'));
    
    // give the admin user full global rights
    $adminRole = RoleModel::create(array('roleDescription' => 'Administrators'));
    $adminRole->grant('view');
    $adminRole->grant('edit');
    $adminRole->grant('approve');
    $adminRole->grant('adminiter');
    $adminRole->save();
    DbUserModel::findByUsername('admin')->addRole($adminRole)->save();  
  }

  public function down() {
    $this->dropTable('role');
    $this->dropTable('assignment');
  }
}
