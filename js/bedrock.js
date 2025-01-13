(function ($) {
  'use strict';

  Backdrop.behaviors.bedrock = {
    attach: function (context, settings) {
      $('body').removeClass('no-jscript');
    }
  }
})(jQuery);
