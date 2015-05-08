$(function() {
  "use strict";

  $('.toggle-list').on('click', function(e) {
    e.preventDefault();

    $(e.target).parent().next().toggle();
  });
});
