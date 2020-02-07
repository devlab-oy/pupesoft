<?php

class Tilitys {
    private $maksaja;
    private $viite;
    private $summa;
    private $kirjauspaiva;
    private $maksupaiva;

    private function __construct($rivi) {
        $this->maksaja = $rivi['maksaja'];
        $this->viite = $rivi['viite'];
        $this->asetaDouble("summa", $rivi['summa']);
        $this->kirjausPaiva = $rivi['kirjauspaiva'];
        $this->kirjausPaiva = $rivi['maksupaiva'];
    }

    protected function asetaDouble($muuttuja, $arvo) {
        if(property_exists($this, $muuttuja)) {
            $this->$muuttuja = doubleval($arvo);
        }
    }

    /**
     *
     * Palauttaa tilitysrivin
     *
     * @param -
     * @return array assosiatiivinen taulukko tilitysrivin sarakkeista
     *
     * @throws -
     */
    public function annaRivi() {
        $taulukko = array();
        $taulukko["maksaja"] = mb_convert_encoding($this->maksaja,"UTF-8");
        $taulukko["viite"] = $this->viite;
        $taulukko["summa"] = $this->summa;
        $taulukko["kirjauspaiva"] = $this->kirjauspaiva;
        $taulukko["maksupaiva"] = $this->maksupaiva;

        return $taulukko;
    }

    /**
     *
     * Palauttaa taulukon laskun viitteellä tietokannasta haettuja Tilitys-olioita
     *
     * @param $laskuViite laskun viite
     * @return array
     *
     * @throws Exception Jos perintätaulua ei ole
     */
    public static function haeTilitykset($laskuViite) {
        $tilitykset = array();

        $vastaus = KantaOperaatiot::annaTilitykset($laskuViite);
        while($rivi = mysql_fetch_assoc($vastaus)) {
            array_push($tilitykset, new Tilitys($rivi));
        }

        return $tilitykset;
    }

    /**
     *
     * Kirjaa tilityksen ja palauttaa tilitysolion
     *
     * @param $rivi tilityksen tiedot assosiatiivisena taulukkona
     * @return Tilitys olio
     *
     * @throws Exception Jos perintätaulua ei ole
     */
    public static function kirjaaTilitys($rivi) {
        $tilitys = new Tilitys($rivi);
        
        KantaOperaatiot::luoTilitysrivi($rivi['viite'], $rivi['summa'], $rivi['maksaja'], $rivi['kirjauspaiva'], $rivi['maksupaiva']);

        return $tilitys;
    }
}
