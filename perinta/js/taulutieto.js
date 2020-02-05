var tauluTieto = {
    tieto: {},
    laskuTauluIndeksiYhteys: {},
    lisaaRivi: function(arvo) {
        this.tieto[arvo.lasku_tunnus] = arvo;
    },
    luoRivi: function (arvo) {
        var rivi = [arvo.asiakas_tunnus, arvo.asiakas_ytunnus, arvo.asiakas_nimi, arvo.lasku_tunnus, arvo.lasku_numero, arvo.lasku_viite,
                    arvo.lasku_erapaiva, arvo.lasku_summa, arvo.lasku_maksettu, arvo.lasku_karhukierros,
                    arvo.perinta_toimeksiantotunnus, arvo.perinta_siirto, arvo.perinta_tekija_nimi,
                    this.muotoileTila(arvo.perinta_tila)];

        return rivi;
    },
    annaTauluTieto: function () {
        var taulu = [];

        for(var avain in this.tieto) {
            taulu.push(tauluTieto.luoRivi(this.tieto[avain]));
        }

        return taulu;
    },
    annaTauluTietoTunnuksilla: function (tunnukset) {
        var taulu = [];
        for(var j = 0, len = tunnukset.length; j < len;j++){
            taulu.push(tauluTieto.luoRivi(this.tieto[tunnukset[j]]));
        }

        return taulu;
    },
    annaTieto: function () {
        return this.tieto;
    },
    onkoMuuttunut: function (lasku, arvo) {
        if(JSON.stringify(this.tieto[lasku]) != JSON.stringify(arvo)) {
            return true;
        }

        return false;
    },
    lisaaLaskuTauluIndeksiYhteys: function (lasku, indeksi) {
        this.laskuTauluIndeksiYhteys[lasku] = indeksi;
    },
    annaTauluIndeksi: function (lasku) {
        return this.laskuTauluIndeksiYhteys[lasku];
    },
    annaTietoLaskulla: function (lasku) {
        return this.tieto[this.annaTietoIndeksi(lasku)];
    },
    muotoileTila: function (tila) {
        if(tila == "eiperinnassa") {
            return "EI PERINNÄSSÄ";
        } else if(tila == 'luotu') {
            return "LUOTU";
        } else if(tila == 'peruttu') {
            return "PERUTTU";
        } else if(tila == 'valmis') {
            return "VALMIS";
        } else if(tila == 'perinnassa') {
            return "PERINTÄ KÄYNNISSÄ";
        } else if(tila == 'manuaaliperinta') {
            return "MANUAALIPERINNÄSSÄ";
        } else {
            return "";
        }
    },
    tyhjenna: function () {
        this.tieto = [];
    }
}
