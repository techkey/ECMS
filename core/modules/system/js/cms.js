"use strict";

(function ($) {
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
      var $dummy = $('tr:eq(0)', this).clone();
      $dummy.addClass('dummy');
      $dummy.css('display', 'none');
      $('tbody', this).prepend($dummy);

      $thead.width($dummy.width());

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

    /**
     * Collapsible fieldset.
     *
     * todo: improve collapsible fieldset
     */
    $('fieldset.collapsible').each(function () {
      var $fs = $(this);
      var $fw = $('.fieldset-wrapper', this);
      $('.fieldset-legend', this).click(function () {
        $fw.toggle();
        if ($fw.attr('display') === 'block') {
          $(this).css({
            background: 'url(/core/misc/menu-expanded.png) no-repeat 10px 11px'
          });
        } else {
          $(this).css({
            background: 'url(/core/misc/menu-collapsed.png) no-repeat 10px 11px'
          });
        }
      });
      if ($fs.hasClass('collapsed')) {
        $('.fieldset-legend', this).click();
      }
    });

    /**
     * Collapsible admin menu block.
     */
    var bShowAdminMenu = $.cookie('bShowAdminMenu') || 'true';
    bShowAdminMenu = (bShowAdminMenu === 'true');

    $('#block-system-admin_menu > div').css({
      display: bShowAdminMenu ? 'block' : 'none'
    });

    $('#block-system-admin_menu h3').css({
      cursor: 'pointer'
    });

    $('#block-system-admin_menu h3').click(function () {
      bShowAdminMenu = !bShowAdminMenu;
      $.cookie('bShowAdminMenu', bShowAdminMenu, {expires: 356});
      $('#block-system-admin_menu > div').toggle(bShowAdminMenu);
    });

  });
})(jQuery);

