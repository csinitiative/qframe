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
class DomainModel implements QFrame_Paginable, QFrame_Permissible {

  /**
   * Table object that members of this class will use to perform
   * database actions
   * @var QFrame_Db_Table_Domain
   */
  private static $domainTable;

  /**
   * Table object that members of this class will use to perform
   * database actions
   * @var QFrame_Db_Table_DomainQuestionnaire
   */
  private static $domainQuestionnaireTable;

  /**
   * List of attributes that cannot be set
   * @var Array
   */
  private static $restrictedAttributes = array('domainID');

  /**
   * Zend_Db_Table_Row object representing this domain in the database
   * @var Zend_Db_Table_Row
   */
  private $row;

  /**
   * Stores original value of each attribute (to decide whether a save is really necessary)
   * @var Array
   */
  private $originalAttributes;

  /**
   * Stores whether or not this object has yet been persisted
   * @var boolean
   */
  private $persisted = true;

  /**
   * (Private) constructor.  Allows the fetch methods that are part of this
   * class to construct new DomainModel objects without allowing new objects to
   * be constructed by a user.
   *
   * @param Zend_Db_Table_Row_Abstract database row that this object represents
   */
  private function __construct(Zend_Db_Table_Row_Abstract $row) {
    $this->row = $row;
  }

  /**
   * Magic method that returns values of properties
   *
   * @param  string key that is being requested
   * @return mixed
   */
  public function __get($key) {

    if(isset($this->row->$key)) {
      return $this->row->$key;
    }

    // Otherwise, throw an exception
    throw new Exception("Attribute not found [$key]");
  }
  
  /**
   * Magic method that returns true if the requested property exists and false otherwise
   *
   * @param  string  key that is being requested
   * @return boolean
   */
  public function __isset($key) {
    return isset($this->sectionRow->$key);
  }

  public function save() {
    $this->row->save();
  }

  public function delete() {
    $this->row->delete();
    return $this;
  }

  /**
   * Return an ID that is guaranteed to be unique among objects of type DomainModel
   *
   * @return string
   */
  public function objectID() {
    return "{$this->domainID}";
  }

  /**
   * Get one page worth of results
   *
   * @param  integer number of objects to return
   * @param  integer offset from the beginning of the result set
   * @param  string  (optional) order clause to apply to the result set
   * @param  string  (optional) search term to apply to the result set
   * @return Array
   */
  public static function getPage($num, $offset, $order = null, $search = null) {
    $where = ($search === null) ? null : self::searchWhere($search);
    return self::_find(array(
      'where'   => $where,
      'order'   => $order,
      'limit'   => $num,
      'offset'  => $offset
    ));
  }

  /**
   * Returns the count of domains that match a given search criteria
   *
   * @param  string (optional) search term to apply
   * @return integer
   */
  public static function count($search = null) {
    if($search !== null) $search = self::searchWhere($search);
    else $search = '1';

    $adapter = Zend_Db_Table_Abstract::getDefaultAdapter();
    return(intval($adapter->fetchOne("SELECT COUNT(*) FROM `domain` WHERE {$search}")));
  }

  /**
   * Returns an array of DomainModel objects matching the given criteria
   *
   * @param  Array (optional) various parameters to use when querying (recognized keys are
   * where, order, limit, and offset)
   * @return Array
   */
  public static function _find($args = array()) {
    if (!isset(self::$domainTable)) self::$domainTable = QFrame_Db_Table::getTable('domain');

    // set up default values for all of the allowed arguments
    $args = array_merge(array(
      'where'   => null,
      'order'   => null,
      'limit'   => null,
      'offset'  => null
    ), $args);

    $domains = array();
    $domainRows =
        self::$domainTable->fetchAll($args['where'], $args['order'], $args['limit'], $args['offset']);
    foreach($domainRows as $row) $domains[] = new DomainModel($row);
    return $domains;
  }

  /**
   * Produces a where clause for the given search term
   *
   * @param  string search term
   * @return string
   */
  private static function searchWhere($search) {
    $adapter = Zend_Db_Table_Abstract::getDefaultAdapter();
    $whereParts = array();
    foreach(array('domainDescription') as $column) {
      $whereParts[] = $adapter->quoteInto("{$column} LIKE ?", "%{$search}%");
    }
    return implode(' OR ', $whereParts);
  }

  /**
   * Returns a DomainModel or DomainModels that match the given criteria
   *
   * @param  integer the ID of the domain being looked for (or one of the strings 'first' or 'all')
   * for the more involved find() syntax)
   * @param  Array   (optional) in the more advanced form of find() this is where additional
   * options are specified.
   * @return DomainModel
   */
  public static function find($id, $args = array()) {
    if (!isset(self::$domainTable)) self::$domainTable = QFrame_Db_Table::getTable('domain');

    // if the first argument is numeric, treat it as an ID
    if(is_numeric($id))
      return new DomainModel(self::$domainTable->find(intval($id))->current());

    // if we have been asked to retrieve just the first matching element
    if($id === 'first') {
      $args['limit'] = 1;
      $domains = self::_find($args);
      return $domains[0];
    }
    elseif($id === 'all') {
      return self::_find($args);
    }
    else
      throw new Exception('First argument must be an integer or the string \'first\' or \'all\'.');
  }

