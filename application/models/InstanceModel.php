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
 * along wit. $this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */

/**
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */
class InstanceModel extends RegQ_Db_SerializableTransaction implements RegQ_Storer {

  private $instrumentRow;
  private $instanceRow;
  private $tabs;
  private $tabsIndex;
  private $depth;
  static $instrumentTable;
  static $instanceTable;
  static $tabTable;
  static $questionTable;
  static $ruleTable;
  static $questionTypeTable;
  static $questionPromptTable;
  static $questionReferenceTable;
  static $referenceTable;
  static $referenceDetailTable;
  static $responseTable;
  static $sectionReferenceTable;
  static $tabReferenceTable;
  static $sectionTable;

  /**
   * Create a new InstanceModel object
   *
   * @param array containing instanceID or instrumentName,instrumentVersion,revision,instanceName
   */
  function __construct ($args = array()) {

    $args = array_merge(array(
      'depth'   => 'tab'
    ), $args);

    if (!isset(self::$instrumentTable)) self::$instrumentTable = RegQ_Db_Table::getTable('instrument');
    if (!isset(self::$instanceTable)) self::$instanceTable = RegQ_Db_Table::getTable('instance');
    if (!isset(self::$tabTable)) self::$tabTable = RegQ_Db_Table::getTable('tab');
    if (!isset(self::$questionTable)) self::$questionTable = RegQ_Db_Table::getTable('question');
    if (!isset(self::$ruleTable)) self::$ruleTable = RegQ_Db_Table::getTable('rule');
    if (!isset(self::$questionTypeTable)) self::$questionTypeTable = RegQ_Db_Table::getTable('questionType');
    if (!isset(self::$questionPromptTable)) self::$questionPromptTable = RegQ_Db_Table::getTable('questionPrompt');
    if (!isset(self::$questionReferenceTable)) self::$questionReferenceTable = RegQ_Db_Table::getTable('questionReference');
    if (!isset(self::$referenceTable)) self::$referenceTable = RegQ_Db_Table::getTable('reference');
    if (!isset(self::$referenceDetailTable)) self::$referenceDetailTable = RegQ_Db_Table::getTable('referenceDetail');
    if (!isset(self::$responseTable)) self::$responseTable = RegQ_Db_Table::getTable('response');
    if (!isset(self::$sectionReferenceTable)) self::$sectionReferenceTable = RegQ_Db_Table::getTable('sectionReference');
    if (!isset(self::$tabReferenceTable)) self::$tabReferenceTable = RegQ_Db_Table::getTable('tabReference');
    if (!isset(self::$sectionTable)) self::$sectionTable = RegQ_Db_Table::getTable('section');

    if (isset($args['instanceID'])) {
      $where = self::$instanceTable->getAdapter()->quoteInto('instanceID = ?', $args['instanceID']);
      $this->instanceRow = self::$instanceTable->fetchRow($where);
      // instance row assertion
      if (!isset($this->instanceRow)) {
        throw new Exception('Instance not found [' . $args['instanceID'] . ']');
      }
      $this->parent = new InstrumentModel(array('instrumentID' => $this->instanceRow->instrumentID,
                                                'depth' => 'instrument'));
    }
    elseif (isset($args['instrumentName']) && isset($args['instrumentVersion']) && isset($args['revision']) && isset($args['instanceName'])) {
      $this->parent = new InstrumentModel(array('instrumentName' => $args['instrumentName'],
                                                'instrumentVersion' => $args['instrumentVersion'],
                                                'revision' => $args['revision'],
                                                'depth' => 'instrument'));
      $where = self::$instanceTable->getAdapter()->quoteInto('instrumentID = ?', $this->parent->instrumentID);
      $where .= self::$instanceTable->getAdapter()->quoteInto(' AND instanceName = ?', $args['instanceName']);
      $this->instanceRow = self::$instanceTable->fetchRow($where);
      // instance row assertion
      if (!isset($this->instanceRow)) {
        throw new Exception('Instance not found');
      }
    }
    else {
      throw new InvalidArgumentException('Missing arguments to InstanceModel constructor');
    }
        
    if ($args['depth'] !== 'instance') {
      $this->depth = $args['depth'];
      $this->_loadTabs();
    }
    
  }

  /**
   * Allows user to get any of the properties of an InstanceModel
   *
   * @param  string the property being requested
   * @return mixed
   */
  public function __get($key) {
    if (isset($this->instanceRow->$key)) {
      return $this->instanceRow->$key;
    }
 
    return $this->parent->$key;
   }

  /**
   * Saves InstanceModel data to the database and children objects as specified by depth
   */
  public function save() {

    if (count($this->tabs)) {
      foreach ($this->tabs as $tab) {
        $tab->save();
      }
    }

    $this->instanceRow->numQuestions = $this->getNumQuestions();
    $this->instanceRow->numComplete = $this->getNumQuestionsComplete();
    $this->instanceRow->numApproved = $this->getNumQuestionsApproved();
    $this->instanceRow->save();
    
    if ($this->depth !== 'instance') $this->_loadTabs();
  }

  /**
   * Returns the next TabModel associated with this InstanceModel
   *
   * @return TabModel Returns null if there are no further tabs
   */
  public function nextTab() {
    $nextTab = each($this->tabs);
    if(!$nextTab) return;

    return $nextTab['value'];
  }

  /**
   * Returns a TabModel with a specific id
   *
   * @param  integer tabID of the wanted TabModel
   * @return TabModel
   */
  public function getTab($id) {
    foreach($this->tabs as $tab) {
      if($tab->tabID == $id) return $tab;
    }
    throw new Exception('No tab was found with the specified tabID [' . $id . ']');
  }

  /**
   * Returns the first TabModel associated with this InstanceModel
   *
   * @return TabModel
   */
  public function getFirstTab() {
    return current(array_slice($this->tabs, 0, 1));
  }
  
  /**
   * Returns the last TabModel associated with this InstanceModel
   *
   * @return TabModel
   */
  public function getLastTab() {
    return current(array_slice($this->tabs, -1, 1));
  }
  
