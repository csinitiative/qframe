var CsiRegq = {
  /**
   * Adds the necessary class name to menu items on mouse over
   *
   * @param Event event object for the mouseover event
   */
  menuMouseOver: function(evt) {
    Element.addClassName(Event.element(evt).up(), 'over');
  },

  /**
   * Removes the necessary class name to menu items on mouse out
   *
   * @param Event event object for the mouseout event
   */  
  menuMouseOut: function(e) {
    Element.removeClassName(Event.element(e).up(), 'over');
  },
  
  /**
   * Process question form submission.  Any required validation would occur here
   * as well as manipulation of form elements prior to submission.
   *
   * @param Event event object representing the event that triggered this call
   */
  questionsSubmit: function(evt) {
    $$('.additionalInfo').each(function(e) {
      if(!e.hasClassName('hasContent') || e.value == '') {
        e.remove();
      }
    });
    return true;
  },
  
  /**
   * Event handler for focus events on additional info text boxes
   *
   * @param Event event object representing this event
   */
  addlInfoFocus: function(evt) {
    var e = Event.element(evt);
    if(!e.hasClassName('hasContent')) {
      e.value = '';
      e.addClassName('hasContent');
      $(e.name + '_mod').value = 1;
    }
  },
  
  /**
   * Event handler for blur events on additional info text boxes
   *
   * @param Event event object representing this event
   */
  addlInfoBlur: function(evt) {
    var e = Event.element(evt);
    if(e.value == '') {
      e.value = 'Enter additional information here';
      if(e.hasClassName('additionalInfoRequired')) e.value += ' (required)';
      e.removeClassName('hasContent');
      $(e.name + '_mod').value = 0;
    }
  },
  
  /**
   * Sets up focus and blur event listeners for new addditional info
   * text boxes
   *
   * @param Element element to add listeners to
   */
  setupAddlInfoListeners: function(e) {
    e.observe('focus', CsiRegq.addlInfoFocus);
    e.observe('blur', CsiRegq.addlInfoBlur);
  },
  
  /**
   * Swaps an image on mouse over
   *
   * @param Event event object for the mouse over event
   */
  imgMouseOver: function(evt) {
    var img = Event.element(evt);
    img.src = img.src.replace(/(\.\w{2,5})$/, '-hover$1');
  },
  
  /**
   * Swaps an image on mouse out
   *
   * @param Event event object for the mouse out event
   */
  imgMouseOut: function(evt) {
    var img = Event.element(evt);
    img.src = img.src.replace(/-hover(\.\w{2,5})$/, '$1');
  },
  
  /**
   * Handle the click of a lock icon (will allow a user to force unlock a tab)
   *
   * @param Event the click event that triggered this handler
   */
  unlockTab: function(event) {
    event.stop();
    
    var image = Event.element(event);
    if(image.down('img')) image = image.down('img');
    
    if(confirm('Unlocking this tab will cause the user who currently has the tab locked to lose ' +
        'any unsaved work.  Are you sure?')) {
      window.location = image.id;    
    }
  }
};


/**
 * Set up various listeners once the page has fully loaded
 */
Event.observe(window, 'load', function () {
  $$('#page #menu ol li a').each(function(e) {
    e.observe('mouseover', CsiRegq.menuMouseOver);
    e.observe('mouseout', CsiRegq.menuMouseOut);
  });
  
  $$('.additionalInfo').each(function(e) {
    CsiRegq.setupAddlInfoListeners(e);
  });
  
  $$('form.questions').each(function(frm) {
    frm.observe('submit', CsiRegq.questionsSubmit);
  });
  
  $$('img.hover').each(function(img) {
    img.observe('mouseover', CsiRegq.imgMouseOver);
    img.observe('mouseout', CsiRegq.imgMouseOut);
  });
  
  $$('a img.lock').each(function(image) {
    image.up('a').observe('click', CsiRegq.unlockTab);
  });
});
