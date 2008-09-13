<?php
class AddPageIDColumnToSectionReference extends Migration {

  public function up() {
    $this->addColumn('section_reference', 'pageID', 'integer', array('limit' => 20));
  }
  
  public function down() {
    $this->removeColumn('section_reference', 'pageID');
  }
}
