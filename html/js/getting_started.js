var GettingStarted = {
  
  /**
   * String to use when setting a cookie to hide the "getting started" screen
   */
  cookieString: 'tab_edit_hidegs',
  
  /**
   * Process mouseover event for the close button
   *
   * @param Event event object for this mouseover event
   */
  closeOver: function(event) {
    var image = $$('#gettingStarted .closeButton img').first();
    var newSrc = image.src.replace(/\.png$/, '_hover.png');
    image.src = newSrc;
  },

  /**
   * Process mouseout event for the close button
   *
   * @param Event event object for this mouseout event
   */
  closeOut: function(event) {
    var image = $$('#gettingStarted .close .closeButton img').first();
    var newSrc = image.src.replace(/_hover\.png$/, '.png');
    image.src = newSrc;
  },
  
  /**
   * Process a click event for the close button
   *
   * @param Event click event object
   */
  closeClick: function(event) {
    event.stop();
    
    var cookie = GettingStarted.cookieString + '=1; '
    var expires = '';
    if($('dontShow').checked) {
      var today = new Date();
      today.setTime(today.getTime() + 1000000000000);
      expires = 'expires=' + today.toGMTString() + '; ';
    }
    cookie += expires + 'path=/';
    document.cookie = cookie;
    Effect.Fade('gettingStarted', { duration: 0.25 });
  },
  
  /**
   * Setup tasks.
   *
   * @param Event event object (this is an onload handler)
   */
  setup: function(event) {
    var gettingStarted = $('gettingStarted').remove();
    
    if(!Cookies.exists(GettingStarted.cookieString)) {
      $(document.body).insert({ bottom: gettingStarted });
      Effect.Appear(gettingStarted, { duration: 0.25, to: 0.88 });
    
      var closeButton = $$('#gettingStarted .close .closeButton').first();
      closeButton.observe('mouseover', GettingStarted.closeOver);
      closeButton.observe('mouseout', GettingStarted.closeOut);
      closeButton.observe('click', GettingStarted.closeClick);
    }
  }
}

// run setup tasks on window load
Event.observe(window, 'load', GettingStarted.setup);