<?php
/**
 * This file is part of the CSI RegQ.
 *
 * The CSI RegQ is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The CSI RegQ is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category   RegQ_Test
 * @package    RegQ_Test_Unit
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */


/**
 * PHPUnit_Framework
 */
require_once 'PHPUnit/Framework.php';


/**
 * @category   RegQ_Test
 * @package    RegQ_Test_Unit
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */
class Test_Unit_InstanceModelTest extends RegQ_Test_Unit {
  
  public function start() {
    $this->fixture(array('InstrumentModel', 'TabModel', 'SectionModel', 'DbUserModel', 'RoleModel'));
  }
  
  private function auth() {
    // perform mock authentication
    $auth_adapter = new RegQ_Auth_Adapter('sample1', 'password');
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
   * test that by default, instance loads to a depth of 'tab'
   */
  public function testDepthDefaultsToTab() {
    $this->auth();
    $instance = $this->instance();
    $this->assertNotNull($instance->nextTab());
    $this->assertNull($instance->getFirstTab()->nextSection());
  }
  
  /*
   * test that it is possible to set the load depth to something
   * other than the default of 'tab'
   */
  public function testDepthIsConfigurable() {
    $this->auth();
    $instance = $this->instance(array('depth' => 'section'));
    $this->assertNotNull($instance->getFirstTab()->nextSection());
  }
  
  /*
   * test that tabs are given in the proper order when using the nextTab
   * method of fetching one tab at a time
   */
  public function testProperOrderingOfTabs() {
    $this->auth();
    $instance = $this->instance();
    $lastTab = 0;
    while($tab = $instance->nextTab()) {
      $this->assertTrue($tab->seqNumber > $lastTab);
      $lastTab = $tab->seqNumber;
    }
  }
  
  /*
   * test that calling getTab() on an instance returns a tab when the
   * instance contains a tab with that ID
   */
  public function testGetTabPositive() {
    $this->auth();
    $instance = $this->instance();
    $this->assertNotNull($instance->getTab(1));
  }
  
  /*
   * test that calling getTab() on a tab that does not exist
   * results in an exception
   */
  public function testGetTabNegative() {
    $this->auth();
    $instance = $this->instance();
    try {
      $instance->getTab(4);
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
    $tab = $instance->nextTab();
    $this->assertNotNull($tab->parent->instrumentName);
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

    $xml = file_get_contents(PROJECT_PATH . "/test/data/xml/test1-instrument-definition.xml");
    InstrumentModel::importXML($xml);
    InstanceModel::importXML($xml, 'Test1 Company');
    $instance = new InstanceModel(array('instrumentName' => 'Test1 Instrument',
                                        'instrumentVersion' => '3.00',
                                        'revision' => 1,
                                        'instanceName' => 'Test1 Company'));
    $xmlExport = $instance->toXML(1);
    InstrumentModel::importXML($xmlExport);
    InstanceModel::importXML($xmlExport, 'Test1 Copy Company');
    $instanceCopy = new InstanceModel(array('instrumentName' => 'Test1 Instrument',
                                            'instrumentVersion' => '3.00',
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

    $xml = file_get_contents(PROJECT_PATH . "/test/data/xml/test1-instrument-definition.xml");
    InstrumentModel::importXML($xml);
    InstanceModel::importXML($xml, 'Test1 Company');
    $instance = new InstanceModel(array('instrumentName' => 'Test1 Instrument',
                                        'instrumentVersion' => '3.00',
                                        'revision' => 1,
                                        'instanceName' => 'Test1 Company',
                                        'depth' => 'question'));
    while($tab = $instance->nextTab()) {
      while ($section = $tab->nextSection()) {
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

    InstrumentModel::importXML($zip);
    $instanceID = InstanceModel::importXML($zip, 'Test1 Company', array('tabResponses' => array('all' => 1)));
    $instance = new InstanceModel(array('instanceID' => $instanceID,
                                        'depth' => 'instance'));
    $this->assertEquals($zip->getInstanceFullResponsesXMLDocument(), $instance->toXML(1));
  }
  
  /*
   * test that an instance with no responses merged with another instance's responses of the same instrument
   * have equal XML exports except for the instanceName and responseDates
   */
  public function testNoResponsesInstanceWithMergedResponsesExportEqualsResponsesInstanceExport() {
    $this->auth();

    $xml = file_get_contents(PROJECT_PATH . "/test/data/xml/responses-instrument-definition.xml");

    InstrumentModel::importXML($xml);
    InstanceModel::importXML($xml, 'Test1 Resp. Company', array('tabResponses' => array('all' => 1)));
    $instance1 = new InstanceModel(array('instrumentName' => 'Test1 Instrument',
                                         'instrumentVersion' => '3.00',
                                         'revision' => 1,
                                         'instanceName' => 'Test1 Resp. Company'));
    $xml = file_get_contents(PROJECT_PATH . "/test/data/xml/no-responses-instrument-definition.xml");
    InstanceModel::importXML($xml, 'Test1 Company', array('instanceID' => $instance1->instanceID));
    $instance2 = new InstanceModel(array('instrumentName' => 'Test1 Instrument',
                                         'instrumentVersion' => '3.00',
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
    $args = array_merge(array('instrumentName' => 'Sample Instrument',
                              'instrumentVersion' => "3.0",
                              'revision' => 1,
                              'instanceName' => 'Sample Instance 2 for Instrument 2'),
    $args);
    return new InstanceModel($args);
  }
}
