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
 * @category   QFrame_View
 * @package    QFrame_View_Helper
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */


/**
 * @category   QFrame_View
 * @package    QFrame_View_Helper
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */
class QFrame_View_Helper_ApplicationHelpers {
  
  /**
   * Stores the associated view for persistence
   * @var Zend_View_Interface
   */
  private $view = null;
  
  /**
   * Sets the associated view (should be called automatically by the view)
   *
   * @param Zend_View_Interface
   */
  public function setView($view) {
    $this->view = $view;
  }

  /**
   * Provides a short form of the htmlentities() function as a helper and provides a single
   * point where additional transformations can be performed on data that is coming from
   * the database to avoid XSS attacks.
   *
   * @param  string the string being transformed
   * @return string
   */
  public function h($str) {
    return htmlentities($str, ENT_COMPAT, "UTF-8");
  }
  
  /**
   * Generates a link to some javascript
   *
   * @param  string       url to link to
   * @param  string       text of the link
   * @param  string|array (optional) alternate url to use (if js is unavailable)
   */
  public function linkToJavascript($javascript, $text, $url = '#') {
    if(is_array($url)) $url = $this->view->url($url, null, true);
    echo Zend_Controller_Front::getInstance()->getBaseUrl(); exit;
    return '<a href="' . $url . '" onclick="' . $javascript . '">' . $text . '</a>';
  }
  
  /**
   * Generates a link to a specific URL
   *
   * @param  string url to link to
   * @param  string (optional) text of the link
   * @param  Array  (optional) additional attributes for this link
   */
  public function linkTo($url, $text = null, $attrs = array()) {    
    if(is_array($url)) {
      if(!isset($url['controller'])) {
        $url['controller'] =
          Zend_Controller_Front::getInstance()->getRequest()->getControllerName();
      }
      $baseUrl = $this->view->url($url, null, true);
      unset($url['module']);
      unset($url['controller']);
      unset($url['action']);
      unset($url['id']);
      $queryString = (count($url) > 0) ? '?' . http_build_query($url) : '';
      $url = "{$baseUrl}{$queryString}";
    }
    if($text === null) $text = $url;
    $attrs = self::tagAttributes($attrs);
    return "<a href=\"{$url}\" {$attrs}>{$text}</a>";
  }
  
  /**
   * Generates a link to a specific URL
   *
   * @param  string name of the image file (sans base URL and images/ directory)
   * @param  Array  (optional) list of additional tag options
   * @return string
   */
  public function imageTag($img, $options = array()) {
    $options = array_merge(array(
      'alt' => preg_replace('/\.\w{2,5}$/', '', $img)
    ), $options);
    
    $url = "<img src=\"{$this->imageSrc($img)}\" ";
    foreach($options as $property => $value) $url .= sprintf('%s="%s" ', $property, $value);
    
    return $url . '/>';
  }
  
  /**
   * Generates an absolutely pathed URL for an image file
   *
   * @param  string base image name
   * @return string
   */
  public function imageSrc($image) {
    $baseUrl = Zend_Controller_Front::getInstance()->getBaseUrl();
    return "{$baseUrl}/images/{$image}";
  }
  
  /**
   * Output a lock icon which also serves as a link to unlock a page (if the user has permission
   * to do this)
   *
   * @param  Array       menu item array for this lock
   * @param  DbUserModel user that is currently logged in
   * @return string
   */
  public function lockIcon($menu, DbUserModel $user) {
    if($menu['locked']) {
      $user = new DbUserModel(array('dbUserID' => $menu['locked'], 'depth' => 'dbUser'));
      $title = "Currently locked by '{$this->h($user->dbUserFullName)}'.";
      if($user->hasAccess('edit', $menu['page']) ||
         $user->hasAccess('approve', $menu['page']) ||
         $user->hasAccess('edit', $menu['page']->parent->domain) ||
         $user->hasAccess('approve', $menu['page']->parent->domain)) {
        $title .= ' Click to unlock.';
        $html = $this->view->linkTo('#', $this->view->imageTag('icons/ffffff/lock_small.png', array(
          'id'    => $this->view->url(array('action' => 'unlock', 'id' => $menu['page']->pageID)),
          'class' => 'inline lock tooltip',
          'tooltip' => $title
        )));
      }
      else {
        $html = $this->view->imageTag('icons/ffffff/lock_small.png', array(
          'class' => 'inline',
          'title' => $title
        ));
      }
    }
    else {
      $html = '';
    }
    return $html;
  }
  
