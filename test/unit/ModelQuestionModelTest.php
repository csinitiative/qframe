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
class Test_Unit_ModelQuestionModelTest extends QFrame_Test_Unit {
  
  public function start() {
    $this->fixture(array(
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
   * test getting question object attributes
   */
  public function testGetQuestionAttributes() {
    $this->auth();
    $modelQuestion = new ModelQuestionModel(array('modelID' => 1,
                                                  'questionID' => 7));
    $this->assertNotNull($modelQuestion->qText);
  }
  
  /*
   * test save() saves all model responses for this question
   */
  public function testModelQuestionModelSavesModelResponses() {
    $this->auth();
    $question = new ModelQuestionModel(array('modelID' => 1,
                                             'questionID' => 7,
                                             'depth' => 'response'));
    $response = $question->createModelResponse('match', 'test');
    $modelResponseID = $response->modelResponseID;
    $question->save();
    $testResponse = new ModelResponseModel(array('modelResponseID' => $modelResponseID));
    $this->assertEquals($modelResponseID, $testResponse->modelResponseID);
  }

}
