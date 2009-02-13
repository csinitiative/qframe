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
class QFrame_View_Helper_PageHelpers {

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
   * Generate the form element required for a particular question/response
   *
   * @param  QuestionModel question object for which an element is being generated 
   * @param  ResponseModel response object
   * @return string
   */
  public function questionElement($q, $r) {
    $b = new Tag_Builder;
    $responseText = $this->view->h($r->responseText);

    switch(strtolower(substr($q->format, 0, 1))) {
      case 'd':
        return
          '<br/>' . $this->view->formText("q{$q->questionID}", $responseText, array('class' => 'calendarText', 'size' => 13, 'readonly' => 1)) . $this->view->linkTo('#showCalendar', $this->view->imageTag('icons/calendar.png', array('id' => "c{$q->questionID}", 'class' => 'calendarButton', 'title' => 'calendar')));
      case 't':
        return
          '<br/>' . $this->view->formText("q{$q->questionID}", $responseText, array('size' => 50));
      case 's':
        $response = '<br/>';
        if(count($q->prompts) > 4) {
          $options = '';
          foreach($q->prompts as $prompt) {
            $class = ($prompt['requireAddlInfo']) ? 'require-addl' : null;
            $selected = ($prompt['promptID'] == $responseText) ? 'selected' : null;
            $options .= $b->option(
              array_filter(array(
                'value'     => $prompt['promptID'],
                'class'     => $class,
                'selected'  => $selected
              )),
              $this->view->h($prompt['value'])
            );
            $response .= $this->rulesElements($prompt);
          }
          $response .= $b->select(array('name' => "q{$q->questionID}"), $options);
        }
        else {
          foreach($q->prompts as $prompt) {
            $class = ($prompt['requireAddlInfo']) ? 'require-addl' : null;
            $checked = ($prompt['promptID'] == $r->responseText) ? 'checked' : null;
            $response .= $b->input(array_filter(array(
              'type'    => 'radio',
              'value'   => $prompt['promptID'],
              'name'    => "q{$q->questionID}",
              'checked' => $checked,
              'class'   => $class
            )));
            $response .= $this->rulesElements($prompt);
            $response .= $b->label($this->view->h($prompt['value']));
          }
        }
        return $response;
      case 'm':
        $response = '<br/>';
        foreach (split(',', $responseText) as $id) {
          $responseIDs[$id] = true;
        }      
        foreach($q->prompts as $prompt) {
          $class = ($prompt['requireAddlInfo']) ? 'require-addl' : null;
          $checked = (isset($responseIDs[$prompt['promptID']])) ? 'checked' : null;
          $response .= $b->input(array_filter(array(
            'type'    => 'checkbox',
            'value'   => $prompt['promptID'],
            'name'    => "q{$q->questionID}_m{$prompt['promptID']}",
            'checked' => $checked,
            'class'   => $class
          )));
          $response .= $this->rulesElements($prompt);
          $response .= $b->label($this->view->h($prompt['value']));
          $response .= '<br/>';
        }
        return $response;
      case '_':
        return $this->view->formHidden("q{$q->questionID}", '');
      default:
        throw new Exception('Unrecognized question type');
    }
    return '';
  }
  
  /**
   * Outputs a list of elements representing the rules for a prompt
   *
   * @param Object prompt whose rules we are generating
   */
  private function rulesElements($prompt) {
    if(count($prompt['rules']) <= 0) return;
    
    $rules = '';
    foreach($prompt['rules'] as $rule) {
      $ruleValue = "{$rule->targetID}:{$rule->type}";
      $rules .= $this->view->formHidden("rule", $ruleValue);
    }

    $builder = new Tag_Builder;
    return $builder->div(
      array(
        'id'    => "rules-{$prompt['promptID']}",
        'class' => 'rules',
        'style' => 'display: none;'
      ),
      $rules
    );
  }
  
  /**
   * Generate a view-only version of the form element
   *
   * @param  ResponseModel question response
   * @return string
   */
  public function questionText($response) {    
    $question = $response->parent;
    $builder = new Tag_Builder;
    $responseText = $this->view->h($response->responseText);
    switch(strtolower(substr($question->format, 0, 1))) {
      case 'd':
        return $builder->span(array('class' => 'response'), $responseText);
      case 't':
        return $builder->span(array('class' => 'response'), $responseText);
      case 's':
        foreach($question->prompts as $prompt) {
          if($prompt['promptID'] == $responseText) {
            return $builder->span(array('class' => 'response'), $this->view->h($prompt['value']));
          }
        }
      case 'm':
        $promptIDs = array();
        foreach (split(',', $responseText) as $promptID) {
          $promptIDs[$promptID] = true;
        }
        $return = array();
        foreach($question->prompts as $prompt) {
          if(isset($promptIDs[$prompt['promptID']])) {
            $return[] = $builder->span(array('class' => 'response'), $this->view->h($prompt['value']));
          }
        }
        return join(', ', $return);
    }
    return '';
  }