  /**
   * Set a bunch of attributes at once
   *
   * @param  Array   hash of attributes and their values
   * @param  boolean (optional) whether to enforce restrictions on setting attributes
   * @return DomainModel
   */
  public function setAttributes($attributes, $restrict = true) {
    // make sure what was passed in was an array
    if(!is_array($attributes)) throw new exception('DomainModel::setAttributes() requires an array.');

    // check to make sure that an invalid or restricted property has not been referenced
    foreach($attributes as $attribute => $value) {
      if(!isset($this->row->$attribute))
        throw new Exception("Attempt to set property [{$attribute}] which does not exist.");
      if($restrict && in_array($attribute, self::$restrictedAttributes))
        throw new Exception("Attempt to set property [{$attribute}] which is restricted.");
    }
    $this->row->setFromArray($attributes);
    return $this;
  }

  /**
   * Create a new DomainModel object
   *
   * @param  Array     list of attributes for this RoleModel object
   * @return RoleModel
   */
  public static function create($attributes) {
    if (!isset(self::$domainTable)) self::$domainTable = QFrame_Db_Table::getTable('domain');
    $domain = new DomainModel(self::$domainTable->createRow());
    return $domain->setAttributes($attributes);
  }

  /**
   * Get allowed questionnaires for this domain
   *
   * @return QuestionnaireModel
   */
  public function getQuestionnaires() {
	if (!isset(self::$domainQuestionnaireTable)) self::$domainQuestionnaireTable = QFrame_Db_Table::getTable('domain_questionnaire');
	
	$questionnaires = array();
	$where = Zend_Db_Table_Abstract::getDefaultAdapter()->quoteInto('domainID = ?', $this->domainID);
    $domainQuestionnaireRows = self::$domainQuestionnaireTable->fetchall($where, 'questionnaireID');
    foreach ($domainQuestionnaireRows as $row) {
	  $questionnaires[] = new QuestionnaireModel(array('questionnaireID' => $row->questionnaireID));
    }

    return $questionnaires;
  }

  /**
   * Get allowed questionnaires for this domain
   *
   * @return QuestionnaireModel
   */
  public function hasQuestionnaire(QuestionnaireModel $questionnaire) {
	if (!isset(self::$domainQuestionnaireTable)) self::$domainQuestionnaireTable = QFrame_Db_Table::getTable('domain_questionnaire');

	$questionnaires = array();
	$where = Zend_Db_Table_Abstract::getDefaultAdapter()->quoteInto('domainID = ?', $this->domainID);
	$where .= ' AND ' . Zend_Db_Table_Abstract::getDefaultAdapter()->quoteInto('questionnaireID = ?', $questionnaire->questionnaireID);
	
    $row = self::$domainQuestionnaireTable->fetchrow($where, 'questionnaireID');
    return $row ? true : false;
  }

  /**
   * Grant access to a questionnaire for this domain
   *
   * @param  QuestionnaireModel
   */
  public function grant(QuestionnaireModel $questionnaire) {
    if (!isset(self::$domainQuestionnaireTable)) self::$domainQuestionnaireTable = QFrame_Db_Table::getTable('domain_questionnaire');
   
    $this->deny($questionnaire);

    self::$domainQuestionnaireTable->insert(array(
	      'domainID'		=> $this->domainID,
	      'questionnaireID' => $questionnaire->questionnaireID
	));
  }

  /**
   * Deny access to a questionnaire for this domain
   *
   * @param  QuestionnaireModel
   */
  public function deny(QuestionnaireModel $questionnaire) {
	if (!isset(self::$domainQuestionnaireTable)) self::$domainQuestionnaireTable = QFrame_Db_Table::getTable('domain_questionnaire');
	
	$where = self::$domainQuestionnaireTable->getAdapter()->quoteInto('domainID = ?', $this->domainID);
	$where .= ' AND ' . self::$domainQuestionnaireTable->getAdapter()->quoteInto('questionnaireID = ?', $questionnaire->questionnaireID);
	
    self::$domainQuestionnaireTable->delete($where);
  }

  /**
   * Return all available domains
   *
   * @return array DomainModel
   */
  public static function getAllDomains() {
    $adapter = Zend_Db_Table_Abstract::getDefaultAdapter();
    $select = $adapter->select()
        ->from(array('domain' => 'domain'), array('domain.domainID'))
        ->order(array('domainDescription ASC'));
    $stmt = $adapter->query($select);
    $result = $stmt->fetchAll();
    $domains = array();
    while (list($key, $val) = each($result)) {
      array_push($domains, DomainModel::find($val['domainID']));
    }
    return $domains;
  }

  /**
   * Return an ID that is unique to this page on this domain
   *
   * @return string
   */
  public function getPermissionID() {
    $id = get_class($this) . '_';
    $id .= "$this->domainID";

    return $id;
  }


}
