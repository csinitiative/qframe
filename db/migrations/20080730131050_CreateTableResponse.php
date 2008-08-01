<?php
/*
 * A response is an instance of an answer to a question. Responses can be 
 * modified until approved, once approved they should be archived, tagged 
 * with the date and author of the modification. 
 *
 * approverComments is a 1.0b1 hack, eventually there will need to be a
 * a separate table to allow a history of responses and comments by 
 * reviewer username.
 */

class CreateTableResponse extends Migration {
    
  public function up() {
    $this->createTable('response', array('primary' => 'responseID'), array(
      array('responseID', 'integer', array('limit' => 20)),
      array('instanceID', 'integer'),
      array('pageID', 'integer'),
      array('sectionID', 'integer'),
      array('questionID', 'integer', array('limit' => 20)),
      array('responseDate', 'timestamp', array('null' => true)),
      array('responseEndDate', 'datetime', array('null' => true)),
      array('responseText', 'text', array('null' => true)),
      array('additionalInfo', 'text', array('null' => true)),
      array('approverComments', 'text', array('null' => true)),
      array('externalReference', 'string', array('limit' => 80, 'null' => true)),
      array('state', 'integer', array('default' => 1))
    ));
    $this->createIndex('response', array('questionID'));
    $this->createIndex('response', array('instanceID'));
  }

  public function down() {
    $this->dropTable('response');
  }
}