  /**
   * Exports an Instance XML Document
   *
   * @param  integer If true, exports the entire instrument definition (question text, etc). 
   *                 If false, only exports guid and response data.
   * @return string XML Document
   */
  public function toXML($complete = 0) {
    $instanceID = $this->instanceRow->instanceID;

    RegQ_Db_Table::resetAll();

    RegQ_Db_Table::preloadAll($instanceID, null);

    $instance = new InstanceModel(array('instanceID' => $instanceID,
                                        'depth' => 'section'));
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<csi:instrument';

    $xml .= ' xmlns:csi="http://www.csinitiative.com/ns/csi-regq"';

    $xml .= ' instrumentName="' . self::_xmlentities($instance->instrumentName) . 
            '" instrumentVersion="' . self::_xmlentities($instance->instrumentVersion) . 
            '" revision="' . self::_xmlentities($instance->revision) . 
            '" targetRegQVersion="' . REGQ_VERSION . 
            '" instanceName="' . self::_xmlentities($instance->instanceName) . '">' . "\n";
    $xml .= "  <csi:tabs>\n";
    while ($tab = $instance->nextTab()) {
      $xml .= "    <csi:tab>\n";
      if ($complete) {
        $xml .= "      <csi:tabHeader>" . self::_xmlentities($tab->tabHeader) . "</csi:tabHeader>\n";
      }
      $xml .= "      <csi:tabGUID>" . self::_xmlentities($tab->tabGUID) . "</csi:tabGUID>\n";
      $xml .= "      <csi:seqNumber>" . self::_xmlentities($tab->seqNumber) . "</csi:seqNumber>\n";
      if ($complete) {
        $xml .= "      <csi:description>" . self::_xmlentities($tab->description) . "</csi:description>\n";
        $xml .= "      <csi:headerText>" . self::_xmlentities($tab->headerText) . "</csi:headerText>\n";
        $xml .= "      <csi:footerText>" . self::_xmlentities($tab->footerText) . "</csi:footerText>\n";
        $xml .= "      <csi:cloneable>" . self::_xmlentities($tab->cloneable) . "</csi:cloneable>\n";
        $xml .= "      <csi:defaultTabHidden>" . self::_xmlentities($tab->defaultTabHidden) . "</csi:defaultTabHidden>\n";
      }
      $xml .= "      <csi:sections>\n";
      while ($s = $tab->nextSection()) {
        $section = new SectionModel(array('sectionID' => $s->sectionID, 'depth' => 'response'));
        $xml .= "        <csi:section>\n";
        if ($complete) {
          $xml .= '          <csi:sectionHeader>' . self::_xmlentities($section->sectionHeader) . "</csi:sectionHeader>\n";
        }
        $xml .= '          <csi:sectionGUID>' . self::_xmlentities($section->sectionGUID) . "</csi:sectionGUID>\n";
        $xml .= '          <csi:seqNumber>' . self::_xmlentities($section->seqNumber) . "</csi:seqNumber>\n";
        if ($complete) {
          $xml .= '          <csi:description>' . self::_xmlentities($section->description) . "</csi:description>\n";
          $xml .= '          <csi:cloneable>' . self::_xmlentities($section->cloneable) . "</csi:cloneable>\n";
          $xml .= '          <csi:defaultSectionHidden>' . self::_xmlentities($section->defaultSectionHidden) . "</csi:defaultSectionHidden>\n";
          $references = $section->references;
          if (count($references) > 0) {
            $xml .= "          <csi:sectionReferences>";
            foreach ($references as $reference) {
              $xml .= "            <csi:reference>";
              $xml .= '              <csi:shortName>' . self::_xmlentities($reference['shortName']) . "</csi:shortName>\n";
              $xml .= '              <csi:referenceName>' . self::_xmlentities($reference['referenceName']) . "</csi:referenceName>\n";
              $xml .= '              <csi:item>' . self::_xmlentities($reference['item']) . "</csi:item>\n";
              $xml .= '              <csi:referenceURL>' . self::_xmlentities($reference['referenceURL']) . "</csi:referenceURL>\n";
              $xml .= '              <csi:referenceText>' . self::_xmlentities($reference['referenceText']) . "</csi:referenceText>\n";
              $xml .= "</csi:reference>\n";
            }
            $xml .= "</csi:sectionReferences>\n";
          }
        }
        $xml .= "          <csi:questions>\n";
        while ($question = $section->nextQuestion()) {
          $padding = '';
          if (count($question->children)) {
            $xml .= "            <csi:questionGroup>\n";
            if ($complete) {
              $xml .= "              <csi:qText>" . self::_xmlentities($question->qText) . "</csi:qText>\n";
            }
            $xml .= "              <csi:questionGUID>" . self::_xmlentities($question->questionGUID) . "</csi:questionGUID>\n";
            $xml .= "              <csi:seqNumber>" . self::_xmlentities($question->seqNumber) . "</csi:seqNumber>\n";
            if ($complete && strlen(self::_xmlentities($question->questionNumber))) {
              $xml .= "              <csi:groupQuestionNumber>" . self::_xmlentities($question->questionNumber) . "</csi:groupQuestionNumber>\n";
            }
            $xml .= "              <csi:cloneable>" . self::_xmlentities($question->cloneable) . "</csi:cloneable>\n";
            $xml .= "              <csi:groupDefaultQuestionHidden>" . self::_xmlentities($question->defaultQuestionHidden) . "</csi:groupDefaultQuestionHidden>\n";
            $references = $question->references;
            if (count($references) > 0) {
              $xml .= "              <csi:groupQuestionReferences>";
              foreach ($references as $reference) {
                $xml .= "              <csi:reference>\n";
                $xml .= "                <csi:shortName>" . self::_xmlentities($reference['shortName']) . "</csi:shortName>\n";
                $xml .= "                <csi:referenceName>" . self::_xmlentities($reference['referenceName']) . "</csi:referenceName>\n";
                $xml .= "                <csi:item>" . self::_xmlentities($reference['item']) . "</csi:item>\n";
                $xml .= "                <csi:referenceURL>" . self::_xmlentities($reference['referenceURL']) . "</csi:referenceURL>\n";
                $xml .= "                <csi:referenceText>" . self::_xmlentities($reference['referenceText']) . "</csi:referenceText>\n";
                $xml .= "              </csi:reference>\n";
              }
              $xml .= "</csi:groupQuestionReferences>\n";
            }
            $fileObj = new FileModel($question);
            $ids = $fileObj->fetchAll();
            if (count($ids)) {
              $xml .= "              <csi:attachments>\n";
              foreach ($ids as $id) {
                $xml .= "                <csi:attachment>\n";
                $file = $fileObj->fetchProperties($id);
                $xml .= "                  <csi:filename>" . self::_xmlentities($file['filename']) . "</csi:filename>\n";
                $xml .= "                  <csi:mime>" . self::_xmlentities($file['mime']) . "</csi:mime>\n";
                $xml .= "                  <csi:location>files/{$id}</csi:location>\n";
                $xml .= "                </csi:attachment>\n";
              }
              $xml .= "              </csi:attachments>\n";
            }
            $padding = '  ';
            $questions = $question->children;
          }
          else {
            $questions = array($question);
          }
          foreach ($questions as $question) {
            $xml .= "$padding            <csi:question>\n";
            if ($question->virtualQuestion) {
              $xml .= "$padding              <csi:questionGUID>" . self::_xmlentities($question->questionGUID) . "</csi:questionGUID>\n";
              $xml .= "$padding              <csi:seqNumber>" . self::_xmlentities($question->seqNumber) . "</csi:seqNumber>\n";
              $xml .= "$padding              <csi:questionType>V</csi:questionType>\n";
            }
            else {
              if ($complete) {
                $xml .= "$padding              <csi:qText>" . self::_xmlentities($question->qText) . "</csi:qText>\n";
              }
              $xml .= "$padding              <csi:questionGUID>" . self::_xmlentities($question->questionGUID) . "</csi:questionGUID>\n";
              $xml .= "$padding              <csi:seqNumber>" . self::_xmlentities($question->seqNumber) . "</csi:seqNumber>\n";
              $prompts = $question->prompts;
              if ($complete) {
                if (strlen(self::_xmlentities($question->questionNumber))) {
                  $xml .= "$padding              <csi:questionNumber>" . self::_xmlentities($question->questionNumber) . "</csi:questionNumber>\n";
                }
                if (!$padding) {
                  $xml .= "$padding              <csi:cloneable>" . self::_xmlentities($question->cloneable) . "</csi:cloneable>\n";
                }
                $xml .= "$padding              <csi:defaultQuestionHidden>" . self::_xmlentities($question->defaultQuestionHidden) . "</csi:defaultQuestionHidden>\n";
                $references = $question->references;
                if (count($references) > 0) {
                  $xml .= "$padding              <csi:questionReferences>";
                  foreach ($references as $reference) {
                    $xml .= "$padding              <csi:reference>\n";
                    $xml .= "$padding                <csi:shortName>" . self::_xmlentities($reference['shortName']) . "</csi:shortName>\n";
                    $xml .= "$padding                <csi:referenceName>" . self::_xmlentities($reference['referenceName']) . "</csi:referenceName>\n";
                    $xml .= "$padding                <csi:item>" . self::_xmlentities($reference['item']) . "</csi:item>\n";
                    $xml .= "$padding                <csi:referenceURL>" . self::_xmlentities($reference['referenceURL']) . "</csi:referenceURL>\n";
                    $xml .= "$padding                <csi:referenceText>" . self::_xmlentities($reference['referenceText']) . "</csi:referenceText>\n";
                    $xml .= "$padding              </csi:reference>\n";
                  }
                  $xml .= "</csi:questionReferences>\n";
                }
                $xml .= "$padding              <csi:questionType>" . self::_xmlentities($question->format) . "</csi:questionType>\n";
                foreach ($prompts as $prompt) {
                  $xml .= "$padding              <csi:questionPrompt>\n";
                  $xml .= "$padding                <csi:promptText>" . self::_xmlentities($prompt['value']) . "</csi:promptText>\n";
                  $xml .= "$padding                <csi:requireAdditionalInfo>" . self::_xmlentities($prompt['requireAddlInfo']) . "</csi:requireAdditionalInfo>\n";
                  foreach (array('enableTab', 'enableSection', 'enableQuestion', 'disableTab', 'disableSection', 'disableQuestion') as $t) {
                    foreach ($prompt['rules'] as $rule) {
                      $type = $rule->type;
                      if ($type === $t) {
                        $xml .= "$padding                <csi:$type>" . self::_xmlentities($rule->targetGUID) . "</csi:$type>\n";
                      }
                    }
                  }
                  $xml .= "$padding              </csi:questionPrompt>\n";
                }
              }
              $first = true;
              while ($response = $question->nextResponse()) {
                if ($first === true) {
                  $xml .= "$padding              <csi:responses>\n";
                  $xml .= "$padding                <csi:state>" . self::_xmlentities($response->state) . "</csi:state>\n";
                  $xml .= "$padding                <csi:additionalInfo>" . self::_xmlentities($response->additionalInfo) . "</csi:additionalInfo>\n";
                  $xml .= "$padding                <csi:approverComments>" . self::_xmlentities($response->approverComments) . "</csi:approverComments>\n";
                }
                
                $responseDate = $response->responseDate;
                $responseDate = preg_replace('/ /', 'T', $responseDate);
                if (count($prompts) > 0) {
                  $rTexts = explode(',', $response->responseText);
                  foreach ($prompts as $prompt) {
                    foreach ($rTexts as $r) {
                      if ($r === $prompt['promptID']) {
                        $xml .= "$padding                <csi:response>\n";
                        $xml .= "$padding                  <csi:responseDate>" . self::_xmlentities($responseDate) . "</csi:responseDate>\n";
                        $xml .= "$padding                  <csi:responseText>" . self::_xmlentities($prompt['value']) . "</csi:responseText>\n";
                        $xml .= "$padding                </csi:response>\n";
                      }
                    }
                  }
                }
                else {
                  $xml .= "$padding                <csi:response>\n";
                  $xml .= "$padding                  <csi:responseDate>" . self::_xmlentities($responseDate) . "</csi:responseDate>\n";
                  $xml .= "$padding                  <csi:responseText>" . self::_xmlentities($response->responseText) . "</csi:responseText>\n";
                  $xml .= "$padding                </csi:response>\n";
                }
                if ($first === true) {
                  $first = false;
                }
              }
              if ($first === false) {
               $xml .= "$padding              </csi:responses>\n";
              }
            }
            $fileObj = new FileModel($question);
            $ids = $fileObj->fetchAll();
            if (count($ids)) {
              $xml .= "$padding              <csi:attachments>\n";
              foreach ($ids as $id) {
                $xml .= "$padding                <csi:attachment>\n";
                $file = $fileObj->fetchProperties($id);
                $xml .= "$padding                  <csi:filename>" . self::_xmlentities($file['filename']) . "</csi:filename>\n";
                $xml .= "$padding                  <csi:mime>" . self::_xmlentities($file['mime']) . "</csi:mime>\n";
                $xml .= "$padding                  <csi:location>files/{$id}</csi:location>\n";
                $xml .= "$padding                </csi:attachment>\n";
              }
              $xml .= "$padding              </csi:attachments>\n";
            }
            $xml .= "$padding            </csi:question>" . "\n";
          }
          if ($padding) {
            $xml .= "            </csi:questionGroup>\n";
          }
        }
        $xml .= "          </csi:questions>\n";
        $xml .= "        </csi:section>\n";
      }
      $xml .= "      </csi:sections>\n";
      $xml .= "    </csi:tab>\n";
    }
    $xml .= "  </csi:tabs>\n";
    $xml .= "</csi:instrument>\n";

    RegQ_Db_Table::resetAll();

    return $xml;

  }

