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
    container.down('input[type=button][name=new]').hide();
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
    container.down('input[type=button][name=new]').show();
    container.up('form').action = '/compare';
    container.up('form').method = 'get';
  },
  
  /**
   * Event handler for drop down change events on questionnaire select box
   *
   * @param Event change event
   */
  questionnaireSelected: function(event) {
    var form = Event.element(event).up('form');
    form.select('input[name^="model["]').each(function(element) { element.remove() });
    form.action = '/compare';
    form.method = 'get';
    form.submit();
  },
  
  /**
   * Redirect to the edit path for the selected model
   *
   * @param Event event in the case that this is called as an event handler
   */
  editModel: function(event) {
    var editPath = $F('editPath') + '/' + $F('model');
    window.location = editPath;
  },
  
  /**
  * Event handler for drop down change events on model select box
   *
   * @param Event change event
   */
  modelSelected: function(event) {
    var form = Event.element(event).up('form');
    if($F('model') != 0) {
      form.down('input[type=button][name=edit]').enable();
      form.down('input[type=button][name=compare]').enable();
    }
    else {
      form.down('input[type=button][name=edit]').disable();
      form.down('input[type=button][name=compare]').disable();
    }
  },
  
  /**
   * Perform setup tasks for the dashboard
   *
   * @param Event window load event object
   */
  setup: function(event) {
    // fire the appropriate function when the questionnaire select box value changes
    $$('select[name=questionnaire]').first().observe('change', Dashboard.questionnaireSelected);

    // fire the appropriate function when the questionnaire select box value changes
    $$('select[name=model]').first().observe('change', Dashboard.modelSelected);
    
    // fire the appropriate function when the new model button is clicked
    $$('input[type=button][name=new]').first().observe('click', Dashboard.createNew);
    
    // fire the appropriate function when the cancel creation of new model button is clicked
    $$('input[name=cancel]').first().observe('click', Dashboard.cancelNew);
    
    // fire the appropriate function when the edit model button is clicked
    $$('input[type=button][name=edit]').first().observe('click', Dashboard.editModel);
  }
}

// on window load, run the setup function for the this file
Event.observe(window, 'load', Dashboard.setup);