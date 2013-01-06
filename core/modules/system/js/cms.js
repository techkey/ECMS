/*jslint node: true, devel: true, browser: true, todo: true */
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

  // todo: Make this work nice with stupidtable.
  $('table.sticky').each(function () {
    var $sticky = $('<table><thead><tr></tr></thead></table>');
    var $table = $(this);

    // Copy header cells.
    $('th', $table).each(function () {
      var $th = $(this).clone(true);
      // Add 1 to the width because of bordercollapse.
      $th.width($(this).width() + 1);
      $('tr', $sticky).append($th);
    });

    $sticky.attr('class', $table.attr('class'));
    $sticky.removeClass('sticky').addClass('sticky-header');
    $sticky.css({
      position: 'fixed',
      marginTop: 0,
      top: 0,
      left: $(this).css('left'),
      visibility: 'hidden'
    });
    $table.before($sticky);

    $(window).scroll(function () {
      var txt = '';
      var offset = $('thead', $table).offset();

      var visibility = ($(document).scrollTop() > offset.top) ? 'visible' : 'hidden';
      $sticky.css('visibility', visibility);

      txt += 'visibility: ' + visibility + '<br>';
      txt += 'top: ' + offset.top + '<br>';
      txt += 'left: ' + offset.left + '<br>';
      txt += 'scrollTop: ' + $(document).scrollTop() + '<br>';
      txt += 'scrollLeft: ' + $(document).scrollLeft() + '<br>';
      $('#debug').html(txt);
    });
  });

});