  /**
   * Validates the Full Responses XML against the Responses XML Schema
   */
  public function validateFullResponseXML() {
    
    $xml = new DOMDocument();
    $xml->loadXML($this->toXML(1));

    if (!$xml->schemaValidateSource($this->parent->fetchResponseSchema())) {
      $errors = libxml_get_errors();
      if (count($errors)) {
        throw new Exception('XML error on line ' . $errors[0]->line . ': ' . $errors[0]->message);
      }
    }

  }
  
  /**
   * Validates the Responses-Only XML against the Responses XML Schema
   */
  public function validateResponseXML() {
    
    $xml = new DOMDocument();
    $xml->loadXML($this->toXML());

    if (!$xml->schemaValidateSource($this->parent->fetchResponseSchema())) {
      $errors = libxml_get_errors();
      if (count($errors)) {
        throw new Exception('XML error on line ' . $errors[0]->line . ': ' . $errors[0]->message);
      }
    }

  }
  
  /**
   * Validates the Full Responses XML against the Completed Responses XML Schema
   */
  public function validateFullCompletedResponseXML() {
    
    $xml = new DOMDocument();
    $xml->loadXML($this->toXML(1));

    if (!$xml->schemaValidateSource($this->parent->fetchCompletedResponseSchema())) {
      $errors = libxml_get_errors();
      if (count($errors)) {
        throw new Exception('XML error on line ' . $errors[0]->line . ': ' . $errors[0]->message);
      }
    }

  }
  
  /**
   * Validates the Responses-Only XML against the Completed Responses XML Schema
   */
  public function validateCompletedResponseXML() {
    
    $xml = new DOMDocument();
    $xml->loadXML($this->toXML());

    if (!$xml->schemaValidateSource($this->parent->fetchCompletedResponseSchema())) {
      $errors = libxml_get_errors();
      if (count($errors)) {
        throw new Exception('XML error on line ' . $errors[0]->line . ': ' . $errors[0]->message);
      }
    }

  }
  
  /**
   * Returns an ID that is guaranteed to be unique among objects of type InstanceModel
   *
   * @return integer
   */
  public function getID() {
    return $this->instanceID;
  }

  /**
   * Returns the number of questions for this tab
   *
   * @return integer
   */
  public function getNumQuestions() {
    $questionGroupTypeID = self::$questionTypeTable->getQuestionTypeID($this->instanceRow->instanceID, '_questionGroup');
    $virtualQuestionTypeID = self::$questionTypeTable->getQuestionTypeID($this->instanceRow->instanceID, 'V');
    if (!isset($virtualQuestionTypeID)) $virtualQuestionTypeID = -1;
    if (!isset($questionGroupTypeID)) $questionGroupTypeID = -1;
    $select = self::$instanceTable->getAdapter()->select()
            ->from(array('q' => 'question'), array('COUNT(*) as tally'))
            ->where('q.instanceID = ?', $this->instanceRow->instanceID)
            ->where('q.questionTypeID != ?', $questionGroupTypeID)
            ->where('q.questionTypeID != ?', $virtualQuestionTypeID);
    $stmt = self::$instanceTable->getAdapter()->query($select);
    $result = $stmt->fetchAll();
    return $result[0]['tally'];
  }

  /**
   * Returns the number of approved questions for this tab
   *
   * @return integer
   */
  public function getNumQuestionsApproved() {
    $questionGroupTypeID = self::$questionTypeTable->getQuestionTypeID($this->instanceRow->instanceID, '_questionGroup');
    $virtualQuestionTypeID = self::$questionTypeTable->getQuestionTypeID($this->instanceRow->instanceID, 'V');
    if (!isset($virtualQuestionTypeID)) $virtualQuestionTypeID = -1;
    if (!isset($questionGroupTypeID)) $questionGroupTypeID = -1;
    $select = 'SELECT COUNT(q.questionID) AS tally FROM question AS q INNER JOIN ' .
        '(SELECT DISTINCT questionID FROM response WHERE state = 2 AND ISNULL(responseEndDate)) ' .
        'AS r WHERE q.instanceID = ? AND q.questionTypeID != ? AND q.questionTypeID != ? AND ' .
        'q.questionID = r.questionID';
    $bindVars = array(
      $this->instanceRow->instanceID,
      $questionGroupTypeID,
      $virtualQuestionTypeID
    );
    $stmt = self::$instanceTable->getAdapter()->query($select, $bindVars);
    $result = $stmt->fetchAll();
    return $result[0]['tally'];
  }

