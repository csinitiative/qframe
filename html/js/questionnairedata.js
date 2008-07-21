var Data = {
  
  /**
   * Event handler for drop down change events
   *
   * @param Event change event
   */
  selectChange: function(event) {
    Event.element(event).form.submit();
  },

  /**
   * Handles a click on the "QuestionnaireDefinitionXMLDownload" button
   *
   * @param Event click event for the "QuestionnaireDefinitionXMLDownload" button
   */
  QuestionnaireDefinitionXMLDownloadHandler: function(event) {
    _handle(event, 'QuestionnaireDefinitionXML', 'QuestionnaireDefinitionXMLDownload');
  },

  /**
   * Handles a click on the "QuestionnaireDefinitionXMLView" button
   *
   * @param Event click event for the "QuestionnaireDefinitionXMLView" button
   */
  QuestionnaireDefinitionXMLViewHandler: function(event) {
    _handle(event, 'QuestionnaireDefinitionXML', 'QuestionnaireDefinitionXMLView');
  },

  /**
   * Handles a click on the "ImportXML" button
   *
   * @param Event click event for the "ImportXML" button
   */
  ImportQuestionnaireHandler: function(event) {
    _handle(event, 'ImportQuestionnaire', 'ImportQuestionnaire');
  },

  /**
   * Handles a click on the "ResponsesXMLSchemaDownload" button
   *
   * @param Event click event for the "ResponsesXMLSchemaDownload" button
   */
  ResponsesXMLSchemaDownloadHandler: function(event) {
    _handle(event, 'ResponsesXMLSchema', 'ResponsesXMLSchemaDownload');
  },

  /**
   * Handles a click on the "ResponsesXMLSchemaView" button
   *
   * @param Event click event for the "ResponsesXMLSchemaView" button
   */
  ResponsesXMLSchemaViewHandler: function(event) {
    _handle(event, 'ResponsesXMLSchema', 'ResponsesXMLSchemaView');
  },

  /**
   * Handles a click on the "CompletedResponsesXMLSchemaDownload" button
   *
   * @param Event click event for the "CompletedResponsesXMLSchemaDownload" button
   */
  CompletedResponsesXMLSchemaDownloadHandler: function(event) {
    _handle(event, 'CompletedResponsesXMLSchema', 'CompletedResponsesXMLSchemaDownload');
  },

  /**
   * Handles a click on the "CompletedResponsesXMLSchemaView" button
   *
   * @param Event click event for the "CompletedResponsesXMLSchemaView" button
   */
  CompletedResponsesXMLSchemaViewHandler: function(event) {
    _handle(event, 'CompletedResponsesXMLSchema', 'CompletedResponsesXMLSchemaView');
  },
  
  /**
   * Handles a click on the "importQuestionnaire" button
   *
   * @param Event click event for the "importQuestionnaire" button
   */
  importQuestionnaireHandler: function(event) {
    _handle(event, 'importQuestionnaire', 'importQuestionnaire');
  },
  
  /**
   * Handles a click on the "deleteQuestionnaire" button
   *
   * @param Event click event for the "deleteQuestionnaire" button
   */
  deleteQuestionnaireHandler: function(event) {
    _handle(event, 'deleteQuestionnaire', 'deleteQuestionnaire');
  },

  /**
   * Sets up whatever needs to be set up
   *
   * @param Event window load event object
   */
  setup: function(event) {
    $$('.option select').each(function(select) {
      select.observe('change', Data.selectChange);
    });

    $$('.dataButton').each(function(button) {
      var action = button.href.replace(/^.*#(\w+)$/, '$1');
      if(Data[action + 'Handler']) button.observe('click', Data[action + 'Handler']);
    });
    
  }

}

function _handle (event, form, action) {
  event.stop();

  var dims = document.viewport.getDimensions();
  var offsets = document.viewport.getScrollOffsets();
  var content = $('disableOverlay').down('.content');
  var top = (dims.height / 2) - 30 + offsets.top;
  var left = (dims.width / 2) - 30 + offsets.left;

  content.setStyle({ top: top + 'px', left: left + 'px' });
  Effect.Appear('disableOverlay', { duration: 0.15, to: 0.90 });
  if (action) {
    $$('form.' + form).first().action = $F('base_url') + '/questionnairedata/' + action;
  }
  $$('form.' + form).first().submit();

  if (action.match(/(Download|Archive)$/)) {
    setTimeout("Effect.Fade('disableOverlay', { duration: 0.01, to: 0.00 })", 4000);
  }
}

Event.observe(window, 'load', Data.setup);
