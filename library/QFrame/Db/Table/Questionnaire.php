<?php

/**
 * This file is part of QFrame.
 *
 * QFrame is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * QFrame is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @copyright  Copyright (c) 2007, 2008, 2009, 2010, 2011 Collaborative Software Foundation (CSF)
 * @license    http://www.gnu.org/licenses/agpl-3.0.txt   GNU Affero General Public License v3
 */

/**
 * @copyright  Copyright (c) 2007, 2008, 2009, 2010, 2011 Collaborative Software Foundation (CSF)
 * @license    http://www.gnu.org/licenses/agpl-3.0.txt   GNU Affero General Public License v3
 */
class QFrame_Db_Table_Questionnaire extends QFrame_Db_Table {

  protected $_name = 'questionnaire';
  protected $_primary = 'questionnaireID';
  protected $_rowClass = 'QFrame_Db_Table_Row';

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
