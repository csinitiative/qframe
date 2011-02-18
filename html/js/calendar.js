/**
 * This file is part of QFrame.
 *
 * QFrame is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * QFrame is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @copyright  Copyright (c) 2007, 2008, 2009, 2010, 2011 Collaborative Software Foundation (CSF)
 * @license    http://www.gnu.org/licenses/agpl-3.0.txt   GNU Affero General Public License v3
 */


/**
 * Module for interfacing with calendar
 */
var QFrameCalendar = {
  
  /**
   * show the calendar for a given question
   *
   * @param Event click event
   */
  showCalendar: function(event) {
    event.stop();

    var element = Event.element(event);

    var bid = String(element.id);
    var id = bid.replace(/^c/, 'q');
    var format = '%m-%d-%Y';
    var showsTime = null;
    var showsOtherMonths = true;

    var el = $(id);
    if(!el) el = $(id.replace(/^q/, 'response[') + '][target]');
    if(!el) return alert('An unknown error occurred.  Contact administrator.');
    // first-time call, create the calendar.
    var cal = new Calendar(1, null, QFrameCalendar.selected, QFrameCalendar.closeHandler);
    // uncomment the following line to hide the week numbers
    cal.weekNumbers = false;
    if (typeof showsTime == "string") {
      cal.showsTime = true;
      cal.time24 = (showsTime == "24");
    }
    if (showsOtherMonths) {
      cal.showsOtherMonths = true;
    }
    cal.setRange(1900, 2070);        // min/max year allowed.
    cal.create();

    cal.setDateFormat(format);    // set the specified date format
    cal.parseDate(el.value);      // try to parse the text in field
    cal.sel = el;                 // inform it what input field we use

    var bel = document.getElementById(bid);
    var x = 389;
    var y = bel.cumulativeOffset().top;
    cal.showAt(x, y);        // show the calendar
  },
  
  // This function gets called when the end-user clicks on some date.
  selected: function (cal, date) {
    cal.sel.value = date; // just update the date in the input field.
    if (cal.dateClicked)
      // if we add this call we close the calendar on single-click.
      // just to exemplify both cases, we are using this only for the 1st
      // and the 3rd field, while 2nd and 4th will still require double-click.
      cal.callCloseHandler();
  },

  // And this gets called when the end-user clicks on the _selected_ date,
  // or clicks on the "Close" button.  It just hides the calendar without
  // destroying it.
  closeHandler: function (cal) {
    cal.hide();                        // hide the calendar
  },
  
  /**
   * Perform various setup tasks
   *
   * @param Event window load event
   */
  setup: function(event) {
    $$('.calendarButton').each(function(e) {
      e.observe('click', QFrameCalendar.showCalendar);
    });
  }
};

/**
 * Do stuff when the window loads
 */
Event.observe(window, 'load', QFrameCalendar.setup);