  /**
   * Returns the number of questions for this tab that are complete (answered)
   *
   * @return integer
   */
  public function getNumQuestionsComplete() {
    $questionGroupTypeID = self::$questionTypeTable->getQuestionTypeID($this->instanceRow->instanceID, '_questionGroup');
    $virtualQuestionTypeID = self::$questionTypeTable->getQuestionTypeID($this->instanceRow->instanceID, 'V');
    if (!isset($virtualQuestionTypeID)) $virtualQuestionTypeID = -1;
    if (!isset($questionGroupTypeID)) $questionGroupTypeID = -1;

    $count = 0;    
    
    $stmt = self::$instanceTable->getAdapter()->query('SELECT COUNT(*) AS tally FROM question AS ' .
        'q WHERE q.disableCount > 0 AND q.instanceID = ? AND q.questionTypeID != ? AND ' . 
        'q.questionTypeID != ?',
      array($this->instanceRow->instanceID, $questionGroupTypeID, $virtualQuestionTypeID)
    );
    $result = $stmt->fetchAll();
    $count += $result[0]['tally'];

    $stmt = self::$instanceTable->getAdapter()->query('SELECT q.questionID FROM question AS q, ' .
        'questionType AS qt, questionPrompt as qp, response as r WHERE ' .
        'q.questionTypeID = qt.questionTypeID AND qt.questionTypeID = qp.questionTypeID AND ' .
        'q.questionID = r.questionID AND requireAddlInfo = 1 AND ISNULL(r.additionalInfo) AND ' .
        'ISNULL(r.responseEndDate) AND r.responseText = qp.promptID AND q.instanceID = ?',
      array($this->instanceRow->instanceID)
    );
    $missingAddlInfoRows = $stmt->fetchAll();

    $select = 'SELECT COUNT(q.questionID) as tally FROM question AS q INNER JOIN ' .
        "(SELECT DISTINCT questionID FROM response WHERE responseText != '' AND " .
        'ISNULL(responseEndDate)) AS r WHERE q.instanceID = ? AND q.questionTypeID != ? AND ' . 
        'q.questionTypeID != ? AND q.questionID = r.questionID AND q.disableCount = 0';
    $bindVars = array(
      $this->instanceRow->instanceID,
      $questionGroupTypeID,
      $virtualQuestionTypeID
    );          
    foreach ($missingAddlInfoRows as $r) {
      $select .= ' AND q.questionID != ?';
      $bindVars[] = $r['questionID'];
    }
    $stmt = self::$instanceTable->getAdapter()->query($select, $bindVars);
    $result = $stmt->fetchAll();
    $count += $result[0]['tally'];

    return $count;
  }
  
  /**
   * Returns the completion % of questions in this instrument
   *
   * @return float
   */
  public function getPctComplete() {
    if($this->numQuestions == 0) return '100';
    return round(($this->numComplete / $this->numQuestions) * 100, 2);
  }
  
  /**
   * Returns the completion % of questions in this instrument
   *
   * @return float
   */
  public function getPctApproved() {
    if($this->numQuestions == 0) return '100';
    return round(($this->numApproved / $this->numQuestions) * 100, 2);
  }
  
  /**
   * Deletes this instance
   */
  public function delete() {
    $where = self::$instanceTable->getAdapter()->quoteInto('instanceID = ?', $this->instanceID);
    $transactionNumber = self::startSerializableTransaction();
    self::$instanceTable->delete($where);
    self::$tabTable->delete($where);
    self::$questionTable->delete($where);
    self::$ruleTable->delete($where);
    self::$questionTypeTable->delete($where);
    self::$questionPromptTable->delete($where);
    self::$questionReferenceTable->delete($where);
    self::$referenceTable->delete($where);
    self::$referenceDetailTable->delete($where);
    self::$responseTable->delete($where);
    self::$sectionReferenceTable->delete($where);
    self::$tabReferenceTable->delete($where);
    self::$sectionTable->delete($where);
    self::dbCommit($transactionNumber);
    
    // Delete files after transaction to ensure a healthy state such that the worst case
    // scenario is that there may be orphaned files left on disk if the file operation
    // is not successful.
    FileModel::deleteByInstance($this->instanceRow->instanceID);
  }
  
