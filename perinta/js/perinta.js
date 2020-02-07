var YTUNNUS_SARAKE = 1;
var NIMI_SARAKE = 2;
var LASKU_TUNNUS_SARAKE = 3;
var LASKU_NUMERO_SARAKE = 4;
var LASKU_VIITE_SARAKE = 5;
var ERAPAIVA_SARAKE = 6;
var SUMMA_SARAKE = 7;
var SAATU_SARAKE = 8;
var MUISTUTUS_SARAKE = 9;
var TOIMEKSIANTO_SARAKE = 10;
var PERINTAAN_SARAKE = 11;
var SIIRTAJA_SARAKE = 12;
var TILA_SARAKE = 13;
var RIVI_ELEMENTTI_OFFSET = 1;

var dtTaulu = null;

PainikeTila = {
    EI_AKTIIVINEN : 0,
    VIE : 1,
    PERUUTA : 2
}

var painikeTila = PainikeTila.EI_AKTIIVINEN;

function toimintaPainike() {
    if(painikeTila == PainikeTila.VIE) {
        ajax.luoPerinta();
    } else if(painikeTila == PainikeTila.PERUUTA) {
        ajax.peruuta();
    }
}

function paivitaYlapalkki() {
    valittuLkm = $('#laskuTaulu .selected').length;
    var laskunTunnukset = [];
    painikeTila = PainikeTila.EI_AKTIIVINEN;
    var asTunnus =  [];
    var o = RIVI_ELEMENTTI_OFFSET;

    $('.virhe').removeClass('virhe');

    $('#laskuTaulu .selected').each( function () {
        laskunTunnukset.push(parseInt($(this).children(':nth-child(' + (LASKU_TUNNUS_SARAKE+o) + ')').text(),10));

        if($(this).children(':nth-child(' + (TILA_SARAKE+o) + ')').text() == "LUOTU" ||
            $(this).children(':nth-child(' + (TILA_SARAKE+o) + ')').text() == "PERINTÄ KÄYNNISSÄ") {

            if(painikeTila != PainikeTila.VIE) {
                painikeTila = PainikeTila.PERUUTA;
            } else {
                $(this).children(':nth-child(' + (TILA_SARAKE+o) + ')').addClass('virhe');
            }
        } else if($(this).children(':nth-child(' + (TILA_SARAKE+o) + ')').text() == "EI PERINNÄSSÄ"){
            //eiVoiVieda = true;
            if(painikeTila != PainikeTila.PERUUTA) {
                painikeTila = PainikeTila.VIE;
            } else {
                $(this).children(':nth-child(' + (TILA_SARAKE+o) + ')').addClass('virhe');
            }
        } else {
            $(this).children(':nth-child(' + (TILA_SARAKE+o) + ')').addClass('virhe');
        }

        if(parseInt($(this).children(':nth-child(' + (SUMMA_SARAKE+o) + ')').text()) < 0) {
            $(this).children(':nth-child(' + (SUMMA_SARAKE+o) + ')').addClass('virhe');
            //eiVoiVieda = true;
            //painikeTila = PainikeTila.EI_AKTIIVINEN;
        }

        if(!asiakasTieto.tarkistaAsiakkaanYTunnus($(this).children(':nth-child(' + (YTUNNUS_SARAKE+o) + ')').text())) {
            $(this).children(':nth-child(' + (YTUNNUS_SARAKE+o) + ')').addClass('virhe');
            //eiVoiVieda = true;
            //painikeTila = PainikeTila.EI_AKTIIVINEN;
        }

        if(!laskuTieto.tarkistaViite($(this).children(':nth-child(' + (LASKU_TUNNUS_SARAKE+o) + ')').text())) {
            $(this).children(':nth-child(' + (LASKU_VIITE_SARAKE+o) + ')').addClass('virhe');
            //eiVoiVieda = true;
            //painikeTila = PainikeTila.EI_AKTIIVINEN;
        }
    });

    asTunnus = asiakasTieto.haeAsiakkaanTunnus(laskunTunnukset);

    var virheet = $('.virhe').length;

    $('#ddMyohassa').text(asiakasTieto.haeMyohassaKpl(asTunnus) + " kpl");
    $('#ddMyohassaYht').text(asiakasTieto.haeMyohassaSumma(asTunnus).toFixed(2) + " \u20AC");
    $('#ddPerinnassa').text(asiakasTieto.haePerinnassaSumma(asTunnus).toFixed(2) + " \u20AC");
    $('#ddPeritty').text(asiakasTieto.haePeritytKpl(asTunnus) + " kpl");
    $('#ddPerittyYhteensa').text(asiakasTieto.haePeritytSumma(asTunnus).toFixed(2) + " \u20AC");

    if(valittuLkm == 1) {
        if(painikeTila != PainikeTila.EI_AKTIIVINEN && virheet == 0) {
            $('#submitSiirraPerintaan').removeAttr('disabled');
        } else {
            $('#submitSiirraPerintaan').attr('disabled', 'disabled');
        }

        if(painikeTila == PainikeTila.VIE) {
            $('#submitSiirraPerintaan').prop('value', "Siirrä perintään");
        } else if (painikeTila == PainikeTila.PERUUTA && virheet == 0) {
            $('#submitSiirraPerintaan').prop('value', "Peruuta");
        } else {
            $('#submitSiirraPerintaan').prop('value', "-");
        }

        $('#ddNimi').text(asiakasTieto.haeAsiakkaanNimi(laskunTunnukset));
        $('#ddKohdistamattomia').text(asiakasTieto.haeAsiakkaanKohdistamaton(asTunnus) + " \u20AC");
    } else if(valittuLkm > 1) {
        if(painikeTila != PainikeTila.EI_AKTIIVINEN && virheet == 0) {
            $('#submitSiirraPerintaan').removeAttr('disabled');
        } else {
            $('#submitSiirraPerintaan').attr('disabled', 'disabled');
        }

        if(painikeTila == PainikeTila.VIE) {
            $('#submitSiirraPerintaan').prop('value', "Siirrä perintään " + valittuLkm + " kpl");
        } else if (painikeTila == PainikeTila.PERUUTA && virheet == 0) {
            $('#submitSiirraPerintaan').prop('value', "Peruuta " + valittuLkm + " kpl");
        } else {
            $('#submitSiirraPerintaan').prop('value', "-");
        }

        $('#ddNimi').text("(" + valittuLkm + " valittuna)");
        if(asTunnus.length > 1) {
            $('#ddKohdistamattomia').text("-");
        } else {
            $('#ddKohdistamattomia').text(asiakasTieto.haeAsiakkaanKohdistamaton(asTunnus) + " \u20AC");
        }
    } else {
        $('#submitSiirraPerintaan').val("-");
        $('#submitSiirraPerintaan').attr('disabled', 'disabled');
        $('#ddNimi').text("(ei valittuja)");
        $('#ddKohdistamattomia').text("-");
        //$('#ddMyohassa').text("-");
        //$('#ddMyohassaYht').text("-");
        //$('#ddPerinnassa').text("-");
        //$('#ddPeritty').text("-");
        //$('#ddPerittyYhteensa').text("-");
    }

    if(valittuLkm > 0) {
        $('#checkboxValitseKaikki').prop("checked", true);
    } else {
        $('#checkboxValitseKaikki').prop("checked", false);
    }

    if(valittuLkm == 1) {
        $('#naytaLaskut').empty();
        $('#naytaLaskut').append('<a href="#" onclick="rajaaYTunnus(\'' + laskuTieto.haeAsiakkaanYTunnus(laskunTunnukset) + '\')">Rajaa asiakkaalla</a>');
    } else {
        $('#naytaLaskut').empty();
    }
}

