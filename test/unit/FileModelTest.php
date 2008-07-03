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
class Test_Unit_FileModelTest extends QFrame_Test_Unit {
  
  /*
   * set up for each test
   */
  public function start() {
    $this->fixture(array('PageModel', 'SectionModel', 'QuestionModel', 'QuestionTypeModel', 'QuestionPromptModel', 'ResponseModel'));
  }
  
  public function end() {
    FileModel::clear();
  }
  
  /*
   * test that storing a file produces a non-null ID
   */
  public function testStoringFileProducesNonNullID() {
    $contents = file_get_contents(TEST_PATH . '/fixtures/sample.pdf');
    $files = new FileModel(new QuestionModel(array('questionID' => 1)));
    $id = $files->store($contents);
    $this->assertNotNull($id);
  }
  
  /*
   * test that storing a file and then fetching the ID that
   * comes back with the same file
   */
  public function testStoringAndFetchingProducesExpectedResult() {
    $contents = file_get_contents(TEST_PATH . '/fixtures/sample.pdf');
    $files = new FileModel(new QuestionModel(array('questionID' => 1)));
    $id = $files->store($contents);
    $responseContents = $files->fetch($id);
    $this->assertEquals($contents, $responseContents);
  }
  
  /*
   * test that trying to fetch a deleted file results in a null return
   * value
   */
  public function testFetchingDeletedFileProducesNull() {
    $contents = file_get_contents(TEST_PATH . '/fixtures/sample.pdf');
    $files = new FileModel(new QuestionModel(array('questionID' => 1)));
    $id = $files->store($contents);
    $this->assertTrue($files->delete($id));
    $this->assertNull($files->fetch($id));
  }
  
  /*
   * test that fetching all files for a particular object returns all
   * objects known to have been stored
   */
  public function testFetchAllWorksProperly() {
    $contents1 = file_get_contents(TEST_PATH . '/fixtures/sample.pdf');
    $contents2 = file_get_contents(TEST_PATH . '/fixtures/sample.doc');
    $files = new FileModel(new QuestionModel(array('questionID' => 1)));
    $ids = array();
    $ids[] = $files->store($contents1);
    $ids[] = $files->store($contents2);
    $this->assertEquals($ids, $files->fetchAll());
  }
  
  /*
   * test that storing a file by filename produces the same content
   * when fetching that reading the file would have done
   */
  public function testStoringByFilenameWorksProperly() {
    $contents = file_get_contents(TEST_PATH . '/fixtures/sample.pdf');
    $files = new FileModel(new QuestionModel(array('questionID' => 1)));
    $id = $files->storeFilename(TEST_PATH . '/fixtures/sample.pdf');
    $this->assertEquals($contents, $files->fetch($id));
  }
  
  /*
   * test that trying to store a non-existent filename produces an
   * Exception
   */
  public function testStoringNonExistentFileProducesException() {
    $files = new FileModel(new QuestionModel(array('questionID' => 1)));
    try {
      $files->storeFilename(TEST_PATH . '/fixtures/nonExistentFile.abcdefg');
    } catch(Exception $e) { return; }
    $this->fail('Attempting to store an invalid filename should produce an Exception');
  }
  
  /*
   * test that creating two files, deleting the first, then creating
   * a third still produces an ID of 3
   */
  public function testNoFileIDCollision() {
    $files = new FileModel(new QuestionModel(array('questionID' => 1)));
    $id = $files->storeFilename(TEST_PATH . '/fixtures/sample.pdf');
    $files->storeFilename(TEST_PATH . '/fixtures/sample.doc');
    $files->delete($id);
    $id = $files->storeFilename(TEST_PATH . '/fixtures/sample.pdf');
    $this->assertEquals($id, 3);    
  }
  
  /*
   * test that a properties array passed with a file will be stored
   * and returned when that file is fetched
   */
  public function testPropertiesArePreserved() {
    $properties = array(
      'filename'  => 'sample.pdf',
      'mime'      => 'application/pdf'
    );
    $files = new FileModel(new QuestionModel(array('questionID' => 1)));
    $id = $files->storeFilename(TEST_PATH . '/fixtures/sample.pdf', $properties);
    $result = $files->fetchWithProperties($id);
    $this->assertEquals($result['filename'], 'sample.pdf');
    $this->assertEquals($result['mime'], 'application/pdf');
  }
  
