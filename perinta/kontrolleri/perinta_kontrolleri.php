<?php

require_once "../sql/kantaoperaatiot.php";
require_once "../malli/lasku.php";
require_once "../malli/suoritus.php";
require_once "../malli/tilitys.php";

class PerintaKontrolleri {
    private $tunnnus;

    function __construct($id) {
        $this->tunnus = $id;
    }

    /**
     *
     * Palauttaa laskut taulukkona
     *
     * @param $tunnukset laskujen tunnukset
     * @return array laskut taulukossa
     *
     * @throws Exception jos perintä-taulua ei ole
     */
    public function annaSuodatetutLaskut($tunnukset = NULL) {
        if(isset($tunnukset)) {
            $laskutMyohassa = Lasku::haeLaskutTunnuksilla($this->tunnus, $tunnukset);
            Lasku::haeKarhukierrokset($laskutMyohassa);
            Lasku::haeVipit($laskutMyohassa);
        } else {
            $laskutMyohassa = Lasku::haeLaskut($this->tunnus);
            Lasku::haeKarhukierrokset($laskutMyohassa);
            Lasku::haeVipit($laskutMyohassa);
        }

        $taulukko = $this->suodata($laskutMyohassa);

        return $taulukko;
    }

    private static function suodata($laskut = NULL) {
        if(!isset($laskut)) {
            throw new Exception("laskut on null");
        }

        $taulukko = array();
        $asiakkaat = array();

        foreach($laskut as $lasku) {
            $rivi = $lasku->annaRivi();
            $ytunnus = $rivi['asiakas_ytunnus'];

            $asiakkaat[$ytunnus] =
                round($asiakkaat[$ytunnus]
                + $rivi['lasku_summa'] - $rivi['lasku_maksettu'], 2);

            array_push($taulukko, $rivi);
        }

        $kohdistamattomat = KantaOperaatiot::annaKohdistamattomat(array_keys($asiakkaat));

        foreach($taulukko as $avain => $rivi) {
            $ytunnus = $rivi['asiakas_ytunnus'];

            if((array_key_exists($ytunnus, $kohdistamattomat) && $kohdistamattomat[$ytunnus] != 0.0)
                || $asiakkaat[$ytunnus] <= 0.0) {

                unset($taulukko[$avain]);
            } else {
                $taulukko[$avain]['asiakas_kohdistamaton'] = 0.0;
            }
            //
/*
            if(($asiakkaat[$ytunnus] - $kohdistamattomat[$ytunnus]) < 0.0 ) {
                unset($taulukko[$avain]);
            } else {
                $taulukko[$avain]['asiakas_kohdistamaton'] = doubleval($kohdistamattomat[$ytunnus]);
            }*/
        }

        return $taulukko;
    }

    /**
     *
     * Palauttaa perintään vietävät laskut taulukkona
     *
     * @param -
     * @return array laskut taulukossa avaimen 'vanhat' alla laskut, jotka tulevat auki olevaan toimeksiantoon ja
     *               avaimen 'uudet' alla laskut, joille luodaan uusi toimeksianto
     *
     * @throws Exception jos perintä-taulua ei ole
     */
    public function annaToimeksiannot() {
        $laskutPerintaan = Lasku::haePerintaanVietavatLaskut($this->tunnus);
        Lasku::haeVipit($laskutPerintaan['uudet']);
        Lasku::haeVipit($laskutPerintaan['vanhat']);

        $rivit = array();
        $rivit['uudet'] = array();
        $rivit['vanhat'] = array();

        foreach($laskutPerintaan['uudet'] as $lasku) {
            $rivi = $lasku->annaRivi();
            array_push($rivit['uudet'], $rivi);
        }

        foreach($laskutPerintaan['vanhat'] as $lasku) {
            $rivi = $lasku->annaRivi();
            array_push($rivit['vanhat'], $rivi);
        }

        $laskut = array();
        $laskut['uudet'] = PerintaKontrolleri::jaaLaskutToimeksiantoihin($rivit['uudet']);
        $laskut['vanhat'] = PerintaKontrolleri::jaaLaskutToimeksiantoihin($rivit['vanhat']);

        return $laskut;
    }

