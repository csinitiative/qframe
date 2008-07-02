var Checkboxes = {
  
  /**
   * Event handler for all checkbox change events
   *
   * @param Event event object
   */
  change: function(event) {
    var checkbox = Event.element(event);
    if(!checkbox.name.match(/^checkall_/)) {
      $$('input[type=checkbox][name^=checkall_]').each(function(checkall) {
        var suffix = checkall.name.replace(/^checkall_/, '');
        if(checkbox.name.endsWith(suffix)) {
          Checkboxes.initial(checkall);
        }
      });
    }
  },
  
  /**
   * Event handler for "check all" checkboxes changing
   *
   * @param Event event object
   */
  toggle: function(event) {
    var checkbox = Event.element(event);
    var affected = Checkboxes.affected(checkbox);
    
    if(checkbox.checked) {
      affected.each(function(element) { element.checked = true; });
    }
    else {
      affected.each(function(element) { element.checked = false; });
    }
  },
  
  /**
   * Checks for an initial state that indicates a "check all" checkbox should be checked
   *
   * @param Input "check all" checkbox
   */
  initial: function(checkbox) {
    var checked = true;
    Checkboxes.affected(checkbox).each(function(element) {
      if(!element.checked) checked = false;
    });
    checkbox.checked = checked;
  },
  
  /**
   * Fetch the affected checkboxes for a "check all" box
   *
   * @param Input check all checkbox
   */
  affected: function(checkbox) {
    var suffix = checkbox.name.replace(/^checkall_/, '');
    return $$('input[type=checkbox][name!="' + checkbox.name + '"][name$="' + suffix + '"]');
  },
  
  /**
   * Code that runs at page load to set up necessary event handlers, etc.
   *
   * @param Event load event
   */
  setup: function(event) {
    $$('input[type=checkbox][name^=checkall_]').each(function(checkbox) {
      checkbox.observe('click', Checkboxes.toggle);
      Checkboxes.initial(checkbox);
    });
    $$('input[type=checkbox]').each(function(checkbox) {
      checkbox.observe('click', Checkboxes.change);
    });
  }
}

// Call Checkboxes.setup() when the page loads
Element.observe(window, 'load', Checkboxes.setup);