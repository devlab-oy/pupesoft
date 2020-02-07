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

Tyyppi = {
    VIENTI : "V",
    PERUUTA : "P"
}

var ajax = {
    haeLaskut: function() {
        $.ajax({url:ajaxUrl, async: false, dataType: 'json',
            success: function (response) {
                ajax.kasitteleLaskut(response.data);
            },
            error: function(response) {
                alert('Tietojen hakeminen ep‰onnistui: ' + JSON.stringify(response));
            }
            });
    },

    kasitteleLaskut: function(tieto) {
        tauluTieto.tyhjenna();
        asiakasTieto.tyhjenna();
        laskuTieto.tyhjenna();
        for(var i = 0, len = tieto.length; i < len;i++) {
            var arvo = tieto[i];

            tauluTieto.lisaaRivi(arvo);

            asiakasTieto.lisaaTieto(arvo);

            laskuTieto.lisaaTieto(arvo);
        }
    },
    paivitaLaskut: function () {
        $.ajax({url:ajaxUrl, async: true,dataType: 'json',
            success: function (response) {
                var rivit = ajax.tarkistaMuutokset(response.data);

                if(rivit.length > 50) {
                    alert("Taulu sis‰lt‰‰ paljon muutoksia. Ole hyv‰ ja p‰ivit‰ taulu.");
                } else {
                    paivitaRivit(rivit);
                }
            },
            error: function(response) {
                alert('Tietojen hakeminen ep‰onnistui: ' + JSON.stringify(response));
            }
            });
    },
    tarkistaMuutokset: function (data) {
        var muuttuneet = [];
        for(var i = 0, len = data.length; i < len;i++) {
            if(tauluTieto.onkoMuuttunut(data[i].lasku_tunnus, data[i])) {

                muuttuneet.push(data[i])

                tauluTieto.lisaaRivi(data[i]);

            }
        }

        var rivit = [];
        for(var i = 0, len = muuttuneet.length; i < len;i++) {
            rivit.push(muuttuneet[i]);
        }
        asiakasTieto.paivita(rivit);
        return muuttuneet;
    },
    luoPerinta: function() {
        var laskuObj = {"tyyppi": Tyyppi.VIENTI, "laskut": []};

        $('#laskuTaulu tbody .selected').each(function(i, obj) {
            laskuObj["laskut"].push($(this).children(':nth-child('+(LASKU_TUNNUS_SARAKE+RIVI_ELEMENTTI_OFFSET)+')').text());
        });

        if(confirm("Haluatko varmasti vied‰ perint‰‰n laskut " + JSON.stringify(laskuObj["laskut"]))) {
            var json = JSON.stringify(laskuObj);

            $.ajax({
                type: "POST",
                url: ajaxUrl,
                data: json,
                success: function(response) {
                    ajax.tarkistaMuutokset(response.data);
                    paivitaRivit(response.data);
                    $('.selected').toggleClass('selected');
                    paivitaYlapalkki();
                },
                error: function(response) {
                    alert("Rivien vienniss‰ perint‰‰n tapahtui virhe: " + JSON.stringify(response));
                },
                dataType: "json"
            });
        }
    },
    peruuta: function() {
        var laskuObj = {"tyyppi": Tyyppi.PERUUTA, "laskut": []};

        $('#laskuTaulu tbody .selected').each(function(i, obj) {
            laskuObj["laskut"].push($(this).children(':nth-child('+(LASKU_TUNNUS_SARAKE+RIVI_ELEMENTTI_OFFSET)+')').text());
        });

        if(confirm("Haluatko varmasti peruuttaa perinn‰n laskuilta " + JSON.stringify(laskuObj["laskut"]))) {
            var json = JSON.stringify(laskuObj);

            $.ajax({
                type: "POST",
                url: ajaxUrl,
                data: json,
                success: function(response) {
                    ajax.tarkistaMuutokset(response.data);
                    paivitaRivit(response.data);
                    $('.selected').toggleClass('selected');
                    paivitaYlapalkki();
                },
                error: function(response) {
                    alert("Rivien vienniss‰ perint‰‰n tapahtui virhe: " + JSON.stringify(response));
                },
                dataType: "json"
            });
        }
    }
}
