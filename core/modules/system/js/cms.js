/*jslint node: true, devel: true, browser: true, todo: true, plusplus: true */
/*global $ */
"use strict";

//noinspection JSUnusedGlobalSymbols
function showDebug() {
  var $debug = $('<div id="debug"></div>');
  $debug.css({
    position: 'fixed',
    top: '40px',
    right: '10px',
    border: '1px solid blue',
    minWidth: '100px',
    minHeight: '50px',
    background: '#8080FF',
    opacity: 0.8
  });
  $('body').append($debug);
}

$(function () {

//  showDebug();

  $('table.sticky').each(function () {
    var $thead = $('thead', this);

    // Set widths of the th cells.
    var width;
    $('th', $thead).each(function () {
      // Add 1 to the width because of border and bordercollapse.
      width = $(this).width() + 1;
      $(this).width(width);
    });

    // Construct a dummy row.
/*
    var $dummy = $('<tr>');
    $('tbody tr:eq(0) td', this).each(function () {
      // Add 1 to the width because of border and bordercollapse.
      width = $(this).width() + 0;
      $td = $('<td>');
      var o = {
        width: width,
        paddingTop: $(this).css('padding-top'),
        paddingRight: $(this).css('padding-right'),
        paddingBottom: $(this).css('padding-bottom'),
        paddingLeft: $(this).css('padding-left'),
        borderRight: $(this).css('border-right-width'),
        borderLeft: $(this).css('border-left-width')
      };
      $td.css(o);
      $dummy.append($td);
    });
*/
    var $dummy = $('tr:eq(0)', this).clone();

    $dummy.css('display', 'none');
    $('tbody', this).prepend($dummy);

    var offset = $thead.offset();
    var sticky;

    $(window).scroll(function () {
      sticky = ($(document).scrollTop() > offset.top);
      if (sticky) {
        $dummy.css('display', 'table-row');
        $thead.css({
          position: 'fixed',
          top: 0,
          left: offset.left - $(document).scrollLeft()
        });
      } else {
        $dummy.css('display', 'none');
        $thead.css({
          position: 'relative'
        });
      }

//      txt += 'visibility: ' + visibility + '<br>';
//      txt += 'top: ' + offset.top + '<br>';
//      txt += 'left: ' + offset.left + '<br>';
//      txt += 'scrollTop: ' + $(document).scrollTop() + '<br>';
//      txt += 'scrollLeft: ' + $(document).scrollLeft() + '<br>';
//      $('#debug').html(txt);
    });

    $(window).resize(function () {
      offset = (sticky) ? $dummy.offset() : $thead.offset();
    });

  });

});


