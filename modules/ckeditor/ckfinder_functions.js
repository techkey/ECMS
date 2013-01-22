/*jslint node: true, devel: true */
/*global $, cms, CKFinder */
"use strict";

$(function () {

  function getImageDimensions(fileUrl, $o) {
    $.post(
      cms.settings.basePath + 'modules/ckeditor/ajax.php',
      {file_url: fileUrl},
      function (data) {
        $o.html(data);
      }
    );
  }

  function init(api) {
    var doc = $('iframe').get(0).contentDocument;

    var $tb = $('#' + api.addToolPanel('tool'), doc);
    $tb.css({
      display: 'block',
      minHeight: 20,
      padding: 2
    });

    var $ft = $('.files_thumbnails', doc);
    $ft.click(function () {
      var sf = api.getSelectedFile();
      if (sf) {
        getImageDimensions(sf.getUrl(), $tb);
      } else {
        $tb.html('');
      }
    });
  }

  var myfinder = new CKFinder();
  myfinder.basePath = '/library/ckfinder/';
  myfinder.appendTo('myfinder', {
    height: 600,
    callback: init
  });

});
