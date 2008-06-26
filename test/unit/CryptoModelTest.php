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
class Test_Unit_CryptoModelTest extends RegQ_Test_Unit {

  /*
   * set up for each test
   */
  public function start() {
    $this->fixture(array('InstrumentModel', 'InstanceModel', 'DbUserModel', 'RoleModel'));
  }

  /*
   * test that calling 'new CryptoModel' returns a brand new
   * CryptoModel object
   */
  public function testNewProducucesNewCryptoModel() {
    $crypto = new CryptoModel(array('name' => 'test'));
    $this->assertTrue($crypto instanceof CryptoModel);
  }

  /*
   * test that calling 'CryptoModel::generateNewRijndael256Key' returns a brand new
   * CryptoModel object
   */
  public function testGenerateRijndael256KeyProducesNewCryptoModel() {
    $crypto = CryptoModel::generateNewRijndael256Key('GenerateRijndaelTest');
    $this->assertTrue($crypto instanceof CryptoModel);
  }

  /*
   * test that original text matches text after encryption/decryption
   */
  public function testTextMatchesAfterEncryptDecrypt() {
    $crypto = CryptoModel::generateNewRijndael256Key('GenerateRijndaelTest');
    $original = 'This is a test';
    $encrypted = $crypto->encrypt($original);
    $decrypted = $crypto->decrypt($encrypted);
    $this->assertEquals($original, $decrypted);
  }

  /*
   * test that attempting to decrypt text encrypted with a different key results 
   * in non-matching decrypted content
   */
  public function testDecryptingWithWrongKeyThrowsException() {
    $crypto1 = CryptoModel::generateNewRijndael256Key('GenerateRijndaelTest1');
    $crypto2 = CryptoModel::generateNewRijndael256Key('GenerateRijndaelTest2');
    $original = 'This is a test';
    $encrypted = $crypto1->encrypt($original);
    $decrypted = $crypto2->decrypt($encrypted);
    $this->assertNotEquals($original, $decrypted);
  }

  /*
   * test that getAllProfiles produces at least one CryptoModel object
   */
  public function testGetAllProfilesProducesCryptoModel() {
    $cryptos = CryptoModel::getAllProfiles();
    $this->assertTrue($cryptos[0] instanceof CryptoModel);
  }

  /*
   * test that multiple keys can't be generated with the same profile name
   */
  public function testDuplicateProfileNameProducesException() {
    CryptoModel::generateNewRijndael256Key('testdupe');
    try {
      CryptoModel::generateNewRijndael256Key('testdupe');
    }
    catch(Exception $e) { return; }
    $this->fail('Generating another key profile with the same name should throw an exception');
  }

  /*
   * test that multiple keys can't be imported with the same profile name
   */
  public function testDuplicateImportProfileNameProducesException() {
    CryptoModel::importRijndael256Key('testdupe', 'BqPwhnSskTFzxPUlljXG1zgG8cfgTSaGj8UyRWsKanA=');
    try {
      CryptoModel::importRijndael256Key('testdupe', 'IysK7xW5BloF+cvn8Jep6H6aal89/IjkluOgHQcPd4o=');
    }
    catch(Exception $e) { return; }
    $this->fail('Importing another key profile with the same name should throw an exception');
  }

  /*
   * test that an encrypted exported XML document can be imported without exception
   */
  public function testEncryptedXMLExportCanBeImported() {
    $this->auth();
    $crypto = CryptoModel::importRijndael256Key('testdupe', 'BqPwhnSskTFzxPUlljXG1zgG8cfgTSaGj8UyRWsKanA=');
    $instance = new InstanceModel(array('instanceID' => 1));
    $encrypted = $crypto->encrypt($instance->toXML(1));
    $decrypted = $crypto->decrypt($encrypted);
    InstanceModel::importXML($decrypted, 'test encryption import');
  } 

  /*
   * test that an encrypted exported Zip Archive can be imported without exception
   */
  public function testEncryptedZipArchiveExportCanBeImported() {
    $this->auth();
    $crypto = CryptoModel::importRijndael256Key('testdupe', 'BqPwhnSskTFzxPUlljXG1zgG8cfgTSaGj8UyRWsKanA=');
    $instance = new InstanceModel(array('instanceID' => 1));
    $zip = new ZipArchiveModel($instance, array('new' => '1'));
    $zip->addInstanceFullResponsesXMLDocument();
    $zip->close();
    $encrypted = $crypto->encrypt($zip->getZipFileContents());
    $zip->deleteZipFile();
    $decrypted = $crypto->decrypt($encrypted);
    $tempfile = tempnam(PROJECT_PATH . DIRECTORY_SEPARATOR . 'tmp', 'zip');
    unlink($tempfile);
    file_put_contents($tempfile, $decrypted);
    $zip = new ZipArchiveModel(null, array('filename' => $tempfile));
    InstanceModel::importXML($zip, 'test encryption import');
    $zip->deleteZipFile();
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

}
