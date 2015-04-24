$(function() {
  "use strict";

  $('.toggle-list').on('click', function(e) {
    $(e.target).parent().next().toggle();
  });
});
