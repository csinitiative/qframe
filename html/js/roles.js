var Roles = {
  
  /**
   * Event handler for drop down change events
   *
   * @param Event change event
   */
  selectChange: function(event) {
    var url = $F('selectURL') + '?' + $('questionnaire').serialize() + '&' + $('instance').serialize();
    window.location = url;
  },
  
  /**
   * Setup tasks.
   *
   * @param Event event object (this is an onload handler)
   */
  setup: function(event) {
    var form = $('roleForm') || $$('form').first();
    if(form && form.down('#cancelButton')) {
      form.down('#cancelButton').observe('click', function(event) {
        if(confirm('Are you sure?')) window.location = $F('indexUrl');
      });
    }
    
    $$('#permissions #tab .option select').each(function(select) {
      select.observe('change', Roles.selectChange);
    });
  }
}

// run setup tasks on window load
Event.observe(window, 'load', Roles.setup);