  /**
   * Imports an XML Document and creates an instance
   *
   * @param mixed $import maybe a string xml document, DOMDocument object, or ZipArchiveModel object
   * @param string $instanceName is the name of the new instance
   * @param array $options contains a mix of options for import sources and validation 
   * @return integer instanceID
   */
  public static function importXML(&$import, $instanceName, $options = array()) {
    $options = array_merge(array(
      'tabClones'   => 0,
      'sectionClones' => 0,
      'questionClones' => 0,
      'tabResponses' => array('all' => 0)
    ), $options);

    if (!isset($instanceName) || strlen($instanceName) == 0) {
      throw new InvalidArgumentException('Missing instanceName argument');
    }
    
    libxml_use_internal_errors(true);
    
    if (is_a($import, 'ZipArchiveModel')) {
      $zip = &$import;
      $xml = $import->getInstanceFullResponsesXMLDocument();
      if ($xml === NULL) $xml = $import->getInstrumentDefinitionXMLDocument();
      if ($xml === NULL) throw new Exception('Instrument definition not found in zip archive');
      $dom = new DOMDocument();
      $dom->loadXML($xml);
    }
    elseif (is_a($import, 'DOMDocument')) {
      $dom = &$import;
      $xml = $dom->saveXML();
    }
    else {
      $xml = &$import;
      $dom = new DOMDocument();
      $dom->loadXML($xml);
    }

    $errors = libxml_get_errors();
    try {
      $logger = Zend_Registry::get('logger');
    }
    catch (Zend_Exception $e) {}
    foreach ($errors as $error) {
      $message = rtrim("XML error on line {$error->line} of {$error->file}: {$error->message}");
      if(isset($logger) && $logger) $logger->log($message, Zend_Log::ERR);
      error_log($message);
    }
    if(count($errors) > 0) throw new Exception('XML Exception');
    
    if (!isset(self::$questionReferenceTable)) self::$questionReferenceTable = RegQ_Db_Table::getTable('questionReference');
    if (!isset(self::$sectionReferenceTable)) self::$sectionReferenceTable = RegQ_Db_Table::getTable('sectionReference');
    if (!isset(self::$tabReferenceTable)) self::$tabReferenceTable = RegQ_Db_Table::getTable('tabReference');
    if (!isset(self::$referenceTable)) self::$referenceTable = RegQ_Db_Table::getTable('reference');
    if (!isset(self::$referenceDetailTable)) self::$referenceDetailTable = RegQ_Db_Table::getTable('referenceDetail');
    if (!isset(self::$ruleTable)) self::$ruleTable = RegQ_Db_Table::getTable('rule');
    if (!isset(self::$questionTypeTable)) self::$questionTypeTable = RegQ_Db_Table::getTable('questionType');
    if (!isset(self::$questionPromptTable)) self::$questionPromptTable = RegQ_Db_Table::getTable('questionPrompt');
    if (!isset(self::$tabTable)) self::$tabTable = RegQ_Db_Table::getTable('tab');
    if (!isset(self::$sectionTable)) self::$sectionTable = RegQ_Db_Table::getTable('section');
    if (!isset(self::$questionTable)) self::$questionTable = RegQ_Db_Table::getTable('question');
    if (!isset(self::$instrumentTable)) self::$instrumentTable = RegQ_Db_Table::getTable('instrument');
    if (!isset(self::$instanceTable)) self::$instanceTable = RegQ_Db_Table::getTable('instance');

    $transactionNumber = self::startSerializableTransaction();

    $questionTypeIDCache = array();  // Stores questionTypes that have already been inserted
    $rulesMap = array(); // Stores sourceID to questionGUID
    $tabGuidMap = array(); // Stores tabGUID to tabID
    $sectionGuidMap = array(); // Stores sectionGUID to sectionID
    $questionGuidMap = array(); // Stores questionGUID to questionID
    $responseObjs = array(); // Stores response objects so that they may be saved at the end
                             // to ensure all rules and rule targets have been inserted
    $fileAttachments = array(); // Stores information for attachments so that attachments may be inserted
                                // at the end, after the question bulk load is complete
    $processedReferences = array(); // Stores references (shortNames) that have already been inserted
                                    // into the reference table
    $questionPromptsMap = array(); // Stores question prompts.  First key is questionTypeID, second key is
                                   // prompt value, and the value is the promptID.

    $instrument = $dom->getElementsByTagName('instrument')->item(0);
    $instrumentName = $instrument->getAttribute('instrumentName');
    $instrumentVersion = $instrument->getAttribute('instrumentVersion');
    $revision = $instrument->getAttribute('revision');

    // If instrumentID is already known and passed an argument, use it instead of looking it up
    if (isset($options['instrumentID'])) {
      $instrumentID = $options['instrumentID'];
    }
    else {
      $instrumentID = self::$instrumentTable->getInstrumentID($instrumentName, $instrumentVersion, $revision);
    }
    
    if (isset($instrumentID)) {
      $instanceID = self::$instanceTable->getInstanceID($instrumentID, $instanceName);
      if (isset($instanceID)) {
        throw new Exception('Instance name already exists for this instrument');
      }
    }
    else {
      throw new Exception('Instrument was not found');
    }
    
    if (isset($options['instanceID'])) {
      $importResponsesInstance = new InstanceModel(array('instanceID' => $options['instanceID']));
      $importDom = new DOMDocument();
      $importDom->loadXML($importResponsesInstance->toXML());
      $importInstanceQuestionsDom = $importDom->getElementsByTagName('question');
      for ($q = 0; $q < $importInstanceQuestionsDom->length; $q++) {
        $question = $importInstanceQuestionsDom->item($q);
        if ($question->getElementsByTagName('questionType') === 'V') continue;
        $responses = $question->getElementsByTagName('responses');
        if ($responses->length) {
          $resps = $responses->item(0)->getElementsByTagName('response');
          for ($r = 0; $r < $resps->length; $r++) {
            $response = $resps->item($r); 
            $additionalInfo = isset($responses->item(0)->getElementsByTagName('additionalInfo')->item(0)->nodeValue) ? $responses->item(0)->getElementsByTagName('additionalInfo')->item(0)->nodeValue : '';
            $approverComments = isset($responses->item(0)->getElementsByTagName('approverComments')->item(0)->nodeValue) ? $responses->item(0)->getElementsByTagName('approverComments')->item(0)->nodeValue : '';
            $importInstanceResponses[$question->getElementsByTagName('questionGUID')->item(0)->nodeValue][] = array('responseText' => $response->getElementsByTagName('responseText')->item(0)->nodeValue,
                                                                                                                    'additionalInfo' => $additionalInfo,
                                                                                                                    'approverComments' => $approverComments);
          }
        }
      }
    }
    
    $instanceID = self::$instanceTable->insert(array('instrumentID' => $instrumentID,
                                                     'instanceName' => $instanceName));
                                                     
    $tabs = $instrument->getElementsByTagName('tab');
    for ($t = 0; $t < $tabs->length; $t++) {
      $tab = $tabs->item($t);
      $tabIDs = self::importXMLTab($tab, $instrumentID, $instanceID, $tabGuidMap);
      $tabID = $tabIDs[0];
      $tabGUID = $tabIDs[1];
      
      $tabReferences = $tab->getElementsByTagName('tabReferences');
      self::importXMLReferences('tab', $tabReferences, $instanceID, $tabID, null, null, $processedReferences);
 
      $sections = $tab->getElementsByTagName('section');
      for ($s = 0; $s < $sections->length; $s++) {
        $section = $sections->item($s);
        $sectionIDs = self::importXMLSection($section, $instanceID, $tabID, $sectionGuidMap);
        $sectionID = $sectionIDs[0];
        $sectionGUID = $sectionIDs[1];
      
        $sectionReferences = $section->getElementsByTagName('sectionReferences');
        self::importXMLReferences('section', $sectionReferences, $instanceID, $tabID, $sectionID, null, $processedReferences);

        foreach($section->getElementsByTagName('questions')->item(0)->childNodes as $question) {
          if ($question->nodeName === 'csi:question') {
            $questionIDs = self::importXMLQuestion(false, null, $question, $instrumentID, $instanceID, $tabID, $tabGUID, $sectionID, $sectionGUID, $questionGuidMap, $questionTypeIDCache, $rulesMap, $questionPromptsMap);
            $questionID = $questionIDs[0];
            $questionGUID = $questionIDs[1];
            $questionTypeID = $questionIDs[2];
            $questionPrompts = $question->getElementsByTagName('questionPrompt');
            
            $questionReferences = $question->getElementsByTagName('questionReferences');
            self::importXMLReferences('question', $questionReferences, $instanceID, $tabID, $sectionID, $questionID, $processedReferences);
            
            $responses = $question->getElementsByTagName('responses');
            self::importXMLResponses($responses, $question, $instanceID, $tabID, $sectionID, $questionID, $questionGUID, $questionTypeID, $importInstanceResponses, $questionPrompts, $questionPromptsMap, $responseObjs, $fileAttachments, $options);
          }
          elseif ($question->nodeName === 'csi:questionGroup') {
            $questionIDs = self::importXMLQuestion(true, null, $question, $instrumentID, $instanceID, $tabID, $tabGUID, $sectionID, $sectionGUID, $questionGuidMap, $questionTypeIDCache, $rulesMap, $questionPromptsMap);
            $parentQuestionID = $questionIDs[0];
            
            $questionReferences = $question->getElementsByTagName('groupQuestionReferences');
            self::importXMLReferences('question', $questionReferences, $instanceID, $tabID, $sectionID, $parentQuestionID, $processedReferences);
            
            if ($options['tabResponses']['all'] || (isset($options['tabResponses'][$tabID]) && $options['tabResponses'][$tabID])) {
              $attachments = $question->getElementsByTagName('attachment');
              $fileAttachments[$parentQuestionID] = $attachments;
            }
            $childQuestions = $question->getElementsByTagName('question');
            for ($cq = 0; $cq < $childQuestions->length; $cq++) {
              $question = $childQuestions->item($cq);
              $questionIDs = self::importXMLQuestion(false, $parentQuestionID, $question, $instrumentID, $instanceID, $tabID, $tabGUID, $sectionID, $sectionGUID, $questionGuidMap, $questionTypeIDCache, $rulesMap, $questionPromptsMap);
              $questionID = $questionIDs[0];
              $questionGUID = $questionIDs[1];
              $questionTypeID = $questionIDs[2];
              $questionPrompts = $question->getElementsByTagName('questionPrompt');

              $questionReferences = $question->getElementsByTagName('questionReferences');
              self::importXMLReferences('question', $questionReferences, $instanceID, $tabID, $sectionID, $questionID, $processedReferences);
              
              $responses = $question->getElementsByTagName('responses');
              self::importXMLResponses($responses, $question, $instanceID, $tabID, $sectionID, $questionID, $questionGUID, $questionTypeID, $importInstanceResponses, $questionPrompts, $questionPromptsMap, $responseObjs, $fileAttachments, $options);
            }
          }
        }
      }
    }
    
    self::importXMLRules($instrumentID, $instanceID, $rulesMap, $tabGuidMap, $sectionGuidMap, $questionGuidMap);
    self::$questionTable->processBulk();
    self::$sectionTable->processBulk();
    self::$tabTable->processBulk();
    self::$questionPromptTable->processBulk();
    self::$questionTypeTable->processBulk();
    self::$ruleTable->processBulk();
    self::$referenceTable->processBulk();
    self::$referenceDetailTable->processBulk();
    self::$questionReferenceTable->processBulk();
    self::$sectionReferenceTable->processBulk();
    self::$tabReferenceTable->processBulk();
   
    foreach ($responseObjs as $response) {
      $response->save();
    }
   
    foreach ($fileAttachments as $questionID => $attachments) {
      $questionModel = new QuestionModel(array('questionID' => $questionID,
                                               'depth' => 'question'));
      self::importXMLAttachments($attachments, $questionModel, $import);
    }
    
    $instance = new InstanceModel(array('instanceID' => $instanceID,
                                        'depth' => 'tab'));
    $instance->save();
    
    self::dbCommit($transactionNumber);
    
    return $instance->instanceID;
    
  }
  
