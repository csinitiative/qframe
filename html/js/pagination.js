var Pagination = {
  /**
   * Handler for user hovering over a paged object
   *
   * @param Event event object
   */
  over: function(event) {
    var element = Event.element(event);
    if(!element.hasClassName('paged')) element = element.up('.paged');

    element.addClassName('hover');
    if(element.down('.controls')) element.down('.controls').show();
  },
  
  /**
   * Handler for user unhovering over a paged object
   *
   * @param Event event object
   */
  out: function(event) {
    var element = Event.element(event);
    if(!element.hasClassName('paged')) element = element.up('.paged');

    element.removeClassName('hover');
    if(element.down('.controls')) element.down('.controls').hide();
  },
  
  /**
   * Serves as a click handler for links that require confirmation
   *
   * @param Event click event
   */
  confirm: function(event) {
    if(!confirm('Are you sure?')) event.stop();
  },
  
  /**
   * Perform setup operations for paged objects on the current page
   */
  setup: function() {
    // process each 'page' element inside a 'pageContainer' element
    $$('.pageContainer .paged').each(function(element) {
      element.observe('mouseover', Pagination.over);
      element.observe('mouseout', Pagination.out);
      element.select('.confirm').each(function(element) {
        element.observe('click', Pagination.confirm);
      });
    });
  }
}

// run setup tasks on window load
Event.observe(window, 'load', Pagination.setup);