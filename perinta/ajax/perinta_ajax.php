<?php

/*
require "../../inc/salasanat.php";

$link = mysql_connect($dbhost, $dbuser, $dbpass);
mysql_select_db($dbkanta);

function pupe_query($query) {
    $resp = mysql_query($query);
    if($resp === false) {
        echo mysql_error();
    }
    return $resp;
}
*/
require_once "../kontrolleri/perinta_kontrolleri.php";

class PerintaAjax {
    private $kukarow;
    private $yhtiorow;

    private $kontrolleri;

    private $virhe = "";
    private $vastaus = "";

    private $tunnus = 0;

    const PERUUTUS_TYYPPI = "P";
    const VIENTI_TYYPPI = "V";

    /**
     *
     * K�sittelee post ajax -pyynn�n
     *
     * @param $data post data
     * @return boolean tapahtuiko virhe
     *
     * @throws -
     */
    public function suoritaPost($id, $data) {
        $this->tunnus = $id;
        $aikaleima = isset($data["aikaleima"]) ? intval($data["aikaleima"]) : null;

        if (isset($this->tunnus))
        {

            $this->kontrolleri = new PerintaKontrolleri($this->tunnus);

            try {
                if ($data['tyyppi'] == self::VIENTI_TYYPPI) {
                    $laskut = $this->kontrolleri->viePerintaanLaskut($data['laskut']);
                } else if ($data['tyyppi'] == self::PERUUTUS_TYYPPI) {
                    $laskut = $this->kontrolleri->peruutaLaskut($data['laskut']);
                }
            } catch (Exception $e) {
                $this->virhe = json_encode(array("status" => $e->__toString()));
                error_log($this->virhe);
                return false;
            }

            if (empty($laskut)) {
                $this->virhe = json_encode(array("status" => "Yhtään laskua ei viety perintään."));
                error_log($this->virhe);
                return false;
            }

            $this->muodostaJSONLaskutVastaus($laskut);
        } else {
            $this->virhe = json_encode(array("status" => "Virheellinen tunnus."));
            error_log($this->virhe);
            return false;
        }

        return true;
    }

    /**
     *
     * K�sittelee get ajax -pyynn�n
     *
     * @param $data get data
     * @return boolean tapahtuiko virhe
     *
     * @throws -
     */
    public function suoritaGet($id, $data) {
        $this->tunnus = $id;
        if (isset($this->tunnus))
        {
            $this->kontrolleri = new PerintaKontrolleri($this->tunnus);

            try {
                if (isset($_GET['tunnukset'])) {
                    $laskut = $this->kontrolleri->annaSuodatetutLaskut(explode(',', trim($_GET['tunnukset'])));
                } else {
                    $laskut = $this->kontrolleri->annaSuodatetutLaskut();
                }
            } catch (Exception $e) {
                $this->virhe = json_encode(array("status" => $e->__toString()));
                error_log($this->virhe);
                return false;
            }
            $this->muodostaJSONLaskutVastaus($laskut);
        }else{
            $this->virhe = json_encode(array("status" => "Virheellinen tunnus."));
            error_log($this->virhe);
            return false;
        }

        return true;
    }

    private function muodostaJSONLaskutVastaus($laskut) {
        $data = array();
        $tmp = array();

        foreach ($laskut as $lasku) {
            $lasku['lasku_erapaiva'] = $this->muotoileErapaiva($lasku['lasku_erapaiva']);
            $lasku['lasku_muistutus_pvm'] = $this->muotoileErapaiva($lasku['lasku_muistutus_pvm']);
            $lasku['perinta_siirto'] = $this->muotoileSiirtoPvm($lasku['perinta_siirto']);
            array_push($tmp, $lasku);
        }
        $data['data'] = $tmp;
        $this->vastaus = json_encode($data);
    }

    private static function muotoileErapaiva($pvm) {
        if (isset($pvm) && $pvm != '') {
            $pvm = date_create($pvm);
            return date_format($pvm, "d.m.Y");
        } else {
            return "";
        }
    }

    private static function muotoileSiirtoPvm($pvm) {
        if (isset($pvm) && $pvm != '' && $pvm != '0000-00-00 00:00:00') {
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
     * Tarkistaa oikeudet
     *
     * @param $phpnimi php-tiedoston nimi
     * @return boolean oliko oikeuksia
     *
     * @throws -
     */
    public function tarkistaOikeudet($phpnimi) {
        $oikeurow = tarkista_oikeus($phpnimi, "", '', 'X');
        if ($oikeurow === FALSE) {
            $this->virhe = json_encode(array("status" => "Käyttäjällä ei ole oikeuksia."));
            return false;
        }

        return true;
    }

    /**
     *
     * Hakee pupesoftin kannasta kukarow:n
     *
     * @param $sessio käyttäjän sessio-data
     * @return array kukarow
     *
     * @throws -
     */
    public function haeKukarow($sessio) {
        if (!isset($sessio) || $sessio == '') {
            $this->virhe = json_encode(array("status" => "Ei sessiota."));
            return false;
        }

        $query = "SELECT *
                  FROM kuka
                  WHERE session='" . mysql_real_escape_string($sessio) . "'";
        $result = perinta_query($query);

        return mysql_fetch_assoc($result);
    }
}
?>