  /**
   * Generate responder information
   *
   * @param  ResponseModel question response
   * @return string
   */
  public function responderInfo($response) {
    $builder = new Tag_Builder;
    $result = '';
    if (is_numeric($response->dbUserID) && strlen($response->responseText) > 0) {
      if ($response->dbUserID != -1) {
        $user = new DbUserModel(array('dbUserID' => $response->dbUserID));
        $result = "[{$user->dbUserName}]";
      }
    }
    $result = $builder->span(array('class' => 'responderInfo'), $result);
    return $result;
  }
    
  /**
   * Generates the HTML for a question prompt
   *
   * @param  QuestionModel question the prompt is for
   * @return string
   */
  public function questionPrompt(QuestionModel $q) {
    $b = new Tag_Builder;
    $qNum = $this->view->h($q->questionNumber);
    if($qNum === null || $qNum === '' || $qNum === '0')
      $qNum = '';
    else $qNum = "({$qNum}) &nbsp;";
    return $b->label(
      array('for' => "q{$q->questionID}", 'class' => 'outer'),
      $b->em($qNum),
      $b->strong($this->view->h($q->qText)),
      $this->referenceString($q)
    );
  }
  
  /**
   * Generate a string containing all of the references for an object
   *
   * @param  mixed  object whose references we are going to print out
   * @param  string (optional) prompt string
   * @return string
   */
  public function referenceString($referenced, $prompt = null) {
    if(!isset($referenced->references) || count($referenced->references) <= 0) return;
    
    $builder = new Tag_Builder;
    foreach($referenced->references as $reference) {
      $shortName = $this->view->h($reference['shortName']);
      $item = $this->view->h($reference['item']);
      $references[] = "{$shortName} {$item}";
    }
    
    $output = '';
    if($prompt !== null) $output = "<strong>{$prompt}:</strong> ";
    $output .= (isset($references)) ? $builder->em(' (' . implode(', ', $references) . ')') : '';
    return $output;
  }
  
  /**
   * Return HTML for the additional information box
   *
   * @param  ResponseModel response in queston
   * @return string
   */
  public function additionalInfo(ResponseModel $response) {
    $builder = new Tag_Builder;
    $class = 'additionalInfo';
    if($response->hasAdditionalInfo()) {
      $class .= ' hasContent';
      $content = $this->view->h($response->additionalInfo);
      $style = '';
      $mod = 1;
    }
    elseif($response->requiresAdditionalInfo()) {
      $class .= ' additionalInfoRequired';
      $content = 'Enter additional information here (required)';
      $style = '';
      $mod = 0;
    }
    else {
      $style = 'display: none;';
      $content = 'Enter additional information here';
      $mod = 0;
    }
    
    $addlInfo = "<br/>additional information:<br/>\n";
    $addlInfo .= $this->view->formTextarea("q{$response->parent->questionID}_addl", $content, array('class' => $class, 'style' => $style));
    $addlInfo = $builder->span(array('class' => 'additionalInfo_main', 'style' => $style), $addlInfo);
    $addlInfoMod = $this->view->formHidden("q{$response->parent->questionID}_addl_mod", $mod);
    
    return $addlInfo . $addlInfoMod;
  }

  /**
   * Return HTML for the private notes box
   *
   * @param  ResponseModel response in queston
   * @return string
   */
  public function privateNote(ResponseModel $response) {
    $builder = new Tag_Builder;
    $class = 'privateNote';
    if($response->hasPrivateNote()) {
      $class .= ' hasContent';
      $content = $this->view->h($response->privateNote);
      $style = '';
      $mod = 1;
    }
    else {
      $style = 'display: none;';
      $content = 'Enter private notes here';
      $mod = 0;
    }

    $privNote = "<br/>private notes:<br/>\n";
    $privNote .= $this->view->formTextarea("q{$response->parent->questionID}_privateNote", $content, array('class' => $class, 'style' => $style));
    $privNote = $builder->span(array('class' => 'privateNote_main', 'style' => $style), $privNote);
    $privNoteMod = $this->view->formHidden("q{$response->parent->questionID}_privateNote_mod", $mod);

    return $privNote . $privNoteMod;
  }
  
  /**
   * Outputs an option button for the "more options" panel
   *
   * @param  string name of the javascript event handler
   * @param  string image filename
   * @param  string description (when hovered over)
   * @param  string (optional) class to apply to the button
   * @return string
   */
  public function optionButton($handler, $image, $description, $class = 'inline') {
    $image = 'icons/ffffff/' . $image;
    return $this->view->linkTo(
      "#{$handler}",
      $this->view->imageTag($image, array('class' => $class, 'title' => $description))
    );
  }

