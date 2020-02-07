<?php

class Lasku {
    private $kayttajaId;

    private $tunnus;
    private $summa;
    private $maksettu;
    private $erapaiva;
    private $mapaiva;
    private $numero;
    private $viite;
    private $paiva;
    private $perintaSumma;
    private $perintaMaksettu;
    private $perintaLuonti;
    private $perintaSiirto;
    private $perintaTekija;
    private $perintaTekijaNimi;
    private $perintaPaivitys;
    private $perintaTila;
    private $perintaToimeksiantotunnus;
    private $perintaPaivitettava;
    private $perintaOhimaksu;

    private $korkolasku;
    private $asiakasTunnus;
    private $asiakasNimi;
    private $asiakasNimitark;
    private $asiakasOsoite;
    private $asiakasPostinumero;
    private $asiakasPostitoimipaikka;
    private $asiakasPuhelin;
    private $asiakasGsm;
    private $asikaasTyopuhelin;
    private $asiakasKieli;
    private $asiakasKansalaisuus;
    private $asiakasMaa;

    private $asiakasTilinumero;
    private $asiakasSelaus;
    private $asiakasYTunnus;
    private $asiakasManuaaliPerinta;

    private $karhukierros;
    private $muistutusPvm;
    private $muistutusPaivia;

    private function __construct($id, $rivi) {
        $this->kayttajaId = $id;

        $this->asetaInt("tunnus", $rivi['lasku_tunnus']);

        $this->asetaDouble("summa", $rivi['lasku_summa']);
        $this->asetaDouble("maksettu", $rivi['lasku_maksettu']);
        $this->asetaDouble("perintaSumma", $rivi['perinta_summa']);
        $this->asetaDouble("perintaMaksettu", $rivi['perinta_maksettu']);

        $this->asetaBool("korkolasku", $rivi['lasku_korkolasku']);

        $this->erapaiva = $rivi['lasku_erapaiva'];
        $this->mapaiva = $rivi['lasku_mapaiva'];
        $this->paiva = $rivi['lasku_paiva'];
        $this->numero = $rivi['lasku_numero'];
        $this->viite = $rivi['lasku_viite'];

        $this->asiakasNimi = $rivi['asiakas_nimi'];
        $this->asiakasNimitark = $rivi['asiakas_nimitark'];
        $this->asiakasYTunnus = $rivi['asiakas_ytunnus'];
        $this->asiakasOsoite = $rivi['asiakas_osoite'];
        $this->asetaInt("asiakasTunnus", $rivi['asiakas_tunnus']);
        $this->asiakasSelaus = $rivi['asiakas_selaus'];
        $this->asiakasPostitoimipaikka = $rivi['asiakas_toimipaikka'];
        $this->asiakasPuhelin = $rivi['asiakas_puhelin'];
        $this->asiakasGsm = $rivi['asiakas_gsm'];
        $this->asiakasTyopuhelin = $rivi['asiakas_tyopuhelin'];
        $this->asiakasTilinumero = $rivi['asiakas_tilinumero'];
        $this->asiakasPostinumero = $rivi['asiakas_postinumero'];
        $this->asiakasKieli = $rivi['asiakas_kieli'];
        $this->asiakasKansalaisuus = $rivi['asiakas_kansalaisuus'];
        $this->asiakasMaa = $rivi['asiakas_maa'];

        $this->perintaTekija = $rivi['perinta_tekija'];
        $this->perintaTekijaNimi = $rivi['perinta_tekija_nimi'];

        $this->perintaLuonti = $rivi['perinta_luonti'];
        $this->perintaSiirto = $rivi['perinta_siirto'];
        $this->perintaTila = $rivi['perinta_tila'];

        $this->asiakasManuaaliPerinta = FALSE;
        if($this->perintaTila == 'eiperinnassa' && $rivi['asiakas_luottoraja'] == 1.00) {
            $this->asiakasManuaaliPerinta = TRUE;
        }

        $this->perintaPaivitys = $rivi['perinta_paivitys'];
        $this->perintaToimeksiantotunnus = $rivi['perinta_toimeksiantotunnus'];
        $this->perintaPaivitettava = $rivi['perinta_paivitettava'];

        $this->karhukierros = NULL;
        $this->muistutusPvm = NULL;
        $this->muistutusPaivia = NULL;

        $this->asiakasVip = NULL;
    }

