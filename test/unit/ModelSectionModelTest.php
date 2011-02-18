<?php
/**
 * This file is part of QFrame.
 *
 * QFrame is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * QFrame is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category   QFrame_Test
 * @package    QFrame_Test_Unit
 * @copyright  Copyright (c) 2007, 2008, 2009, 2010, 2011 Collaborative Software Foundation (CSF)
 * @license    http://www.gnu.org/licenses/agpl-3.0.txt   GNU Affero General Public License v3
 */


/**
 * PHPUnit_Framework
 */
require_once 'PHPUnit/Framework.php';


/**
 * @category   QFrame_Test
 * @package    QFrame_Test_Unit
 * @copyright  Copyright (c) 2007, 2008, 2009, 2010, 2011 Collaborative Software Foundation (CSF)
 * @license    http://www.gnu.org/licenses/agpl-3.0.txt   GNU Affero General Public License v3
 */
class Test_Unit_ModelSectionModelTest extends QFrame_Test_Unit {
  
  public function start() {
    $this->fixture(array(
      'QuestionnaireModel',
      'InstanceModel',
      'ModelModel',
      'PageModel',
      'SectionModel',
      'QuestionModel',
      'QuestionTypeModel',
      'QuestionPromptModel',
      'ResponseModel',
      'DbUserModel',
      'RoleModel'
    ));
  }

  private function auth() {
    // perform mock authentication
    $auth_adapter = new QFrame_Auth_Adapter('sample1', 'password');
    $auth = Zend_Auth::getInstance();
    $auth->authenticate($auth_adapter);

    // authorize the sample1 user with the admin role and give the admin role
    // all possible global rights
    $adminRole = RoleModel::find(4);
    $adminRole->grant('view');
    $adminRole->grant('edit');
    $adminRole->grant('approve');
    $adminRole->grant('administer');
    $adminRole->save();
    $user = new DbUserModel(array('dbUserID' => 1));
    $user->addRole($adminRole);
  }

  /*
   * test getting section object attributes
   */
  public function testGetSectionAttributes() {
    $this->auth();
    $modelSection = new ModelSectionModel(array('modelID' => 1,
                                                'sectionID' => 5));
    $this->assertNotNull($modelSection->sectionHeader);
  }
  
  /*
   * test save() saves all model responses for this section
   */
  public function testModelSectionModelSavesModelResponses() {
    $this->auth();
    $section = new ModelSectionModel(array('modelID' => 1,
                                           'sectionID' => 5,
                                           'depth' => 'response'));
    $question = $section->nextModelQuestion();
    $response = $question->createModelResponse('match', 'test');
    $modelResponseID = $response->modelResponseID;
    $section->save();
    $testResponse = new ModelResponseModel(array('modelResponseID' => $modelResponseID));
    $this->assertEquals($modelResponseID, $testResponse->modelResponseID);
  }
  
}
