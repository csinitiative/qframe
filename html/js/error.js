var Error = {
  
  /**
   * Toggles the stack trace for a particular error
   *
   * @param Event click event object
   */
  toggleStackTrace: function(event) {
    event.stop();
    
    var link = Event.element(event);
    link.up('.location').next('.trace').toggle();
    link.innerHTML = ((link.innerHTML.match(/^show/)) ? 'hide' : 'show') + ' stack trace';
  },
  
  /**
   * Perform setup (set up event handlers for the most part)
   *
   * @param Event load event
   */
  setup: function(event) {
    $$('.error .location a').each(function(element) {
      element.observe('click', Error.toggleStackTrace);
    });
  }
}

// Call the page setup method onload
Event.observe(window, 'load', Error.setup);