  /**
   * Helper function for importXML.  Logic specifically for responses.
   *
   * @param  See importXML.
   */
  private static function importXMLResponses($responses, $question, $instanceID, $tabID, $sectionID, $questionID, $questionGUID, $questionTypeID, &$importInstanceResponses, $questionPrompts, &$questionPromptsMap, &$responseObjs, &$fileAttachments, $options) {
    $attachments = $question->getElementsByTagName('attachment');
    if (isset($importInstanceResponses[$questionGUID])) {
      $importResponses = $importInstanceResponses[$questionGUID];
      $rt = array();
      if ($questionPrompts->length) {
        foreach ($importResponses as $importResponse) {
          $responseText = $importResponse['responseText'];
          $additionalInfo = $importResponse['additionalInfo'];
          $approverComments = $importResponse['approverComments'];
          $questionPromptID = $questionPromptsMap[$questionTypeID][$responseText];
          if (!isset($questionPromptID)) throw new Exception('Question prompt ID not found');
          array_push($rt, $questionPromptID);
        }    
        $newResponse = new ResponseModel(array('questionID' => $questionID,
                                               'instanceID' => $instanceID,
                                               'tabID' => $tabID,
                                               'sectionID' => $sectionID,
                                               'responseText' => join(",", $rt),
                                               'additionalInfo' => $additionalInfo,
                                               'approverComments' => $approverComments,
                                               'state' => 1));
        $responseObjs[] = $newResponse;
      }
      else {
        $importResponse = $importResponses[0];
        $responseText = $importResponse['responseText'];
        $additionalInfo = $importResponse['additionalInfo'];
        $approverComments = $importResponse['approverComments'];
        $newResponse = new ResponseModel(array('questionID' => $questionID,
                                               'instanceID' => $instanceID,
                                               'tabID' => $tabID,
                                               'sectionID' => $sectionID,
                                               'responseText' => $responseText,
                                               'additionalInfo' => $additionalInfo,
                                               'approverComments' => $approverComments,
                                               'state' => 1));
        $responseObjs[] = $newResponse;
      }
    }
    elseif (($responses->length || $attachments->length) && ($options['tabResponses']['all'] || isset($options['tabResponses'][$tabID]))) {
      if ($responses->length) {
        $resps = $responses->item(0)->getElementsByTagName('response'); // individual response elements
        if ($resps->length) {
          $state = isset($responses->item(0)->getElementsByTagName('state')->item(0)->nodeValue) ? $responses->item(0)->getElementsByTagName('state')->item(0)->nodeValue : 0; 
          $additionalInfo = isset($responses->item(0)->getElementsByTagName('additionalInfo')->item(0)->nodeValue) ? $responses->item(0)->getElementsByTagName('additionalInfo')->item(0)->nodeValue : '';
          $approverComments = isset($responses->item(0)->getElementsByTagName('approverComments')->item(0)->nodeValue) ? $responses->item(0)->getElementsByTagName('approverComments')->item(0)->nodeValue : '';
          if ($questionPrompts->length) {
            $rt = array();
            for ($r = 0; $r < $resps->length; $r++) {
              $response = $resps->item($r);
              $responseDate = $response->getElementsByTagName('responseDate')->item(0)->nodeValue;
              $responseText = $response->getElementsByTagName('responseText')->item(0)->nodeValue;
              $questionPromptID = $questionPromptsMap[$questionTypeID][$responseText];
              if (!isset($questionPromptID)) throw new Exception('Question prompt ID not found');
              array_push($rt, $questionPromptID);
            }
            $newResponse = new ResponseModel(array('questionID' => $questionID,
                                                   'instanceID' => $instanceID,
                                                   'tabID' => $tabID,
                                                   'sectionID' => $sectionID,
                                                   'responseText' => join(",", $rt),
                                                   'additionalInfo' => $additionalInfo,
                                                   'approverComments' => $approverComments,
                                                   'state' => $state));
            $responseObjs[] = $newResponse;
          }
          else {
            for ($r = 0; $r < $resps->length; $r++) {
              $response = $resps->item($r);
              $responseDate = $response->getElementsByTagName('responseDate')->item(0)->nodeValue;
              $responseText = $response->getElementsByTagName('responseText')->item(0)->nodeValue;
              $newResponse = new ResponseModel(array('questionID' => $questionID,
                                                     'instanceID' => $instanceID,
                                                     'tabID' => $tabID,
                                                     'sectionID' => $sectionID,
                                                     'responseText' => $responseText,
                                                     'additionalInfo' => $additionalInfo,
                                                     'approverComments' => $approverComments,
                                                     'state' => $state));
              $responseObjs[] = $newResponse;
            }
          }
        }
      }
      $questionType = $question->getElementsByTagName('questionType')->item(0)->nodeValue;
      if ($questionType !== 'V') {
        $fileAttachments[$questionID] = $attachments;
      }
    }
  }
  
  /**
   * Helper function for importXML.  Logic specifically for question.
   *
   * @param  See importXML.
   */
  private static function importXMLQuestion($parent, $parentID, $question, $instrumentID, $instanceID, $tabID, $tabGUID, $sectionID, $sectionGUID, &$questionGuidMap, &$questionTypeIDCache, &$rulesMap, &$questionPromptsMap) {   
    $qSeqNumber = $question->getElementsByTagName('seqNumber')->item(0)->nodeValue;
    $qText = isset($question->getElementsByTagName('qText')->item(0)->nodeValue) ? $question->getElementsByTagName('qText')->item(0)->nodeValue : null;
    if ($parent) {
      $questionNumber = isset($question->getElementsByTagName('groupQuestionNumber')->item(0)->nodeValue) ? $question->getElementsByTagName('groupQuestionNumber')->item(0)->nodeValue : null;
      $defaultQuestionHidden = isset($question->getElementsByTagName('groupDefaulQuestiontHidden')->item(0)->nodeValue) ? $question->getElementsByTagName('groupDefaultQuestionHidden')->item(0)->nodeValue : 0;
    }
    else {
      $questionNumber = isset($question->getElementsByTagName('questionNumber')->item(0)->nodeValue) ? $question->getElementsByTagName('questionNumber')->item(0)->nodeValue : null;
      $defaultQuestionHidden = isset($question->getElementsByTagName('defaultQuestionHidden')->item(0)->nodeValue) ? $question->getElementsByTagName('defaultQuestionHidden')->item(0)->nodeValue : 0;
    }
    $cloneable = isset($question->getElementsByTagName('cloneable')->item(0)->nodeValue) ? $question->getElementsByTagName('cloneable')->item(0)->nodeValue : 0;
    $questionGUID = $question->getElementsByTagName('questionGUID')->item(0)->nodeValue;

    $questionTypeID = self::importXMLQuestionType($question, $questionTypeIDCache, $rulesMap, $questionPromptsMap, $questionGUID, $sectionGUID, $tabGUID, $instanceID);
    
    $questionID = self::$questionTable->insertBulk(array('instrumentID' => $instrumentID,
                                                         'instanceID' => $instanceID,
                                                         'tabID' => $tabID,
                                                         'parentID' => $parentID,
                                                         'sectionID' => $sectionID,
                                                         'questionGUID' => $questionGUID,
                                                         'questionNumber' => $questionNumber,
                                                         'seqNumber' => $qSeqNumber,
                                                         'questionTypeID' => $questionTypeID,
                                                         'qText' => $qText,
                                                         'cloneable' => $cloneable,
                                                         'defaultQuestionHidden' => $defaultQuestionHidden));         
    
    if ($question->getElementsByTagName('questionType')->item(0)->nodeValue !== 'V')
      $questionGuidMap[$questionGUID] = $questionID;
    
    return array($questionID, $questionGUID, $questionTypeID);
  }
  
  /**
   * Helper function for importXML.  Logic specifically for sections.
   *
   * @param  See importXML.
   */
  private static function importXMLSection($section, $instanceID, $tabID, &$sectionGuidMap) {
    $seqNumber = $section->getElementsByTagName('seqNumber')->item(0)->nodeValue;
    $sectionHeader = $section->getElementsByTagName('sectionHeader')->item(0)->nodeValue;
    $description = isset($section->getElementsByTagName('description')->item(0)->nodeValue) ? $section->getElementsByTagName('description')->item(0)->nodeValue : '';
    $cloneable = isset($section->getElementsByTagName('cloneable')->item(0)->nodeValue) ? $section->getElementsByTagName('cloneable')->item(0)->nodeValue : 0;
    $defaultSectionHidden = isset($section->getElementsByTagName('defaultSectionHidden')->item(0)->nodeValue) ? $section->getElementsByTagName('defaultSectionHidden')->item(0)->nodeValue : 0;
    $sectionGUID = $section->getElementsByTagName('sectionGUID')->item(0)->nodeValue;
    $sectionID = self::$sectionTable->insertBulk(array('instanceID' => $instanceID,
                                                       'tabID' => $tabID,
                                                       'seqNumber' => $seqNumber,
                                                       'sectionGUID' => $sectionGUID,
                                                       'sectionHeader' => $sectionHeader,
                                                       'description' => $description,
                                                       'cloneable' => $cloneable,
                                                       'defaultSectionHidden' => $defaultSectionHidden));
    $sectionGuidMap[$sectionGUID] = $sectionID;
    return array($sectionID, $sectionGUID);        
  }
  
