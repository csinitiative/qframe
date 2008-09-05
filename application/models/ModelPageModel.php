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
 * @category   Application
 * @package    Models
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */


/**
 * @category   Application
 * @package    Models
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */
class ModelPageModel {
  
  /**
   * Stores the model table object used by this class
   * @var QFrame_Db_Table_Model
   */
  private static $modelTable;
  
  /**
   * Stores the section table object used by this class
   * @var QFrame_Db_Table_Section
   */
  private static $sectionTable;
  
  /**
   * Stores the page object
   * @var PageModel
   */
  private $page;
  
  /**
   * Stores the instance that is being compared to the model (optional)
   * @var InstanceModel
   */
  private $compareInstance;
    
  /**
   * Determines depth of object hierarchy
   */
  private $depth;
  
  /**
   * ModelSection objects associated with this model
   */
  private $modelSections = array();
   
  /**
   * Store the modelID
   */
  private $modelID;
   
  /**
   * Instantiate a new ModelPageModel object
   *
   * @param array
   */
  public function __construct($args = array()) {
    
    $args = array_merge(array(
      'depth' => 'page',
      'instance' => null
    ), $args);
    $this->depth = $args['depth'];
    $this->compareInstance = $args['instance'];
    
    if (!isset(self::$modelTable)) self::$modelTable = QFrame_Db_Table::getTable('model');
    if (!isset(self::$sectionTable)) self::$sectionTable = QFrame_Db_Table::getTable('section');
    
    if (isset($args['modelID']) && isset($args['pageID'])) {
      $this->modelID = $args['modelID'];
      $this->page = new PageModel(array('pageID' => $args['pageID'],
                                        'depth' => $args['depth'])); 
    }
    else {
      throw new InvalidArgumentException('Missing arguments to ModelPageModel constructor');
    }

    if ($this->depth !== 'page') $this->_loadModelSections();
  }
  
  /**
   * Return attributes of this ModelPage object
   *
   * @param  string key
   * @return mixed
   */
  public function __get($key) {
    return $this->page->$key;
  }
  
  /**
   * Return true if an attribute exists, false otherwise
   *
   * @return boolean
   */
  public function __isset($key) {
    if (isset($this->page->$key)) return true;
    return false;
  }
  
  /**
   * Save this ModelPageModel object and its descendents
   *
   * @return boolean
   */
  public function save() {
    
    if (count($this->modelSections)) {
      foreach ($this->modelSections as $modelSection) {
        $modelSection->save();
      }
    }
  
  }
  
  /**
   * Returns the next ModelSectionModel associated with this ModelPageModel
   *
   * @return ModelSectionModel Returns null if there are no further pages
   */
  public function nextModelSection() {
    $nextModelSection = each($this->modelSections);
    if(!$nextModelSection) return;

    return $nextModelSection['value'];
  }
  
  /**
   * Deletes all responses for this Model Page
   */
  public function delete() {
    $where = self::$modelTable->getAdapter()->quoteInto('modelID = ?', $this->modelID) .
             self::$modelTable->getAdapter()->quoteInto(' AND pageID = ?', $this->page->pageID);
    $this->modelTable->delete($where);
  }

  /**
   * Loads Model Sections
   */
  private function _loadModelSections() {
    $where = self::$sectionTable->getAdapter()->quoteInto('instanceID = ?', $this->page->instanceID) .
             self::$sectionTable->getAdapter()->quoteInto(' AND pageID = ?', $this->page->pageID);
    
    $rows = self::$sectionTable->fetchAll($where, 'seqNumber ASC');
    foreach ($rows as $row) {
      $this->modelSections[] = new ModelSectionModel(array('modelID' => $this->modelID,
                                                           'sectionID' => $row->sectionID,
                                                           'depth' => $this->depth,
                                                           'instance' => $this->compareInstance
      ));
    }
  }
  
}