    /**
     *
     * Vie perintään parametrina annetut laskut
     *
     * @param $tunnukset tunnukset taulukkona
     * @return array onnistuneesti vietyjen laskujen tunnukset
     *
     * @throws -
     */
    public function viePerintaanLaskut($tunnukset) {
        $taulukko = array();
        if(!isset($tunnukset) || !is_array($tunnukset)) {
            return $taulukko;
        }

        $laskutMyohassa = Lasku::haeLaskutTunnuksilla($this->tunnus, $tunnukset);

        foreach($tunnukset as $tunnus) {
            $lasku = $this->haeLasku(intval($tunnus), $laskutMyohassa);
            if(isset($lasku)) {
                try {
                    $lasku->viePerintaan();
                    $tmpTaulu = $lasku->annaRivi();
                    $suoritukset = Suoritus::haeSuoritukset($tmpTaulu['lasku_viite']);

                    foreach($suoritukset as $suoritus) {
                        $suoritusRivi = $suoritus->annaRivi();
                        KantaOperaatiot::luoPerintaSuoritus($suoritusRivi['tunnus'], $tmpTaulu['lasku_viite']);
                        $suoritus->kasitelty();
                    }

                    array_push($taulukko, $tmpTaulu['lasku_tunnus']);
                } catch (Exception $e) {
                    error_log($e->__toString());
                }
            }
        }

        $viedytLaskut = Lasku::haeLaskutTunnuksilla($this->tunnus, $taulukko);
        Lasku::haeKarhukierrokset($viedytLaskut);
        Lasku::haeVipit($viedytLaskut);

        $taulukko = array();
        foreach($viedytLaskut as $lasku) {
            array_push($taulukko, $lasku->annaRivi());
        }
        return $taulukko;
    }

    /**
     *
     * Peruuttaa perinnän parametrina annetuilta laskuilta.
     *
     * @param $tunnukset Laskujen tunnukset taulukkona
     * @return array Onnistuneesti peruutettujen laskujen tunnukset
     *
     * @throws -
     */
    public function peruutaLaskut($tunnukset) {
        $taulukko = array();
        if(!isset($tunnukset) || !is_array($tunnukset)) {
            return $taulukko;
        }

        $laskut = Lasku::haeLaskutTunnuksilla($this->tunnus, $tunnukset);

        foreach($tunnukset as $tunnus) {
            $lasku = $this->haeLasku(intval($tunnus), $laskut);
            if(isset($lasku)) {
                try {
                    $rivi = $lasku->annaRivi();
                    $lasku->peruuta();

                    array_push($taulukko, $rivi['lasku_tunnus']);
                } catch (Exception $e) {
                    error_log($e->__toString());
                }
            }
        }

        $viedytLaskut = Lasku::haeLaskutTunnuksilla($this->tunnus, $taulukko);
        Lasku::haeKarhukierrokset($viedytLaskut);
        Lasku::haeVipit($viedytLaskut);

        $taulukko = array();
        foreach($viedytLaskut as $lasku) {
            array_push($taulukko, $lasku->annaRivi());
        }
        return $taulukko;
    }

    /**
     *
     * Kuittaa laskut viedyksi perintään tila='perinnassa'
     *
     * @param $tunnukset Laskujen tunnukset
     * @return -
     *
     * @throws Exception Jos kuittaus epäonnistui
     */
    public function kuittaaViedyksi($tunnukset) {
        $laskut = Lasku::haeLaskutTunnuksilla($this->tunnus, $tunnukset);
        foreach($laskut as $lasku) {
            $lasku->vietyPerintaan($tunnus);
            $lasku->lisaaMuutoshistoria('luonti');
        }
    }

    /**
     *
     * Kuittaa laskut päivitetyksi
     *
     * @param $data Kuittausdata
     * @return -
     *
     * @throws Exception Jos kuittaus epäonnistui
     */
    public function kuittaaPaivitetyksi($data) {
        $laskuTunnukset = $data['laskut'];
        $suoritusTunnukset = $data['suoritukset'];
        $suoritukset = Suoritus::haeSuorituksetTunnuksilla($suoritusTunnukset);
        $suoritusViite = array();

        foreach($suoritukset as $suoritus) {
            $suoritusRivi = $suoritus->annaRivi();
            $suoritus->kasitelty();

            if(!isset($suoritusViite[$suoritusRivi['viite']])) {
                $suoritusViite[$suoritusRivi['viite']] = 0.0;
            }

            $suoritusViite[$suoritusRivi['viite']] =
                round($suoritusViite[$suoritusRivi['viite']]
                + $suoritusRivi['summa'], 2);
        }

        $laskut = Lasku::haeLaskutTunnuksilla($this->tunnus, $laskuTunnukset);
        foreach($laskut as $lasku) {
            $laskuRivi = $lasku->annaRivi();
            $summa = round($laskuRivi['perinta_summa'] - $suoritusViite[$laskuRivi['lasku_viite']], 2);
            $lasku->paivitaPerintaSumma($summa);
            $lasku->kuittaaPaivitys();
            $lasku->lisaaMuutoshistoria('muutos');
        }
    }