  /**
   * Helper function for importXML.  Logic specifically for tabs.
   *
   * @param  See importXML.
   */
  private static function importXMLTab($tab, $instrumentID, $instanceID, &$tabGuidMap) {
    $seqNumber = $tab->getElementsByTagName('seqNumber')->item(0)->nodeValue;
    $tabHeader = $tab->getElementsByTagName('tabHeader')->item(0)->nodeValue;
    $description = isset($tab->getElementsByTagName('description')->item(0)->nodeValue) ? $tab->getElementsByTagName('description')->item(0)->nodeValue : '';
    $headerText = $tab->getElementsByTagName('headerText')->item(0)->nodeValue;
    $footerText = $tab->getElementsByTagName('footerText')->item(0)->nodeValue;
    $cloneable = isset($tab->getElementsByTagName('cloneable')->item(0)->nodeValue) ? $tab->getElementsByTagName('cloneable')->item(0)->nodeValue : 0;
    $defaultTabHidden = isset($tab->getElementsByTagName('defaultTabHidden')->item(0)->nodeValue) ? $tab->getElementsByTagName('defaultTabHidden')->item(0)->nodeValue : 0;
    $tabGUID = $tab->getElementsByTagName('tabGUID')->item(0)->nodeValue;
    $tabID = self::$tabTable->insertBulk(array('instrumentID' => $instrumentID,
                                               'instanceID' => $instanceID,
                                               'seqNumber' => $seqNumber,
                                               'tabGUID' => $tabGUID,
                                               'tabHeader' => $tabHeader,
                                               'description' => $description,
                                               'headerText' => $headerText,
                                               'footerText' => $footerText,
                                               'cloneable' => $cloneable,
                                               'defaultTabHidden' => $defaultTabHidden,
                                               'numQuestions' => 0));
    $tabGuidMap[$tabGUID] = $tabID;
    return array($tabID, $tabGUID);
  }
  
  /**
   * Helper function for importXML.  Logic specifically for question types.
   *
   * @param  See importXML.
   */      
  private static function importXMLQuestionType($question, &$questionTypeIDCache, &$rulesMap, &$questionPromptsMap, $questionGUID, $sectionGUID, $tabGUID, $instanceID) {
    // Question groups do not have a question type specified in the xml
    if ($question->nodeName === 'csi:questionGroup') {
      $questionType = '_questionGroup';
      $questionTypeIDCacheKey = '_questionGroup';
    }
    else {
      $questionType = $question->getElementsByTagName('questionType')->item(0)->nodeValue;
      $questionTypeIDCacheKey = $questionType;
      $questionPrompts = $question->getElementsByTagName('questionPrompt');

      for ($qp = 0; $qp < $questionPrompts->length; $qp++) {
        $questionPrompt = $questionPrompts->item($qp);
        $promptText = $questionPrompt->getElementsByTagName('promptText')->item(0)->nodeValue;
        $requireAddlInfo = 0;
        $requireAddlInfo = isset($questionPrompt->getElementsByTagName('requireAdditionalInfo')->item(0)->nodeValue) ? $questionPrompt->getElementsByTagName('requireAdditionalInfo')->item(0)->nodeValue : 0;
        $questionTypeIDCacheKey .= "|$promptText|$requireAddlInfo";
        foreach (array('enableTab', 'enableSection', 'enableQuestion', 'disableTab', 'disableSection', 'disableQuestion') as $name) {
          $rules = $questionPrompt->getElementsByTagName($name);
          for ($r = 0; $r < $rules->length; $r++) {
            $rule = $rules->item($r);
            $targetID = $rule->nodeValue;
            switch($name) {
              case 'enableQuestion':
                if($targetID == $questionGUID) {
                  throw new Exception('An enableQuestion rule cannot target itself');
                }
                break;
              case 'enableSection':
                if($targetID == $sectionGUID) {
                  throw new Exception('An enableSection rule cannot target the section to which it belongs');
                }
                break;
              case 'enableTab':
                if($targetID == $tabGUID) {
                  throw new Exception('An enableTab rule cannot target the tab to which it belongs');
                }
                break;
              case 'disableQuestion':
                if($targetID == $questionGUID) {
                  throw new Exception('A disableQuestion rule cannot target itself');
                }
                break;
              case 'disableSection':
                if($targetID == $sectionGUID) {
                  throw new Exception('A disableSection rule cannot target the section to which it belongs');
                }
                break;
              case 'disableTab':
                if($targetID == $tabGUID) {
                  throw new Exception('A disableTab rule cannot target the tab to which it belongs');
                }
                break;
            }
            $questionTypeIDCacheKey .= "|${name}${targetID}";
          }
        }
      }
    }
    
    if (isset($questionTypeIDCache[$questionTypeIDCacheKey])) {
      $questionTypeID = $questionTypeIDCache[$questionTypeIDCacheKey];
      return $questionTypeID;
    }
    else {
      $questionTypeIDCacheKey = $questionType;
      $questionTypeID = self::$questionTypeTable->insertBulk(array('instanceID' => $instanceID,
                                                                   'format' => $questionType));
 
      if ($question->nodeName !== 'csi:questionGroup') {                                            
        // If there are any question prompts for this question, the prompts and any rules they have
        // must be part of the question type key
        $questionPrompts = $question->getElementsByTagName('questionPrompt');
        for ($qp = 0; $qp < $questionPrompts->length; $qp++) {
          $questionPrompt = $questionPrompts->item($qp);
          $promptText = $questionPrompt->getElementsByTagName('promptText')->item(0)->nodeValue;
          $requireAddlInfo = 0;
          $requireAddlInfo = isset($questionPrompt->getElementsByTagName('requireAdditionalInfo')->item(0)->nodeValue) ? $questionPrompt->getElementsByTagName('requireAdditionalInfo')->item(0)->nodeValue : 0;
          $questionTypeIDCacheKey .= "|$promptText|$requireAddlInfo";
          $questionPromptID = self::$questionPromptTable->insertBulk(array('questionTypeID' => $questionTypeID,
                                                                           'instanceID' => $instanceID,
                                                                           'value' => $promptText,
                                                                           'requireAddlInfo' => $requireAddlInfo));
          $questionPromptsMap[$questionTypeID][$promptText] = $questionPromptID;
          foreach (array('enableTab', 'enableSection', 'enableQuestion', 'disableTab', 'disableSection', 'disableQuestion') as $name) {
            $rules = $questionPrompt->getElementsByTagName($name);
            for ($r = 0; $r < $rules->length; $r++) {
              $rule = $rules->item($r);
              $targetID = $rule->nodeValue;
              $questionTypeIDCacheKey .= "|${name}${targetID}";
              $rulesMap[$questionPromptID][$name][] = $targetID;
            }
          }
        }
      }
      
      $questionTypeIDCache[$questionTypeIDCacheKey] = $questionTypeID;
      return $questionTypeID;
    }
  }
  
  /**
   * Helper function for importXML.  Logic specifically for attachments.
   *
   * @param  See importXML.
   */
  private static function importXMLAttachments ($attachments, $question, $zip) {
    if ($attachments->length) {
      for ($a = 0; $a < $attachments->length; $a++) {
        $attachment = $attachments->item($a);
        $filename = $attachment->getElementsByTagName('filename')->item(0)->nodeValue;
        $mime = $attachment->getElementsByTagName('mime')->item(0)->nodeValue;
        $location = $attachment->getElementsByTagName('location')->item(0)->nodeValue;
        $fileObj = new FileModel($question);
        if (!is_a($zip, 'ZipArchiveModel')) throw new Exception('XML contains attachment but no zip archive was loaded');
        $content = $zip->getFromName($location);
        if ($content === FALSE) {
          throw new Exception("Unable to find filename in zip archive: {$location}");
        }
        $fileObj->store($content, array('filename' => $filename,
                                        'mime' => $mime));
      }
    }
  }
  
