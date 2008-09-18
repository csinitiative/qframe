<?php
/**
 * This file is part of the CSI QFrame.
 *
 * The CSI QFrame is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The CSI QFrame is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category   Migration
 * @package    Migration
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */


/**
 * @category   Migration
 * @package    Migration
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */
class Migration_Generator {
 
  /**
   * Generate a new migration
   *
   * @param string path where the new migration will live
   * @param string name of the new migration
   */
  public static function generate($path, $name) {
    $version = strftime('%Y%m%d%H%M%S');
    $fileName = _path($path, "{$version}_{$name}.php");
    if(!file_put_contents($fileName, self::templatize($name)))
      die("Unable to create migration\n\n");
    exit;
  }
  
  /**
   * Pick the correct template, then templatize (tm)
   *
   * @param  string name of the class we are creating
   * @return string
   */
  public function templatize($className) {
    if(preg_match('/^CreateTable(\w+)$/', $className, $matches)) {
      $tableName = ltrim(strtolower(preg_replace('/[A-Z]/', '_$0', $matches[1])), '_');
      $content = self::templateWithCreateTable($className, $tableName);
    }
    else {
      $content = self::template($className);
    }
    
    return $content;
  }
  
  /**
   * Returns a string with the class name interpolated
   *
   * @param  string class name
   * @return string
   */
  private static function template($className) {
    return <<<END
<?php
class {$className} extends Migration {

  public function up() {
    
  }
  
  public function down() {
    
  }
}

END;
  }
  
  /**
   * Returns a string with the class name interpolated
   *
   * @param  string class name
   * @param  string table name
   * @return string
   */
  private static function templateWithCreateTable($className, $tableName) {
    return <<<END
<?php
class {$className} extends Migration {

  public function up() {
    \$this->createTable('{$tableName}', array(), array(
      array('<name>', '<type>', array())
    ));
  }

  public function down() {
    \$this->dropTable('{$tableName}');
  }
}

END;
  }
}