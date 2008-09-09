var Dashboard = {

  /**
   * Event handler for drop down change events on questionnaire select box
   *
   * @param Event change event
   */
  selectChanged: function(event) {
    var form = Event.element(event).up('form');
    form.submit();
  },

  /**
   * Perform setup tasks for the dashboard
   *
   * @param Event window load event object
   */
  setup: function(event) {
    // fire the appropriate function when the questionnaire select box value changes
    $$('select[name=questionnaire]').first().observe('change', Dashboard.selectChanged);

    // fire the appropriate function when the instance select box value changes
    $$('select[name=instance]').first().observe('change', Dashboard.selectChanged);
  }
}

// on window load, run the setup function for the this file
Event.observe(window, 'load', Dashboard.setup);