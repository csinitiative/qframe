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
class Test_Unit_InstanceModelTest extends QFrame_Test_Unit {
  
  public function start() {
    $this->fixture(array('QuestionnaireModel', 'PageModel', 'SectionModel', 'DbUserModel', 'RoleModel'));
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
   * test that trying to get the value of a non-existent attribute
   * throws an exception
   */
  public function testGetInvalidAttributeProducesException() {
    try {
      $instance = $this->instance();
      $value = $instance->NoAttribute;
    }
    catch(Exception $e) { return; }
    
    $this->fail('Expected exception but no exception was thrown');
  }
  
  /*
   * test that by default, instance loads to a depth of 'page'
   */
  public function testDepthDefaultsToPage() {
    $this->auth();
    $instance = $this->instance();
    $this->assertNotNull($instance->nextPage());
    $this->assertNull($instance->getFirstPage()->nextSection());
  }
  
  /*
   * test that it is possible to set the load depth to something
   * other than the default of 'page'
   */
  public function testDepthIsConfigurable() {
    $this->auth();
    $instance = $this->instance(array('depth' => 'section'));
    $this->assertNotNull($instance->getFirstPage()->nextSection());
  }
  
  /*
   * test that pages are given in the proper order when using the nextPage
   * method of fetching one page at a time
   */
  public function testProperOrderingOfPages() {
    $this->auth();
    $instance = $this->instance();
    $lastPage = 0;
    while($page = $instance->nextPage()) {
      $this->assertTrue($page->seqNumber > $lastPage);
      $lastPage = $page->seqNumber;
    }
  }
  
  /*
   * test that calling getPage() on an instance returns a page when the
   * instance contains a page with that ID
   */
  public function testGetPagePositive() {
    $this->auth();
    $instance = $this->instance();
    $this->assertNotNull($instance->getPage(1));
  }
  
  /*
   * test that calling getPage() on a page that does not exist
   * results in an exception
   */
  public function testGetPageNegative() {
    $this->auth();
    $instance = $this->instance();
    try {
      $instance->getPage(4);
    }
    catch(Exception $e) { return; }
    $this->fail('Expected exception but Exception not thrown');
  }

  /*
   * test that child has a sane parent object
   */
  public function testInstanceChildParent() {
    $this->auth();
    $instance = $this->instance(array('depth' => 'section'));
    $page = $instance->nextPage();
    $this->assertNotNull($page->parent->questionnaireName);
  }

  /*
   * test that calling delete() on an instance returns and that
   * attempting to load the instance again results in an exception.
   */
  public function testInstanceDelete() {
    $this->auth();
    $instance = $this->instance();
    $instanceID = $instance->instanceID;
    $instance->delete();
    try {
      $badInstance = new InstanceModel(array('instanceID' => $instanceID));
    }
    catch(Exception $e) { return; }
    $this->fail('Expected exception but Exception not thrown');
  }
  
  /*
   * test that an instance and a copy of the instance have equal XML exports
   * except for the instanceName
   */
  public function testInstanceExportEqualsInstanceCopyExport() {
    $this->auth();

    $xml = file_get_contents(PROJECT_PATH . "/test/data/xml/test1-questionnaire-definition.xml");
    QuestionnaireModel::importXML($xml);
    InstanceModel::importXML($xml, 'Test1 Company');
    $instance = new InstanceModel(array('questionnaireName' => 'Test1 Questionnaire',
                                        'questionnaireVersion' => '3.00',
                                        'revision' => 1,
                                        'instanceName' => 'Test1 Company'));
    $xmlExport = $instance->toXML(1);
    QuestionnaireModel::importXML($xmlExport);
    InstanceModel::importXML($xmlExport, 'Test1 Copy Company');
    $instanceCopy = new InstanceModel(array('questionnaireName' => 'Test1 Questionnaire',
                                            'questionnaireVersion' => '3.00',
                                            'revision' => 1,
                                            'instanceName' => 'Test1 Copy Company'));
    $xmlExportCopy = $instanceCopy->toXML(1);
    $xmlExportCopy = preg_replace("/Test1 Copy Company/", "Test1 Company", $xmlExportCopy);
    $this->assertEquals($xmlExport, $xmlExportCopy);
  }

  /*
   * test that known good zip archive equals the dynamically generated zip archive
   */
  public function testKnownGoodZipArchiveEqualsGeneratedZipArchive() {
    $this->auth();

    $goodZip = new ZipArchiveModel(null, array('filename' => PROJECT_PATH . "/test/data/zip/test-archive.zip"));

    $xml = file_get_contents(PROJECT_PATH . "/test/data/xml/test1-questionnaire-definition.xml");
    QuestionnaireModel::importXML($xml);
    InstanceModel::importXML($xml, 'Test1 Company');
    $instance = new InstanceModel(array('questionnaireName' => 'Test1 Questionnaire',
                                        'questionnaireVersion' => '3.00',
                                        'revision' => 1,
                                        'instanceName' => 'Test1 Company',
                                        'depth' => 'question'));
    while($page = $instance->nextPage()) {
      while ($section = $page->nextSection()) {
        while ($question = $section->nextQuestion()) {
          $fileObj = new FileModel($question);
          $fileObj->store(file_get_contents(PROJECT_PATH . '/test/data/zip/test-archive-attachment.txt'), array('filename' => 'test-archive-attachment.txt'));
          break 3;
        }
      }
    }
    $zip = new ZipArchiveModel($instance, array('new' => '1'));
    $zip->addInstanceFullResponsesXMLDocument();
    $zip->addAttachments();
    $zip->close();
    $zip = new ZipArchiveModel($instance, array('filename' => $zip->getZipFileName()));
    $this->assertEquals($goodZip->getInstanceFullResponsesXMLDocument(), $zip->getInstanceFullResponsesXMLDocument());
    $this->assertEquals($goodZip->getFromName('files/4'), $zip->getFromName('files/4'));
    $zip->deleteZipFile();
  }

  /*
   * test that import from known good zip archive equals contents in zip archive
   */
  public function testZipArchiveWithResponsesAndAttachmentsEqualsExportContent() {
    $this->auth();

    $zip = new ZipArchiveModel(null, array('filename' => PROJECT_PATH . "/test/data/zip/test-archive.zip"));

    QuestionnaireModel::importXML($zip);
    $instanceID = InstanceModel::importXML($zip, 'Test1 Company', array('pageResponses' => array('all' => 1)));
    $instance = new InstanceModel(array('instanceID' => $instanceID,
                                        'depth' => 'instance'));
    $this->assertEquals($zip->getInstanceFullResponsesXMLDocument(), $instance->toXML(1));
  }
  
  /*
   * test that an instance with no responses merged with another instance's responses of the same questionnaire
   * have equal XML exports except for the instanceName and responseDates
   */
  public function testNoResponsesInstanceWithMergedResponsesExportEqualsResponsesInstanceExport() {
    $this->auth();

    $xml = file_get_contents(PROJECT_PATH . "/test/data/xml/responses-questionnaire-definition.xml");

    QuestionnaireModel::importXML($xml);
    InstanceModel::importXML($xml, 'Test1 Resp. Company', array('pageResponses' => array('all' => 1)));
    $instance1 = new InstanceModel(array('questionnaireName' => 'Test1 Questionnaire',
                                         'questionnaireVersion' => '3.00',
                                         'revision' => 1,
                                         'instanceName' => 'Test1 Resp. Company'));
    $xml = file_get_contents(PROJECT_PATH . "/test/data/xml/no-responses-questionnaire-definition.xml");
    InstanceModel::importXML($xml, 'Test1 Company', array('instanceID' => $instance1->instanceID));
    $instance2 = new InstanceModel(array('questionnaireName' => 'Test1 Questionnaire',
                                         'questionnaireVersion' => '3.00',
                                         'revision' => 1,
                                         'instanceName' => 'Test1 Company'));
    $xml1 = $instance1->toXML(1);
    $xml2 = $instance2->toXML(1);
    $xml1 = preg_replace("/Test1 Resp. Company/", "Test1 Company", $xml1);
    $xml1 = preg_replace("/<csi:responseDate>.+<\/csi:responseDate>/", "", $xml1);
    $xml2 = preg_replace("/<csi:responseDate>.+<\/csi:responseDate>/", "", $xml2);
    $this->assertEquals($xml1, $xml2);
  }
  
  /*
   * fetch an instance with some default properties
   */
  private function instance($args = array()) {
    $args = array_merge(array('questionnaireName' => 'Sample Questionnaire',
                              'questionnaireVersion' => "3.0",
                              'revision' => 1,
                              'instanceName' => 'Sample Instance 2 for Questionnaire 2'),
    $args);
    return new InstanceModel($args);
  }
}
