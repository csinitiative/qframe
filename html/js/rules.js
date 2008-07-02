var Rules = {
  /**
   * Handle the click of a radio button (processing rules associated with that button and others
   * in that button's group)
   *
   * @param Event click event for the radio button
   */
  radioClick: function(event) {
    var radio = Event.element(event);
    var question = radio.up('.question');
    
    if(radio.readAttribute('previouslyChecked')) return;
    var previouslyChecked = Rules.clearPreviouslyChecked(question);
    
    if(previouslyChecked) {
      var previousRules = question.down('#rules-' + previouslyChecked.value);
      if(previousRules) Rules.processRulesReverse(previousRules);
    }
    
    var currentRules = question.down('#rules-' + radio.value);
    if(currentRules) Rules.processRules(currentRules);
        
    radio.writeAttribute('previouslyChecked', 1);
  },
  
  /**
   * Process all rules in the passed in rules div in reverse (disable = enable, etc)
   *
   * @param Element contains all the rules to be processed
   */
  processRulesReverse: function(rules) {
    rules.select('input[type=hidden]').each(function(rule) {
      var ruleParts = rule.value.split(/:/);
      if(ruleParts[1].match(/^enable/)) ruleParts[1] = ruleParts[1].replace(/^enable/, 'disable');
      else ruleParts[1] = ruleParts[1].replace(/^disable/, 'enable');
      Rules.processRule(ruleParts[0], ruleParts[1]);
    });
  },
  
  /**
   * Process all rules in the passed in rules div
   *
   * @param Element contains all the rules to be processed
   */
  processRules: function(rules) {
    rules.select('input[type=hidden]').each(function(rule) {
      var ruleParts = rule.value.split(/:/);
      Rules.processRule(ruleParts[0], ruleParts[1]);
    });
  },
  
  /**
   * Process a single rule
   *
   * @param string target GUID for this rule
   * @param string action that this rule specifies (enable|disable(Question|Section|Tab))
   */
  processRule: function(target, rule) {
    if(rule.match(/Question$/)) {
      Rules.processQuestionRule(target, rule.replace(/Question$/, ''));
    }
    else if(rule.match(/Tab$/)) {
      Rules.processTabRule(target, rule.replace(/Tab$/, ''));
    }
    else if(rule.match(/Section$/)) {
      Rules.processSectionRule(target, rule.replace(/Section$/, ''));
    }
  },
  
  /**
   * Fetch the disableCount element of the passed in element
   *
   * @param  Element element in question
   * @return Element
   */
  getDisableCount: function(element) {
    var hiddenElements = element.select('input[type=hidden]');
    for(var i in hiddenElements) {
      if(hiddenElements[i].id.match(/^disableCount/)) {
        return hiddenElements[i];
        
      }
    }
    return null;
  },
  
  /**
   * Process a single "section" rule (rule with a section as its target)
   *
   * @param string target ID 
   * @param string rule type (enable/disable)
   */
  processSectionRule: function(target, rule) {
    // fetch the target section's element (if it exists on this page)
    var section = $('section-' + target);
    if(!section) return;
    var legend = section.down('.legend');
    var disableText = section.down('.disableText');
    
    // fetch the hidden disableCount element for this tab
    var disableCount = Rules.getDisableCount(section);
    if(!disableCount) return;
    
    if(rule == 'disable') {
      disableCount.value++;
      if(disableCount.value > 0) {
        section.addClassName('disabled');
        disableText.innerHTML = ' (disabled)';
        section.down('ol').hide();
      }
    }
    else if(rule == 'enable') {
      disableCount.value--;
      if(disableCount.value <= 0) {
        section.removeClassName('disabled');
        disableText.innerHTML = '';
        section.down('ol').show();
      }
    }
  },
  
  /**
   * Process a single "tab" rule (rule with a tab as its target)
   *
   * @param string target ID
   * @param string rule type (enable/disable)
   */
  processTabRule: function(target, rule) {    
    // fetch the target tab's element (if it exists on this page)
    var tab = $('tab-' + target);
    if(!tab) return;
    
    // fetch the hidden disableCount element for this tab
    var disableCount = Rules.getDisableCount(tab);
    if(!disableCount) return;
    
    if(rule == 'disable') {
      disableCount.value++;
      if(disableCount.value > 0) {
        tab.down('div').addClassName('disabled');
        tab.down('div').innerHTML = tab.down('a').innerHTML;
      }
    }
    else if(rule == 'enable') {
      disableCount.value--;
      if(disableCount.value <= 0) {
        tab.down('div').removeClassName('disabled');
        tab.down('div').innerHTML = '<a href="' + tab.readAttribute('url') + '">' +
            tab.down('div').innerHTML + '</a>';
        tab.down('a').observe('mouseover', CsiQframe.menuMouseOver);
        tab.down('a').observe('mouseout', CsiQframe.menuMouseOut);
      }
    }
  },
  
  /**
   * Process a single "question" rule (rule with a question as its target)
   *
   * @param string target ID
   * @param string rule type (enable/disable)
   */
  processQuestionRule: function(target, rule) {
    // fetch the target question's element (if it exists on this page)
    var question = $('question-' + target);
    if(!question) return;
  
    // fetch the hidden disableCount element for this question
    var disableCount = Rules.getDisableCount(question);
    if(!disableCount) return;
    
    if(rule == 'disable') {
      disableCount.value++;
      if(disableCount.value > 0) {
        question.up('li').setStyle({ display: 'none' });
      }
    }
    else if(rule == 'enable') {
      disableCount.value--;
      if(disableCount.value <= 0) {
        question.up('li').setStyle({ display: null });
      }
    }
  },
  
  /**
   * Clears the previouslyChecked attribute from whatever element it current resides on
   *
   * @param Element question element that we are clearing within
   */
  clearPreviouslyChecked: function(question) {
    var previouslyChecked = Rules.findPreviouslyChecked(question);
    if(previouslyChecked) previouslyChecked.writeAttribute('previouslyChecked', null);
    return previouslyChecked;
  },
  
  /**
   * Find the previously checked radio button for this question
   *
   * @param Element question element we are looking within
   */
  findPreviouslyChecked: function(question) {
    var radios = question.select('input[type=radio]');
    for(var i in radios) {
      if(radios[i].readAttribute && radios[i].readAttribute('previouslyChecked')) return radios[i];
    }
    return null;
  },

  /**
   * Sets up whatever needs to be set up
   *
   * @param Event window load event
   */
  setup: function(event) {
    $$('form.questions input').each(function(element) {
      if(element.type == 'radio') {
        element.observe('click', Rules.radioClick);
        if(element.checked) element.writeAttribute('previouslyChecked', 1);
      }
    });
  }
};

// Call the tab setup method onload
Event.observe(window, 'load', Rules.setup);
