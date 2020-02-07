<?php

/*
require "../../inc/salasanat.php";

$link = mysql_connect($dbhost, $dbuser, $dbpass);
mysql_select_db($dbkanta);

function perinta_query($query) {
    $resp = mysql_query($query);
    if($resp === false) {
        echo mysql_error();
    }
    return $resp;
}
*/
require_once "../kontrolleri/perinta_kontrolleri.php";

class PerintaIntegraatioApi {
    private $kukarow;
    private $yhtiorow;

    private $kontrolleri;

    private $virhe = "";
    private $vastaus = "";

    private $tunnus = 0;

    const TOKEN = "jocVufNuAl6";
    const UUDET = "uudet";
    const MUUTTUNEET = "muuttuneet";
    const TILITYKSET = "tilitykset";
    const KUITTAUS_VIENTI = "kuittaus_vienti";
    const KUITTAUS_PAIVITYS = "kuittaus_paivitys";

    /**
     *
     * Käsittelee post ajax -pyynnön
     *
     * @param $data post data
     * @return boolean tapahtuiko virhe
     *
     * @throws -
     */
    public function suoritaPost($get, $data) {
        if($get['tyyppi'] == self::KUITTAUS_VIENTI) {
            try {
                $this->kontrolleri = new PerintaKontrolleri($this->tunnus);
                $this->kontrolleri->kuittaaViedyksi($data['laskut']);
            } catch (Exception $e) {
                $this->virhe = json_encode(array("status" => $e->__toString()));
                error_log($this->virhe);
                return false;
            }
        } else if($get['tyyppi'] == self::KUITTAUS_PAIVITYS) {
            try {
                $this->kontrolleri = new PerintaKontrolleri($this->tunnus);
                $this->kontrolleri->kuittaaPaivitetyksi($data);
            } catch (Exception $e) {
                $this->virhe = json_encode(array("status" => $e->__toString()));
                error_log($this->virhe);
                return false;
            }
        } else if($get['tyyppi'] == self::TILITYKSET) {
            try {
                $this->kontrolleri = new PerintaKontrolleri($this->tunnus);
                $this->kontrolleri->lisaaTilitykset($data[self::TILITYKSET]);
            } catch (Exception $e) {
                $this->virhe = json_encode(array("status" => $e->__toString()));
                error_log($this->virhe);
                return false;
            }
        } else {
            $this->virhe = json_encode(array("status" => "Komentoa ei ole toteutettu."));
            return false;
        }

        return true;
    }

    /**
     *
     * Käsittelee get ajax -pyynnön
     *
     * @param $data get data
     * @return boolean tapahtuiko virhe
     *
     * @throws -
     */
    public function suoritaGet($data) {
        if($data['tyyppi'] == self::UUDET) {
            $this->kontrolleri = new PerintaKontrolleri($this->tunnus);
            try {
                $toimeksiannot = $this->kontrolleri->annaToimeksiannot();
                $data = array();
                $data['data'] = $toimeksiannot;
                $this->vastaus = json_encode($data);
            } catch (Exception $e) {
                $this->virhe = json_encode(array("status" => $e->__toString()));
                error_log($this->virhe);
                return false;
            }
        } else if($data['tyyppi'] == self::MUUTTUNEET) {
            $this->kontrolleri = new PerintaKontrolleri($this->tunnus);
            try {
                $paivitettavat = $this->kontrolleri->annaPaivitettavatLaskut();
                $data = array();
                $data['data'] = $paivitettavat;
                $this->vastaus = json_encode($data);
            } catch (Exception $e) {
                $this->virhe = json_encode(array("status" => $e->__toString()));
                error_log($this->virhe);
                return false;
            }
            return true;
        } else {
            $this->virhe = json_encode(array("status" => "Virheellinen komento"));
            error_log($this->virhe);
            return false;
        }

        return true;
    }

    private function muodostaJSONLaskutVastaus($laskut) {
        $data = array();
        $tmp = array();

        foreach($laskut as $lasku) {
            $lasku['lasku_erapaiva'] = $this->muotoileErapaiva($lasku['lasku_erapaiva']);
            $lasku['perinta_siirto'] = $this->muotoileSiirtoPvm($lasku['perinta_siirto']);
            $lasku['lasku_muistutus_pvm'] = $this->muotoileErapaiva($lasku['lasku_muistutus_pvm']);
            array_push($tmp, $lasku);
        }
        $data['data'] = $tmp;
        $this->vastaus = json_encode($data);
    }

    private static function muotoileErapaiva($pvm) {
        if(isset($pvm) && $pvm != '') {
            $pvm = date_create($pvm);
            return date_format($pvm, "d.m.Y");
        } else {
            return "";
        }
    }

    private static function muotoileSiirtoPvm($pvm) {
        if(isset($pvm) && $pvm != '' && $pvm != '0000-00-00 00:00:00') {
            $pvm = DateTime::createFromFormat('Y-m-d H:i:s' ,$pvm);
            return date_format($pvm, "d.m.Y");
        } else {
            return "";
        }
    }

    /**
     *
     * Tulostaa vastauksen
     *
     * @param -
     * @return -
     *
     * @throws -
     */
    public function tulostaVastaus() {
        echo $this->vastaus;
    }

    /**
     *
     * Tulostaa virheen
     *
     * @param -
     * @return -
     *
     * @throws -
     */
    public function tulostaVirhe() {
        echo $this->virhe;
    }

    /**
     *
     * Tarkistaa tokenin oikeellisuuden
     *
     * @param -
     * @return boolean Oliko token oikein
     *
     * @throws -
     */
    public function tarkistaToken($data) {
        if(empty($data['token']) || $data['token'] != self::TOKEN) {
            $this->virhe = json_encode(array("status" => "Virheellinen token."));
            error_log($this->virhe);
            return false;
        }

        return true;
    }
}
?>
