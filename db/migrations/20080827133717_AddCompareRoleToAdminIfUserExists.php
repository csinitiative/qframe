<?php
class AddCompareRoleToAdminIfUserExists extends Migration {

  public function up() {
    $role = RoleModel::findBy('first', 'roleDescription', 'Administrators');
    if($role !== null) {
      $role->grant('compare');
      $role->save();
    }
  }
  
  public function down() {
    $role = RoleModel::findBy('first', 'roleDescription', 'Administrators');
    if($role !== null) {
      $role->deny('compare');
      $role->save();
    }
  }
}
