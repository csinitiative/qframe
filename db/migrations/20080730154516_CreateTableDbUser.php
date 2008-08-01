<?php
/*
 * The following tables provide the structure for defining application users,
 * the role(s) a user can hold in building questionnaire responses, and the 
 * requirements and actions to define a simple workflow facility for assigning
 * and tracking completion of the full response document.
 *
 * A dbuser is a user of the application who can answer or approve a response.
 * dbUserPW is sized to hold a SHA1 hash plus salt. Note that with the addition
 * of dbUserFullName, dbUserName has been shrunk to char (20). dbUserActive
 * indicates whether the user account is enabled in the system.
 */

class CreateTableDbUser extends Migration {

  public function up() {
    $this->createTable('db_user', array('primary' => 'dbUserID'), array(
      array('dbUserID', 'integer'),
      array('dbUserName', 'string', array('limit' => 20)),
      array('dbUserPW', 'string', array('limit' => 50)),
      array('dbUserFullName', 'string', array('limit' => 50, 'null' => true)),
      array('dbUserActive', 'string', array('limit' => 1, 'default' => 'Y')),
      array('ACLstring', 'text', array('null' => true))
    ));
    $this->createIndex('db_user', array('dbUserName'));
    
    // create some default data
    $admin = new DbUserModel(
      array('dbUserName' => 'admin', 'dbUserPW' => 'admin', 'dbUserFullName'  => 'Administrator')
    );
    $admin->save();
    $user = new DbUserModel(
      array('dbUserName' => 'user', 'dbUserPW' => 'user', 'dbUserFullName'  => 'User')
    );
    $user->save();
  }

  public function down() {
    $this->dropTable('db_user');
  }
}
