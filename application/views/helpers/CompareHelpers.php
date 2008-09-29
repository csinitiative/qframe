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
   * Render a question (including sub questions, response elements, etc)
   *
   * @param  ModelQuestionModel question being rendered
   * @return string
   */
  public function renderQuestion(ModelQuestionModel $question) {
    $builder = new Tag_Builder;
    
    $questionText = $builder->strong($this->view->h($question->qText));
    if(!_blank($question->questionNumber)) {
      $questionNum = $builder->em("({$this->view->h($question->questionNumber)})");
      $questionText = "{$questionNum}&nbsp;{$questionText}";
    }
    $questionText .= $this->referenceString($question);
    
    $rendered = $builder->div(array('class' => 'questionText'), $questionText);
    $rendered .= $builder->span(array('class' => 'response'), $this->renderResponse($question));

    return $rendered;
  }
  
  /**
   * Generate a string containing all of the references for an object
   *
   * @param  mixed  object whose references we are going to print out
   * @return string
   */
  public function referenceString($referenced) {
    // if the object has no references, just return a blank string
    if(!isset($referenced->references) || count($referenced->references) <= 0) return '';
    
    // new tag builder object
    $builder = new Tag_Builder;
    
    // go through each reference generating a short reference string
    foreach($referenced->references as $reference) {
      $shortName = $this->view->h($reference['shortName']);
      $item = $this->view->h($reference['item']);
      $references[] = "{$shortName} {$item}";
    }
    
    // return an imploded version of all generated strings
    return $builder->em('&nbsp;(' . implode(',&nbsp;', $references) . ')');
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
    // if we are dealing with a virtual question, don't output a control
    if($question->virtualQuestion) return '';
    
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
  
  /**
   * Generates a drop down box listing all instances that belong to the chosen instance
   *
   * @param  Array   list of all instances to which this user has access
   * @return string
   */
  public function instanceSelect($instances) {
    $options[0] = ' ';
    foreach($instances as $instance) {
      $options[$instance->instanceID] = $this->view->h($instance->instanceName);
    }
    
    return $this->view->formSelect('instance', null, null, $options);
  }
  
  /**
   * Escape a string for CSV
   *
   * @param  string that is being esacped
   * @return string
   */
  public function escapeForCsv($string) {
    $result = preg_replace('/"/', '""', $string);
    $result = preg_replace("/\s+/", ' ', $result);
    $result = preg_replace("/^\s+|\s+$/", '', $result);
    
    return $result;
  }
  
  /**
   * Return whether or not a failures array really contains any failures
   *
   * @param  array of failures
   * @return boolean
   */
  public function hasFailures(array $failures) {
    $fail = (isset($failures['model_fail']) && count($failures['model_fail']) > 0);
    $addl = (isset($failures['additional_information']) &&
             count($failures['additional_information']) > 0);
    $pass = (isset($failures['model_pass']) && count($failures['model_pass']) > 0);
    
    return ($fail || $addl || $pass);
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
   * Return HTML for the remediation info box
   *
   * @param  ModelQuestionModel
   * @return string
   */
  public function remediationInfo(ModelQuestionModel $modelQuestion) {
    $class = 'remediationInfo';
    if($modelQuestion->hasRemediationInfo()) {
      $class .= ' hasContent';
      $content = $this->view->h($modelQuestion->remediationInfo());
      $style = '';
      $mod = 1;
    }
    else {
      $style = 'display: none;';
      $content = 'Enter remediation information here';
      $mod = 0;
    }

    $remediationInfo = $this->view->formTextarea("response[{$modelQuestion->questionID}][remediationInfo]", $content, array(
      'class' => $class,
      'style' => $style
    ));
    $remediationInfoMod = $this->view->formHidden("response[{$modelQuestion->questionID}][remediationInfoMod]", $mod);

    return $remediationInfo . $remediationInfoMod;
  }

}