function valitseKaikki() {
    if($('#laskuTaulu .selected').length > 0) {
        poistaValinnat();
    } else {
        $('#laskuTaulu tbody tr').each(function() {
            paivitaValinta($(this), false);
        });
    }

    paivitaYlapalkki();
}

function poistaValinnat() {
    $('#laskuTaulu .selected').each(function() {
        paivitaValinta($(this), false);
    });
    $('#checkboxValitseKaikki').prop("checked", false);
}

function valitseLoput() {
    $('#laskuTaulu .selected').each(function() {
        paivitaValinta($(this), false);
    });

    $('#laskuTaulu tbody tr').each(function() {
        paivitaValinta($(this), false);
    });

    paivitaYlapalkki();
}

function checkboxValitse(e) {
    e.stopPropagation();
    valitse(e, $(this).closest('tr'), true);
}

function checkboxTdValitse(e) {
    if($(this).children('.selectionCheckbox').length > 0) {
        var checkbox = $(this).children('.selectionCheckbox');
        e.stopPropagation();
        checkbox.prop("checked", !checkbox.prop("checked"));
        valitse(e, $(this).closest('tr'), true);
    }
}

function riviValitse(e) {
    valitse(e, $(this), false);
}

function valitse(e, el, checkbox) {
    var valittuLkm = $('#laskuTaulu .selected').length;
    if(valittuLkm == 0 || (checkbox || e.ctrlKey) || el.hasClass('selected')) {
        //$(this).toggleClass('selected');
        paivitaValinta(el, checkbox);
    } else if(e.shiftKey && !el.hasClass('selected') ) {
        document.getSelection().removeAllRanges();
        var aikaisemmatElementit = el.prevAll('.selected');
        var myohemmatElementit = el.nextAll('.selected');
        if(aikaisemmatElementit.length > 0) {
            var elementti = el;
            while(!elementti.hasClass('selected')) {
                //elementti.toggleClass('selected');
                paivitaValinta(elementti, checkbox);
                elementti = elementti.prev();
            }
        } else if(myohemmatElementit.length > 0) {
            var elementti = el;
            while(!elementti.hasClass('selected')) {
                //elementti.toggleClass('selected');
                paivitaValinta(elementti, checkbox);
                elementti = elementti.next();
            }
        }
    } else {
        $('.selected').each(function() {
            paivitaValinta($(this), checkbox);
        });
        paivitaValinta(el, checkbox);
    }

    paivitaYlapalkki();
}

