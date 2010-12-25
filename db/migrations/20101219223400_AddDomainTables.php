<?php

class AddDomainTables extends Migration {

  public function up() {

    $this->createTable('domain', array('primary' => 'domainID'), array(
      array('domainID', 'integer'),
      array('domainDescription', 'string', array('limit' => 255, 'null' => false)),
    ));

    $this->createTable('domain_questionnaire', array('primary' => array('domainID', 'questionnaireID')), array(
      array('domainID', 'integer'),
      array('questionnaireID', 'integer')
    ));
    
    $this->createTable('domain_instance', array('primary' => array('domainID', 'instanceID')), array(
      array('domainID', 'integer'),
      array('instanceID', 'integer')
    ));

    // reset db metadata cache
    QFrame_Db_Table::scanDb();

    $adapter = Zend_Db_Table_Abstract::getDefaultAdapter();

    $domainTable = QFrame_Db_Table::getTable('domain');
    $domainQuestionnaireTable = QFrame_Db_Table::getTable('domain_questionnaire');
    $domainInstanceTable = QFrame_Db_Table::getTable('domain_instance');

    $domainID = $domainTable->insert(array('domainDescription' => 'Default'));

    $statement = $adapter->select()->from('questionnaire')->query();
    while($row = $statement->fetchObject()) {
      $domainQuestionnaireTable->insert(array('domainID' => $domainID,
                                              'questionnaireID' => $row->questionnaireID));
    }

    $statement = $adapter->select()->from('instance')->query();
    while($row = $statement->fetchObject()) {
      $domainInstanceTable->insert(array('domainID' => $domainID,
                                         'instanceID' => $row->instanceID));
    }

  }

  public function down() {
    $this->dropTable('domain');
    $this->dropTable('domain_questionnaire');
    $this->dropTable('domain_instance');
  }
}
