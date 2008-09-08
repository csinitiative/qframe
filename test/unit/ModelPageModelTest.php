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
class Test_Unit_ModelPageModelTest extends QFrame_Test_Unit {
  
  public function start() {
    $this->fixture(array(
      'ModelModel',
      'QuestionnaireModel',
      'InstanceModel',
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
   * test getting page object attributes
   */
  public function testGetPageAttributes() {
    $this->auth();
    $modelPage = new ModelPageModel(array('modelID' => 1,
                                          'pageID' => 6));
    $this->assertNotNull($modelPage->pageHeader);
  }
  
  /*
   * test save() saves all model responses for this page
   */
  public function testModelPageModelSavesModelResponses() {
    $this->auth();
    $page = new ModelPageModel(array('modelID' => 1,
                                     'pageID' => 6,
                                     'depth' => 'response'));
    $section = $page->nextModelSection();
    $question = $section->nextModelQuestion();
    $response = $question->createModelResponse('match', 'test');
    $modelResponseID = $response->modelResponseID;
    $page->save();
    $testResponse = new ModelResponseModel(array('modelResponseID' => $modelResponseID));
    $this->assertEquals($modelResponseID, $testResponse->modelResponseID);
  }
  
  /*
   * test comparing a question of type T to a passed match modelResponse
   */
  public function testCompareMatchModelResponsePass() {
    $this->auth();
    $instance = new InstanceModel(array('instanceID' => 1,
                                        'depth' => 'response'
    ));
    $question = new QuestionModel(array('questionID' => 7,
    		                            'depth' => 'response'
    ));
    $response = $question->getResponse();
    $response->responseText = 'test';
    $response->save(); 
    QFrame_Db_Table::reset('response');
    $modelQuestion = new ModelQuestionModel(array('modelID' => 1,
                                                  'questionID' => 7,
                                                  'depth' => 'response',
                                                  'instance' => $instance));
    $modelResponse = $modelQuestion->createModelResponse('match', 'test');
    $modelQuestion->save();
    $modelPage = new ModelPageModel(array('modelID' => 1,
                                          'pageID' => 6,
                                          'depth' => 'response',
                                          'instance' => $instance));
    $report = $modelPage->compare(array('model_pass' => true));
    $this->assertEquals($report['model_pass'][0]['messages'][0], 'Matches test');
  }
  
  /**
   * test comparing a question of type T to a failed match modelResponse
   */
  public function testCompareMatchModelResponseFail() {
    $this->auth();
    $instance = new InstanceModel(array('instanceID' => 1,
                                        'depth' => 'response'
    ));
    $question = new QuestionModel(array('questionID' => 7,
    		                            'depth' => 'response'
    ));
    $response = $question->getResponse();
    $response->responseText = 'foo';
    $response->save(); 
    QFrame_Db_Table::reset('response');
    $modelQuestion = new ModelQuestionModel(array('modelID' => 1,
                                                  'questionID' => 7,
                                                  'depth' => 'response',
                                                  'instance' => $instance));
    $modelResponse = $modelQuestion->createModelResponse('match', 'test');
    $modelQuestion->save();
    $modelPage = new ModelPageModel(array('modelID' => 1,
                                          'pageID' => 6,
                                          'depth' => 'response',
                                          'instance' => $instance));
    $report = $modelPage->compare(array('model_pass' => true));
    $this->assertEquals($report['model_fail'][0]['messages'][0], 'Does not match test');
  }
  
  /*
   * test comparing a question of type S to a passed "selected" modelResponse
   */
  public function testCompareSelectedModelResponsePass() {
    $this->auth();
    $instance = new InstanceModel(array('instanceID' => 1,
                                        'depth' => 'response'
    ));
    $question = new QuestionModel(array('questionID' => 8,
    		                            'depth' => 'response'
    ));
    $response = $question->getResponse();
    $response->responseText = '1'; // promptID for "Yes"
    $response->save();
    QFrame_Db_Table::reset('response');
    $modelQuestion = new ModelQuestionModel(array('modelID' => 1,
                                                  'questionID' => 8,
                                                  'depth' => 'response',
                                                  'instance' => $instance));
    $modelResponse = $modelQuestion->createModelResponse('selected', '1');
    $modelQuestion->save();
    $modelPage = new ModelPageModel(array('modelID' => 1,
                                          'pageID' => 6,
                                          'depth' => 'response',
                                          'instance' => $instance));
    $report = $modelPage->compare(array('model_pass' => true));
    $this->assertEquals($report['model_pass'][0]['messages'][0], 'Prompt selected: Yes');
  }
  
  /*
   * test comparing a question of type S to a failed "selected" modelResponse
   */
  public function testCompareSelectedModelResponseFail() {
    $this->auth();
    $instance = new InstanceModel(array('instanceID' => 1,
                                        'depth' => 'response'
    ));
    $question = new QuestionModel(array('questionID' => 8,
    		                            'depth' => 'response'
    ));
    $response = $question->getResponse();
    $response->responseText = '2'; // promptID for "Yes"
    $response->save();
    QFrame_Db_Table::reset('response');
    $modelQuestion = new ModelQuestionModel(array('modelID' => 1,
                                                  'questionID' => 8,
                                                  'depth' => 'response',
                                                  'instance' => $instance));
    $modelResponse = $modelQuestion->createModelResponse('selected', '1');
    $modelQuestion->save();
    $modelPage = new ModelPageModel(array('modelID' => 1,
                                          'pageID' => 6,
                                          'depth' => 'response',
                                          'instance' => $instance));
    $report = $modelPage->compare(array('model_pass' => true));
    $this->assertEquals($report['model_fail'][0]['messages'][0], 'Prompt not selected: Yes');
  }

}
