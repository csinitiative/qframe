var Search = {
  
  /**
   * Event handler for form submission (ensures that a blank form doesn't cause
   * a page refresh, etc.)
   *
   * @param Event event object
   */
  submit: function(event) {
    var form = Event.element(event);
    if(form.elements.q.value == '') event.stop();
    else $(form.elements.baseUrl).remove();
  },
  
  /**
   * Handle a keypress event for the query box
   *
   * @param Event event object
   */
  keypress: function(event) {
    var element = Event.element(event);
    var form = element.up('form');
    var key = event.which || event.keyCode;
    
    if(element.value.length > 1)
      form.down('.closeButton').show();
    else if(element.value.length == 0 && key != Event.KEY_BACKSPACE && key != Event.KEY_DELETE)
      form.down('.closeButton').show();
    else if(element.value.length == 1 && (key == Event.KEY_BACKSPACE || key == Event.KEY_DELETE))
      form.down('.closeButton').hide();
      
    if(key == Event.KEY_RETURN) this.submit(event);
  },
  
  /**
   * Clears the current search results
   *
   * @param Event event object
   */
  clear: function(event) {
    event.stop();
    var form = Event.element(event).up('form');
    
    if($(form.elements.q).readAttribute('original') == '') {
      form.reset();
      form.down('.closeButton').hide();
    }
    else {
      window.location = form.elements.baseUrl.value;
    }
  },
  
  /**
   * Process a page load (find search form elements and set up event handlers)
   *
   * @param Event event object
   */
  load: function(event) {
    $$('.searchForm').each(function(element) {
      element.observe('submit', Search.submit);
      element.down('.closeButton').observe('click', Search.clear);
      $(element.elements.q).observe('keypress', Search.keypress);
      if(element.elements.q.value != '') element.down('.closeButton').show();
    });
  }
}

// run the Search.load method when the window is loaded
Event.observe(window, 'load', Search.load);