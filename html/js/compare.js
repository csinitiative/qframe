var Compare = {
  
  /**
   * Handle click events for noinclude checkboxes
   *
   * @param Event click event
   */
  toggleNoInclude: function(event) {
    var element = Event.element(event);
    if(element.checked) {
      element.up('.response').select('input').each(function(input) {
        if(input.type == 'text') input.value = '';
        if(input.type == 'checkbox' && !input.hasClassName('noinclude')) input.checked = false;
      });
    }
  },
  
  /**
   * Handle change events for non-noinclude input elements
   *
   * @param Event change event
   */
  elementChanged: function(event) {
    // locate needed elements
    var element = Event.element(event);
    var noinclude = element.up('.response').down('input.noinclude');
    
    // a couple of conditions that if met, we simply want to return
    if(element.hasClassName('noinclude') || !noinclude) return;

    // toggle noinclude off
    noinclude.checked = false;
  },
  
  /**
   * Perform setup (set up event handlers for the most part)
   *
   * @param Event load event
   */
  setup: function(event) {
    // handler to make sure that checking "noinclude" takes away all other options
    $$('input.noinclude').each(function(element) {
      element.observe('click', Compare.toggleNoInclude);
    });
    
    // handler to make sure that changing an input that is not a noinclude unchecks
    // the corresponding noinclude checkbox
    $$('input[type=text]').each(function(element) {
      element.observe('keypress', Compare.elementChanged);
    });
    $$('input[type=checkbox]').each(function(element) {
      element.observe('click', Compare.elementChanged);
    });
    
  }
}

// Call the page setup method onload
Event.observe(window, 'load', Compare.setup);