  /*
   * test that fetchAllProperties works as expected (returns a two element
   * two dimensional array of properties)
   */
  public function testFetchAllPropertiesWorks() {
    $properties1 = array(
      'filename'  => 'sample.pdf',
      'mime'      => 'application/pdf'
    );
    $properties2 = array(
      'filename'  => 'sample.doc',
      'mime'      => 'application/ms-word'
    );
    $files = new FileModel(new QuestionModel(array('questionID' => 1)));
    $id1 = $files->storeFilename(TEST_PATH . '/fixtures/sample.pdf', $properties1);
    $id2 = $files->storeFilename(TEST_PATH . '/fixtures/sample.doc', $properties2);
    $properties = $files->fetchAllProperties();
    $this->assertEquals(count($properties), 2);
    foreach($properties as $propertySet) $this->assertTrue(is_array($propertySet));
  }
  
  /*
   * test that removing a file removes its properties as well
   */
  public function testRemovingFileRemovesProperties() {
    $properties = array(
      'filename'  => 'sample.pdf',
      'mime'      => 'application/pdf'
    );
    $files = new FileModel(new QuestionModel(array('questionID' => 1)));
    $id = $files->storeFilename(TEST_PATH . '/fixtures/sample.pdf', $properties);
    $files->delete($id);
    $this->assertEquals(count($files->fetchAllProperties()), 0);
  }
  
  /*
   * test that instanceID is set when available
   */
  public function testInstanceIDIsSetWhenAvailable() {
    $properties = array(
      'filename'  => 'sample.pdf',
      'mime'      => 'application/pdf'
    );
    $files = new FileModel(new InstanceFoo);
    $id = $files->storeFilename(TEST_PATH . '/fixtures/sample.pdf', $properties);
    $properties = $files->fetchAllProperties();
    foreach($properties as $propertySet) $this->assertEquals($propertySet['instanceID'], 1);
  }
  
  /*
   * test that instanceID is set when available
   */
  public function testInstanceIDIsNullWhenNotAvailable() {
    $properties = array(
      'filename'  => 'sample.pdf',
      'mime'      => 'application/pdf'
    );
    $files = new FileModel(new NoInstanceFoo);
    $id = $files->storeFilename(TEST_PATH . '/fixtures/sample.pdf', $properties);
    $properties = $files->fetchAllProperties();
    foreach($properties as $propertySet) $this->assertNull($propertySet['instanceID']);
  }
  
  /*
   * test that deleteByInstance deletes all files associated with a particular instance
   */
  public function testDeleteByInstance() {
    $properties = array(
      'filename'  => 'sample.pdf',
      'mime'      => 'application/pdf'
    );
    $filesNoId = new FileModel(new NoInstanceFoo);
    $filesNoId->storeFilename(TEST_PATH . '/fixtures/sample.pdf', $properties);
    $filesId = new FileModel(new InstanceFoo);
    $filesId->storeFilename(TEST_PATH . '/fixtures/sample.pdf', $properties);
    FileModel::deleteByInstance(1);
    $this->assertEquals(count($filesId->fetchAll()), 0);
    $this->assertEquals(count($filesNoId->fetchAll()), 1);
  }
  
  /*
   * test that fetchObjectIdsByInstance returns an array object IDs arranged by object type
   * for only the objects that belong to the specified instance
   */
  public function testFetchObjectIdsByInstance() {
    $properties = array(
      'filename'  => 'sample.pdf',
      'mime'      => 'application/pdf'
    );
    $filesNoId = new FileModel(new NoInstanceFoo);
    $filesNoId->storeFilename(TEST_PATH . '/fixtures/sample.pdf', $properties);
    $filesId = new FileModel(new InstanceFoo);
    $filesId->storeFilename(TEST_PATH . '/fixtures/sample.pdf', $properties);
    $objectIds = FileModel::fetchObjectIdsByInstance(1);
    $this->assertTrue(is_array($objectIds));
    $this->assertEquals(count($objectIds), 1);
    $this->assertTrue(isset($objectIds['InstanceFoo']));
    $this->assertTrue(is_array($objectIds['InstanceFoo']));
    $this->assertEquals(count($objectIds['InstanceFoo']), 1);
  }
}

// Class the does implement isset and return a value for the property 'instanceID'
class InstanceFoo implements QFrame_Storer {
  public function getID() { return 1; }
  public function __get($property) { if($property == 'instanceID') return 1; }
  public function __isset($property) { if($property == 'instanceID') return true; }
}

// Class that does not
class NoInstanceFoo implements QFrame_Storer {
  public function getID() { return 1; }  
}
