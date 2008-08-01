<?php
/*
 * The attachment table provides the metadata needed to attach supporting 
 * files to docment a specifc response.
 */

class CreateTableAttachment extends Migration {

  public function up() {
    $this->createTable('attachment', array('primary' => 'attachmentID'), array(
      array('attachmentID', 'integer'),
      array('instanceID', 'integer', array('null' => true)),
      array('objectType', 'string'),
      array('objectID', 'string'),
      array('filename', 'string'),
      array('mime', 'string', array('limit' => 32, 'null' => true)),
      array('content', 'binary', array('null' => true, 'limit' => '20M')),
      array('created', 'timestamp', array('null' => true))
    ));
    $this->createIndex('attachment', array('objectID'));
    $this->createIndex('attachment', array('instanceID'));
  }

  public function down() {
    $this->dropTable('attachment');
  }
}