  /**
   * Check to see whether a value is "blank" (null or == '')
   *
   * @param  string  value to be checked
   * @return boolean
   */
  public function isBlank($value) {
    return $value === null || $value === '';
  }
  
  /**
   * Generates an html tag attribute string from an array of attribute/value pairs
   *
   * @param  Array  attributes
   * @return string
   */
  private static function tagAttributes($attrs) {
    $attrString = '';
    foreach($attrs as $attr => $value) {
      $attrString .= (($attrString == '') ? '' : ' ') . "{$attr}=\"{$value}\"";
    }
    return $attrString;
  }  

  /**
   * Generates a drop down box listing all instances that belong to the chosen questionnaire
   *
   * @param integer id of questionnaire that has been selected
   * @param string element name
   * @param integer id of instance that should be selected
   * @param DbUserModel
   * @return string
   */
  public function instanceSelect($questionnaireID, $name = 'instance', $instanceID = null, $user, $permissions = array('edit', 'view', 'approve')) {

    if ($questionnaireID) {
      $questionnaire = new QuestionnaireModel(array('questionnaireID' => $questionnaireID,
                                                    'depth' => 'page'));
      $options[0] = '';
      
      while ($instance = $questionnaire->nextInstance()) {
        $instanceName = $instance->instanceName;
        if ($user->hasAnyAccess($instance->domain)) {
          $options[$instance->instanceID] = $instanceName;
          break;
        }
        while ($page = $instance->nextPage()) {
          if ($user->hasAnyAccess($page)) {
            $options[$instance->instanceID] = $instanceName;
            break 2;
          }
        }
      }
    }
    else {
      $options[0] = 'Select a questionnaire';
    }

    return $this->view->formSelect($name, $instanceID, null, $options);
  }

  /**
   * Generates a drop down box listing all questionnaires
   *
   * @param integer current questionnaire (or null if no current questionnaire)
   * @param string element name
   * @param DbUserModel
   * @return string
   */
  public function questionnaireSelect($questionnaireID = null, $name = 'questionnaire', $user) {
    if($questionnaireID === null) $options[0] = ' ';

    $questionnaires = QuestionnaireModel::getAllQuestionnaires('page');

    foreach($questionnaires as $questionnaire) {
      $questionnaireName = $this->view->h($questionnaire->questionnaireName);
      $questionnaireVersion = $this->view->h($questionnaire->questionnaireVersion);
      $revision = $this->view->h($questionnaire->revision);
      while ($instance = $questionnaire->nextInstance()) {
        if ($user->hasAnyAccess($instance->domain)) {
          $options[$questionnaire->questionnaireID] = "{$questionnaireName} {$questionnaireVersion}";
          if ($revision != 1) {
            $options[$questionnaire->questionnaireID] .= " (rev. {$revision})";
          }
          break;
        }
        while ($page = $instance->nextPage()) {
          if ($user->hasAnyAccess($page)) {
            $options[$questionnaire->questionnaireID] = "{$questionnaireName} {$questionnaireVersion}";
            if ($revision != 1) {
              $options[$questionnaire->questionnaireID] .= " (rev. {$revision})";
            }
            break 2;
          }
        }
      }
    }

    return $this->view->formSelect($name, $questionnaireID, null, $options);
  }

  /**
   * Generates a drop down box listing all domains to which the user has permission
   *
   * @param integer current domain (or null if no current domain)
   * @param string element name
   * @param DbUserModel
   * @return string
   */
  public function domainSelect($domainID = null, $name = 'domain', $user, $permissions = array('edit', 'view', 'approve')) {
    if($domainID === null) $options[0] = ' ';
    $domains = DomainModel::getAllDomains();
    foreach($domains as $domain) {
      foreach ($permissions as $permission) {
        if ($user->hasAccess($permission) || $user->hasAccess($permission, $domain)) {
          $domainDescription = $this->view->h($domain->domainDescription);
          if(!isset($options[$domain->domainID])) {
            $options[$domain->domainID] = $domainDescription;
          }
        }
      }
    }
    return $this->view->formSelect($name, $domainID, null, $options);
  }

}
