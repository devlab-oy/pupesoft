$(document).ready(function() {

  // Tarvitaan yhtiö
  yhtio = $('#yhtiorow').val();
  resurssit = "tuotannonsuunnittelu.php?no_head=yes&ajax_request=1&yhtio="+yhtio+"&resurssit=true";

  // Kalenterin asetukset
  $('#calendar').fullCalendar({
    defaultView: 'resourceNextWeeks',
    editable: false,
    weekends: false,
    height: 400,
    width: 700,
    monthNames: ['Tammikuu', 'Helmikuu', 'Maaliskuu', 'Huhtikuu', 'Toukokuu', 'Kesäkuu', 'Heinäkuu', 'Elokuu', 'Syyskuu', 'Lokakuu', 'Marraskuu', 'Joulukuu'],
    monthNamesShort: ['Tammi', 'Helmi', 'Maalis', 'Huhti', 'Touko', 'Kesä', 'Heinä', 'Elo', 'Syys', 'Loka', 'Marras', 'Joulu'],
    dayNames: ['Sunnuntai', 'Maanantai', 'Tiistai', 'Keskiviikko', 'Torstai', 'Perjantai', 'Lauantai'],
    dayNamesShort: ['Su', 'Ma', 'Ti', 'Ke', 'To', 'Pe', 'La'],
    columnFormat: {
      resourceNextWeeks: 'dddd d.M'
    },
    header: {
      left: '',
      center: 'title',
      right:  'today prev,next'
    },
    titleFormat: {
      resourceNextWeeks: "d.M.[yyyy]{ '-' d.M.yyyy}"
    },
    buttonText: {
            prev:     '&nbsp;&#9668;&nbsp;',  // left triangle
            next:     '&nbsp;&#9658;&nbsp;',  // right triangle
            prevYear: '&nbsp;&lt;&lt;&nbsp;', // <<
            nextYear: '&nbsp;&gt;&gt;&nbsp;', // >>
            today:    'Tänään',
            month:    'kuukausi',
            week:     'viikko',
            day:      'päivä'
          },
    resources: resurssit,
    events: {
      url: 'tuotannonsuunnittelu.php?no_head=yes&ajax_request=1',
      type: 'GET',
      data: {
        yhtio: yhtio,
        valmistukset: 'true'
      },
      error: function() {
        alert("Virhe valmistuslinjojen hakemisessa");
      },
      color: '#060'
    },
    eventClick: function(event, jsEvent, view) {
      if (event.tyyppi === 'valmistus') {
        show_details(event, jsEvent);
      }
    },
    eventRender: function(event, element) {

      // Valmistuksille lisätään nappulat, muille ei
      if (event.tyyppi === 'valmistus') {
        // Eteenpäin ja taaksepäin siirto napit
        var prev_link = "<a href='tuotannonsuunnittelu.php?method=move&direction=left&tunnus=" + event.tunnus + "'> < </a>";
        var next_link = "<a href='tuotannonsuunnittelu.php?method=move&direction=right&tunnus=" + event.tunnus + "'> > </a>";

        // Otsikko
        var title = event.tila + " " + event.title + event.kesto + "H";

        // Lisätään tiedot eventtiin
        if (event.tila !== 'VT') {
          $(element).children('.fc-event-inner').prepend('<span class="fc-event-next">' + next_link + '</span>');
          $(element).children('.fc-event-inner').prepend('<span class="fc-event-prev">' + prev_link +'</span>');
        }
        // Otsikko
        $(element).children('.fc-event-inner').children('.fc-event-title').html(title);
      }
      else {
        var link_to = "<a href='tuotannonsuunnittelu.php?tee=poista&tunnus=" + event.tunnus + "'>Poista</a>";
        $(element).children('.fc-event-inner').children('.fc-event-title').append("<br>" + link_to);

      }

      // alku- ja loppuaika
      $(element).children('.fc-event-inner').children('.fc-event-time').html(parse_date(event.start) + " - " + parse_date(event.end));
    }
  });

  $('#close_bubble').click(function() {
    $('#bubble').fadeOut('fast');
  });
});

function show_details(event, jsEvent) {
  var x = null;
  if (jsEvent.pageX > ($(document).width() - $('#bubble').width())) {
    x = $(document).width() - ($('#bubble').width() + 50);
  }
  else {
    x = jsEvent.pageX;
  }

  var body = document.body;
  var html = document.documentElement;
  var height = Math.min(body.scrollHeight, body.offsetHeight, html.clientHeight, html.scrollHeight, html.offsetHeight);
  var y = null;
  if (jsEvent.pageY > (height - $('#bubble').height())) {

    y = height - ($('#bubble').height() + 50);
  }
  else {
    y = jsEvent.pageY;
  }
  var scrollTop = $(window).scrollTop(); //how much user has scrolled
  y = y - scrollTop;

  // Info laatikko
  $('#bubble').css('top', y);
  $('#bubble').css('left', x);
  $('#bubble').fadeIn('fast');

  $('#valmistuksen_tunnus').val(event.tunnus);

  // Eventin tietoja boksiin
  start = parse_date(event.start);
  end = parse_date(event.end);

  // Lisätään valmistuksen tiedot kalenteriin
  // Korvataan \n -rivivaihto <br> -rivivaihdolla
  var content = event.title.replace(/\n/g, "<br>") + "<br>";
  content += start + " - " + end + "<br>";

  // Jos on puutteita
  if (!$.isEmptyObject(event.puutteet)) {
    content += "Puutteet:<br>";
    for (var tuote in event.puutteet) {
      content += tuote + " " + event.puutteet[tuote] + "<br>";
    }
  }

  if (event.tunnus) {
    $('#header').html("Valmistus: " + event.tunnus);
  }
  $('#content').html(content);
}

function parse_date(pvm) {
  d = pvm.getDate();
  m = pvm.getMonth()+1;
  Y = pvm.getFullYear();
  H = (pvm.getHours() < 10 ? "0" : "") + pvm.getHours();
  i = (pvm.getMinutes() < 10 ? "0" : "") + pvm.getMinutes();

  // Palautetaan d.m.y h:i
  return d + "." + m + "." + Y + " " + H + ":" + i;
}
