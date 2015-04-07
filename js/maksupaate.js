$(function() {
  "use strict";

  var dialogi = $('#dialog');
  var annettu = $('#annettu');
  var kateinenKunnossa = false;
  var saaSubmittaa = false;
  var seka = $('#seka');
  var maksupaateTapahtuma = $('#maksupaatetapahtuma');
  var maksupaate = $('#maksupaate');
  var jaljella = $('#jaljella');
  var laskuriTee = $('#laskuriTee');
  var erotus;
  var kateisFormi = $('#kateisFormi');
  var korttimaksu = $('#korttimaksu');
  var kateinen = $('#kateinen');
  var pyoristysSarake = $('#pyoristysSarake');
  var laskuForm = $('#laskuForm');
  var laskuTee = $('#laskuTee');
  var yksiValittu = $('#yksiValittu');

  dialogi.dialog({
    modal: true,
    draggable: false,
    resizable: false,
    dialogClass: "no-close no-title",
    autoOpen: false,
    minWidth: 500,
    minHeight: 250,
    buttons: [
      {
        text: "Peru maksu",
        click: function() {
          $(this).dialog("close");
        }
      },
      {
        id: "hyvaksyKateinen",
        text: "Hyväksy (Enter)",
        disabled: 'disabled',
        click: function() {
          kateisFormi.submit();
        }
      }
    ]
  });

  $('#kateismaksunappi').on('click', function() {
    var kateisFloat = parseFloat(kateinen.val());
    var korttimaksuFloat = parseFloat(korttimaksu.val());

    if (kateisFloat) {
      annettu.val(korttimaksuFloat + kateisFloat);
    }
    else {
      annettu.val(korttimaksuFloat);
    }

    paivitaLaskurinLuvut();
    dialogi.dialog("open");
  });

  annettu.on('input', paivitaLaskurinLuvut);

  kateisFormi.on('submit', function(e) {
    e.preventDefault();

    if (kateinenKunnossa) {
      submitMaksupaate();
    }
    else {
      kateinen.val(annettu.val());
      $('#kateistaAnnettu').val(annettu.val());
      korttimaksu.val(Math.abs(erotus));
      $('#pyoristysOtsikko').text('Maksettavaa jäljellä');
      pyoristysSarake.text(Math.abs(erotus));
      pyoristysSarake.attr('align', 'right');
      dialogi.dialog("close");
    }
  });

  $('#korttimaksunappi').on('click', maksaMaksupaatteella);

  $('#peruuta_viimeisin').click(function() {
    saaSubmittaa = true;
    seka.val('X');
    maksupaateTapahtuma.val('X');
    $('#peruutus').val('X');
    maksupaate.submit();
  });

  maksupaate.on('submit', function(e) {
    e.preventDefault();

    if (saaSubmittaa) {
      this.submit();
    } else {
      maksaMaksupaatteella();
    }
  });

  $('#keraykseen').on('click', function() {
    laskuriTee.val('VALMIS');
    $(this.form.kateisohitus).val('X');

    this.form.submit();
  });

  laskuForm.on('click', 'input', function(e) {
    if (laskuForm.data('maksupaate') === 'kylla') {
      laskuForm.find('input').removeAttr('checked');
      $(e.target).attr('checked', 'checked');
      laskuTee.val('VALITSE');
      yksiValittu.val('JOO');
      laskuForm.submit();
    }
  });

  function submitMaksupaate() {
    kateinen.val(jaljella.text());
    $('#kateistaAnnettu').val(annettu.val());
    seka.val('kylla');
    laskuriTee.val('VALMIS');
    saaSubmittaa = true;

    maksupaate.submit();
  }

  function maksaMaksupaatteella() {
    seka.val('X');
    maksupaateTapahtuma.val('X');
    saaSubmittaa = true;
    maksupaate.submit();
  }

  function paivitaLaskurinLuvut() {
    var hyvaksyKateinen;
    var annettuInt = Math.round(parseFloat(annettu.val()) * 100);
    var jaljellaInt = Math.round(parseFloat(jaljella.text()) * 100);
    var takaisin = $('#takaisin');
    erotus = (annettuInt - jaljellaInt) / 100;

    hyvaksyKateinen = $('#hyvaksyKateinen');

    if (isNaN(erotus)) {
      takaisin.text("");
      kateinenKunnossa = false;
      hyvaksyKateinen.button('option', 'disabled', true);
    }
    else if (erotus < 0) {
      takaisin.text(Math.abs(erotus));
      kateinenKunnossa = false;
      hyvaksyKateinen.button('option', 'disabled', false);
      $('#takaisinTeksti').text("Kortille jää");
    }
    else {
      takaisin.text(erotus);
      kateinenKunnossa = true;
      hyvaksyKateinen.button('option', 'disabled', false);
      $('#takaisinTeksti').text("Takaisin");
    }
  }
});
