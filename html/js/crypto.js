var Crypto = {
  
  /**
   * Event handler for drop down change events
   *
   * @param Event change event
   */
  selectChange: function(event) {
    var url = $F('selectURL') + '?' + $('instrument').serialize() + '&' + $('instance').serialize();
    window.location = url;
  },
  
  /**
   * Setup tasks.
   *
   * @param Event event object (this is an onload handler)
   */
  setup: function(event) {
    var form = $('cryptoForm') || $$('form').first();
    if(form && form.down('#cancelButton')) {
      form.down('#cancelButton').observe('click', function(event) {
        if(confirm('Are you sure?')) window.location = $F('indexUrl');
      });
    }
    
  }
}

// run setup tasks on window load
Event.observe(window, 'load', Crypto.setup);