    protected function asetaInt($muuttuja, $arvo) {
        if(property_exists($this, $muuttuja)) {
            $this->$muuttuja = intval($arvo);
        }
    }

    protected function asetaDouble($muuttuja, $arvo) {
        if(property_exists($this, $muuttuja)) {
            $this->$muuttuja = doubleval($arvo);
        }
    }

    protected function asetaBool($muuttuja, $arvo) {
        if(property_exists($this, $muuttuja)) {
            if($arvo === '1') {
                $this->$muuttuja = TRUE;
            } else {
                $this->$muuttuja = FALSE;
            }
        }
    }

    /**
     *
     * Palauttaa laskun ominaisuuden
     *
     * @param -
     * @return object ominaisuuden arvo
     *
     * @throws Exception Jos ominaisuutta ei löydy
     */
    public function __get($nimi) {
        if (property_exists($this, $nimi)) {
            return $this->$nimi;
        } else {
            throw new Exception("Ominaisuutta ei löytynyt.");
        }
    }

    /**
     *
     * Palauttaa laskurivin
     *
     * @param -
     * @return array assosiatiivinen taulukko laskurivin sarakkeista
     *
     * @throws -
     */
    public function annaRivi() {
        $taulukko = array();
        $taulukko["perinta_tekija"] = mb_convert_encoding($this->tunnus,"UTF-8");
        $taulukko["lasku_tunnus"] = $this->tunnus;
        $taulukko["lasku_summa"] = $this->summa;
        $taulukko["lasku_maksettu"] = $this->maksettu;
        $taulukko["lasku_erapaiva"] = $this->erapaiva;
        $taulukko["lasku_mapaiva"] = $this->mapaiva;
        $taulukko["lasku_numero"] = $this->numero;
        $taulukko["lasku_viite"] = $this->viite;
        $taulukko["lasku_paiva"] = $this->paiva;
        $taulukko["perinta_summa"] = $this->perintaSumma;
        $taulukko["perinta_maksettu"] = $this->perintaMaksettu;
        $taulukko["perinta_luonti"] = $this->perintaLuonti;
        $taulukko["perinta_paivitys"] = $this->perintaPaivitys;
        $taulukko["perinta_siirto"] = $this->perintaSiirto;
        $taulukko["perinta_tila"] = $this->perintaTila;
        $taulukko["perinta_tekija"] = $this->perintaTekija;
        $taulukko["perinta_tekija_nimi"] = $this->perintaTekijaNimi;
        $taulukko["perinta_toimeksiantotunnus"] = $this->perintaToimeksiantotunnus;
        $taulukko["perinta_paivitettava"] = $this->perintaPaivitettava;
        $taulukko["asiakas_nimi"] = mb_convert_encoding($this->asiakasNimi, "UTF-8");
        $taulukko["asiakas_nimitark"] = mb_convert_encoding($this->asiakasNimitark, "UTF-8");
        $taulukko["asiakas_ytunnus"] = mb_convert_encoding($this->asiakasYTunnus, "UTF-8");
        $taulukko["asiakas_osoite"] = mb_convert_encoding($this->asiakasOsoite, "UTF-8");
        $taulukko["asiakas_kieli"] = mb_convert_encoding($this->asiakasKieli, "UTF-8");
        $taulukko["asiakas_kansalaisuus"] = mb_convert_encoding($this->asiakasKansalaisuus, "UTF-8");
        $taulukko["asiakas_maa"] = mb_convert_encoding($this->asiakasMaa, "UTF-8");
        $taulukko["asiakas_tunnus"] = $this->asiakasTunnus;
        $taulukko["asiakas_selaus"] = mb_convert_encoding($this->asiakasSelaus, "UTF-8");
        $taulukko["asiakas_toimipaikka"] = mb_convert_encoding($this->asiakasPostitoimipaikka, "UTF-8");
        $taulukko["asiakas_postinumero"] = $this->asiakasPostinumero;
        $taulukko["asiakas_tilinumero"] = $this->asiakasTilinumero;
        $taulukko["asiakas_puhelin"] = mb_convert_encoding($this->asiakasPuhelin, "UTF-8");
        $taulukko["asiakas_gsm"] = mb_convert_encoding($this->asiakasGsm, "UTF-8");
        $taulukko["asiakas_tyopuhelin"] = mb_convert_encoding($this->asiakasTyopuhelin, "UTF-8");
        $taulukko["asiakas_vip"] = $this->asiakasVip;
        $taulukko["asiakas_manuaaliperinta"] = $this->asiakasManuaaliPerinta;
        $taulukko["lasku_korkolasku"] = $this->korkolasku;
        $taulukko["lasku_karhukierros"] = $this->karhukierros;
        $taulukko["lasku_muistutus_pvm"] = $this->muistutusPvm;
        $taulukko["lasku_muistutus_paivia"] = $this->muistutusPaivia;

        return $taulukko;
    }

