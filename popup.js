jQuery.fn.tooltip = function(allowHtml, className){
  jQuery.fn.tooltip.created.id = 'toolTip';
  $('body').append(jQuery.fn.tooltip.created);

  var toolTip = $(jQuery.fn.tooltip.created);

  toolTip.css({'position':'absolute','display':'none'});

  //functions
  function getMouseX(e) {
    var x = null;

    if (e.pageX)   {
      x = e.pageX;
    }
    else if (e.clientX) {
      x = e.clientX + document.body.scrollLeft + document.documentElement.scrollLeft;
    }
    return x;
  }

  function getMouseY(e) {
    var y = null;

    if (e.pageY)   {
      y = e.pageY;
    }
    else if (e.clientY) {
      y = e.clientY + document.body.scrollTop
        + document.documentElement.scrollTop;
    }

    return y;
  }

  function toolTipShow(e, x, y) {
    toolTip.css({'top':y, 'left':x}).delay(500).show('fast');
  }

  function toolTipHide() {
    toolTip.clearQueue();
    toolTip.hide();
  }

  //events for each element
  this.each(function() {

    $(this).mousemove(function(e){
      var x = getMouseX(e) + 20;
      var y = getMouseY(e) + 20;

      // get content id from element and fetch text from the div
      var content_id = $(this).attr('id');
      var content = $('#div_'+content_id).html();

      var div_height = $('#div_'+content_id).height();
      var div_width = $('#div_'+content_id).width();

      var window_width = $(window).width();
      var window_height = $(window).height();

      var scrollY = document.body.scrollTop + document.documentElement.scrollTop;
      var scrollX = document.body.scrollLeft + document.documentElement.scrollLeft;

      //  Jos saimme riittävästi tietoa voimme kalkuloida oikean position
      if (div_height != null && div_width != null && x != null && y != null) {
        //  Riittääkö leveys
        if (((x - scrollX) + div_width + 30) > window_width) {
          //  Siirretään tämä ihan oikeaan laitaan..
          x = window_width - div_width - 30 + scrollX;
        }

        if ((y - scrollY) + div_height > window_height) {
          y -= (div_height);
        }

        //  Oikea laita on kuitenkin aina tärkein!
        if (x < 10) {
          x = 10;
        }
        if (y < 10) {
          y = 10;
        }
      }

      toolTipShow(e, x, y);

      //update the content
      if (allowHtml) {
        toolTip.html(content);
      }
      else {
        toolTip.text(content);
      }

      //remove all classes for the tipBox before add a new one and to avoid the 'append class'
      toolTip.removeClass();

      //set class if specified
      if (className) {
        toolTip.addClass(className);
      }
    });

    $(this).mouseout(function(){
      toolTipHide();
    });
  });
};

$(function(){
  //create the element (avoiding create multiple divisions for the tooltip)
  jQuery.fn.tooltip.created = document.createElement('div');

  $('.tooltip').tooltip('yes', 'popup');
});
