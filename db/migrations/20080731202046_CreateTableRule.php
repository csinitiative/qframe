<?php
/*
 * The rules table is used to provide persistence for simple rules on the 
 * requirement to complete subsets of the questions based on answers to
 * other questions. The initial implementation will support, as an example
 * the 'High-Level Questions' refinement capability of the BITS SIG v3.
 */

class CreateTableRule extends Migration {

  public function up() {
    $this->createTable('rule', array('primary' => 'ruleID'), array(
      array('ruleID', 'integer', array('limit' => 20)),
      array('questionnaireID', 'integer'),
      array('instanceID', 'integer'),
      array('sourceID', 'integer', array('limit' => 20)),
      array('targetID', 'integer', array('limit' => 20)),
      array('targetGUID', 'integer'),
      array('enabled', 'string', array('limit' => 1, 'default' => 'N')),
      array('type', 'string', array('limit' => 50))
    ));
    
    $this->createIndex('rule', array('targetID'));
    $this->createIndex('rule', array('sourceID'));
    $this->createIndex('rule', array('instanceID'));
  }

  public function down() {
    $this->dropTable('rule');
  }
}
