<?php

class AddDefaultDomainAdministrators extends Migration {


  public function up() {

    define("DEFAULT_DOMAIN_ID", 1);

    $roleTable = QFrame_Db_Table::getTable('role');

    $roleID = $roleTable->insert(array('roleDescription' => 'Default Domain Administrators', 
                                       'domainID' => 1));

    $role = RoleModel::find($roleID);

    $domain = DomainModel::find(DEFAULT_DOMAIN_ID);

    $role->grant('administer', $domain);

    $role->save();

  }

  public function down() {
  }
}
