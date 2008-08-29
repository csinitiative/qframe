var Dashboard = {

  /**
   * Event handler for clicks of new object buttons
   *
   * @param Event click event
   */
  createNew: function(event) {
    event.stop();
    var container = Event.element(event).up('.option');
    container.down('select').hide();
    container.down('input[type=text]').show();
    container.down('input[name=create]').show();
    container.down('input[name=cancel]').show();
    container.down('a.new-link').hide();
    container.up('form').action = '/compare/create';
    container.up('form').method = 'post';
  },
  
  /**
   * Event handler for clicks of the cancel creation button
   *
   * @param Event click event
   */
  cancelNew: function(event) {
    event.stop();
    var container = Event.element(event).up('.option');
    container.down('select').show();
    container.down('input[type=text]').hide();
    container.down('input[type=text]').value = '';
    container.down('input[name=create]').hide();
    container.down('input[name=cancel]').hide();
    container.down('a.new-link').show();
    container.up('form').action = '/compare';
    container.up('form').method = 'get';
  },
  
  /**
   * Event handler for drop down change events
   *
   * @param Event change event
   */
  selectChange: function(event) {
    var form = Event.element(event).up('form');
    form.select('input[name^="model["]').each(function(element) { element.remove() });
    form.action = '/compare';
    form.method = 'get';
    form.submit();
  },
  
  /**
   * Perform setup tasks for the dashboard
   *
   * @param Event window load event object
   */
  setup: function(event) {
    $$('.option select').each(function(select) {
      select.observe('change', Dashboard.selectChange);
    });
    
    $$('a.new-link').each(function(link) {
      link.observe('click', Dashboard.createNew);
    });
    
    $$('input[name=cancel]').each(function(button) {
      button.observe('click', Dashboard.cancelNew);
    });
  }
}

Event.observe(window, 'load', Dashboard.setup);