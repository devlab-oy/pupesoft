/*
 * scripts.katelaskenta.php
 *
 * Tiedosto sis‰lt‰‰ javascript koodit k‰yttˆliittym‰n toimintoja varten,
 * jotka sijaitsevat template.katelaskenta.php tiedostossa. Tiedoston
 * alussa esitell‰‰n k‰ytett‰vi‰ muuttujia ja funktioita.
 */
$(document).ready(function () {

    // Esitell‰‰n muuttujat.
    // Kaikki muuttujat alustetaan funktiossa myˆhemm‰ss‰
    // vaiheessa. 
    var tuoterivitTaulukko; // koko taulukko
    var tuoterivit; // kaikki taulukon rivit
    var tuoterivitCheckboxes; // tuoterivien checkboxit
    var footerRivi; // taulukon footer rivi
    var footerCheckbox; // taulukon footer checkbox
    var footerLaskeKaikki;  // Footer osion
    
    // Kaikki sarake-p‰‰tteiset muuttujat ovat jqueryn
    // selectoreita kertomaan, miss‰ oikea sarake on
    // mik‰li k‰yttˆliittym‰‰ menn‰‰n muuttamaan.
    var footerKateMyyntihintaSarake; 
    var footerKateMyymalahintaSarake; 
    var footerKateNettohintaSarake; 
    var tuoteriviCheckboxSarake;
    var tuoteriviLaskeNappiSarake;
    var kateMyyntihintaSarake;
    var kateMyymalahintaSarake;
    var kateNettohintaSarake;
    var myyntihintaSarake;
    var myymalahintaSarake;
    var nettohintaSarake;
    
    
    // Esitell‰‰n funktiot.
    // Toteutukset lˆytyv‰t alapuolelta.
    var alustaMuuttujat;
    var onkoVirheellinenMyyntikate;
    var onkoTyhja;
    var lisaaHintaanKate;
    var asetaUusiHinta;
    
    /**
     * Funtion toiminto on vain alustaa tavittavat muuttujat, joita
     * eri toiminnallisuuksissa k‰ytet‰‰n. N‰in elementtien hakuja
     * on helpompi muuttaa, jos k‰yttˆliittym‰ss‰ muuttuu jokin.
     */
    var alustaMuuttujat = function() {
        tuoterivitTaulukko = $("#katelaskenta-hakutulokset");
        tuoterivit = tuoterivitTaulukko.find("tbody tr");
        tuoterivitCheckboxes = tuoterivitTaulukko.find("tbody tr td:nth-child(2) input[type=checkbox]");
        footerRivi = tuoterivitTaulukko.find("tfoot tr");
        footerCheckbox = tuoterivitTaulukko.find("tfoot tr td:first-child input[type=checkbox]");
        footerLaskeKaikki = tuoterivitTaulukko.find("tfoot tr td:last-child a");
        
        footerKateMyyntihintaSarake = "td:nth-child(4) input";
        footerKateMyymalahintaSarake = "td:nth-child(6) input";
        footerKateNettohintaSarake = "td:nth-child(8) input";
        kateMyyntihintaSarake = "td:nth-child(8) input";
        kateMyymalahintaSarake = "td:nth-child(10) input";
        kateNettohintaSarake = "td:nth-child(12) input";
        myyntihintaSarake = "td:nth-child(7) span.hinta";
        myymalahintaSarake = "td:nth-child(9) span.hinta";
        nettohintaSarake = "td:nth-child(11) span.hinta";
        tuoteriviCheckboxSarake = "td:nth-child(2) input[type=checkbox]";
        tuoteriviLaskeNappiSarake = "td:last-child a";
    }
    
    
    /**
     * Funktio tarkistaa annetun myyntikatteen, jotta laskutoimitukset
     * voidaan suorittaa.
     *
     * Palauttaa false, jos virhe lˆytyy.
     */
    var onkoVirheellinenMyyntikate = function(myyntikate) {
        if (isNaN(myyntikate)) 
            return false;
        if (myyntikate >= 100 || myyntikate < 0) 
            return false;
        return true;
    };
    
    /**
     * Funktio tarkistaa onko annettu arvo tyhja.
     */
    var onkoTyhja = function(myyntikate) {
        if(myyntikate === "")
            return true;
        return false;
    };
    
    /**
     * Funktio lis‰‰ annettuun hintaan annetun katteen.
     *
     * Kate annetaan prosentteina, eik‰ desimaaleissa. Desimaaleja
     * voi k‰ytt‰‰ prosenteissa. Palauttaa hinnan laskutoimituksen
     * j‰lkeen. Jos syˆtetyiss‰ tiedoissa on virhe, palautetaan false
     * ja n‰ytet‰‰n alert-ikkuna.
     */
    var lisaaHintaanKate = function(keskihankintahinta, myyntikate) {
        var floatKeskihankintaHinta = parseFloat(keskihankintahinta);
        var floatMyyntikate = parseFloat(myyntikate);
        
        if (onkoTyhja(floatMyyntikate)) {
            alert("Katekentt‰ ei voi olla tyhj‰.");
            return false;
        }
        if (isNaN(floatKeskihankintaHinta)) {
            alert("Virheellinen keskihankintahinta.");
            return false;
        }
        
        if(!onkoVirheellinenMyyntikate(floatMyyntikate)) {
            alert("Virheellinen kate. Katteen pit‰‰ olla 0-100 v‰lill‰.");
            return false;
        }

        return floatKeskihankintaHinta / (1 - (floatMyyntikate / 100));
    };
    
    /**
     * Funktio asettaa elementtiin uuden hinnan.
     *
     * Hintaan m‰‰ritet‰‰n k‰‰rin elementti eli t‰ss‰ tapauksessa
     * <font>. Hinta v‰rj‰t‰‰n punaiseksi ja asetetaan annettuun
     * elementtiin.
     */
    var asetaUusiHinta = function(hinta, kohdeElementti) {
        var htmlUusiHinta = $("<font></font>")
                                .css("color", "red")
                                .css("font-weight", "bold")
                                .text(hinta.toFixed(2));
        $(kohdeElementti).empty().html(htmlUusiHinta);
    };
    
    /**
     * T‰st‰ l‰htien ohjelmakoodissa m‰‰ritell‰‰n elementeille niiden
     * toimintalogiikka aikaisemmin esitettyjen funktioiden avulla.
     * Kaikkien funktioiden ja yleisten muuttujien kuuluisi olla esitetty
     * ennen seuraavia toimenpiteit‰.
     */
    
    // Kutsutaan muuttujien alustus.
    alustaMuuttujat();
    
    // Lis‰t‰‰n jokaiselle tuoterivin laske-painikkeellle toimintalogiikka.
    // Laske painike laskee annetun kateprosentin mukaan uuden hinnan.
    $.each(tuoterivit, function () {
        var keskihankintahinta = $(this).data("kehahinta");

        var myyntikate = $(this).find(kateMyyntihintaSarake);
        var myyntihintaElementti = $(this).find(myyntihintaSarake);
        
        var myymalakate = $(this).find(kateMyymalahintaSarake);
        var myymalahintaElementti = $(this).find(myymalahintaSarake);
        
        var nettokate = $(this).find(kateNettohintaSarake);
        var nettohintaElementti = $(this).find(nettohintaSarake);
        
        $(this).find(tuoteriviLaskeNappiSarake).on("click", function (event) {
            event.preventDefault();
            var uusiMyyntihinta = lisaaHintaanKate(keskihankintahinta, myyntikate.val());
            var uusiMyymalahinta = lisaaHintaanKate(keskihankintahinta, myymalakate.val());
            var uusiNettohinta = lisaaHintaanKate(keskihankintahinta, nettokate.val());
            
            if(uusiMyyntihinta !== false && myyntikate.val() > 0) {
                asetaUusiHinta(uusiMyyntihinta, myyntihintaElementti);
            }
            
            if(uusiMyymalahinta !== false && myymalakate.val() > 0) {
                asetaUusiHinta(uusiMyymalahinta, myymalahintaElementti);
            }
            
            if(uusiNettohinta !== false && nettokate.val() > 0) {
                asetaUusiHinta(uusiNettohinta, nettohintaElementti);
            }
        });
    });
    
    // Lis‰t‰‰n taulukon viimeisen rivin valintaruudulle toimintalogiikka.
    // Ruutua klikkaamalla joko valitaan kaikki tai poistetaan valinta
    // kaikista ruuduista.
    footerCheckbox.on("click", function (event) {
            if ($(this).attr("checked") === "checked") {
                 $.each(tuoterivitCheckboxes, function () {
                    $(this).prop("checked", true);
                });
            } else {
                 $.each(tuoterivitCheckboxes, function () {
                    $(this).prop("checked", false);
                });
            }
        });
    
    // Lis‰t‰‰n taulukon viimeisen rivin "laske kaikki" -painikkeelle
    // toimintalogiikka. Painiketta painaessa lasketaan uusi hinta ja
    // samat arvot m‰‰ritet‰‰n taulukon kaikille muille tuoteriveille.
     footerLaskeKaikki.on("click", function (event) {
        event.preventDefault();
        
        var myyntikate = tuoterivitTaulukko.find("tfoot tr").find(footerKateMyyntihintaSarake).val();
        var myymalakate = tuoterivitTaulukko.find("tfoot tr").find(footerKateMyymalahintaSarake).val();
        var nettokate = tuoterivitTaulukko.find("tfoot tr").find(footerKateNettohintaSarake).val();
        
        if (!onkoTyhja(myyntikate)) {
            if(!onkoVirheellinenMyyntikate(myyntikate)) {
                alert("Virheellinen kate. Myyntikatekentt‰ ei voi olla tyhj‰ ja katteen pit‰‰ olla 0-100 v‰lill‰.");
                return true;
            }    
        }
        
        if (!onkoTyhja(myymalakate)) {
            if(!onkoVirheellinenMyyntikate(myymalakate)) {
                alert("Virheellinen kate. Myymalaatekentt‰ ei voi olla tyhj‰ ja katteen pit‰‰ olla 0-100 v‰lill‰.");
                return true;
            }
        }

        if (!onkoTyhja(nettokate)) {
            if(!onkoVirheellinenMyyntikate(nettokate)) {
                alert("Virheellinen kate. Nettokatekentt‰ ei voi olla tyhj‰ ja katteen pit‰‰ olla 0-100 v‰lill‰.");
                return true;
            }
        }
        
        // K‰yd‰‰n jokainen rivi l‰pi ja asetetaan uusi hinta, jos hinta
        // ei ole virheellinen.
        $.each(tuoterivit, function () {
            var valintaElementti = $(this).find(tuoteriviCheckboxSarake);

            if (valintaElementti.attr("checked") === "checked") {
                var keskihankintahinta = $(this).data("kehahinta");

                if (!onkoTyhja(myyntikate)) {
                    var myyntihintaElementti = $(this).find(myyntihintaSarake);
                    var uusiMyyntihinta = lisaaHintaanKate(keskihankintahinta, myyntikate);
                    if(uusiMyyntihinta !== false && myyntikate > 0) {
                        asetaUusiHinta(uusiMyyntihinta, myyntihintaElementti);
                    }
                    $(this).find(kateMyyntihintaSarake).val(myyntikate);
                }
                
                if (!onkoTyhja(myymalakate)) {
                    var myymalahintaElementti = $(this).find(myymalahintaSarake);
                    var uusiMyymalahinta = lisaaHintaanKate(keskihankintahinta, myymalakate);
                    if(uusiMyymalahinta !== false && myymalakate > 0) {
                        asetaUusiHinta(uusiMyymalahinta, myymalahintaElementti);
                    }
                    $(this).find(kateMyymalahintaSarake).val(myymalakate);
                }
                
                if (!onkoTyhja(nettokate)) {
                    var nettohintaElementti = $(this).find(nettohintaSarake);
                    var uusiNettohinta = lisaaHintaanKate(keskihankintahinta, nettokate);
                    if(uusiNettohinta !== false && nettokate > 0) {
                        asetaUusiHinta(uusiNettohinta, nettohintaElementti);
                    }
                    $(this).find(kateNettohintaSarake).val(nettokate);
                }
            }
        });
    });
    
});