  /**
   * Outputs an subpages buttons
   *
   * @param  PageModel Page object
   * @param  integer The current page number
   * @param  integer Questions per page
   * @return string
   */
  public function subpagesButtons($page, $spNum, $qPerPage) {
    $totalSubPages = ceil($page->numQuestions / $qPerPage);
    if($page->numQuestions <= 0 || $totalSubPages <= 1) return;

    $action = Zend_Controller_Front::getInstance()->getRequest()->getActionName();
    for ($p = 1; $p <= $totalSubPages; $p++) {
      if ($p == $spNum) {
        $links[] = "<span class='selectedsubpage'>{$p}</span>";
      }
      else {
        $links[] = $this->view->linkTo($this->view->url(array('action' => $action, 'id' => $page->pageID)) . "?sp={$p}", $p);
      }
    }
    $actions = '<ul id="subpages"><li>';
    if(isset($links)) $actions .= implode($links, ' | </li><li>') . '</li>';
    return "{$actions}<li class=\"bottom\"></li></ul>";
  }

  
  /**
   * Returns a series of attachment links for the given question
   *
   * @param  QuestionModel question attachments are for
   * @param  string        (optional) mode that we are rendering in
   * @return string
   */
  public function attachments(QuestionModel $question, $mode = 'edit') {
    $output = '';
    foreach($question->getAttachments() as $id => $properties) {
      $attachment = array(
        'filename'    => $properties['filename'],
        'elementName' => "q{$question->questionID}_file{$id}_delete",
        'url'         => $this->view->url(array(
          'controller' => 'page',
          'action'     => 'download',
          'id'         => $question->questionID
        )) . "?fileID={$id}"
      );
      $output .= $this->view->renderPartial(
        'attachment',
        $attachment,
        false,
        false,
        array('mode' => $mode)
      );
    }
    return $output;
  }
  
  /**
   * Returns a form that will be used for uploading files
   */
  public function uploadForm() {
    return $this->view->form(
      array('action' => 'upload', 'id' => null),
      false,
      'post',
      array(
        'style'   => 'display: none;',
        'id'      => 'uploadForm',
        'target'  => 'uploadIframe',
        'enctype' => 'multipart/form-data'
      )
    ) . $this->view->form(null, true);
  }
  
  /**
   * Returns a link for the header bar of a page
   *
   * @param  PageModel    current page
   * @param  DbUserModel currently logged in user
   * @param  integer The current page number
   * @return string
   */
  public function topLinks($page, $user, $spNum) {
    if($page->numQuestions <= 0) return;
    
    $current = Zend_Controller_Front::getInstance()->getRequest()->getActionName();
    $actions = "<li class=\"current\">{$current}</li>";
    //TODO need to relocate this list of available actions somewhere more appropriate
    foreach(array('view', 'edit', 'approve') as $action) {
      if($action !== $current && $user->hasAccess($action, $page)) {
        $links[] = $this->view->linkTo($this->view->url(array('action' => $action, 'id' => $page->pageID)) . "?sp={$spNum}", $action);
      }
    }
    $actions = '<ul id="pageHeading">' . $actions . '<li>';
    if(isset($links)) $actions .= implode($links, ' | </li><li>') . '</li>';
    return "{$actions}<li class=\"stats\">{$this->stats($page)}</li><li class=\"bottom\"></li></ul>";
  }

  /**
   * Returns formatted output for disabled question's source information
   *
   * @param  QuestionModel the disabled question
   * @return string
   */
  public function disabledSource($question) {
    $source = $question->getDisableSourceQuestions();
    if (empty($source)) return;
    $pageHeader = $source[0]->parent->parent->pageHeader;
    $s = $source[0];
    $questionNumber = $s->questionNumber;
    $qText = (strlen($s->qText) <= 30) ? $s->qText : substr($s->qText, 0, 30) . "...";
    if ($questionNumber)
      return "{$pageHeader}: {$questionNumber}";
    else
      return "{$pageHeader}: {$qText}";
  }
  
  /**
   * Returns an <li> with page statistics inside
   *
   * @param  PageModel the page we are looking at statistics for
   * @return string
   */
  public function stats($page) {
    $disabledNum = $page->getNumQuestionsDisabled();
    $completePcnt = round(($page->numComplete / $page->numQuestions) * 100, 2) . '%';
    $approvedPcnt = round(($page->numApproved / $page->numQuestions) * 100, 2) . '%';
    $complete = "complete: <strong>{$page->numComplete}</strong> of " .
        "<strong>{$page->numQuestions}</strong> ({$completePcnt})";
    $availableQuestions = $page->numQuestions - $disabledNum;
    if ($availableQuestions > 0) {
      $approved = "approved: <strong>{$page->numApproved}</strong> of " .
                  "<strong>{$availableQuestions}</strong> ({$approvedPcnt})";
      if ($disabledNum > 0) 
        $approved .= " | disabled: {$disabledNum}";
      return "{$complete} | {$approved}";
    }
    return "{$complete} | approved: all disabled";
  }

}
