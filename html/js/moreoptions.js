/**
 * This file is part of the CSI QFrame.
 *
 * The CSI QFrame is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The CSI QFrame is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @copyright  Copyright (c) 2007 Collaborative Software Initiative (CSI)
 * @license    http://www.gnu.org/licenses/   GNU General Public License v3
 */


/**
 * Module to handle the "more options" panels (move them around, process clicks,
 * etc.)
 */
var MoreOptions = {
  
  /**
   * show the options panel for a given question
   *
   * @param Event click event
   */
  showOptions: function(event) {
    event.stop();
    
    // Set up (get required elements, etc.)
    var link = Event.element(event);
    wrapper = MoreOptions.wrapLink(link);
    var linkWrapper = wrapper.firstDescendant();
    var optionsPanel = wrapper.up().next('.more-options');
    if(!optionsPanel) optionsPanel = wrapper.previous('.more-options');
    
    // If this is the first time we are showing the more options panel
    // position it where it belongs
    var top = wrapper.positionedOffset().top - 4;
    var left = parseInt(wrapper.getStyle('left')) + 12;
    optionsPanel.setStyle({
      top:   top + 'px',
      left:  left + 'px',
      width: optionsPanel.getWidth() + 'px'
    });      
    
    // Hide any panels that are currently shown
    $$('.more-options').each(function(e) {
      if(e.visible()) MoreOptions.hideOptions(e.up('.question'));
    });
                
    // Show the panel
    Effect.toggle(linkWrapper, 'blind', {
      duration: 0.25,
      scaleX:   true,
      scaleY:   false,
      queue:    'end'
    });
    Effect.toggle(optionsPanel, 'appear', { duration: 0.1, queue: 'end' });
  },
  
  /**
   * Handler that converts an event into the appropriate element and then calls
   * hideOptions()
   *
   * @param Event event object corresponding to a click on a button
   */
  hideOptionsHandler: function(event) {
    event.stop();
    MoreOptions.hideOptions(Event.element(event).up('.question'));
  },
  
  /**
   * hide the options panel for a given question
   *
   * @param Element the question element for which the more options panel is to be hidden
   */
  hideOptions: function(question) {
    var optionsPanel = question.down('.more-options');
    var outerWrapper = optionsPanel.up('div').down().next('.wrapper');
    if(!outerWrapper) outerWrapper = optionsPanel.up('div').down('.wrapper');
    var linkWrapper = outerWrapper.firstDescendant();
    
    Effect.toggle(optionsPanel, 'appear', { duration: 0.1, queue: 'end' });
    Effect.toggle(linkWrapper, 'blind', {
      duration:    0.25,
      scaleX:      true,
      queue:       'end',
      afterFinish: function() {
        var oldImage = outerWrapper.previous('img');
        var oldLink = outerWrapper.previous('a')
        outerWrapper.remove();      
        oldImage.toggle();
        oldLink.toggle();
    }});
  },
  
  /**
   * Display an icon's description when the user mouses over the icon
   *
   * @param Event mouseover event
   */
  mouseOver: function(event) {
    event.stop();
    
    var link = Event.element(event);
    link = Element.extend(link);
    if(!link.href || link.src) link = link.up('a');
    var description = link.up('.more-options').down('.description');

    description.writeAttribute('originalText', description.innerHTML);
    description.innerHTML = link.down('img').title;
  },
  
  /**
   * Display the "hover over an icon to see a description" message when the mouse
   * is no longer hovering over an icon
   *
   * @param Event mouseout event
   */
  mouseOut: function(event) {
    event.stop();
    
    var link = Event.element(event);
    if(!link.href) link = link.up('a');
    var description = link.up('.more-options').down('.description');
    
    description.innerHTML = description.readAttribute('originalText');
  },
  
  /**
   * wrap the link passed in so that it can have effects applied to it.
   * the link will get wrapped in one div, the arrow in another.  then
   * both will be wrapped in an outer div.  this will allow the arrow
   * to move smoothly as the link is hidden via effects.
   *
   * @param element to be wrapped (specifically, the link element)
   */
  wrapLink: function(el) {
    // Get the elements we are going to be working on
    el = $(el);
    arrow = el.next('img');
    
    // Clone the more options link
    var newLink = $(document.createElement('a'));
    newLink.href= el.href;
    newLink.className = el.className;
    newLink.innerHTML = el.innerHTML;
    newLink.observe('click', MoreOptions.hideOptionsHandler);
    
    // Clone the more options arrow
    var newArrow = $(document.createElement('img'));
    newArrow.src = arrow.src;
    newArrow.className = arrow.className;
    
    // Generate the wrappers for these elements
    var wrapper = $(document.createElement('div')).addClassName('wrapper');
    var linkWrapper = $(document.createElement('div'));
    var arrowWrapper = $(document.createElement('div'));
    
    // Set the necessary styles for the wrappers
    wrapper.setStyle({
      position: 'absolute',
      top: el.positionedOffset().top + 'px',
      left: el.positionedOffset().left + 'px',
      height: el.getHeight() + 'px',
      overflow: 'hidden'
    });
    linkWrapper.setStyle({float: 'left'});
    arrowWrapper.setStyle({float: 'left', paddingTop: '1px'});
    
    // Wrap elements in their inner wrappers and add them to the
    // outer wrapper replacing them in the DOM with the new wrapped
    // version
    wrapper.appendChild(linkWrapper);
    wrapper.appendChild(arrowWrapper);
    el.up().appendChild(wrapper);
    
    //linkWrapper.appendChild(el);
    linkWrapper.appendChild(newLink);
    el.toggle();
    
    linkWrapper.innerHTML += '&nbsp;';
    //arrowWrapper.appendChild(arrow);
    arrowWrapper.appendChild(newArrow);
    arrow.toggle();
    
    // Return the finished product
    return wrapper;
  },
  
  /**
   * Show the additional info panel for the question associated with
   * the passed in link element
   *
   * @param Event event object for the click that generated this call
   */
  showAddlInfo: function(event) {
    event.stop();
    
    var link = Event.element(event);
    var moreOptions = link.up('.more-options');
    var questionWrapper = link.up('.question');
    
    questionWrapper.down('.additionalInfo_main').show();
    questionWrapper.down('.additionalInfo').show();
    MoreOptions.hideOptions(questionWrapper);
    $(link).up('li').hide();
  },

  /**
   * Show the private notes panel for the question associated with
   * the passed in link element
   *
   * @param Event event object for the click that generated this call
   */
  showPrivateNote: function(event) {
    event.stop();
    var link = Event.element(event);
    var moreOptions = link.up('.more-options');
    var questionWrapper = link.up('.question');

    questionWrapper.down('.privateNote_main').show();
    questionWrapper.down('.privateNote').show();
    MoreOptions.hideOptions(questionWrapper);
    $(link).up('li').hide();
  },

  /**
   * Show the remediation info panel for the model question associated with
   * the passed in link element
   *
   * @param Event event object for the click that generated this call
   */
  showRemediationInfo: function(event) {
    event.stop();
  
    var link = Event.element(event);
    var moreOptions = link.up('.more-options');
    var questionWrapper = link.up('.question');
  
    questionWrapper.down('.remediationInfo').show();
    MoreOptions.hideOptions(questionWrapper);
    $(link).up('li').hide();
  },
  
  /**
   * Attach a new file to this response
   *
   * @param Event event object for the click that generated this call
   */
  attach: function(event) {
    event.stop();
    
    var question = Event.element(event).up('.question');
    var attachments = question.down('.attachments');
    
    // create the div that wraps this attachment and insert it in the attachments div
    var attachment = Element.extend(document.createElement('div'));
    attachment.addClassName('attachment');
    attachments.insert({ bottom: attachment });
    
    // create the link to delete this new attachment
    var deleteLink = Element.extend(document.createElement('a'));
    deleteLink.href = '#';
    deleteLink.addClassName('delete');
    deleteLink.observe('click', MoreOptions.attachDelete);
    
    // create the image that will be the content of the aforementioned link
    deleteImage = Element.extend(document.createElement('img'));
    deleteImage.src = $F('base_url') + '/images/icons/dddddd/attach_delete.png';
    deleteImage.alt = 'attach_delete';
    
    // add the image to the link and the link to the attachment
    deleteLink.insert({ bottom: deleteImage });
    attachment.insert({ bottom: deleteLink });
    
    // create the file element and insert into the attachment
    fileElement = Element.extend(document.createElement('input'));
    fileElement.type = 'file';
    fileElement.name = question.id.replace(/^question-/, '') + '_tmp_attachment';
    fileElement.observe('change', MoreOptions.attachChange);
    attachment.insert({ bottom: fileElement });
    
    // create the progress image and insert into the attachment
    progressImage = Element.extend(document.createElement('img'));
    progressImage.src = $F('base_url') + '/images/ajax-loader.gif';
    progressImage.alt = '';
    progressImage.addClassName('progress');
    progressImage.setStyle({ display: 'none' });
    attachment.insert({ bottom: progressImage });
    
    // hide the "more options" panel            
    MoreOptions.hideOptions(question);
  },
  
  /**
   * delete the attachment associated with the passed in link
   *
   * @param Event event object
   */
  attachDelete: function(event) {
    event.stop();
    
    var attachment = Event.element(event).up('.attachment');
    if(attachment.down('.existing')) {
      if(!confirm('This cannot be undone.  Are you sure?')) return;
      attachment.down('.existing').value = 'true';
      attachment.hide();
    }
    else attachment.remove();
  },
  
  /**
   * Set a timer that will upload the image "behind the scenes" if there has been no change
   * in the image status for a certain amount of time
   *
   * @param Event change event object
   */
  attachChange: function(event) {
    // fetch the attachment element
    var attachment = Event.element(event).up('.attachment');
    
    // if there is already a running time for this element, stop it
    if(attachment.timer) attachment.timer.stop();
    
    // set a new timer and add it to the attachment element
    attachment.timer = new PeriodicalExecuter(function(executer) {
      executer.stop();
      MoreOptions.uploadFile(attachment);
    }, 5);
  },
  
  /**
   * Upload the file specified by the attachment element in the background
   *
   * @param Element attachment element
   */
  uploadFile: function(attachment) {
    var uploadForm = $('uploadForm');
    var fileElement = attachment.down('input');
    var filename = fileElement.value.replace(/^.*[\\\/](.+?\.\w{2,6})$/, '$1');
    fileElement.hide();
    fileElement.insert({ after: filename });
    attachment.down('.progress').show();
    uploadForm.insert({ bottom: fileElement });
    uploadForm.submit();
    attachment.addClassName('uploading');
  },
  
  /**
   * Process the results of an upload operation
   *
   * @param Event iframe load even
   */
  uploadFinished: function(event) {
    // fetch elements needed for processing
    var newFile = $(document.createElement('input'));
    var attachment = $$('.attachments .uploading').first();
    var question = attachment.up('.question').down('label').readAttribute('for');
    
    // create and insert the new hidden element that will cause the attachment to get associated
    // with the correct response
    newFile.type = 'hidden';
    newFile.name = question + '_attachments[]';
    newFile.addClassName('pending');
    if ($('uploadIframe').contentWindow) { // IE6
      newFile.value = $('uploadIframe').contentWindow.document.body.innerHTML.replace(/^\s+|\s+$/g, '');
    }
    else {
      newFile.value = $('uploadIframe').contentDocument.body.innerHTML.replace(/^\s+|\s+$/g, '');
    }
    attachment.insert({ bottom: newFile });
    
    // do some cleanup (highlight newly inserted attachment, remove progress indicator, etc.)
    new Effect.Highlight(attachment);
    attachment.removeClassName('uploading');
    attachment.down('.progress').hide();
    $('uploadForm').select('input').each(function(input) { input.remove(); });
  },
  
  /**
   * Perform various setup tasks
   *
   * @param Event window load event
   */
  setup: function(event) {
    $$('.attachments .attachment .delete').each(function(e) {
      e.observe('click', MoreOptions.attachDelete);
    });

    $$('.question .options-link').each(function(e) {
      e.observe('click', MoreOptions.showOptions);
    });
    
    $$('.question .more-options a').each(function(link) {
      if(link.href.match(/#(\w+)$/) && MoreOptions[RegExp.$1]) {
        link.observe('click', MoreOptions[RegExp.$1]);
      }
      link.observe('mouseover', MoreOptions.mouseOver);
      link.observe('mouseout', MoreOptions.mouseOut);
    });
  }
};

/**
 * Do stuff when the window loads
 */
Event.observe(window, 'load', MoreOptions.setup);
