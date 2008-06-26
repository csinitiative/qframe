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
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */

/**
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */
class RegQ_Db_Table_Instance extends RegQ_Db_Table {

  protected $_name = 'instance';
  protected $_primary = array('instanceID');
  protected $_rowClass = 'RegQ_Db_Table_Row';

  public function insert(array $data) {
    $info = $this->info();
    $instanceNameLength = $info['metadata']['instanceName']['LENGTH'];
    if (strlen($data['instanceName']) > $instanceNameLength) {
      throw new Exception("Instance name must not be longer than $instanceNameLength characters");
    }
    return parent::insert($data);
  }
  
  public function getInstanceID($instrumentID, $instanceName) {

    $where = $this->getAdapter()->quoteInto('instrumentID = ?', intval($instrumentID));
    $where .= $this->getAdapter()->quoteInto(' AND instanceName = ?', $instanceName);

    if ($row = $this->fetchRow($where)) {
      return $row->instanceID;
    }

    return;

  }  

}
