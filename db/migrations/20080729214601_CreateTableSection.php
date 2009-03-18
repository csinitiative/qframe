<?php
/*
 * A section is a subset of questions wihin an instance of a page used for 
 * conceptual subsetting of the response. In this instance, each occurs once.
 * In future releases of the application it is possible to use this as a 
 * collection device for repeating groups of questions within a page.
 */

class CreateTableSection extends Migration {

  public function up() {
    $this->createTable('section', array('primary' => 'sectionID'), array(
      array('instanceID', 'integer'),
      array('pageID', 'integer'),
      array('sectionID', 'integer'),
      array('sectionGUID', 'integer', array('null' => true)),
      array('seqNumber', 'integer'),
      array('sectionHeader', 'string', array('limit' => 255, 'null' => true)),
      array('description', 'string', array('limit' => 128, 'null' => true)),
      array('required', 'boolean', array('default' => 1)),
      array('cloneable', 'boolean', array('default' => 0)),
      array('defaultSectionHidden', 'boolean', array('default' => 0)),
      array('disableCount', 'integer', array('default' => 0))
    ));
    $this->createIndex('section', array('pageID'));
    $this->createIndex('section', array('instanceID'));
  }

  public function down() {
    $this->dropTable('section');
  }
}
