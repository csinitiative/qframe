var Tabs = {
  
  /**
   * Handle a scroll event on the window object (re-position floating elements)
   *
   * @param Event scroll event
   */
  windowScroll: function(event) {
    var offset = (window.pageYOffset) ? window.pageYOffset : document.documentElement.scrollTop;
    var element = $('formControls');
    if(element) element.setStyle({top: (offset + 200) + 'px'});
  },
  
  /**
   * Process a click event for a radio button that requires additional info
   *
   * @param Event click event
   */
  requireAddlRadioClick: function(event) {
    var radio = Event.element(event);
    var addlInfo = radio.up('.question').down('.additionalInfo');
    if(radio.checked) {
      addlInfo.addClassName('additionalInfoRequired');
      addlInfo.value += ' (required)';
    }
    if(!addlInfo.visible()) addlInfo.show();
  },
  
  /**
   * Process a click event for a radio button that does not require additional info
   *
   * @param Event click event
   */
  radioClick: function(event) {
    var radio = Event.element(event);
    var addlInfo = radio.up('.question').down('.additionalInfo');
    if(!radio.hasClassName('require-addl') && radio.checked && addlInfo.visible()) {
      addlInfo.removeClassName('additionalInfoRequired');
      addlInfo.value = addlInfo.value.replace(/ \(required\)$/, '');
    }
  },
  
  /**
   * Handles a click on the "save" button
   *
   * @param Event click event for the save button
   */
  saveHandler: function(event) {
    event.stop();
    
    /* Cancel any attachment timers that may be running */
    $$('.attachment').each(function(attachment) {
      if(attachment.timer) attachment.timer.stop();
    });
    
    /* Check for a still-uploading file and more the file element back to the main form
     * if one is found */
    var upload = $('uploadForm');
    if(upload) upload = upload.down('input[type=file]');
    if(upload) $('content').down('form').insert({ bottom: upload });
    
    var dims = document.viewport.getDimensions();
    var offsets = document.viewport.getScrollOffsets();
    var content = $('disableOverlay').down('.content');
    var top = (dims.height / 2) - 30 + offsets.top;
    var left = (dims.width / 2) - 30 + offsets.left;
    
    content.setStyle({ top: top + 'px', left: left + 'px' });
    Effect.Appear('disableOverlay', { duration: 0.15, to: 0.90 });
    $$('form.questions').first().submit();
  },
  
  /**
   * Handles a click on the "cancel" button
   *
   * @param Event click event for the cancel button
   */
  cancelHandler: function(event) {
    event.stop();
    
    if(confirm('Are you sure?')) {
      document.location = $F('cancelPath');
    }
  },
  
  /**
   * Handles clicks of the link to toggle tab references
   *
   * @param Event event object for the click
   */
  toggleTabReferences: function(event) {
    var element = Event.element(event);
    if(element.innerHTML.match(/^hide/)) element.innerHTML = 'show references';
    else element.innerHTML = 'hide references';
    $('tabReferences').toggle();
  },
  
  /**
   * Sets up whatever needs to be set up
   *
   * @param Event window load event
   */
  setup: function(event) {
    $$('.question .require-addl').each(function(radio) {
      radio.observe('click', Tabs.requireAddlRadioClick);
    });
    $$('.question input[type=radio]').each(function(radio) {
      radio.observe('click', Tabs.radioClick);
    });
    
    Event.observe(window, 'scroll', Tabs.windowScroll);
    
    $$('.controlButton').each(function(button) {
      var action = button.href.replace(/^.*#(\w+)$/, '$1');
      if(Tabs[action + 'Handler']) button.observe('click', Tabs[action + 'Handler']);
    });
    
    if($('toggleTabReferences'))
      $('toggleTabReferences').observe('click', Tabs.toggleTabReferences);
  }
};

// Call the tab setup method onload
Event.observe(window, 'load', Tabs.setup);