  /**
   * Helper function for importXML.  Logic specifically for rules.
   *
   * @param  See importXML.
   */
  private static function importXMLRules ($instrumentID, $instanceID, &$rulesMap, &$tabGuidMap, &$sectionGuidMap, &$questionGuidMap) {
    foreach ($rulesMap as $questionPromptID => $questionPromptIDArray) {
      foreach ($questionPromptIDArray as $name => $nameArray) {
        foreach ($nameArray as $targetGUID) {
          if ($name === 'enableQuestion') {
            $questionID = $questionGuidMap[$targetGUID];
            if (!isset($questionID)) {
              throw new Exception("questionGUID referenced in XML Instrument Definition does not exist: $targetID");
            }
            $ruleID = self::$ruleTable->insertBulk(array('instrumentID' => $instrumentID,
                                                         'instanceID' => $instanceID,
                                                         'sourceID' => $questionPromptID,
                                                         'targetID' => $questionID,
                                                         'targetGUID' => $targetGUID,
                                                         'type' => $name,
                                                         'enabled' => 'N'));
          }
          elseif ($name === 'enableSection') {
            $sectionID = $sectionGuidMap[$targetGUID];
            if (!isset($sectionID)) {
              throw new Exception("sectionGUID referenced in XML Instrument Definition does not exist: $targetID");
            }
            $ruleID = self::$ruleTable->insertBulk(array('instrumentID' => $instrumentID,
                                                         'instanceID' => $instanceID,
                                                         'sourceID' => $questionPromptID,
                                                         'targetID' => $sectionID,
                                                         'targetGUID' => $targetGUID,
                                                         'type' => $name,
                                                         'enabled' => 'N'));
          }
          elseif ($name === 'enableTab') {
            $tabID = $tabGuidMap[$targetGUID];
            if (!isset($tabID)) {
              throw new Exception("tabGUID referenced in XML Instrument Definition does not exist: $targetID");
            }
            $ruleID = self::$ruleTable->insertBulk(array('instrumentID' => $instrumentID,
                                                         'instanceID' => $instanceID,
                                                         'sourceID' => $questionPromptID,
                                                         'targetID' => $tabID,
                                                         'targetGUID' => $targetGUID,
                                                         'type' => $name,
                                                         'enabled' => 'N'));
          }
          elseif ($name === 'disableQuestion') {
            $questionID = $questionGuidMap[$targetGUID];
            if (!isset($questionID)) {
              throw new Exception("questionGUID referenced in XML Instrument Definition does not exist: $targetID");
            }
            $ruleID = self::$ruleTable->insertBulk(array('instrumentID' => $instrumentID,
                                                         'instanceID' => $instanceID,
                                                         'sourceID' => $questionPromptID,
                                                         'targetID' => $questionID,
                                                         'targetGUID' => $targetGUID,
                                                         'type' => $name,
                                                         'enabled' => 'N'));
          }
          elseif ($name === 'disableSection') {
            $sectionID = $sectionGuidMap[$targetGUID];
            if (!isset($sectionID)) {
              throw new Exception("sectionGUID referenced in XML Instrument Definition does not exist: $targetID");
            }
            $ruleID = self::$ruleTable->insertBulk(array('instrumentID' => $instrumentID,
                                                         'instanceID' => $instanceID,
                                                         'sourceID' => $questionPromptID,
                                                         'targetID' => $sectionID,
                                                         'targetGUID' => $targetGUID,
                                                         'type' => $name,
                                                         'enabled' => 'N'));
          }
          elseif ($name === 'disableTab') {
            $tabID = $tabGuidMap[$targetGUID];
            if (!isset($tabID)) {
              throw new Exception("tabGUID referenced in XML Instrument Definition does not exist: $targetID");
            }
            $ruleID = self::$ruleTable->insertBulk(array('instrumentID' => $instrumentID,
                                                         'instanceID' => $instanceID,
                                                         'sourceID' => $questionPromptID,
                                                         'targetID' => $tabID,
                                                         'targetGUID' => $targetGUID,
                                                         'type' => $name,
                                                         'enabled' => 'N'));
          }
        }
      }
    }
    
    // reset table cache as there are now new rules
    RegQ_Db_Table::reset('rules');
  }
  
  /**
   * Helper function for importXML.  Logic specifically for references.
   *
   * @param  See importXML.
   */
  private static function importXMLReferences($type, $references, $instanceID, $tabID, $sectionID, $questionID, &$processedReferences) {
    if ($references->length) {
      $references = $references->item(0)->getElementsByTagName('reference');
      for ($r = 0; $r < $references->length; $r++) {
        $reference = $references->item($r);
        $shortName = $reference->getElementsByTagName('shortName')->item(0)->nodeValue;
        $referenceName = $reference->getElementsByTagName('referenceName')->item(0)->nodeValue;
        $item = $reference->getElementsByTagName('item')->item(0)->nodeValue;
        $referenceText = $reference->getElementsByTagName('referenceText')->item(0)->nodeValue;
        $referenceURL = $reference->getElementsByTagName('referenceURL')->item(0)->nodeValue;
        if (!isset($processedReferences[$shortName])) {
          self::$referenceTable->insertBulk(array('shortName' => $shortName,
                                                  'instanceID' => $instanceID,
                                                  'referenceName' => $referenceName));
          $processedReferences[$shortName] = true;
        }
        $referenceDetailID = self::$referenceDetailTable->insertBulk(array('shortName' => $shortName,
                                                                           'instanceID' => $instanceID,
                                                                           'item' => $item,
                                                                           'referenceText' => $referenceText,
                                                                           'referenceURL' => $referenceURL));
        switch($type) {
          case 'question':
            self::importXMLQuestionReferences($instanceID, $questionID, $referenceDetailID, $tabID, $sectionID);
            break;
          case 'section':
            self::importXMLSectionReferences($instanceID, $sectionID, $referenceDetailID, $tabID);
            break;
          case 'tab':
            self::importXMLTabReferences($instanceID, $tabID, $referenceDetailID);
            break;
        }
      }
    }     
  }
  
  /**
   * Helper function for importXML.  Logic specifically for tab references.
   *
   * @param  See importXML.
   */
  private static function importXMLTabReferences($instanceID, $tabID, $referenceDetailID) {
    $rows = self::$tabReferenceTable->fetchRows('tabID', $tabID, null, $instanceID);
    foreach ($rows as $row) {
      if ($referenceDetailID == $row->referenceDetailID) return;
    }
    self::$tabReferenceTable->insertBulk(array('tabID' => $tabID,
                                               'referenceDetailID' => $referenceDetailID,
                                               'instanceID' => $instanceID));
  }
  
  /**
   * Helper function for importXML.  Logic specifically for section references.
   *
   * @param  See importXML.
   */
  private static function importXMLSectionReferences($instanceID, $sectionID, $referenceDetailID, $tabID) {
    $rows = self::$sectionReferenceTable->fetchRows('sectionID', $sectionID, null, $instanceID);
    foreach ($rows as $row) {
      if ($referenceDetailID == $row->referenceDetailID) return;
    }
    self::$sectionReferenceTable->insertBulk(array('sectionID' => $sectionID,
                                                   'referenceDetailID' => $referenceDetailID,
                                                   'instanceID' => $instanceID,
                                                   'tabID' => $tabID));
  }
  
  /**
   * Helper function for importXML.  Logic specifically for question references.
   *
   * @param  See importXML.
   */
  private static function importXMLQuestionReferences($instanceID, $questionID, $referenceDetailID, $tabID, $sectionID) {
    $rows = self::$questionReferenceTable->fetchRows('questionID', $questionID, null, $instanceID);
    foreach ($rows as $row) {
      if ($referenceDetailID == $row->referenceDetailID) return;
    }
    self::$questionReferenceTable->insertBulk(array('questionID' => $questionID,
                                                    'referenceDetailID' => $referenceDetailID,
                                                    'instanceID' => $instanceID,
                                                    'tabID' => $tabID,
                                                    'sectionID' => $sectionID));
  }

  /**
   * Load tabs associated with this InstanceModel
   */
  private function _loadTabs() {
    $auth = Zend_Auth::getInstance();
    if($auth->hasIdentity()) $user = DbUserModel::findByUsername($auth->getIdentity());
    else throw new Exception("Hey, no loading tabs without being logged in");
    
    $where = self::$tabTable->getAdapter()->quoteInto('instrumentID = ?', $this->instrumentID) .
             self::$tabTable->getAdapter()->quoteInto(' AND instanceID = ?', $this->instanceID);
    $tabRowset = self::$tabTable->fetchAll($where, 'seqNumber ASC');

    $this->tabs = array();
    foreach ($tabRowset as $tRow) {
      $tab = new TabModel(array(
        'tabID' => $tRow->tabID,
        'depth' => $this->depth
      ));
      if($user->hasAnyAccess($tab)) $this->tabs[] = $tab;
    }
    
    $this->tabsIndex = 0;
  }
  
  /**
   * Utility function for converting special characters to xml entities
   *
   * @return string
   */
  private static function _xmlentities ($string) {
    return str_replace(
      array('&', '"', "'", '<', '>'),
      array('&amp;', '&quot;', '&apos;', '&lt;', '&gt;'),
      $string
    );
  }

}
