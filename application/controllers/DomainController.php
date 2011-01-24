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
class DomainController extends QFrame_Controller_Admin {

  /**
   * Method to execute before dispatching takes place
   */
  public function preDispatch() {
    parent::preDispatch();
    if(!$this->_user->isGlobalAdministrator()) $this->denyAccess();
  }

  /**
   * Index action...displays whatever search terms/pages are requested
   */
  public function indexAction() {
    $this->view->q = $this->_getParam('q');
    $page = ($this->_hasParam('page')) ? intval($this->_getParam('page')) : 1;
    $this->view->pager =
        new QFrame_Paginator('DomainModel', 20, $page, 'domainDescription ASC', $this->view->q);
  }
  
  /**
   * Create action.  Creates a domain and redirects back to the index action.
   */
  public function createAction() {
    $domain = DomainModel::create(array(
                                   'domainDescription' => $this->_getParam('domainDescription')
                                 ));
    $domain->save();
    
    # Create a role for administers of this domain automatically
    $role = RoleModel::create(array('roleDescription' => "{$domain->domainDescription} Domain Administrators",
                                    'domainID' => $domain->domainID));
    $role->grant('administer', $domain);
    $role->save();

    $this->flash('notice', 'Domain successfully created');
    $this->_redirector->gotoRoute(array('action' => 'index'));
  }
  
  /**
   * Delete action.  Removes the specified domain.
   */
  public function deleteAction() {
    DomainModel::find($this->_getParam('id'))->delete();
    $this->flash('notice', 'Domain successfully deleted');
    $this->_redirector->gotoRoute(array('action' => 'index', 'id' => null));
  }
  
  /**
   * Edit action.  Sets a domain up to be modified.
   */
  public function editAction() {
    $this->view->domain = DomainModel::find($this->_getParam('id'));
  }
  
  /**
   * Modify action. Completes the modification of a domain
   */
  public function modifyAction() {
    $domain = DomainModel::find($this->_getParam('id'));
    $domain->setAttributes(array(
      'domainDescription'   => $this->_getParam('domainDescription')
    ));
    $domain->save();
    $this->flash('notice', 'Domain successfully modified');
    $this->_redirector->gotoRoute(array('action' => 'index', 'id' => null));
  }
  
  /**
   * Questionnaires action.  Presents a list of questionnaires that can be set as available
   * for a domain.
   */
  public function questionnairesAction() {
    // if this is a post request, just go ahead and update the allowed questionnaires
    if($this->getRequest()->isPost()) $this->updateQuestionnaires();
    
    if ($this->_hasParam('id')) {    
      $this->view->domain = DomainModel::find($this->_getParam('id'));
      $this->view->questionnaires = QuestionnaireModel::getAllQuestionnaires();
    }
    else {
      throw new Exception("Missing domain");
    }

  }
  
  /**
   * Processes an update to allowed questionnaires
   */
  private function updateQuestionnaires() {
    $questionnaires = $this->_getParam('questionnaire');
    $domain = DomainModel::find($this->_getParam('id'));
    
    foreach($questionnaires as $name => $value) {
	  $questionnaire = new QuestionnaireModel(array('questionnaireID' => $name));
      if($value) $domain->grant($questionnaire);
      else $domain->deny($questionnaire);
    }

    $domain->save();
    $this->flash('notice', 'Domain allowed questionnaires updated successfully');
    $this->_redirector->gotoRouteAndExit(array('action' => 'index', 'id' => null));
    
  }

}