function paivitaValinta(elementti, checkbox) {
    if(!checkbox) {
        var checkbox = elementti.find(".selectionCheckbox");
        checkbox.prop("checked", !checkbox.prop("checked"));
    }
    elementti.toggleClass('selected');
}

function rajaaYTunnus(tunnus) {
    dtTaulu
        .column( YTUNNUS_SARAKE )
        .search( tunnus )
        .draw();

    $('#inputSuodatus' + YTUNNUS_SARAKE).val(tunnus);
}

function suodataTooltip(tunnus) {
    var tooltip = '';

    tooltip += '<a href="#" onclick="rajaaYTunnus(\'' + tunnus + '\');">Suodata Y-Tunnuksella</a>';

    return tooltip;
}


$(function(){
    dtTaulu = $('#laskuTaulu').DataTable({
                            "sDom":"<\"H\"lr>t<\"F\"<\"tauluFooter\"pi>>",
                            "oLanguage": {
                              "oPaginate": {
                                "sNext": "&gt;",
                                "sPrevious": "&lt;",
                                "sFirst":"&lt;&lt; ",
                                "sLast":"&gt;&gt;"
                               },
                               "sLengthMenu": "Näytä _MENU_ laskua sivulla",
                               "sZeroRecords": "",
                               "sProcessing":"Ladataan...",
                               "sLoadingRecords":"Ladataan...",
                               "sInfo":"_START_-_END_, yhteensä _TOTAL_",
                               "sInfoEmpty":"Ei tuloksia",
                               "sInfoFiltered":"suodatettu _MAX_ tuloksesta"
                           },
                           "processing": true,
                           "serverSide": false,
                           "pagingType": "full_numbers",
                           "bLengthChange": true,
                           "columnDefs": [{"targets": 0, "visible":true, width: "20px",
                                            render: function (data, type, row) {
                                                var arvo = '<input id="checkbox_' + row[LASKU_TUNNUS_SARAKE] + '" type="checkbox" name="checkbox_' + row[LASKU_TUNNUS_SARAKE] + '" class="selectionCheckbox" />';

                                                return arvo;
                                            }},
                                            { width: "90px",
                                            "targets": TOIMEKSIANTO_SARAKE},
                                            { width: "100px",
                                            "targets": YTUNNUS_SARAKE,
                                            render: function (data, type, row) {
                                                var arvo = '<a href="/myyntires/myyntilaskut_asiakasraportti.php?alatila=etsi&alatila=Y&ytunnus=' + data + '" target="_blank">' + data + '</a>';

                                                return arvo;
                                            }},
                                            { width: "60px",
                                            "targets": LASKU_TUNNUS_SARAKE},
                                            { width: "70px",
                                            "targets": LASKU_NUMERO_SARAKE,
                                            render: function (data, type, row) {
                                                var arvo = '<a href="/tilauskasittely/tulostakopio.php?toim=LASKU&tee=ETSILASKU&laskunro=' + data + '" target="_blank">' + data + '</a>';

                                                return arvo;
                                            }},
                                            { width: "115px",
                                            "targets": LASKU_VIITE_SARAKE},
                                            { width: "20%",
                                            "targets": NIMI_SARAKE ,
                                            "createdCell": function (td, cellData, rowData, row, col) {
                               $(td).attr("title", laskuTieto.haeTooltip(rowData[LASKU_TUNNUS_SARAKE]));
                               $(td).tooltip({show: false, hide: false, position: { my: "left top", at: "left bottom" }, content: function() {
                                   var element = $(this);
                                   return element.attr('title');
                               }});
                           }, render: function (data, type, row) {
                               var arvo = '<span class="tooltipSolu">' + data + '</span>';

                               if(laskuTieto.haeAsiakkaanVip(row[LASKU_TUNNUS_SARAKE])) {
                                   arvo += '<span class="vipInfo">VIP</span>';
                               }

                               if(laskuTieto.haeAsiakkaanManuaaliPerinta(row[LASKU_TUNNUS_SARAKE])) {
                                   arvo += '<span class="manuaaliperintaInfo">!</span>';
                               }

                               return arvo;
                           }},{ type: 'de_date', targets: ERAPAIVA_SARAKE, width: "70px"},
                           {type: 'de_date', targets: PERINTAAN_SARAKE, width: "70px"},
                           {width: "170px", targets: TILA_SARAKE, render: function ( data, type, row ) {
                               if(data == "EI PERINNÄSSÄ") {
                                   return '<div class="eiperinnassa">' + data+ '</div>';
                               } else if(data == "LUOTU") {
                                   return '<div class="luotu">' + data+ '</div>';
                               } else if(data == "PERUTTU") {
                                   return '<div class="peruttu">' + data+ '</div>';
                               } else if(data == "VALMIS") {
                                   return '<div class="valmis">' + data+ '</div>';
                               } else if(data == "PERINTÄ KÄYNNISSÄ") {
                                   return '<div class="perinnassa">' + data+ '</div>';
                               } else if(data == "MANUAALIPERINNÄSSÄ") {
                                   return '<div class="manuaaliperinta">' + data+ '</div>';
                               } else {
                                   return data;
                               }
                           }},
                           {width: "100px", targets: SUMMA_SARAKE, render: function (data, type, row) {
                               return '<strong>' + data.toFixed(2) + '</strong>';
                           }},
                           {width: "100px", targets: SAATU_SARAKE, render: function (data, type, row) {
                               return data.toFixed(2);
                           }},
                           {width: "40px", targets: MUISTUTUS_SARAKE, createdCell: function (td, cellData, rowData, row, col) {
                               $(td).attr("title", laskuTieto.haeMuistutusTooltip(rowData[LASKU_TUNNUS_SARAKE]));
                               $(td).tooltip({show: false, hide: false, position: { my: "left top", at: "left bottom" }, content: function() {
                           var element = $(this);
                           return element.attr('title');
                           }});
                           }, render: function (data, type, row) {
                           return '<span class="tooltipSolu">' + data + '</span>';
                           }}
                           ],
                           responsive: true,
                           "createdRow": function ( row, data, index ) {
                               tauluTieto.lisaaLaskuTauluIndeksiYhteys(data[LASKU_TUNNUS_SARAKE], index);
                            }
    });

    $('th').off('click');
    dtTaulu.order.listener( $('#sort1'), 1);
    dtTaulu.order.listener( $('#sort2'), 2);
    dtTaulu.order.listener( $('#sort3'), 3);
    dtTaulu.order.listener( $('#sort4'), 4);
    dtTaulu.order.listener( $('#sort5'), 5);
    dtTaulu.order.listener( $('#sort6'), 6);
    dtTaulu.order.listener( $('#sort7'), 7);
    dtTaulu.order.listener( $('#sort8'), 8);
    dtTaulu.order.listener( $('#sort9'), 9);
    dtTaulu.order.listener( $('#sort10'), 10);
    dtTaulu.order.listener( $('#sort11'), 11);
    dtTaulu.order.listener( $('#sort12'), 12);
    dtTaulu.order.listener( $('#sort13'), 13);
    $('th input').not('#checkboxValitseKaikki').on( "click", (function() {
        return false;
    }));
    $('#laskuTaulu tbody').on( 'click', 'tr', riviValitse);
    $('#laskuTaulu tbody').on( 'click', 'td', checkboxTdValitse);
    $('#laskuTaulu tbody').on( 'click', 'input', checkboxValitse);
    //$('.selectionCheckbox').on( 'click', '.scheckboxValitse);

    $('#laskuTaulu').on('page.dt', function () {
        //$('.selected').toggleClass('selected');
        poistaValinnat();
        paivitaYlapalkki();}
    );

    $('#laskuTaulu').on('order.dt', function () {
        //$('.selected').toggleClass('selected');
        //poistaValinnat();
        paivitaYlapalkki();}
    );

    paivitaTaulu();
    paivitaYlapalkki();
    luoPaivitaNappi();

    /*setInterval(function(){ ajax.paivitaLaskut() }, 60000);*/
});

