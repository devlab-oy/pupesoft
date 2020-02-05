var laskuTieto = {
    tieto: {},
    tyhjenna: function () {
        this.tieto = {};
    },
    lisaaTieto: function (rivi) {
        if(this.tieto[rivi.lasku_tunnus] == undefined) {
            this.tieto[rivi.lasku_tunnus] = {};
            this.tieto[rivi.lasku_tunnus]['lasku_karhukierros'] = rivi.lasku_karhukierros;
            this.tieto[rivi.lasku_tunnus]['lasku_muistutus_pvm'] = rivi.lasku_muistutus_pvm;
            this.tieto[rivi.lasku_tunnus]['lasku_muistutus_paivia'] = rivi.lasku_muistutus_paivia;
            this.tieto[rivi.lasku_tunnus]['vip'] = rivi.asiakas_vip;
            this.tieto[rivi.lasku_tunnus]['manuaaliperinta'] = rivi.asiakas_manuaaliperinta;
            this.tieto[rivi.lasku_tunnus]['nimi'] = rivi.asiakas_nimi;
            this.tieto[rivi.lasku_tunnus]['nimitark'] = rivi.asiakas_nimitark;
            this.tieto[rivi.lasku_tunnus]['osoite'] = rivi.asiakas_osoite;
            this.tieto[rivi.lasku_tunnus]['toimipaikka'] = rivi.asiakas_toimipaikka;
            this.tieto[rivi.lasku_tunnus]['postinumero'] = rivi.asiakas_postinumero;
            this.tieto[rivi.lasku_tunnus]['puhelin'] = rivi.asiakas_puhelin;
            this.tieto[rivi.lasku_tunnus]['ytunnus'] = rivi.asiakas_ytunnus;
            this.tieto[rivi.lasku_tunnus]['viite'] = rivi.lasku_viite;
        }
    },
    haeMuistutusTooltip: function (laskunTunnus) {
        var tooltip = 'Ei muistutuksia';
        if(this.tieto[laskunTunnus]['lasku_karhukierros'] > 0) {
            tooltip = 'Edellinen muistutus <strong>';
            tooltip += this.tieto[laskunTunnus]['lasku_muistutus_paivia'] + '</strong> p‰iv‰‰ sitten <strong>';
            tooltip += this.tieto[laskunTunnus]['lasku_muistutus_pvm'] + '</strong>.';
        }

        return tooltip;
    },
    haeTooltip: function (laskunTunnus) {
        var tooltip = '';
        if(this.tieto[laskunTunnus]['vip']) {
            tooltip += '<strong style="color: green;">VIP-asiakas</strong><br/>';
        }
        if(this.tieto[laskunTunnus]['manuaaliperinta']) {
            tooltip += '<strong style="color: #FF6600;">Asiakkaalla on manuaaliperint‰ k‰ynniss‰.</strong><br/>';
        }
        tooltip += '<strong>' + this.tieto[laskunTunnus]['nimi']
            + ' ' + this.tieto[laskunTunnus]['nimitark'] + '</strong><br/>';
        tooltip += this.tieto[laskunTunnus]['osoite'] + '<br/>';
        tooltip += this.tieto[laskunTunnus]['toimipaikka'] + '<br/>';
        tooltip += this.tieto[laskunTunnus]['postinumero'] + '<br/>';
        tooltip += this.tieto[laskunTunnus]['puhelin'] + '<br/>';

        return tooltip;
    },
    haeAsiakkaanVip: function (laskunTunnus) {
        return this.tieto[laskunTunnus]["vip"];
    },
    haeAsiakkaanManuaaliPerinta: function (laskunTunnus) {
        return this.tieto[laskunTunnus]["manuaaliperinta"];
    },
    haeAsiakkaanYTunnus: function (laskunTunnus) {
        return this.tieto[laskunTunnus]["ytunnus"];
    },
    tarkistaViite: function (laskunTunnus) {
        return this.tieto[laskunTunnus]["viite"];
    }
}
