<?php
// The crypto table provides the keys and metadata for encrypting files

class CreateTableCrypto extends Migration {

  public function up() {
    $this->createTable('crypto', array('primary' => 'cryptoID'), array(
      array('cryptoID', 'integer'),
      array('name', 'string'),
      array('type', 'string', array('limit' => 50)),
      array('cryptoKey', 'text', array('limit' => 255)),
      array('secret', 'text', array('null' => true, 'limit' => 255))
    ));
  }

  public function down() {
    $this->dropTable('crypto');
  }
}
