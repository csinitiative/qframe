<?php
class ExpandSectionHeaderInSection extends Migration {

  public function up() {
    // alter columns
    $this->alterColumn('section', 'sectionHeader', 'string', array('limit' => 255));
  }
  
  public function down() {

  }
  
}