    /**
     *
     * Hakee laskuille karhukierroslukumäärät
     *
     * @param ref array $laskut laskut
     * @return -
     *
     * @throws Exception Jos tietokantaoperaatio epäonnistuu
     */
    public static function haeKarhukierrokset(&$laskut) {

        $ltunnukset = array();

        foreach($laskut as $lasku) {
            $rivi = $lasku->annaRivi();
            array_push($ltunnukset, $rivi["lasku_tunnus"]);
        }

        $karhukierrokset = KantaOperaatiot::annaKarhukierrokset($ltunnukset);

        foreach($laskut as $lasku) {
            $rivi = $lasku->annaRivi();
            $tunnus = $rivi["lasku_tunnus"];

            if($karhukierrokset[$tunnus] == NULL) {
                $karhukierrokset[$tunnus]["kierros"] = 0;
                $karhukierrokset[$tunnus]["pvm"] = NULL;
            }

            $lasku->asetaInt("karhukierros", $karhukierrokset[$rivi["lasku_tunnus"]]["kierros"]);
            $lasku->muistutusPvm = $karhukierrokset[$tunnus]["pvm"];

            if(isset($lasku->muistutusPvm)) {
                $paivaysMuistutus = new DateTime($lasku->muistutusPvm);
                $paivaysTanaan = new DateTime('NOW');
                $ero = $paivaysMuistutus->diff($paivaysTanaan);
                $lasku->muistutusPaivia = intval($ero->format('%R%a'));
            }
        }
    }

    /**
     *
     * Hakee laskujen asiakkaiden vip-statukset
     *
     * @param ref array $laskut laskut
     * @return -
     *
     * @throws Exception Jos tietokantaoperaatio epäonnistuu
     */
    public static function haeVipit(&$laskut) {

        $atunnukset = array();

        foreach($laskut as $lasku) {
            $rivi = $lasku->annaRivi();

            $atunnukset[$rivi["asiakas_tunnus"]] = TRUE;
        }

        $vipit = array();
        if(count($atunnukset) > 0) {
            $vipit = KantaOperaatiot::annaVipit($atunnukset);
        }

        foreach($laskut as $lasku) {
            $rivi = $lasku->annaRivi();
            $tunnus = $rivi["asiakas_tunnus"];

            if(array_key_exists($tunnus, $vipit)) {
                $lasku->asetaBool("asiakasVip", '1');
            } else {
                $lasku->asetaBool("asiakasVip", '0');
            }
        }
    }

    /**
     *
     * Vie laskun perintään
     *
     * @param -
     * @return -
     *
     * @throws Exception Jos lasku on jo perinnässä tai rivin luonti epäonnistuu
     */
    public function viePerintaan() {
        KantaOperaatiot::viePerintaan($this->tunnus,
                                      $this->asiakasTunnus,
                                      round($this->summa-$this->maksettu, 2),
                                      $this->summa,
                                      $this->maksettu,
                                      $this->kayttajaId);
    }