function luoPaivitaNappi() {
    var elementti = $('<input type="submit" id="paivitaNappi" value="Päivitä taulu" onclick="paivitaTaulu();"/>');
    elementti.appendTo($('#laskuTaulu_length').parent());
}

function paivitaTaulu() {
    $(".dataTables_processing").css('visibility','visible');
    $(".dataTables_processing").css('display','block');
    ajax.haeLaskut();
    dtTaulu.clear();
    dtTaulu.rows.add(tauluTieto.annaTauluTieto());
    dtTaulu.draw(false);

    asetaTekstihaku(YTUNNUS_SARAKE);
    asetaTekstihaku(NIMI_SARAKE);
    asetaTekstihaku(ERAPAIVA_SARAKE);
    asetaTekstihaku(LASKU_TUNNUS_SARAKE);
    asetaTekstihaku(LASKU_NUMERO_SARAKE);
    asetaTekstihaku(LASKU_VIITE_SARAKE);
    asetaTekstihaku(PERINTAAN_SARAKE);
    asetaTekstihaku(TOIMEKSIANTO_SARAKE);
    asetaValintahaku(SIIRTAJA_SARAKE);
    asetaValintahaku(TILA_SARAKE);

    $(".dataTables_processing").css('visibility','hidden');
    $(".dataTables_processing").css('display','none');
}

