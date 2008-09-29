<?php
class AddInfoColumnToModelResponse extends Migration {

  public function up() {
    // add the column
    $this->addColumn('model_response', 'info', 'text', array('null' => true));
  }
  
  public function down() {
    $this->removeColumn('model_response', 'info');
  }
}
