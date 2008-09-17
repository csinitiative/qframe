<?php
class AddPageIDColumnToSectionReference extends Migration {

  public function up() {
    // add the column
    $this->addColumn('section_reference', 'pageID', 'integer', array('limit' => 20));
    
    // update the column for existing rows
    $adapter = Zend_Db_Table_Abstract::getDefaultAdapter();
    $statement = $adapter->select()->from('section')->query();
    while($section = $statement->fetchObject()) {
      $newValues = array('pageID' => $section->pageID);
      $where = "sectionID={$section->sectionID}";
      $adapter->update('section_reference', $newValues, $where);
    }
  }
  
  public function down() {
    $this->removeColumn('section_reference', 'pageID');
  }
}