function paivitaRivit(rivit) {
    for(var j = 0, len = rivit.length; j < len;j++){
        var indeksi = tauluTieto.annaTauluIndeksi(rivit[j].lasku_tunnus);

        dtTaulu.row(indeksi).data(tauluTieto.luoRivi(rivit[j])).draw(false);
    }
    asetaTekstihaku(YTUNNUS_SARAKE);
    asetaTekstihaku(NIMI_SARAKE);
    asetaTekstihaku(ERAPAIVA_SARAKE);
    asetaTekstihaku(LASKU_TUNNUS_SARAKE);
    asetaTekstihaku(LASKU_NUMERO_SARAKE);
    asetaTekstihaku(LASKU_VIITE_SARAKE);
    asetaTekstihaku(PERINTAAN_SARAKE);
    asetaTekstihaku(TOIMEKSIANTO_SARAKE);
    asetaValintahaku(SIIRTAJA_SARAKE);
    asetaValintahaku(TILA_SARAKE);
}

function asetaTekstihaku(sarake) {
    var tauluSarake = dtTaulu.column(sarake);
    var elementti = $('<br/><input type="text" value="' + tauluSarake.search() + '" id="inputSuodatus' + sarake + '"/>');
    $(tauluSarake.header()).find('input').prev().remove();
    $(tauluSarake.header()).find('input').remove();
    elementti.appendTo($(tauluSarake.header())).on( 'keyup change', function () {
        dtTaulu
            .column( sarake )
            .search( this.value )
            .draw();

        poistaValinnat();
        paivitaYlapalkki();
    } );
}

function asetaValintahaku(sarake) {
    var elementti = $('<br/><select id="valintaHaku' + sarake + '"><option value=""></option></select>');
    var tauluSarake = dtTaulu.column(sarake);
    $(tauluSarake.header()).find('select').prev().remove();
    $(tauluSarake.header()).find('select').remove();
    elementti.appendTo($(tauluSarake.header())).on( 'change', function () {
        dtTaulu
            .column( sarake )
            .search( this.value )
            .draw();

        poistaValinnat();
        paivitaYlapalkki();
    } );

    tauluSarake.indexes().flatten().each( function ( i ) {
        tauluSarake.data().unique().sort().each( function ( d, j ) {
            if(d != null) {
                elementti.append( '<option value="'+d+'">'+d+'</option>' );
            }
        });
    });

    $('#valintaHaku' + sarake).val(tauluSarake.search());
}
