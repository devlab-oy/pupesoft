var asiakasTieto = {
    tieto: {},
    laskuAsiakasYhteys: {},
    tyhjenna: function () {
        this.tieto = {};
        this.laskuAsiakasYhteys = {};
    },
    lisaaYhteys: function (laskunTunnus, asiakkaanYTunnus, nimi) {
        if(this.laskuAsiakasYhteys[laskunTunnus] == undefined) {
            this.laskuAsiakasYhteys[laskunTunnus] = {"tunnus": asiakkaanYTunnus, "nimi": nimi};
        }
    },
    lisaaTieto: function (rivi) {
        if(this.tieto[rivi.asiakas_ytunnus] == undefined) {
            this.tieto[rivi.asiakas_ytunnus] = {};
            this.tieto[rivi.asiakas_ytunnus]['nimi'] = rivi.asiakas_nimi;
            this.tieto[rivi.asiakas_ytunnus]['osoite'] = rivi.asiakas_osoite;
            this.tieto[rivi.asiakas_ytunnus]['toimipaikka'] = rivi.asiakas_toimipaikka;
            this.tieto[rivi.asiakas_ytunnus]['postinumero'] = rivi.asiakas_postinumero;
            this.tieto[rivi.asiakas_ytunnus]['puhelin'] = rivi.asiakas_puhelin;
            this.tieto[rivi.asiakas_ytunnus]['kohdistamaton'] = rivi.asiakas_kohdistamaton;
            this.tieto[rivi.asiakas_ytunnus]['laskut'] = [];
            this.tieto[rivi.asiakas_ytunnus]['vip'] = rivi.asiakas_vip;
        }
        this.tieto[rivi.asiakas_ytunnus]['laskut'][rivi.lasku_tunnus] = rivi;

        asiakasTieto.lisaaYhteys(rivi.lasku_tunnus, rivi.asiakas_ytunnus, rivi.asiakas_nimi);
    },
    tarkistaAsiakkaanYTunnus: function(tunnus) {
        if(tunnus.substr(0, 2) != 'FI' || tunnus.length != 10) {
            return false;
        }

        return true;
    },
    haeAsiakkaanTunnus: function (laskunTunnukset) {
        var tunnukset = [];

        for(var j = 0, len = laskunTunnukset.length; j < len;j++){
            if(tunnukset.indexOf(this.laskuAsiakasYhteys[laskunTunnukset[j]]["tunnus"]) < 0) {
                tunnukset.push(this.laskuAsiakasYhteys[laskunTunnukset[j]]["tunnus"]);
            }
        }

        return tunnukset;
    },
    haeAsiakkaanNimi: function (laskunTunnukset) {
        if(laskunTunnukset.length == 1) {
            return this.laskuAsiakasYhteys[laskunTunnukset]["nimi"];
        }

        return "-";
    },
    haeAsiakkaanKohdistamaton: function (tunnus) {

        if(tunnus.length == 1) {
            return this.tieto[tunnus]["kohdistamaton"];
        }

        return "-";
    },
    haeAsiakkaanVip: function (tunnus) {
        return this.tieto[tunnus]["vip"];
    },
    haeMyohassaKpl: function(tunnus) {
        var kpl = 0;
        var lisatyt = {};


        if(tunnus == '') {
            for(var key in this.tieto) {
                lisatyt[key] = true;
                for(var avain in this.tieto[key]['laskut']) {
                    var lasku = this.tieto[key]['laskut'][avain];
                    if(lasku.lasku_summa > 0) {
                        kpl += 1
                    }
                }
            }
        } else {
            for(var j = 0, len = tunnus.length; j < len;j++){
                if(this.tieto[tunnus[j]] != undefined && lisatyt[tunnus[j]] == undefined) {
                    lisatyt[tunnus[j]] = true;
                    for(var avain in this.tieto[tunnus[j]]['laskut']) {
                        var lasku = this.tieto[tunnus[j]]['laskut'][avain];
                        if(lasku.lasku_summa > 0) {
                            kpl += 1
                        }
                    }
                }
            }
        }

        return kpl;
    },
    haeMyohassaSumma: function(tunnus) {
        var summa = 0;
        var lisatyt = {};

        if(tunnus == '') {
            for(var key in this.tieto) {
                lisatyt[key] = true;
                for(var avain in this.tieto[key]['laskut']) {
                    var lasku = this.tieto[key]['laskut'][avain];
                    if(lasku.lasku_summa > 0) {
                        summa += (lasku.lasku_summa - lasku.lasku_maksettu);
                    }
                }
            }
        } else {
            for(var j = 0, len = tunnus.length; j < len;j++){
                if(this.tieto[tunnus[j]] != undefined && lisatyt[tunnus[j]] == undefined) {
                    lisatyt[tunnus[j]] = true;
                    for(var avain in this.tieto[tunnus[j]]['laskut']) {
                        var lasku = this.tieto[tunnus[j]]['laskut'][avain];
                        if(lasku.lasku_summa > 0) {
                            summa += (lasku.lasku_summa - lasku.lasku_maksettu);
                        }
                    }
                }
            }
        }


        return summa;
    },
    haePerinnassaSumma: function(tunnus) {
        var summa = 0.0;
        var lisatyt = {};

        if(tunnus == '') {
            for(var key in this.tieto) {
                lisatyt[key] = true;
                for(var avain in this.tieto[key]['laskut']) {
                    var lasku = this.tieto[key]['laskut'][avain];
                    if(lasku.perinta_tila != 'ei_perinnassa' && lasku.perinta_tila != 'valmis') {
                        summa += (lasku.perinta_summa - lasku.perinta_maksettu);
                    }
                }
            }
        } else {
            for(var j = 0, len = tunnus.length; j < len;j++){
                if(this.tieto[tunnus[j]] != undefined && lisatyt[tunnus[j]] == undefined) {
                    lisatyt[tunnus[j]] = true;
                    for(var avain in this.tieto[tunnus[j]]['laskut']) {
                        var lasku = this.tieto[tunnus[j]]['laskut'][avain];
                        if(lasku.perinta_tila != 'ei_perinnassa' && lasku.perinta_tila != 'valmis') {
                            summa += (lasku.perinta_summa - lasku.perinta_maksettu);
                        }
                    }
                }
            }
        }

        return summa;
    },
    haePeritytKpl: function(tunnus) {
        var kpl = 0;
        var lisatyt = {};

        if(tunnus == '') {
            for(var key in this.tieto) {
                lisatyt[key] = true;
                for(var avain in this.tieto[key]['laskut']) {
                    var lasku = this.tieto[key]['laskut'][avain];
                    if(lasku.perinta_tila == 'valmis') {
                        kpl += 1
                    }
                }
            }
        } else {
            for(var j = 0, len = tunnus.length; j < len;j++){
                if(this.tieto[tunnus[j]] != undefined && lisatyt[tunnus[j]] == undefined) {
                    lisatyt[tunnus[j]] = true;
                    for(var avain in this.tieto[tunnus[j]]['laskut']) {
                        var lasku = this.tieto[tunnus[j]]['laskut'][avain];
                        if(lasku.perinta_tila == 'valmis') {
                            kpl += 1
                        }
                    }
                }
            }
        }

        return kpl;
    },
    haePeritytSumma: function(tunnus) {
        var summa = 0;
        var lisatyt = {};

        if(tunnus == '') {
            for(var key in this.tieto) {
                lisatyt[key] = true;
                for(var avain in this.tieto[key]['laskut']) {
                    var lasku = this.tieto[key]['laskut'][avain];
                    if(lasku.lasku_summa > 0) {
                        summa += lasku.perinta_maksettu;
                    }
                }
            }
        } else {
            for(var j = 0, len = tunnus.length; j < len;j++){
                if(this.tieto[tunnus[j]] != undefined && lisatyt[tunnus[j]] == undefined) {
                    lisatyt[tunnus[j]] = true;
                    for(var avain in this.tieto[tunnus[j]]['laskut']) {
                        var lasku = this.tieto[tunnus[j]]['laskut'][avain];
                        if(lasku.lasku_summa > 0) {
                            summa += lasku.perinta_maksettu;
                        }
                    }
                }
            }
        }

        return summa;
    },
    haeTooltip: function (asiakkaanTunnus) {
        var tooltip = '';
        if(this.tieto[asiakkaanTunnus]['vip']) {
            tooltip += '<strong style="color: green;">VIP-asiakas</strong><br/>';
        }
        tooltip += '<strong>' + this.tieto[asiakkaanTunnus]['nimi'] + '</strong><br/>';
        tooltip += this.tieto[asiakkaanTunnus]['osoite'] + '<br/>';
        tooltip += this.tieto[asiakkaanTunnus]['toimipaikka'] + '<br/>';
        tooltip += this.tieto[asiakkaanTunnus]['postinumero'] + '<br/>';
        tooltip += this.tieto[asiakkaanTunnus]['puhelin'] + '<br/>';


        return tooltip;
    },
    paivita: function (data) {
        //asiakasTieto.tyhjenna();

        for(var avain in data) {
            var arvo = data[avain];
            asiakasTieto.lisaaTieto(arvo);
        }
    }
}
