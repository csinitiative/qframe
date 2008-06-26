var Dashboard = {
  
  /**
   * Event handler for drop down change events
   *
   * @param Event change event
   */
  selectChange: function(event) {
    Event.element(event).form.submit();
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
  }
}

Event.observe(window, 'load', Dashboard.setup);