    /**
     *
     * Peruuttaa laskun perinnän
     *
     * @param -
     * @return -
     *
     * @throws Exception Jos lasku on jo perinnässä tai peruutus epäonnistuu
     */
    public function peruuta() {
        if ($this->perintaTila == 'luotu') {
            KantaOperaatiot::poistaPerintarivi($this->tunnus);
            KantaOperaatiot::poistaPerintaSuoritus($this->viite);
        } else if ($this->perintaTila == 'perinnassa') {
            KantaOperaatiot::asetaPerintaPeruttu($this->tunnus);
        } else {
            throw new Exception('Perintä on väärässä tilassa. Peruutus ei onnistu.');
        }

    }

    /**
     *
     * Lisää muutoshistoriatiedon laskusta
     *
     * @param $tyyppi muutoksen tyyppi
     * @return -
     *
     * @throws Exception Jos muutoshistoriamerkinnän luonti epäonnistuu
     */
    public function lisaaMuutoshistoria($muutosTyyppi) {
        $summa = $this->perintaSumma;
        KantaOperaatiot::lisaaMuutoshistoria($this->tunnus,
                                             $muutosTyyppi,
                                             $summa);
    }

    /**
     *
     * Päivittää perittävän summan laskun summa ja maksettu kenttien perusteella
     * Jos toimeksiannon summa laskee nollaan, merkitään se valmiiksi.
     *
     * @param -
     * @return -
     *
     * @throws Exception Jos tietokantaoperaatio epäonnistuu
     */
    public function paivitaPerintaSumma($summa) {
        $this->perintaSumma = $summa;
        KantaOperaatiot::asetaPerintaSumma($this->tunnus,
                                           $summa);

       if($summa <= 0.0) {
           KantaOperaatiot::asetaPerintaValmis($this->tunnus);
       }
    }

    /**
     *
     * Päivitetään perittävän summan laskun summa ja maksettu kenttien perusteella.
     * Jos toimeksiannon summa tulee maksetuksi, merkitään se valmiiksi.
     *
     * @param -
     * @return -
     *
     * @throws Exception Jos tietokantaoperaatio epäonnistuu
     */
    public function paivitaPerintaMaksettu($summa) {
        $uusiSumma = round($this->perintaMaksettu + $summa, 2);
        $this->perintaMaksettu = $uusiSumma;
        KantaOperaatiot::asetaPerintaMaksettu($this->tunnus,
                                           $uusiSumma);
        if($uusiSumma == $this->summa) {
            KantaOperaatiot::asetaPerintaValmis($this->tunnus);
        }
    }

    /**
     *
     * Päivitetään perittävän summan laskun summa ja maksettu kenttien perusteella
     *
     * @param -
     * @return -
     *
     * @throws Exception Jos tietokantaoperaatio epäonnistuu
     */
    public function kuittaaPaivitys() {
        //KantaOperaatiot::asetaOhimaksu(0.0);
        KantaOperaatiot::kuittaaPaivitys($this->tunnus);
    }

    /**
     *
     * Kuittaa laskun viedyksi perintään
     *
     * @param -
     * @return -
     *
     * @throws Exception Jos kuittaus epäonnistui
     */
    public function vietyPerintaan() {
        KantaOperaatiot::kuittaaPerinta($this->tunnus);
    }

    /**
     *
     * Palauttaa taulukon tietokannasta päivitettävät Lasku-olioit
     *
     * @param $id käyttäjän id
     * @return array
     *
     * @throws Exception Jos perintätaulua ei ole
     */
     public static function haePaivitettavatLaskut($id) {
        $laskut = array();

        $vastaus = KantaOperaatiot::annaPaivitettavatLaskut();
        while($rivi = mysql_fetch_assoc($vastaus)) {
            array_push($laskut, new Lasku($id, $rivi));
        }

        return $laskut;
     }

