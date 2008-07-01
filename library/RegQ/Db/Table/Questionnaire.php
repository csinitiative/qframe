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
class RegQ_Db_Table_Questionnaire extends RegQ_Db_Table {

  protected $_name = 'questionnaire';
  protected $_primary = 'questionnaireID';
  protected $_rowClass = 'RegQ_Db_Table_Row';

  public function getQuestionnaireID($questionnaireName, $questionnaireVersion, $revision) {

    $where = $this->getAdapter()->quoteInto('questionnaireName = ?', $questionnaireName);
    $where .= $this->getAdapter()->quoteInto(' AND questionnaireVersion = ?', $questionnaireVersion);
    $where .= $this->getAdapter()->quoteInto(' AND revision = ?', $revision);

    if ($row = $this->fetchRow($where)) {
      return $row->questionnaireID;
    }

    return;

  }
 
}
