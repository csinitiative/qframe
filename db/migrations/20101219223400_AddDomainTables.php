<?php

class AddDomainTables extends Migration {

  public function up() {

    $this->createTable('domain', array('primary' => 'domainID'), array(
      array('domainID', 'integer'),
      array('domainDescription', 'string', array('limit' => 255, 'null' => false)),
    ));
    $this->createIndex('domain', array('domainDescription'));

    $this->createTable('domain_questionnaire', array('primary' => array('domainID', 'questionnaireID')), array(
      array('domainID', 'integer'),
      array('questionnaireID', 'integer')
    ));

    $this->addColumn('instance', 'domainID', 'integer', array('limit' => 20));

    $this->addColumn('role', 'domainID', 'integer', array('limit' => 20));

    $this->addColumn('db_user', 'domainID', 'integer', array('limit' => 20));

    // reset db metadata cache
    QFrame_Db_Table::scanDb();

    $adapter = Zend_Db_Table_Abstract::getDefaultAdapter();

    $domainTable = QFrame_Db_Table::getTable('domain');
    $domainQuestionnaireTable = QFrame_Db_Table::getTable('domain_questionnaire');

    $domainID = $domainTable->insert(array('domainDescription' => 'Default'));

    // set the domain for existing instances to default
    $adapter = Zend_Db_Table_Abstract::getDefaultAdapter();
    $statement = $adapter->select()->from('instance')->query();
    while($instance = $statement->fetchObject()) {
      $newValues = array('domainID' => $domainID);
      $where = "instanceID={$instance->instanceID}";
      $adapter->update('instance', $newValues, $where);
    }

    // set the domain for existing roles to default
    $adapter = Zend_Db_Table_Abstract::getDefaultAdapter();
    $statement = $adapter->select()->from('role')->query();
    while($role = $statement->fetchObject()) {
      $newValues = array('domainID' => $domainID);
      $where = "roleID={$role->roleID}";
      $adapter->update('role', $newValues, $where);
    }

    // set the domain for existing users to default
    $adapter = Zend_Db_Table_Abstract::getDefaultAdapter();
    $statement = $adapter->select()->from('db_user')->query();
    while($user = $statement->fetchObject()) {
      $newValues = array('domainID' => $domainID);
      $where = "dbUserID={$user->dbUserID}";
      $adapter->update('db_user', $newValues, $where);
    }

    $statement = $adapter->select()->from('questionnaire')->query();
    while($row = $statement->fetchObject()) {
      $domainQuestionnaireTable->insert(array('domainID' => $domainID,
                                              'questionnaireID' => $row->questionnaireID));
    }

    $this->createIndex('instance', array('domainID'));
    $this->createIndex('role', array('domainID'));
    $this->createIndex('db_user', array('domainID'));

  }

  public function down() {
    $this->dropTable('domain');
    $this->dropTable('domain_questionnaire');
    $this->removeColumn('instance', 'domainID');
    $this->removeColumn('role', 'domainID');
    $this->removeColumn('db_user', 'domainID');
  }
}
