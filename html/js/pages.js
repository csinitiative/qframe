var Pages = {
  
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
    var addlInfoMain = radio.up('.question').down('.additionalInfo_main');
    var addlInfo = radio.up('.question').down('.additionalInfo');
    if(radio.checked) {
      addlInfo.addClassName('additionalInfoRequired');
      addlInfo.value += ' (required)';
    }
    if(!addlInfoMain.visible()) {
      addlInfoMain.show();
      addlInfo.show();
    }
      
  },
  
  /**
   * Process a click event for a radio button that does not require additional info
   *
   * @param Event click event
   */
  radioClick: function(event) {
    var radio = Event.element(event);
    var addlInfoMain = radio.up('.question').down('.additionalInfo_main');
    var addlInfo = radio.up('.question').down('.additionalInfo');
    if(!radio.hasClassName('require-addl') && radio.checked && addlInfoMain && addlInfoMain.visible()) {
      addlInfo.removeClassName('additionalInfoRequired');
      addlInfo.value = addlInfo.value.replace(/ \(required\)$/, '');
    }
  },
  
  /**
   * Since a save does not refresh the page, this function will reset all of the controls to the
   * states that they should be in at page load
   */
  resetControls: function() {
    // reset all addl_mod element values to 0 (after a save no elements have been modififed)
    $$('input[type=hidden][name$=addl_mod]').each(function(element) {
      element.value = 0;
    });
    
    // hide any additional info boxes that now have no content
    $$('textarea:not([class~=hasContent])').each(function(element) {
      var container = element.up('span');
      if(container.visible()) {
        container.hide();
        element.value = 'Enter additional information here';
        if(element.hasClassName('additionalInfoRequired')) element.value += ' (required)';
        
        container.next('.more-options').select('ul li').each(function(option) {
          if(container.hasClassName('additionalInfo_main') && option.down('a[href="#showAddlInfo"]')) {
            option.show();
          }
          else if(container.hasClassName('privateNote_main') && option.down('a[href="#showPrivateNote"]')) {
            option.show();
          }
        });
      }
    });
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
    
    /* Check for a still-uploading file and move the file element back to the main form
     * if one is found */
    var upload = $('uploadForm');
    if(upload) upload = upload.down('input[type=file]');
    if(upload) $('content').down('form').insert({ bottom: upload });
    
    var dims = document.viewport.getDimensions();
    var offsets = document.viewport.getScrollOffsets();
    var content = $('disableOverlay').down('.content');
    var top = (dims.height / 2) - 30;
    var left = (dims.width / 2) - 30;
    $('disableOverlay').setStyle({ top: offsets.top + 'px', left: 0 });
    content.setStyle({ top: top + 'px', left: left + 'px' });
    Effect.Appear('disableOverlay', { duration: 0.15, to: 0.90 });
    
    /* make sure that any additional_info boxes that are marked as having no content are wiped */
    $$('textarea:not([class~=hasContent])').each(function(element) {
      element.value = '';
    });

    $$('form.questions').first().submit();
  },

  /**
   * Handles a click on the "saveModel" button
   *
   * @param Event click event for the save button
   */
  saveModelHandler: function(event) {
    event.stop();
    
    var forms = $$('form');
    if(forms) forms.first().submit();
    else alert('Could not save because no form was found on the page.')
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
   * Handles clicks of the link to toggle page references
   *
   * @param Event event object for the click
   */
  togglePageReferences: function(event) {
    var element = Event.element(event);
    if(element.innerHTML.match(/^hide/)) element.innerHTML = 'show references';
    else element.innerHTML = 'hide references';
    $('pageReferences').toggle();
  },
  
  /**
   * Sets up whatever needs to be set up
   *
   * @param Event window load event
   */
  setup: function(event) {
    $$('.question .require-addl').each(function(radio) {
      radio.observe('click', Pages.requireAddlRadioClick);
    });
    $$('.question input[type=radio]').each(function(radio) {
      radio.observe('click', Pages.radioClick);
    });
    
    Event.observe(window, 'scroll', Pages.windowScroll);
    
    $$('.controlButton').each(function(button) {
      var action = button.href.replace(/^.*#(\w+)$/, '$1');
      if(Pages[action + 'Handler']) button.observe('click', Pages[action + 'Handler']);
    });
    
    if($('togglePageReferences'))
      $('togglePageReferences').observe('click', Pages.togglePageReferences);
  }
};

// Call the page setup method onload
Event.observe(window, 'load', Pages.setup);
