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
class QFrame_View_Helper_CompareHelpers {
  
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
   * Generates a drop down box listing all questionnaires
   *
   * @param  Array   list of all questionnaires
   * @param  integer (optional) currently selected questionnaire
   * @return string
   */
  public function questionnaireSelect($questionnaires, $selected = null) {
    if($selected === null) $options[0] = ' ';
    foreach($questionnaires as $questionnaire) {
      $questionnaireName = $this->view->h($questionnaire->questionnaireName);
      $questionnaireVersion = $this->view->h($questionnaire->questionnaireVersion);
      $revision = $this->view->h($questionnaire->revision);
      if(!isset($options[$questionnaire->questionnaireID])) {
        $options[$questionnaire->questionnaireID] = "{$questionnaireName} {$questionnaireVersion}";
        if ($revision != 1) {
          $options[$questionnaire->questionnaireID] .= " (rev. {$revision})";
        }
      }
    }
    return $this->view->formSelect('questionnaire', $selected, null, $options);
  }

  /**
   * Generates a drop down box listing all models (or just a message to select a questionnaire)
   *
   * @param  Array   list of all models
   * @return string
   */
  public function modelSelect($models) {
    if($models === null) $options = array(0 => '  Select a Questionnaire');
    else {
      $options = array(0 => ' ');
      foreach($models as $model) {
        $options[$model->modelID] = $model->name;
      }
    }
    return $this->view->formSelect('model', null, null, $options);
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
   * Render a question (including sub questions, response elements, etc)
   *
   * @param  ModelQuestionModel question being rendered
   * @return string
   */
  public function renderQuestion(ModelQuestionModel $question) {
    $builder = new Tag_Builder;
    $rendered = $builder->div(array('class' => 'question'), $this->view->h($question->qText));
    $rendered .= $builder->div(array('class' => 'response'), $this->renderResponse($question));
    return $rendered;
  }
  
  /**
   * Render form controls for a text question
   *
   * @param  ModelQuestionModel question being rendered
   * @return string
   */
  public function renderText(ModelQuestionModel $question) {
    $name = "response[{$question->questionID}][target]";
    $value = ($question->hasModelResponse()) ? $question->nextModelResponse()->target : null;
    return $this->view->formText($name, $value, array('size' => 50));
  }
  
  /**
   * Render form controls for a date question
   *
   * @param  ModelQuestionModel question being rendered
   * @return string
   */
  public function renderDate(ModelQuestionModel $question) {
    $name = "response[{$question->questionID}][target]";
    $value = ($question->hasModelResponse()) ? $question->nextModelResponse()->target : null;
    return $this->view->formText(
      $name,
      $value,
      array('class' => 'calendarText', 'size' => 13, 'readonly' => 1)
    ) .
    $this->view->linkTo('#showCalendar', $this->view->imageTag('icons/calendar.png', array(
      'id'    => "c{$question->questionID}",
      'class' => 'calendarButton',
      'title' => 'calendar'
    )));
  }
  
  /**
   * Render form controls for a single-select question
   *
   * @param  ModelQuestionModel question being rendered
   * @return string
   */
  public function renderSingleSelect(ModelQuestionModel $question) {
    $rendered = '';
    foreach($question->prompts as $prompt) {
      $name = "response[{$question->questionID}][target][{$prompt['promptID']}]";
      $value = ($question->hasModelResponse($prompt['promptID'])) ? 1 : 0;
      $rendered .= $this->view->formCheckbox($name, $value);
      $rendered .= $this->view->formLabel($name, $prompt['value']);
    }
    return $rendered;
  }
    
  /**
   * Render form controls necessary for responding to a particular question
   *
   * TODO - This needs to be extended to deal with multi-select questions
   *
   * @param  ModelQuestionModel question response controls are being rendered for
   * @return string
   */
  public function renderResponse(ModelQuestionModel $question) {
    $result = '';
    switch(substr($question->format, 0, 1)) {
      case 'T':
        $result .= $this->renderText($question);
        break;
      case 'D':
        $result .= $this->renderDate($question);
        break;
      case 'S':
        $result .= $this->renderSingleSelect($question);
        break;
      case '_':
        if($question->format == '_questionGroup') {
          foreach($question->children as $child) {
            $result .= $this->renderQuestion($child);
          }
          return $result;
        }
      default:
        throw new Exception('Unknown question type');
    }
    $name = "response[$question->questionID][noinclude]";
    $result .= $this->view->formCheckbox(
      $name,
      $question->hasNoPreference(),
      array('class' => 'noinclude')
    );
    $result .= $this->view->formLabel($name, 'Do not include');
    return $result;
  }
}