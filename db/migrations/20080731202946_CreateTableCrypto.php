<?php
// The crypto table provides the keys and metadata for encrypting files

class CreateTableCrypto extends Migration {

  public function up() {
    $this->createTable('crypto', array('primary' => 'cryptoID'), array(
      array('cryptoID', 'integer'),
      array('name', 'string'),
      array('type', 'string', array('limit' => 50)),
      array('cryptoKey', 'text'),
      array('secret', 'text', array('null' => true))
    ));
  }

  public function down() {
    $this->dropTable('crypto');
  }
}
