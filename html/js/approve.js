var Approve = {
  /**
   * Process a click of a section-level approval link
   *
   * @param Event click event
   */
  approveSection: function(event) {
    event.stop();
    
    var section = Event.element(event).up('.fieldset');
    section.select('input[type=radio]').each(function(radio) {
      if(radio.name.match(/^approvals/) && radio.value == 2) {
        radio.checked = true;
      }
    });
  },
  
  /**
   * Process a click of a section-level un-approval link
   *
   * @param Event click event
   */
  unapproveSection: function(event) {
    event.stop();
    
    var section = Event.element(event).up('.fieldset');
    section.select('input[type=radio]').each(function(radio) {
      if(radio.name.match(/^approvals/) && radio.value == 1) {
        radio.checked = true;
      }
    });
  },
  
  /**
   * Show the approver comment box so the user can add an approver comment
   *
   * @param Event event object for the link click
   */
  addComment: function(event) {
    event.stop();
    
    var link = Event.element(event);
    var comment = link.next('textarea');
    comment.show();
    link.remove();
  },

  /**
   * Perform any necessary setup tasks
   *
   * @param Event window's load event
   */
  setup: function(event) {
    $$('.bulkApproval, .approval a').each(function(element) {
      if(element.href.match(/#(\w+)$/)) element.observe('click', Approve[RegExp.$1]);
    });
  }
};

// Call the tab setup method onload
Event.observe(window, 'load', Approve.setup);