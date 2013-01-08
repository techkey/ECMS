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

    // Set widths of the th cells and construct a dummy row.
    var $dummy = $('<tr>');
    var width, $td;
    $('th', $thead).each(function () {
      // Add 1 to the width because of border and bordercollapse.
      width = $(this).width() + 1;
      $(this).width(width);
      $td = $('<td>');
      $td.css({
        width: width,
        paddingTop: $(this).css('padding-top'),
        paddingRight: $(this).css('padding-right'),
        paddingBottom: $(this).css('padding-bottom'),
        paddingLeft: $(this).css('padding-left')
      });
      $dummy.append($td);
//      $('#debug').html('padding: ' + $(this).css('padding-right'));
    });

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


