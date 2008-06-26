var Users = {
  
  /**
   * Setup tasks.
   *
   * @param Event event object (this is an onload handler)
   */
  setup: function(event) {
    var cancelButton = $('cancelButton');
    if(cancelButton) {
      cancelButton.observe('click', function(event) {
        if(!cancelButton.hasClassName('confirm') || confirm('Are you sure?'))
          window.location = $F('indexUrl');
      });
    }
  }
}

// run setup tasks on window load
Event.observe(window, 'load', Users.setup);