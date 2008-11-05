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
   * Handles a click on the "CopyInstance" button
   *
   * @param Event click event for the "CopyInstance" button
   */
  CopyInstanceHandler: function(event) {
    _handle(event, 'CopyInstance', '');
  },
  
  /**
   * Handles a click on the "NewInstance" button
   *
   * @param Event click event for the "NewInstance" button
   */
  NewInstanceHandler: function(event) {
    _handle(event, 'NewInstance', 'NewInstance');
  },

  /**
   * Handles a click on the "ImportXML" button
   *
   * @param Event click event for the "ImportXML" button
   */
  ImportInstanceHandler: function(event) {
    _handle(event, 'ImportInstance', 'ImportInstance');
  },

  /**
   * Handles a click on the "ResponsesOnlyXMLArchive" button
   *
   * @param Event click event for the "ResponsesOnlyXMLArchive" button
   */
  ResponsesOnlyXMLArchiveHandler: function(event) {
    _handle(event, 'ResponsesOnlyXML', 'ResponsesOnlyXMLArchive');
  },
  
  /**
   * Handles a click on the "ResponsesOnlyXMLDownload" button
   *
   * @param Event click event for the "ResponsesOnlyXMLDownload" button
   */
  ResponsesOnlyXMLDownloadHandler: function(event) {
    _handle(event, 'ResponsesOnlyXML', 'ResponsesOnlyXMLDownload');
  },

  /**
   * Handles a click on the "PDFDownload" button
   *
   * @param Event click event for the "PDFDownload" button
   */
  PDFDownloadHandler: function(event) {
    _handle(event, 'ResponsesFullXML', 'PDFDownload');
  },

  /**
   * Handles a click on the "ResponsesOnlyXMLView" button
   *
   * @param Event click event for the "ResponsesOnlyXMLView" button
   */
  ResponsesOnlyXMLViewHandler: function(event) {
    _handle(event, 'ResponsesOnlyXML', 'ResponsesOnlyXMLView');
  },

  /**
   * Handles a click on the "ResponsesFullXMLArchive" button
   *
   * @param Event click event for the "ResponsesFullXMLArchive" button
   */
  ResponsesFullXMLArchiveHandler: function(event) {
    _handle(event, 'ResponsesFullXML', 'ResponsesFullXMLArchive');
  },
  
  /**
   * Handles a click on the "ResponsesFullXMLDownload" button
   *
   * @param Event click event for the "ResponsesFullXMLDownload" button
   */
  ResponsesFullXMLDownloadHandler: function(event) {
    _handle(event, 'ResponsesFullXML', 'ResponsesFullXMLDownload');
  },

  /**
   * Handles a click on the "ResponsesFullXMLView" button
   *
   * @param Event click event for the "ResponsesFullXMLView" button
   */
  ResponsesFullXMLViewHandler: function(event) {
    _handle(event, 'ResponsesFullXML', 'ResponsesFullXMLView');
  },
  
  /**
   * Handles a click on the "deleteInstance" button
   *
   * @param Event click event for the "deleteInstance" button
   */
  deleteInstanceHandler: function(event) {
    _handle(event, 'deleteInstance', 'deleteInstance');
  },
  
  /**
   * Handles a click on the "newInstanceImportInstanceResponses" button
   *
   * @param Event click event for the "newInstanceImportInstanceResponses" button
   */
  newInstanceImportInstanceResponsesHandler: function(event) {
  	Effect.Appear('disableNewInstanceImportResponses', { duration: 0.01, to: 1.00 });
  },
    
  /**
   * Handles a click on the "newInstanceDoNotImportAnyResponses" button
   *
   * @param Event click event for the "newInstanceDoNotImportAnyResponses" button
   */
  newInstanceDoNotImportAnyResponsesHandler: function(event) {
  	Effect.Fade('disableNewInstanceImportResponses', { duration: 0.01, to: 0.00 });
  },
  
  /**
   * Handles a click on the "importInstanceResponses" button
   *
   * @param Event click event for the "importInstanceResponses" button
   */
  importInstanceResponsesHandler: function(event) {
  	Effect.Appear('disableImportResponses', { duration: 0.01, to: 1.00 });
  },
  
  /**
   * Handles a click on the "importXMLResponses" button
   *
   * @param Event click event for the "importXMLResponses" button
   */
  importXMLResponsesHandler: function(event) {
  	Effect.Fade('disableImportResponses', { duration: 0.01, to: 0.00 });
  },
  
  /**
   * Handles a click on the "doNotImportAnyResponses" button
   *
   * @param Event click event for the "doNotImportAnyResponses" button
   */
  doNotImportAnyResponsesHandler: function(event) {
  	Effect.Fade('disableImportResponses', { duration: 0.01, to: 0.00 });
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
    
    $$('.importResponsesRadioButton').each(function(button) {
      var action = button.value;
      if(Data[action + 'Handler']) {
      	if (button.checked && action == 'importInstanceResponses') {
          Effect.Appear('disableImportResponses', { duration: 0.00, to: 1.00 });
      	}
      	button.observe('click', Data[action + 'Handler']);
      }
    });
    
    $$('.newInstanceImportResponsesRadioButton').each(function(button) {
      var action = button.value;
      if(Data[action + 'Handler']) {
      	if (button.checked && action == 'newInstanceImportInstanceResponses') {
          Effect.Appear('disableNewInstanceImportResponses', { duration: 0.00, to: 1.00 });
      	}
      	button.observe('click', Data[action + 'Handler']);
      }
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
    $$('form.' + form).first().action = $F('base_url') + '/instancedata/' + action;
  }

  var iframe = document.createElement("iframe");
  iframe.height = '1px';
  iframe.width = '1px';
  iframe.src = $$('form.' + form).first().action + '?cryptoID=' + $$('form.' + form).first().down('#cryptoID').value;
  document.body.appendChild(iframe);

}

Event.observe(window, 'load', Data.setup);