    /**
     *
     * Antaa päivitettävät laskut
     *
     * @param -
     * @return array päivitettävät laskut
     *
     * @throws Exception Jos tapahtui virhe
     */
    public function annaPaivitettavatLaskut() {
        $laskut = Lasku::haePerinnassaLaskut($this->tunnus);
        PerintaKontrolleri::merkitseMuuttuneet($laskut);

        $laskutPaivitettavat = Lasku::haePaivitettavatLaskut($this->tunnus);
        $rivit = array();
        foreach($laskutPaivitettavat as $lasku) {
            $laskuRivi = $lasku->annaRivi();

            $suoritukset = Suoritus::haeOhimaksuSuoritukset($laskuRivi['lasku_viite']);

            $laskuRivi['ohimaksut'] = array();

            foreach($suoritukset as $suoritus) {
                array_push($laskuRivi['ohimaksut'], $suoritus->annaRivi());
            }

            array_push($rivit, $laskuRivi);
        }

        $taulukko = PerintaKontrolleri::jaaLaskutToimeksiantoihin($rivit);

        return $taulukko;
    }

    private static function haeLasku($tunnus, $laskutMyohassa) {
        foreach ($laskutMyohassa as $lasku) {
            $rivi = $lasku->annaRivi();
            if($rivi['lasku_tunnus'] === $tunnus) {
                return $lasku;
            }
        }

        return null;
    }

    private static function jaaLaskutToimeksiantoihin($laskut) {
        $toimeksiannot = array();

        foreach($laskut as $rivi) {
            if(!isset($toimeksiannot[$rivi['perinta_toimeksiantotunnus']])) {
                $toimeksiannot[$rivi['perinta_toimeksiantotunnus']] = array();
                array_push($toimeksiannot[$rivi['perinta_toimeksiantotunnus']], $rivi);
            } else {
                array_push($toimeksiannot[$rivi['perinta_toimeksiantotunnus']], $rivi);
            }
        }
        return $toimeksiannot;
    }

    private static function merkitseMuuttuneet($laskut) {

        foreach($laskut as $lasku) {
            $laskuRivi = $lasku->annaRivi();
            //error_log(var_export($laskuRivi, TRUE));
            $erotus = round($laskuRivi['lasku_summa'] - $laskuRivi['lasku_maksettu'], 2);
            //error_log(var_export($laskuRivi['lasku_mapaiva'], true));
            if($laskuRivi['perinta_summa'] != $erotus
              || ($laskuRivi['lasku_mapaiva'] != '0000-00-00'
                  && $laskuRivi['perinta_tila'] != 'valmis'
                  && $laskuRivi['perinta_tila'] != 'peruttu')) {

                $suoritukset = Suoritus::haeKasittelemattomatSuoritukset($laskuRivi['lasku_viite']);

                $paivitettava = FALSE;
                foreach($suoritukset as $suoritus) {
                    $suoritusRivi = $suoritus->annaRivi();

                    KantaOperaatiot::luoPerintaSuoritus(
                        $suoritusRivi['tunnus'],
                        $laskuRivi['lasku_viite']
                    );

                    $tulos = FALSE;
                    $tulos = KantaOperaatiot::loytyykoTilitysrivi(
                        $laskuRivi['lasku_viite'],
                        $suoritusRivi['summa'],
                        $suoritusRivi['kirjaus_paiva'],
                        $suoritusRivi['maksu_paiva']
                    );

                    if($tulos) {
                        $lasku->paivitaPerintaMaksettu($suoritusRivi['summa']);
                        $suoritus->kasitelty();
                    } else {
                        $paivitettava = TRUE;
                    }
                }

                if($paivitettava) {
                    $lasku->paivitettava();
                }
            }
        }
    }

    /**
     *
     * Lisää tilitykset ohimaksuista erottelua varten
     *
     * @param $tilitykset Tilitykset json taulukkona
     * @return -
     *
     * @throws Exception Tilityksen lisäys epäonnistui
     */
    public function lisaaTilitykset($tilitykset) {
        $viitteet = array();
        $tmp = array();
        foreach ($tilitykset as $tilitys) {
            if (isset($tilitys['viite']) && trim($tilitys['viite']) !== '') {
                array_push($viitteet, $tilitys['viite']);
            }
        }

        $laskut = array();
        if (count($viitteet) > 0) {
            $laskut = Lasku::haeLaskutViitteilla($this->tunnus, $viitteet);
        }

        foreach ($laskut as $lasku) {
            $laskuRivi = $lasku->annaRivi();
            $laskuViite = $laskuRivi['lasku_viite'];

            foreach ($tilitykset as $tilitys) {
                if(isset($tilitys['viite']) && trim($tilitys['viite']) == $laskuViite) {
                    $tilitys = Tilitys::kirjaaTilitys($tilitys);
                }
            }
        }
    }

}
?>
