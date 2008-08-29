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
 * @category   QFrame_Test
 * @package    QFrame_Test_Unit
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */


/**
 * PHPUnit_Framework
 */
require_once 'PHPUnit/Framework.php';


/**
 * @category   QFrame_Test
 * @package    QFrame_Test_Unit
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */
class Test_Unit_ModelModelTest extends QFrame_Test_Unit {
  
  /*
   * test that fetching all models returns the correct number of models
   */
  public function testFindAllReturnsCorrectModels() {
    $this->assertEquals(count(ModelModel::find('all')), 3);
  }
  
  /*
   * test that fetching one model returns the correct thing (ModelModel object)
   */
  public function testFindFirstReturnsOneModel() {
    $this->assertTrue(ModelModel::find('first') instanceof ModelModel);
  }
  
  /*
   * test that fetching models using a conditions array returns the right number of models
   */
  public function testFindWithConditionsArray() {
    $models = ModelModel::find('all', array('modelID = ? OR modelID = ?', 1, 2));
    $this->assertEquals(count($models), 2);
  }
  
  /*
   * test that fetching models using a conditions string return the right number of models
   */
  public function testFindWithConditionsString() {
    $models = ModelModel::find('all', 'modelID = 1 OR modelID = 2');
    $this->assertEquals(count($models), 2);
  }
 
  /*
   * test that fetching models with an order clause causes that order clause to be applied
   */
  public function testFindWithOrderClause() {
    $model = ModelModel::find('first', null, 'modelID DESC');
    $this->assertEquals(intVal($model->modelID), 3);
  }
  
  /*
   * test that creating a new model works properly
   */
  public function testCreateModel() {
    $initialModels = ModelModel::find('all');
    ModelModel::create(array('name' => 'new model'))->save();
    $this->assertEquals(count(ModelModel::find('all')), count($initialModels) + 1);
  }
  
  /*
   * test that findBy correctly limits a find appropriately
   */
  public function testFindBy() {
    $this->assertEquals(count(ModelModel::findBy('questionnaireID', 1)), 2);
  }
}
