var Tooltips = {
  
  // Offsets from pointer position
  offsetX: 17,
  offsetY: 0,
  
  /**
   * Hide (delete) a tooltip
   *
   * @param Element image element
   */
  hide: function(element) {
    var tooltip = $('tooltip');
    if(tooltip) tooltip.remove();
  },
  
  /**
   * Show the tooltip associated with the passed in image
   *
   * @param Element image element
   * @param Object  coordinates of the mouse pointer
   */
  show: function(element, coords) {
    var text = element.readAttribute('tooltip');
    if(text) {
      var tooltip = Tooltips.createTooltip(text);
      tooltip.setStyle(Tooltips.getStyle(coords));
      $(document.body).insert({ bottom: tooltip });
      Element.observe(window, 'mousemove', Tooltips.move);
    }
  },
  
  /**
   * Move the tooltip when the mouse is moved (while the tooltip is being shown)
   *
   * @param Event mousemove event
   */
  move: function(event) {
    var tooltip = $('tooltip');
    if(tooltip) tooltip.setStyle(Tooltips.getStyle(Tooltips.getCoords(event)));
  },
  
  /**
   * Create a complete tooltip element
   *
   * @param String text of the tooltip
   */
  createTooltip: function(text) {
    var tooltip = $(document.createElement('div'));
    var content = $(document.createElement('div'));
    var bottom = $(document.createElement('div'));
    var right = $(document.createElement('div'));
    var top = $(document.createElement('div'));
    var left = $(document.createElement('div'));
    
    tooltip.id = 'tooltip';
    content.id = 'content';
    bottom.id = 'bottom';
    right.id = 'right'
    top.id = 'top'
    left.id = 'left'
    content.innerHTML = text;
    
    tooltip.insert({ bottom: content });
    tooltip.insert({ bottom: bottom });
    tooltip.insert({ bottom: right });
    tooltip.insert({ bottom: top });
    tooltip.insert({ bottom: left });

    return tooltip;
  },
  
  /**
   * Computes the style necessary to position the tooltip 
   *
   * @param Object coordinates
   */
  getStyle: function(coords) {
    return {
      left: (coords.x + Tooltips.offsetX) + 'px',
      top: (coords.y + Tooltips.offsetY) + 'px'
    }
  },
  
  /**
   * Process a mouseover event for an image with class tooltip
   *
   * @param Event mouseover event
   */
  over: function(event) {
    Tooltips.show(Event.element(event), Tooltips.getCoords(event));
  },
  
  /**
   * Process a mouseout event for an image with class tooltip
   *
   * @param Event mouseout event
   */
  out: function(event) {
    Tooltips.hide(Event.element(event));
  },
  
  /**
   * Return an object representing the mouse coordinates for a given event
   *
   * @param Event event object
   */
  getCoords: function(event) {
    return { x: event.pointerX(), y: event.pointerY() };
  },
  
  /**
   * Setup tasks.
   *
   * @param Event event object (this is an onload handler)
   */
  setup: function(event) {
    $$('img.tooltip').each(function(image) {
      image.observe('mouseover', Tooltips.over);
      image.observe('mouseout', Tooltips.out);
    });
  }
}

// run setup tasks on window load
if(!(false /*@cc_on || @_jscript_version < 5.7 @*/)) {
  Event.observe(window, 'load', Tooltips.setup);  
}
