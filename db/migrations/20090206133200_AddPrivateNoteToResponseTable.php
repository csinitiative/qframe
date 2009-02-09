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

class AddPrivateNoteToResponseTable extends Migration {
    
  public function up() {
    $this->addColumn('response', 'privateNote', 'text', array('null' => true));
  }

  public function down() {
    $this->removeColumn('response', 'privateNote');
  }

}
