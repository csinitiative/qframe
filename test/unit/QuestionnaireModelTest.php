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
class Test_Unit_QuestionnaireModelTest extends QFrame_Test_Unit {
  
  public function start() {
    $this->fixture(array('DbUserModel', 'RoleModel', 'InstanceModel'));
    FileModel::setDataPath(TEST_PATH . '/data');
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
   * test that trying to fetch an invalid questionnaire name will result
   * in an exception being thrown
   */
  public function testInvalidQuestionnaireNameProducesException() {
    try {
      $instance = new QuestionnaireModel(array('questionnaireName' => 'INVALID',
                                            'questionnaireVersion' => '1.0',
                                            'revision' => 1));
    }
    catch(Exception $e) { return; }
    
    $this->fail('Expected exception but no exception was thrown');
  }
  
  /*
   * test that trying to construct an instance without providing
   * a name results in an InvalidArgumentException
   */  
  public function testMissingValidArgumentsThrowsException() {
    try {
      $instance = new InstanceModel(array());      
    }
    catch(InvalidArgumentException $e) { return; }
    catch(Exception $e) {}
    $this->fail('Expected exception, InvalidArgumentException not thrown');
  }
  
  /*
   * test that importing questionnaire definition XML document works
   */  
  public function testXMLImportTest1Questionnaire() {
    $this->auth();

    $xml = file_get_contents(PROJECT_PATH . "/test/data/xml/test1-questionnaire-definition.xml");
    QuestionnaireModel::importXML($xml);
    $questionnaire = new QuestionnaireModel(array('questionnaireName' => 'Test1 Questionnaire',
                                            'questionnaireVersion' => '3.00',
                                            'revision' => 1,
                                            'depth' => 'questionnaire'));
    $this->assertEquals($questionnaire->questionnaireName, 'Test1 Questionnaire');
    $this->assertEquals($questionnaire->questionnaireVersion, '3.00');
  }

  /*
   * test that after importing a questionnaire definition, its signature matches expected value
   */
  public function testImportQuestionnaireSignatureMatchesExpected() {
    $this->auth();

    $xml = file_get_contents(PROJECT_PATH . "/test/data/xml/test1-questionnaire-definition.xml");
    QuestionnaireModel::importXML($xml);
    $questionnaire = new QuestionnaireModel(array('questionnaireName' => 'Test1 Questionnaire',
                                            'questionnaireVersion' => '3.00',
                                            'revision' => 1,
                                            'depth' => 'questionnaire'));
    $this->assertEquals($questionnaire->signature, 'cf5a00917b4971f0db16bffacf1cfd70');
  }
  
  /*
   * test that importing questionnaire definition XML document from ZIP file works
   */  
  public function testXMLImportTest1QuestionnaireZip() {
    $this->auth();

    $import = new ZipArchiveModel(null, array('filename' => PROJECT_PATH . "/test/data/zip/questionnaire-definition.zip"));
    QuestionnaireModel::importXML($import);
    $questionnaire = new QuestionnaireModel(array('questionnaireName' => 'Test1 Questionnaire',
                                            'questionnaireVersion' => '3.00',
                                            'revision' => 1,
                                            'depth' => 'questionnaire'));
    $this->assertEquals($questionnaire->questionnaireName, 'Test1 Questionnaire');
    $this->assertEquals($questionnaire->questionnaireVersion, '3.00');
  }
  
  /*
   * Test that a rule associated with a prompt that belongs to a question targeting the same
   * question produces an exception
   */
  public function testSelfReferentialDisableQuestionRuleProducesException() {
    $this->auth();
    
    $xml = file_get_contents(PROJECT_PATH . "/test/data/xml/self-referential-disableQuestion.xml");
    try {
      QuestionnaireModel::importXML($xml);
    }
    catch(Exception $e) { return; }
    $this->fail('Import of a self-referential disableQuestion rule should produce an exception');
  }
  
  /*
   * Test that a rule associated with a prompt that belongs to a question targeting the section
   * to which that question belongs produces an exception
   */
  public function testSelfReferentialDisableSectionRuleProducesException() {
    $this->auth();
    
    $xml = file_get_contents(PROJECT_PATH . "/test/data/xml/self-referential-disableSection.xml");
    try {
      QuestionnaireModel::importXML($xml);
    }
    catch(Exception $e) { return; }
    $this->fail('Import of a self-referential disableSection rule should produce an exception');
  }

  /*
   * Test that a rule associated with a prompt that belongs to a question targeting the tab
   * to which that question belongs produces an exception
   */
  public function testSelfReferentialDisableTabRuleProducesException() {
    $this->auth();
    
    $xml = file_get_contents(PROJECT_PATH . "/test/data/xml/self-referential-disableTab.xml");
    try {
      QuestionnaireModel::importXML($xml);
    }
    catch(Exception $e) { return; }
    $this->fail('Import of a self-referential disableTab rule should produce an exception');
  }
  
}
