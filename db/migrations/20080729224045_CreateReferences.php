<?php
/*
 * References are supplemental documents used to explain or illuminate pages
 * sections, questionGroups, and questions. The reference table describes
 * the source document, the referenceDetail table the specific document element
 * (section, paragraph, item.) Shortname is the prmary key for reference and
 * is the common acronym for the reference (e.g. UAP, ISO, PCI, ...)
 */

class CreateReferences extends Migration {
  
  public function up() {
    $this->createTable('reference', array('primary' => array('instanceID', 'shortName')), array(
      array('instanceID', 'integer'),
      array('shortName', 'string', array('limit' => 8)),
      array('referenceName', 'string', array('limit' => 80, 'null' => true))
    ));

    $this->createTable('reference_detail', array('primary' => 'referenceDetailID'), array(
      array('referenceDetailID', 'integer'),
      array('instanceID', 'integer'),
      array('shortName', 'string', array('limit' => 8)),
      array('item', 'string', array('limit' => 80, 'null' => true)),
      array('referenceText', 'text', array('null' => true)),
      array('referenceURL', 'string', array('null' => true))
    ));
    
    $this->createTable('page_reference',
      array('primary' => array('pageID', 'referenceDetailID')),
      array(
        array('pageID', 'integer'),
        array('referenceDetailID', 'integer'),
        array('instanceID', 'integer')
      )
    );

    $this->createTable('section_reference',
      array('primary' => array('sectionID', 'referenceDetailID')),
      array(
        array('sectionID', 'integer'),
        array('referenceDetailID', 'integer'),
        array('instanceID', 'integer')
      )
    );
    
    $this->createTable('question_reference',
      array('primary' => array('questionID', 'referenceDetailID')),
      array(
        array('questionID', 'integer', array('limit' => 20)),
        array('referenceDetailID', 'integer', array('limit' => 20)),
        array('instanceID', 'integer'),
        array('pageID', 'integer', array('limit' => 20)),
        array('sectionID', 'integer', array('limit' => 20))
      )
    );

    $this->createIndex('reference_detail', array('shortName'));
    $this->createIndex('reference_detail', array('instanceID'));
    $this->createIndex('page_reference', array('instanceID'));
    $this->createIndex('section_reference', array('instanceID'));
    $this->createIndex('question_reference', array('instanceID'));
  }
  
  public function down() {
    $this->dropTable('reference');
    $this->dropTable('reference_detail');
    $this->dropTable('page_reference');
    $this->dropTable('section_reference');
  }
}
