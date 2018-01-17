
function bind_tooltip() {
  $('.tooltip').tooltip({
    items: 'a, img, td',
    content: function() {

      var contentUrl = $(this).data('contentUrl'),
          content_id = $(this).attr('id');

      if (contentUrl) {

        var fetched_data;

        $.ajax({
          async: false,
          type: 'GET',
          url: contentUrl
        }).done(function(data) {
          fetched_data = data;
        });

        return fetched_data;
      }
      else {
        if (!content_id) {
          content_id = $(this).parent().attr('id');
        }

        return $('#div_'+content_id).html();
      }
    }
  });
}

$(function(){
  bind_tooltip();
});
