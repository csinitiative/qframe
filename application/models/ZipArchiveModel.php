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
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */

/**
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */
class ZipArchiveModel extends ZipArchive {

  /**
   * Object that is used to find attachments related to an instance
   * @var InstanceModel
   */
  private $instance;
  
  /**
   * Filename of the zip archive
   * @var string
   */
  private $zipFileName;

  /**
   * Create a new ZipArchiveModel with a certain instance object
   *
   * @param InstanceModel object to use with this zip archive model object
   * @param Arguments for determining whether to create a new zip archive or
   * load an existing zip archive
   */
  function __construct ($instance, $args = array()) {

    if (!is_a($instance, 'InstanceModel') && $instance !== null) {
      throw new InvalidArgumentException('Instance argument to ZipArchiveModel is invalid');
    }
    
    $this->instance = $instance;
  
    if (isset($args['new']) && $args['new']) {
      $this->zipFileName = tempnam(PROJECT_PATH . DIRECTORY_SEPARATOR . 'tmp', 'zip');
      if ($this->zipFileName === FALSE) {
        throw new Exception('Could not create temporary file for zip');
      }
      unlink($this->zipFileName);
      $res = $this->open($this->zipFileName, ZipArchive::CREATE);
      if ($res !== TRUE) {
        throw new Exception('Could not open zip file [' . $this->zipFileName . ']: ' . $res);
      }
    }
    elseif (isset($args['filename'])) {
      $this->zipFileName = $args['filename'];
      $res = $this->open($args['filename']);
      if ($res !== TRUE) {
        throw new Exception('Could not open zip file [' . $this->zipFileName . ']: ' . $res);
      }
    }
    else {
      throw new InvalidArgumentException('Missing argument to ZipArchiveModel constructor');
    }
    
  }
  
  /**
   * Gets the name of the zip archive filename
   * 
   * @return string
   */
  public function getZipFileName() {
    return $this->zipFileName;
  }
  
  /**
   * Gets the raw contents of the zip archive
   * 
   * @return string
   */
  public function getZipFileContents() {
    return file_get_contents($this->zipFileName);
  }

  /**
   * Gets the raw contents of the Questionnaire Definition XML Document
   * 
   * @return string
   */
  public function getQuestionnaireDefinitionXMLDocument() {
    $string = $this->getFromName('xml/questionnaire-definition.xml');
    if ($string === FALSE) return;
    return $string;
  }
  
  /**
   * Gets the raw contents of the Questionnaire Responses XML Schema
   * 
   * @return string
   */
  public function getQuestionnaireResponsesXMLSchema() {
    $string = $this->getFromName('xml/response-schema.xsd');
    if ($string === FALSE) return;
    return $string;
  }
  
  /**
   * Gets the raw contents of the Completed Questionnaire Responses XML Schema
   * 
   * @return string
   */
  public function getQuestionnaireCompletedResponsesXMLSchema() {
    $string = $this->getFromName('xml/completed-response-schema.xsd');
    if ($string === FALSE) return;
    return $string;
  }
  
  /**
   * Gets the raw contents of the Instance Responses XML Document
   * 
   * @return string
   */
  public function getInstanceResponsesXMLDocument() {
    $string = $this->getFromName('xml/instance-responses.xml');
    if ($string === FALSE) return;
    return $string;
  }
  
  /**
   * Gets the raw contents of the Instance Full Responses XML Document
   * 
   * @return string
   */
  public function getInstanceFullResponsesXMLDocument() {
    $string = $this->getFromName('xml/instance-responses.xml');
    if ($string === FALSE) return;
    return $string;
  }

  /**
   * Adds the Questionnaire Definition XML Document to the zip archive
   */
  public function addQuestionnaireDefinitionXMLDocument() {
    $this->addFromString('xml/questionnaire-definition.xml', $this->instance->parent->fetchQuestionnaireDefinition());
  }

  /**
   * Adds the Questionnaire Responses XML Schema to the zip archive
   */  
  public function addQuestionnaireResponsesXMLSchema() {
    $this->addFromString('xml/response-schema.xsd', $this->instance->parent->fetchResponseSchema());
  }
  
  /**
   * Adds the Questionnaire Completed Responses XML Schema to the zip archive
   */
  public function addQuestionnaireCompletedResponsesXMLSchema() {
    $this->addFromString('xml/completed-response-schema.xsd', $this->instance->parent->fetchResponseSchema());
  }
  
  /**
   * Adds the Instance Responses XML Document to the zip archive
   */
  public function addInstanceResponsesXMLDocument() {
    $this->addFromString('xml/instance-responses.xml', $this->instance->toXML());
  }
  
  /**
   * Adds the Instance Full Responses XML Document
   */
  public function addInstanceFullResponsesXMLDocument() {
    $this->addFromString('xml/instance-responses.xml', $this->instance->toXML(1));
  }
  
  /**
   * Adds all attachments associated with the instance to the zip archive
   */
  public function addAttachments() {
    $instance = new InstanceModel(array('instanceID' => $this->instance->instanceID,
                                        'depth' => 'question'));
    while($page = $instance->nextPage()) {
      while ($section = $page->nextSection()) {
        while ($question = $section->nextQuestion()) {
          $fileObj = new FileModel($question);
          $ids = $fileObj->fetchAll();
          if ($ids === NULL) continue;
          foreach ($ids as $id) {
            $file = $fileObj->fetchWithProperties($id);
            $this->addFromString("files/{$id}", $file['contents']);
          }
        }
      }
    }
  }
  
  /**
   * Deletes the zip archive on disk
   */
  public function deleteZipFile() {
    unlink($this->zipFileName);
  }
  
}
