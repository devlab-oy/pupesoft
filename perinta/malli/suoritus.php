<?php

class Suoritus {
    private $maksaja;
    private $viite;
    private $summa;
    private $maksuPaiva;
    private $kirjausPaiva;
    private $laskuTunnus;
    private $tunnus;
    private $kasitelty;

    private function __construct($rivi) {
        $this->maksaja = $rivi['maksaja'];
        $this->viite = $rivi['viite'];
        $this->asetaDouble("summa", $rivi['summa']);
        $this->maksuPaiva = $rivi['maksu_paiva'];
        $this->kirjausPaiva = $rivi['kirjaus_paiva'];
        $this->laskuTunnus = $rivi['lasku_tunnus'];
        $this->tunnus = $rivi['tunnus'];
        $this->kasitelty = KantaOperaatiot::onkoSuoritusKasitelty($rivi['tunnus']);
    }

    protected function asetaDouble($muuttuja, $arvo) {
        if(property_exists($this, $muuttuja)) {
            $this->$muuttuja = doubleval($arvo);
        }
    }

    /**
     *
     * Palauttaa suoritusrivin
     *
     * @param -
     * @return array assosiatiivinen taulukko suoritusrivin sarakkeista
     *
     * @throws -
     */
    public function annaRivi() {
        $taulukko = array();
        $taulukko["maksaja"] = mb_convert_encoding($this->maksaja,"UTF-8");
        $taulukko["viite"] = $this->viite;
        $taulukko["summa"] = $this->summa;
        $taulukko["maksu_paiva"] = $this->maksuPaiva;
        $taulukko["kirjaus_paiva"] = $this->kirjausPaiva;
        $taulukko["lasku_tunnus"] = $this->laskuTunnus;
        $taulukko["tunnus"] = $this->tunnus;
        $taulukko["kasitelty"] = $this->kasitelty;

        return $taulukko;
    }

    /**
     *
     * Palauttaa taulukon laskun viitteell� tietokannasta haettuja Suoritus-olioita
     *
     * @param $laskuViite laskun viite
     * @return array
     *
     * @throws Exception Jos perint�taulua ei ole
     */
    public static function haeSuoritukset($laskuViite) {
        $suoritukset = array();

        $vastaus = KantaOperaatiot::annaSuoritukset($laskuViite);
        while($rivi = mysql_fetch_assoc($vastaus)) {
            array_push($suoritukset, new Suoritus($rivi));
        }

        return $suoritukset;
    }

    /**
     *
     * Palauttaa taulukon tietokannasta suoritustunnuksella haettuja Suoritus-olioita
     *
     * @param $suoritusTunnukset suoritusten tunnukset
     * @return array
     *
     * @throws Exception Jos perint�taulua ei ole
     */
    public static function haeSuorituksetTunnuksilla($suoritusTunnukset) {
        $suoritukset = array();

        $vastaus = KantaOperaatiot::annaSuorituksetTunnuksilla($suoritusTunnukset);
        while($rivi = mysql_fetch_assoc($vastaus)) {
            array_push($suoritukset, new Suoritus($rivi));
        }

        return $suoritukset;
    }

    /**
     *
     * Palauttaa taulukon tietokannasta haettuja Suoritus-olioita, joita ei ole
     * viel� yhdistetty mihink��n perinn�ss� olevaan laskuun
     *
     * @param $laskuViite laskun viite
     * @return array
     *
     * @throws Exception Jos perint�taulua ei ole
     */
    public static function haeKasittelemattomatSuoritukset($laskuViite) {
        $suoritukset = array();

        $vastaus = KantaOperaatiot::annaKasittelemattomatSuoritukset($laskuViite);
        while($rivi = mysql_fetch_assoc($vastaus)) {
            array_push($suoritukset, new Suoritus($rivi));
        }

        return $suoritukset;
    }

    /**
     *
     * Palauttaa taulukon tietokannasta haettuja Suoritus-olioita, joita ei viel�
     * ole k�sitelty. K�yt�nn�ss� viem�t�n ohimaksu.
     *
     * @param $laskuViite laskun viite
     * @return array
     *
     * @throws Exception Jos perint�taulua ei ole
     */
    public static function haeOhimaksuSuoritukset($laskuViite) {
        $suoritukset = array();

        $vastaus = KantaOperaatiot::annaOhimaksut($laskuViite);
        while($rivi = mysql_fetch_assoc($vastaus)) {
            array_push($suoritukset, new Suoritus($rivi));
        }

        return $suoritukset;
    }

    /**
     *
     * Asettaa suorituksen k�sitellyksi
     *
     * @param -
     * @return -
     *
     * @throws Exception jos asettaminen ep�onnistuu
     */
    public function kasitelty() {
        KantaOperaatiot::asetaKasitelty($this->viite);
    }
}
