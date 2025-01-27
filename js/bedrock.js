(function ($) {
  'use strict';

  Backdrop.behaviors.bedrock = {
    attach: function (context, settings) {
      $('body').removeClass('no-jscript');
      // Nicer autocomplete throbber.
      $('input.form-autocomplete').after('<span class="bedrock-autocomplete-throbber"></span>');
    }
  }
})(jQuery);