     /**
      *
      * Palauttaa taulukon tietokannassa perinnässä olevista Lasku-olioista
      *
      * @param $id käyttäjän id
      * @return array
      *
      * @throws Exception Jos perintätaulua ei ole
      */
      public static function haePerinnassaLaskut($id) {
         $laskut = array();

         $vastaus = KantaOperaatiot::annaPerinnassaLaskut();
         while($rivi = mysql_fetch_assoc($vastaus)) {
             array_push($laskut, new Lasku($id, $rivi));
         }

         return $laskut;
      }

    /**
     *
     * Palauttaa taulukon tietokannasta haettuja Lasku-olioita
     *
     * @param $id käyttäjän id
     * @return array
     *
     * @throws Exception Jos perintätaulua ei ole
     */
    public static function haeLaskut($id) {
        $laskut = array();

        $vastaus = KantaOperaatiot::annaLaskut();
        while($rivi = mysql_fetch_assoc($vastaus)) {
            array_push($laskut, new Lasku($id, $rivi));
        }

        return $laskut;
    }

    /**
     *
     * Palauttaa taulukon tietokannasta haettuja Lasku-olioita
     *
     * @param $id käyttäjän id
     * @return array
     *
     * @throws Exception Jos perintätaulua ei ole
     */
    public static function haeLaskutTunnuksilla($id, $laskujenTunnukset) {
        $laskut = array();

        $vastaus = KantaOperaatiot::annaLaskutTunnuksilla($laskujenTunnukset);

        while($rivi = mysql_fetch_assoc($vastaus)) {
            array_push($laskut, new Lasku($id, $rivi));
        }

        return $laskut;
    }

    /**
     *
     * Palauttaa taulukon tietokannasta haettuja Lasku-olioita
     *
     * @param $id käyttäjän id
     * @return array
     *
     * @throws Exception Jos perintätaulua ei ole
     */
    public static function haeLaskutViitteilla($id, $laskuViitteet) {
        $laskut = array();

        $vastaus = KantaOperaatiot::annaLaskutViitteilla($laskuViitteet);

        while($rivi = mysql_fetch_assoc($vastaus)) {
            array_push($laskut, new Lasku($id, $rivi));
        }

        return $laskut;
    }

    /**
     *
     * Palauttaa taulukon perintään vietävistä uusista
     *
     * @param $id käyttäjän id
     * @return array
     *
     * @throws Exception Jos perintätaulua ei ole
     */
    public static function haePerintaanVietavatLaskut($id) {
        $taulu = array();
        $uudet = array();
        $vanhat = array();

        $vastaus = KantaOperaatiot::haePerintaanVietavatLaskut();

        while($rivi = mysql_fetch_assoc($vastaus)) {
            $lasku = new Lasku($id, $rivi);
            $laskunRivi = $lasku->annaRivi();

            if(KantaOperaatiot::perintaKaynnissa($laskunRivi['perinta_toimeksiantotunnus'])) {
                array_push($vanhat, $lasku);
            } else {
                array_push($uudet, $lasku);
            }
        }

        $taulu['uudet'] = $uudet;
        $taulu['vanhat'] = $vanhat;

        return $taulu;
    }

    /**
     *
     * Muodostaa laskuista JSON-taulukon
     *
     * @param $laskut laskut taulukkona
     * @return string JSON laskutaulukko
     *
     */
    public static function muodostaJSONLaskuTaulukko($laskut) {
        $taulukko = array();

        foreach($laskut as $lasku) {
            array_push($taulukko, $lasku->annaRivi());
        }

        return json_encode($taulukko);
    }

    /**
     *
     * Merkitsee laskun päivitettäväksi
     *
     * @param $lasku Lasku-olio
     * @return -
     *
     * @throws Exception Jos perintätaulua ei ole tai laskulla ei ole riviä
     */
    public function paivitettava() {
        KantaOperaatiot::asetaPaivitettavaksi($this->tunnus);
    }
}

?>
