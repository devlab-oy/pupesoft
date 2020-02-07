<?php

/* ISO 8859-15 */

$link = mysql_connect($dbhost, $dbuser, $dbpass);
mysql_select_db($dbkanta);

function perinta_query($query) {
    $resp = mysql_query($query);
    if($resp === false) {
        throw new Exception(mysql_error());
    }
    return $resp;
}

class KantaOperaatiot {

    const SQL_KENTAT =
                    "las.tunnus 'lasku_tunnus', las.summa 'lasku_summa',
                     las.saldo_maksettu 'lasku_maksettu',
                     las.erpcm 'lasku_erapaiva', las.mapvm 'lasku_mapaiva',
                     IF(las.viesti = 'Korkolasku', 1, 0) lasku_korkolasku,
                     las.laskunro 'lasku_numero', las.viite 'lasku_viite',
                     las.tapvm 'lasku_paiva',
                     asiakas.nimi 'asiakas_nimi', asiakas.nimi 'asiakas_selaus',
                     asiakas.nimitark 'asiakas_nimitark',
                     asiakas.ryhma 'asiakas_ryhma', asiakas.tunnus 'asiakas_tunnus',
                     asiakas.osoite 'asiakas_osoite',
                     asiakas.postino 'asiakas_postinumero',
                     asiakas.kieli 'asiakas_kieli',
                     asiakas.kansalaisuus 'asiakas_kansalaisuus',
                     asiakas.maa 'asiakas_maa',
                     asiakas.postitp 'asiakas_toimipaikka',
                     asiakas.puhelin 'asiakas_puhelin',
                     asiakas.gsm 'asiakas_gsm',
                     asiakas.tyopuhelin 'asiakas_tyopuhelin',
                     asiakas.tilino 'asiakas_tilinumero',
                     asiakas.ytunnus 'asiakas_ytunnus',
                     asiakas.luottoraja 'asiakas_luottoraja',
                     COALESCE(perinta.tila, DEFAULT(perinta.tila)) 'perinta_tila',
                     perinta.tekija 'perinta_tekija',
                     perinta.luonti 'perinta_luonti',
                     perinta.siirto 'perinta_siirto',
                     perinta.paivitys 'perinta_paivitys', perinta.paivitys,
                     perinta.maksettu 'perinta_maksettu',
                     perinta.summa 'perinta_summa',
                     perinta.toimeksianto_tunnus 'perinta_toimeksiantotunnus',
                     perinta.vaatii_paivityksen 'perinta_paivitettava',
                     kuka.nimi 'perinta_tekija_nimi'";

    /**
     *
     * Palauttaa laskun viitteell� siihen kohdistetut suoritukset
     *
     * @param $laskuViite laskun viite
     * @return resource
     *
     * @throws -
     */
    public static function annaSuoritukset($laskuViite) {
        KantaOperaatiot::tarkistaTaulut();

        $query = "SELECT
            suoritus.nimi_maksaja 'maksaja',
            lasku.viite 'viite',
            suoritus.summa 'summa',
            suoritus.maksupvm 'maksu_paiva',
            suoritus.kirjpvm 'kirjaus_paiva',
            suoritus.ltunnus 'lasku_tunnus',
            suoritus.tunnus 'tunnus'
        FROM lasku
        JOIN suorituksen_kohdistus ON (
              lasku.yhtio = suorituksen_kohdistus.yhtio AND
              lasku.tunnus = suorituksen_kohdistus.laskutunnus
            )
            JOIN suoritus ON (
              suorituksen_kohdistus.yhtio = suoritus.yhtio AND
              suorituksen_kohdistus.suoritustunnus = suoritus.tunnus
            )
            WHERE
              lasku.yhtio = 'artr' AND
              lasku.tila = 'U' AND
              lasku.alatila = 'X' AND
              suoritus.kohdpvm != '0000-00-00' AND
              suoritus.summa != 0 AND
              lasku.viite = '" . $laskuViite . "'";

        return perinta_query($query);
    }

    /**
     *
     * Palauttaa tunnuksilla suoritukset
     *
     * @param $suoritusTunnukset suoritusten tunnukset
     * @return resource
     *
     * @throws -
     */
    public static function annaSuorituksetTunnuksilla($suoritusTunnukset) {
        $suoritusTunnukset = array_unique($suoritusTunnukset);

        KantaOperaatiot::tarkistaTaulut();

        $query = "SELECT
            suoritus.nimi_maksaja 'maksaja',
            lasku.viite 'viite',
            suoritus.summa 'summa',
            suoritus.maksupvm 'maksu_paiva',
            suoritus.kirjpvm 'kirjaus_paiva',
            suoritus.ltunnus 'lasku_tunnus',
            suoritus.tunnus 'tunnus'
        FROM lasku
        JOIN suorituksen_kohdistus ON (
              lasku.yhtio = suorituksen_kohdistus.yhtio AND
              lasku.tunnus = suorituksen_kohdistus.laskutunnus
            )
            JOIN suoritus ON (
              suorituksen_kohdistus.yhtio = suoritus.yhtio AND
              suorituksen_kohdistus.suoritustunnus = suoritus.tunnus
            )
            WHERE
              lasku.yhtio = 'artr' AND
              lasku.tila = 'U' AND
              lasku.alatila = 'X' AND
              suoritus.kohdpvm != '0000-00-00' AND
              suoritus.summa != 0 AND (";

        foreach($suoritusTunnukset as $tunnus) {
            $query = $query."suoritus.tunnus=".$tunnus;
            if(end($suoritusTunnukset) !== $tunnus)
            {
                $query = $query." OR ";
            } else {
                $query = $query." ) ";
            }
        }

        return perinta_query($query);
    }

    /**
     *
     * Palauttaa laskun viitteell� k�sittelem�tt�m�t ohimaksut
     *
     * @param $laskuViite laskun viite
     * @return array
     *
     * @throws -
     */
    public static function annaOhimaksut($laskuViite) {
        KantaOperaatiot::tarkistaTaulut();

        $taulu = array();

        $query = "SELECT
            suoritus.nimi_maksaja 'maksaja',
            lasku.viite 'viite',
            suoritus.summa 'summa',
            suoritus.maksupvm 'maksu_paiva',
            suoritus.kirjpvm 'kirjaus_paiva',
            suoritus.ltunnus 'lasku_tunnus',
            suoritus.tunnus 'tunnus'
        FROM lasku
        JOIN suorituksen_kohdistus ON (
              lasku.yhtio = suorituksen_kohdistus.yhtio AND
              lasku.tunnus = suorituksen_kohdistus.laskutunnus
            )
        JOIN suoritus ON (
              suorituksen_kohdistus.yhtio = suoritus.yhtio AND
              suorituksen_kohdistus.suoritustunnus = suoritus.tunnus
        )
        INNER JOIN perinta_suoritus ON (
              perinta_suoritus.suoritus_tunnus = suoritus.tunnus
        )
        WHERE
            lasku.yhtio = 'artr' AND
            lasku.tila = 'U' AND
            lasku.alatila = 'X' AND
            suoritus.summa != 0 AND
            suoritus.kohdpvm != '0000-00-00' AND
            lasku.viite = '" . $laskuViite . "' AND
            perinta_suoritus.kasitelty = 0";

        return perinta_query($query);
    }

    /**
     *
     * Palauttaa laskun viitteell� siihen kohdistetut perinn�n k�sittelem�tt�m�t suoritukset
     *
     * @param $laskuViite laskun tunnus
     * @return resource
     *
     * @throws -
     */
    public static function annaKasittelemattomatSuoritukset($laskuViite) {
        KantaOperaatiot::tarkistaTaulut();

        $query = "SELECT
            suoritus.nimi_maksaja 'maksaja',
            lasku.viite 'viite',
            suoritus.summa 'summa',
            suoritus.maksupvm 'maksu_paiva',
            suoritus.kirjpvm 'kirjaus_paiva',
            suoritus.ltunnus 'lasku_tunnus',
            suoritus.tunnus 'tunnus'
        FROM lasku
        JOIN suorituksen_kohdistus ON (
              lasku.yhtio = suorituksen_kohdistus.yhtio AND
              lasku.tunnus = suorituksen_kohdistus.laskutunnus
            )
        JOIN suoritus ON (
              suorituksen_kohdistus.yhtio = suoritus.yhtio AND
              suorituksen_kohdistus.suoritustunnus = suoritus.tunnus
        )
        LEFT JOIN perinta_suoritus ON (
              perinta_suoritus.suoritus_tunnus = suoritus.tunnus
        )
        WHERE
            lasku.yhtio = 'artr' AND
            lasku.tila = 'U' AND
            lasku.alatila = 'X' AND
            suoritus.summa != 0 AND
            suoritus.kohdpvm != '0000-00-00' AND
            lasku.viite = '" . $laskuViite . "' AND
            perinta_suoritus.suoritus_tunnus IS NULL";

        return perinta_query($query);
    }

    /**
     *
     * Palauttaa tiedon onko suoritus jo k�sitelty
     *
     * @param $suoritusTunnus suorituksen tunnus
     * @return boolean
     *
     * @throws -
     */
    public static function onkoSuoritusKasitelty($suoritusTunnus) {
        KantaOperaatiot::tarkistaTaulut();

        $query = "SELECT perinta_suoritus.suoritus_tunnus FROM perinta_suoritus
                    WHERE perinta_suoritus.suoritus_tunnus=".$suoritusTunnus." AND kasitelty=1";

        $vastaus = perinta_query($query);
        if(mysql_num_rows($vastaus) > 0) {
            return true;
        }

        return false;
    }

    /**
     *
     * Luo suorituksen
     *
     * @param $suoritusTunnus suorituksen tunnus
     *        $laskuViite laskun tunnus
     * @return -
     *
     * @throws Exception jos rivin luonti ep�onnistuu
     */
    public static function luoPerintaSuoritus($suoritusTunnus, $laskuViite) {
        KantaOperaatiot::tarkistaTaulut();

        KantaOperaatiot::aloitaAtominen();

        $query = "INSERT INTO perinta_suoritus
                    (lasku_viite, suoritus_tunnus)
                        VALUES (".
                        $laskuViite.",
                        ".$suoritusTunnus.")";

        $vastaus = perinta_query($query);

        KantaOperaatiot::lopetaAtominen();

        if(!$vastaus) {
            throw new Exception('Perinn�n suoritustaulurivin luonti ep�onnistui.');
        }
    }

    /**
     *
     * Poistaa perint�suorituksen
     *
     * @param $laskuViite laskun viitenumero
     *
     * @return -
     *
     * @throws Exception jos poisto ep�onnistuu
     */
    public static function poistaPerintaSuoritus($laskuViite) {
        KantaOperaatiot::tarkistaTaulut();

        KantaOperaatiot::aloitaAtominen();

        $query = "DELETE FROM perinta_suoritus
                    WHERE lasku_viite=" . $laskuViite;

        $vastaus = perinta_query($query);

        KantaOperaatiot::lopetaAtominen();

        if(!$vastaus) {
            throw new Exception('Perinn�n suoritustaulurivien poisto ep�onnistui.');
        }
    }

    /**
     *
     * Asettaa suorituksen k�sitellyksi
     *
     * @param $laskuViite laskun viite
     * @return -
     *
     * @throws Exception jos rivin luonti ep�onnistuu
     */
    public static function asetaKasitelty($laskuViite) {
        KantaOperaatiot::tarkistaTaulut();

        $query = "UPDATE perinta_suoritus
            SET kasitelty=1
            WHERE perinta_suoritus.lasku_viite=".$laskuViite;

        $vastaus = perinta_query($query);

        if(!$vastaus) {
            throw new Exception('Suorituksen asettaminen k�sitellyksi ep�onnistui.');
        }
    }

    /**
     *
     * Palauttaa yritystunnuksella kohdistamattoman suoritusm��r�n
     *
     * @param $ytunnus yritystunnus
     * @return double summa
     *
     * @throws -
     */
    public static function annaKohdistamattomat($ytunnukset) {
        $ytunnukset = array_unique($ytunnukset);

        KantaOperaatiot::tarkistaTaulut();
        $taulu = array();

        $query = "SELECT
            asiakas.ytunnus 'ytunnus',
            sum(suoritus.summa) 'kohdistamaton'
        FROM suoritus
        JOIN asiakas ON asiakas.tunnus=suoritus.asiakas_tunnus
        WHERE suoritus.yhtio = 'artr'
        AND suoritus.kohdpvm = '0000-00-00'
        AND suoritus.ltunnus > 0
        AND suoritus.summa != 0
        AND (";

        foreach($ytunnukset as $tunnus) {
            $query = $query."asiakas.ytunnus='".$tunnus."'";
            if(end($ytunnukset) !== $tunnus)
            {
                $query = $query." OR ";
            }
            else
            {
                $query = $query.") GROUP BY asiakas.ytunnus";
            }
        }

        $vastaus = perinta_query($query);

        while($rivi = mysql_fetch_assoc($vastaus)) {
            $taulu[$rivi['ytunnus']] = doubleval($rivi['kohdistamaton']);
        }

        return $taulu;
    }

    /**
     *
     * Tarkistaa l�ytyyk� tilitysrivi tietokannasta
     *
     * @param $viite tilityksen viitenumero
     *        $summa tilityksen summa
     *        $maksaja tilityksen maksajan nimen alkuosa
     *        $kirjauspaiva tilityksen kirjausp�iv�
     *        $maksupaiva tilityksen maksup�iv�
     * @return bool
     *
     * @throws Exception jos tarkastaminen ep�onnistuu
     */
    public static function loytyykoTilitysrivi($viite, $summa, $kirjauspaiva, $maksupaiva) {
        KantaOperaatiot::tarkistaTaulut();

        $query = "SELECT lasku_viite, summa, maksaja, kirjauspvm, maksupvm
                    FROM perinta_tilitys
                    WHERE lasku_viite='".$viite."'
                        AND summa=".$summa."
                        AND kirjauspvm='".$kirjauspaiva."'
                        AND maksupvm='".$maksupaiva."'";

        $vastaus = perinta_query($query);

        if (!$vastaus) {
            error_log('Perinn�n tilitystaulurivin rivin tarkistus ep�onnistui.');
            throw new Exception('Perinn�n tilitystaulurivin rivin tarkistus ep�onnistui.');
        }

        if (mysql_num_rows($vastaus) > 0) {
            return TRUE;
        }

        return FALSE;
    }

    /**
     *
     * Luo tilitysrivin
     *
     * @param $viite tilityksen viitenumero
     *        $summa tilityksen summa
     *        $maksaja tilityksen maksajan nimen alkuosa
     *        $kirjauspaiva tilityksen kirjausp�iv�
     *        $maksupaiva tilityksen maksup�iv�
     * @return -
     *
     * @throws Exception jos rivin luonti ep�onnistuu
     */
    public static function luoTilitysrivi($viite, $summa, $maksaja, $kirjauspaiva, $maksupaiva) {
        KantaOperaatiot::tarkistaTaulut();

        KantaOperaatiot::aloitaAtominen();

        if (!KantaOperaatiot::loytyykoTilitysrivi($viite, $summa, $kirjauspaiva, $maksupaiva)) {

            $query = "INSERT INTO perinta_tilitys
                        (lasku_viite, summa, maksaja, kirjauspvm, maksupvm)
                            VALUES ('".
                            $viite."',
                            ".$summa.",
                            '".$maksaja."',
                            '".$kirjauspaiva."',
                            '".$maksupaiva."')";

            $vastaus = perinta_query($query);

            if (!$vastaus) {
                error_log('Perinn�n tilitystaulurivin luonti ep�onnistui.');
                throw new Exception('Perinn�n tilitystaulurivin '.$viite.', '
                                        .$summa.', '
                                        .$maksaja.', '
                                        .$kirjauspaiva.', '
                                        .$maksupaiva.' luonti ep�onnistui.');
            }
        } else {
            error_log('Tilitysrivi '.$viite.', '.$summa.', '.$maksaja.', '
                .$kirjauspaiva.', '.$maksupaiva.' l�ytyi jo taulusta.');
        }

        KantaOperaatiot::lopetaAtominen();
    }

    /**
     *
     * Palauttaa laskun viitteell� siihen kohdistetut tilitykset
     *
     * @param $laskuViite laskun viite
     * @return resource
     *
     * @throws -
     */
    public static function annaTilitykset($laskuViite) {
        KantaOperaatiot::tarkistaTaulut();

        $query = "SELECT
            perinta_tilitys.maksaja 'maksaja',
            perinta_tilitys.viite 'viite',
            perinta_tilitys.summa 'summa',
            perinta_tilitys.kirjauspvm 'kirjauspaiva',
            perinta_tilitys.maksupvm 'maksupaiva'
        FROM suoritus
        WHERE perinta_tilitys.viite = " . $laskuViite;

        return perinta_query($query);
    }

    /**
     *
     * Palauttaa asiakkaan tunnuksilla vip statukset
     *
     * @param $atunnukset asiakkaiden tunnukset
     * @return $taulu vip-asiakkaiden tunnukset
     *
     * @throws -
     */
    public static function annaVipit($atunnukset) {
        $atunnukset = array_unique($atunnukset);

        KantaOperaatiot::tarkistaTaulut();
        $taulu = array();

        $query = "SELECT liitostunnus 'atunnus'
            FROM asiakkaan_avainsanat
            WHERE yhtio='artr' AND laji='autoasi_vip' AND ( ";

        foreach($atunnukset as $tunnus => $arvo) {
            $query = $query."liitostunnus='".$tunnus."'";
            if(end(array_keys($atunnukset)) !== $tunnus)
            {
                $query = $query." OR ";
            }
            else
            {
                $query = $query." )";
            }
        }

        $vastaus = perinta_query($query);


        while($rivi = mysql_fetch_assoc($vastaus)) {
            $taulu[$rivi['atunnus']] = NULL;
        }

        return $taulu;
    }

    /**
     *
     * Palauttaa laskujen tunnuksilla laskujen karhukierrokset
     *
     * @param $ltunnukset laskujen tunnukset
     * @return $taulu
     *
     * @throws -
     */
    public static function annaKarhukierrokset($ltunnukset) {
        $ltunnukset = array_unique($ltunnukset);
        KantaOperaatiot::tarkistaTaulut();
        $taulu = array();

        $query = "SELECT ltunnus 'ltunnus', max(pvm) 'pvm', count(*) 'kierros'
            FROM karhu_lasku
            JOIN karhukierros ON (karhukierros.tunnus = karhu_lasku.ktunnus)
            WHERE ";

        foreach($ltunnukset as $tunnus) {
            $query = $query."karhu_lasku.ltunnus='".$tunnus."'";
            if(end($ltunnukset) !== $tunnus)
            {
                $query = $query." OR ";
            }
            else
            {
                $query = $query." GROUP BY ltunnus";
            }
        }

        $vastaus = perinta_query($query);

        while($rivi = mysql_fetch_assoc($vastaus)) {
            $taulu[$rivi['ltunnus']] = array();
            $taulu[$rivi['ltunnus']]['pvm'] = $rivi['pvm'];
            $taulu[$rivi['ltunnus']]['kierros'] = intval($rivi['kierros']);
        }

        return $taulu;
    }


    /**
     *
     * Vie laskun perint��n
     *
     * @param $laskuTunnus Laskun tunnus
     *        $ytunnus Yritystunnus
     *        $summa Summa
     *        $id K�ytt�j�n id
     * @return -
     *
     * @throws Exception Jos lasku on jo perinn�ss� tai rivin luonti ep�onnistuu
     */
    public static function viePerintaan($laskuTunnus, $asiakasTunnus, $summa, $laskuSumma, $laskuMaksettu, $id) {
        KantaOperaatiot::tarkistaTaulut();
        if(KantaOperaatiot::perintaRiviOlemassa($laskuTunnus)) {
            throw new Exception("Laskun numerolla l�ytyi jo rivi kannasta.");
            return;
        }

        KantaOperaatiot::aloitaAtominen();

        $query = "INSERT INTO perinta
                    (lasku_tunnus, toimeksianto_tunnus, summa, tekija, tila, vaatii_paivityksen)
                        VALUES (".
                        $laskuTunnus.",
                        Perinta_ToimeksiantoTunnus('".$asiakasTunnus."'),
                        ".$summa.",".
                        $id.",
                        'luotu', 0)";

        $vastaus = perinta_query($query);

        KantaOperaatiot::lopetaAtominen();

        if(!$vastaus) {
            throw new Exception('Perint�rivin luonti ep�onnistui.');
        }
    }

    /**
     *
     * Poistaa perint�rivin taulusta
     *
     * @param $laskuTunnus Laskun tunnus
     *
     * @return -
     *
     * @throws Exception Jos lasku on jo perinn�ss� tai rivin luonti ep�onnistuu
     */
    public static function poistaPerintarivi($laskuTunnus) {
        KantaOperaatiot::tarkistaTaulut();

        KantaOperaatiot::aloitaAtominen();

        $query = "DELETE FROM perinta
                    WHERE lasku_tunnus=" . $laskuTunnus;

        $vastaus = perinta_query($query);

        KantaOperaatiot::lopetaAtominen();

        if(!$vastaus) {
            throw new Exception('Perint�rivin poisto ep�onnistui.');
        }
    }

    /**
     *
     * Asettaa laskun perinn�n tilan valmiiksi
     *
     * @param $laskuTunnus Laskun tunnus
     * @return -
     *
     * @throws Exception Jos kuittaus ep�onnistui
     */
    public static function asetaPerintaPeruttu($laskuTunnus) {
        KantaOperaatiot::tarkistaTaulut();
        $query = "UPDATE perinta
                    SET perinta.tila='peruttu'
                    WHERE perinta.lasku_tunnus=".$laskuTunnus;


        $vastaus = perinta_query($query);

        if(!$vastaus) {
            throw new Exception('Perint�rivin merkkaus perutuksi ep�onnistui.');
        }
    }

    /**
     *
     * Lis�� muutoshistoriatiedon tietokantaan
     *
     * @param $laskuTunnus Laskun tunnus
     *        $muutosTyyppi Muutoksen tyyppi
     *        $summa Summa muutoksen j�lkeen
     * @return -
     *
     * @throws Exception Jos muutoshistoriamerkinn�n luonti ep�onnistuu
     */
    public static function lisaaMuutoshistoria($laskuTunnus, $muutosTyyppi, $summa) {
        KantaOperaatiot::tarkistaTaulut();

        $query = "INSERT INTO perinta_muutoshistoria
                    (lasku_tunnus, tyyppi, summa) VALUES (".
                        $laskuTunnus.",
                        '".$muutosTyyppi."',
                        ".$summa.")";

        $vastaus = perinta_query($query);

        if(!$vastaus) {
            throw new Exception('Muutoshistoriataulurivin luonti ep�onnistui.');
        }
    }

    /**
     *
     * Asettaa peritt�v�n summan
     *
     * @param $summa Peritt�v� summa
     * @return -
     *
     * @throws Exception Jos tietokantaoperaatio ep�onnistuu
     */
    public static function asetaPerintaSumma($laskuTunnus, $summa) {
        KantaOperaatiot::tarkistaTaulut();

        $query = "UPDATE perinta
            SET summa=".$summa."
            WHERE lasku_tunnus=".$laskuTunnus;

        $vastaus = perinta_query($query);

        if(!$vastaus) {
            throw new Exception('Perint�summan p�ivitys ep�onnistui.');
        }
    }

    /**
     *
     * Asettaa perityn summan
     *
     * @param $summa Peritty summa
     * @return -
     *
     * @throws Exception Jos tietokantaoperaatio ep�onnistuu
     */
    public static function asetaPerintaMaksettu($laskuTunnus, $summa) {
        KantaOperaatiot::tarkistaTaulut();

        $query = "UPDATE perinta
            SET maksettu=".$summa."
            WHERE lasku_tunnus=".$laskuTunnus;

        $vastaus = perinta_query($query);

        if(!$vastaus) {
            throw new Exception('Perint�summan p�ivitys ep�onnistui.');
        }
    }

    /**
     *
     * Asettaa vaatii_paivityksen nollaksi ja asettaa p�ivitysajan
     *
     * @param $laskuTunnus Laskun tunnus
     * @return -
     *
     * @throws Exception Jos tietokantaoperaatio ep�onnistuu
     */
    public static function kuittaaPaivitys($laskuTunnus) {
        KantaOperaatiot::tarkistaTaulut();
        $query = "UPDATE perinta
                    SET vaatii_paivityksen=0, paivitys=NOW()
                    WHERE lasku_tunnus=".$laskuTunnus;

        $vastaus = perinta_query($query);

        if(!$vastaus) {
            throw new Exception('P�ivitysmerkinn�n poisto ep�onnistui.');
        }
    }

    /**
     *
     * Asettaa laskun viedyksi perint��n
     *
     * @param $laskuTunnus Laskun tunnus
     * @return -
     *
     * @throws Exception Jos kuittaus ep�onnistui
     */
    public static function kuittaaPerinta($laskuTunnus) {
        KantaOperaatiot::tarkistaTaulut();
        $query = "UPDATE perinta
                    SET perinta.tila='perinnassa', perinta.siirto=NOW()
                    WHERE perinta.lasku_tunnus=".$laskuTunnus;


        $vastaus = perinta_query($query);

        if(!$vastaus) {
            throw new Exception('Kuittauksen lis�ys ep�onnistui.');
        }
    }

    /**
     *
     * Asettaa laskun perinn�n tilan valmiiksi
     *
     * @param $laskuTunnus Laskun tunnus
     * @return -
     *
     * @throws Exception Jos kuittaus ep�onnistui
     */
    public static function asetaPerintaValmis($laskuTunnus) {
        KantaOperaatiot::tarkistaTaulut();
        $query = "UPDATE perinta
                    SET perinta.tila='valmis'
                    WHERE perinta.lasku_tunnus=".$laskuTunnus;


        $vastaus = perinta_query($query);

        if(!$vastaus) {
            throw new Exception('Kuittauksen lis�ys ep�onnistui.');
        }
    }

    /**
     *
     * Palauttaa taulukon tietokannasta p�ivitett�v�t perint�taulun rivit
     *
     * @param -
     * @return array
     *
     * @throws Exception Jos perint�taulua ei ole
     */
    public static function annaPaivitettavatLaskut() {
         $rivit = array();

         KantaOperaatiot::tarkistaTaulut();

        $query = "SELECT ".self::SQL_KENTAT."
                    FROM (
                            SELECT lasku.* FROM lasku
                            WHERE tila='U'
                                AND mapvm='0000-00-00'
                                AND yhtio='artr'
                            UNION
                            SELECT lasku.* FROM perinta
                              INNER JOIN lasku ON lasku.tunnus=perinta.lasku_tunnus
                    ) las
                    JOIN asiakas ON asiakas.tunnus=las.liitostunnus
                    LEFT JOIN perinta ON perinta.lasku_tunnus=las.tunnus
                    LEFT JOIN kuka ON kuka.tunnus=perinta.tekija
                    WHERE perinta.tila='perinnassa'
                        AND perinta.vaatii_paivityksen=1";

        return perinta_query($query);
    }

    /**
     *
     * Palauttaa perint�taulun rivit
     *
     * @param -
     * @return array
     *
     * @throws Exception Jos perint�taulua ei ole
     */
    public static function annaPerinnassaLaskut() {
         $rivit = array();

         KantaOperaatiot::tarkistaTaulut();

        $query = "SELECT ".self::SQL_KENTAT."
                    FROM (
                            SELECT lasku.* FROM lasku
                            WHERE tila='U'
                                AND mapvm='0000-00-00'
                                AND yhtio='artr'
                            UNION
                            SELECT lasku.* FROM perinta
                              INNER JOIN lasku ON lasku.tunnus=perinta.lasku_tunnus
                    ) las
                    JOIN asiakas ON asiakas.tunnus=las.liitostunnus
                    LEFT JOIN perinta ON perinta.lasku_tunnus=las.tunnus
                    LEFT JOIN kuka ON kuka.tunnus=perinta.tekija
                    WHERE perinta.tila='perinnassa'";

        return perinta_query($query);
    }

    /**
      *
      * Palauttaa taulukon tietokannasta my�h�ss� olevista tai perint�taulussa
      * olevista laskuriveist�
      *
      * @param -
      * @return resource
      *
      * @throws Exception Jos perint�taulua ei ole
      */
    public static function annaLaskut() {
        KantaOperaatiot::tarkistaTaulut();

        $query = "SELECT ".self::SQL_KENTAT."
                    FROM (
                            SELECT lasku.* FROM lasku
                            WHERE tila='U'
                                AND mapvm='0000-00-00'
                                AND yhtio='artr'
                            UNION
                            SELECT lasku.* FROM perinta
                              INNER JOIN lasku ON lasku.tunnus=perinta.lasku_tunnus
                    ) las
                    JOIN asiakas ON asiakas.tunnus=las.liitostunnus
                    LEFT JOIN perinta ON perinta.lasku_tunnus=las.tunnus
                    LEFT JOIN kuka ON kuka.tunnus=perinta.tekija
                    WHERE (las.erpcm < (now() - interval 5 day)
                        AND las.summa != 0
                        AND asiakas.ryhma != 'TOIMITTAJAPALAUTUS'
                        AND asiakas.osasto != 'Henkil�kunta')
                        OR perinta.tila != 'eiperinnassa'";

        return perinta_query($query);
    }

    /**
     *
     * Palauttaa taulukon tietokannasta haettuja Lasku-olioita
     *
     * @param $laskuTunnukset Laskujen tunnukset
     * @return resource
     *
     * @throws Exception Jos perint�taulua ei ole tai yht��n laskun tunnusta ei ole annettu
     */
    public static function annaLaskutTunnuksilla($laskuTunnukset) {

        if(!isset($laskuTunnukset) || empty($laskuTunnukset)) {
            throw new Exception("Virheellinen parametri $laskuTunnukset");
        }

        $laskuTunnukset = array_unique($laskuTunnukset);

        $rivit = array();

        KantaOperaatiot::tarkistaTaulut();

        $query = "SELECT ".self::SQL_KENTAT."
                    FROM (
                            SELECT lasku.* FROM lasku
                            WHERE tila='U'
                                AND mapvm='0000-00-00'
                                AND yhtio='artr'
                            UNION
                            SELECT lasku.* FROM perinta
                              INNER JOIN lasku ON lasku.tunnus=perinta.lasku_tunnus
                    ) las
                    JOIN asiakas ON asiakas.tunnus=las.liitostunnus
                    LEFT JOIN perinta ON perinta.lasku_tunnus=las.tunnus
                    LEFT JOIN kuka ON kuka.tunnus=perinta.tekija
                    WHERE ((las.erpcm < (now() - interval 5 day)
                        AND las.summa != 0
                        AND asiakas.ryhma != 'TOIMITTAJAPALAUTUS'
                        AND asiakas.osasto != 'Henkil�kunta')
                        OR perinta.tila != 'eiperinnassa') AND ";

        foreach($laskuTunnukset as $tunnus) {
            $query = $query."las.tunnus=".$tunnus;
            if(end($laskuTunnukset) !== $tunnus)
            {
                $query = $query." OR ";
            }
        }

        return perinta_query($query);
    }

    /**
     *
     * Palauttaa taulukon tietokannasta haettuja Lasku-olioita
     *
     * @param $laskuTunnukset Laskujen viitteet
     * @return resource
     *
     * @throws Exception Jos perint�taulua ei ole tai yht��n laskun tunnusta ei ole annettu
     */
    public static function annaLaskutViitteilla($laskuViitteet) {
        $laskuViitteet = array_unique($laskuViitteet);

        if(!isset($laskuViitteet) || empty($laskuViitteet)) {
            throw new Exception("Virheellinen parametri $laskuViitteet");
        }

        $rivit = array();

        KantaOperaatiot::tarkistaTaulut();

        $query = "SELECT ".self::SQL_KENTAT."
                    FROM (
                            SELECT lasku.* FROM lasku
                            WHERE tila='U'
                                AND mapvm='0000-00-00'
                                AND yhtio='artr'
                            UNION
                            SELECT lasku.* FROM perinta
                              INNER JOIN lasku ON lasku.tunnus=perinta.lasku_tunnus
                    ) las
                    JOIN asiakas ON asiakas.tunnus=las.liitostunnus
                    LEFT JOIN perinta ON perinta.lasku_tunnus=las.tunnus
                    LEFT JOIN kuka ON kuka.tunnus=perinta.tekija
                    WHERE ((las.erpcm < (now() - interval 5 day)
                        AND las.summa != 0
                        AND asiakas.ryhma != 'TOIMITTAJAPALAUTUS'
                        AND asiakas.osasto != 'Henkil�kunta')
                        OR perinta.tila != 'eiperinnassa') AND ";

        foreach($laskuViitteet as $viite) {
            $query = $query."las.viite=".$viite;
            if(end($laskuViitteet) !== $viite)
            {
                $query = $query." OR ";
            }
        }

        return perinta_query($query);
    }

    /**
     *
     * Palauttaa perint��n viet�v�t laskurivit
     *
     * @param -
     * @return resource
     *
     * @throws Exception Jos perint�taulua ei ole
     */
    public static function haePerintaanVietavatLaskut() {
        KantaOperaatiot::tarkistaTaulut();


        $query = "SELECT ".self::SQL_KENTAT."
                    FROM (
                            SELECT lasku.* FROM lasku
                            WHERE tila='U'
                                AND mapvm='0000-00-00'
                                AND yhtio='artr'
                            UNION
                            SELECT lasku.* FROM perinta
                              INNER JOIN lasku ON lasku.tunnus=perinta.lasku_tunnus
                    ) las
                    JOIN asiakas ON asiakas.tunnus=las.liitostunnus
                    LEFT JOIN perinta ON perinta.lasku_tunnus=las.tunnus
                    LEFT JOIN kuka ON kuka.tunnus=perinta.tekija
            WHERE perinta.tila = 'luotu'
                AND perinta.siirto = '0000-00-00 00:00:00'";

        return perinta_query($query);
    }

    /**
    *
    * Onko toimeksiantotunnuksella perint� k�ynniss�
    *
    * @param -
    * @return boolean
    *
    * @throws -
    */
    public static function perintaKaynnissa($toimeksiantotunnus) {
        $query = "SELECT perinta.lasku_tunnus FROM perinta
                    WHERE perinta.toimeksianto_tunnus=".$toimeksiantotunnus."
                    AND perinta.tila = 'perinnassa'";

        $vastaus = perinta_query($query);
        if(mysql_num_rows($vastaus) > 0) {
            return true;
        }

        return false;
    }

    /**
     *
     * Asettaa perint�taulun laskurivin p�ivitett�v�ksi
     *
     * @param $laskuTunnus
     * @return -
     *
     * @throws Exception Jos perint�taulua ei ole tai laskulla ei ole rivi�
     */
    public function asetaPaivitettavaksi($laskuTunnus) {

        KantaOperaatiot::tarkistaTaulut();

        if(!KantaOperaatiot::perintaRiviOlemassa($laskuTunnus)) {
            throw new Exception("Laskulla ei ole rivi�.");
            return;
        }

        $query = "UPDATE perinta
                    SET vaatii_paivityksen=1
                    WHERE lasku_tunnus=".$laskuTunnus;


        $vastaus = perinta_query($query);
        if(!$vastaus) {
            throw new Exception('Perint�rivin luonti ep�onnistui.');
        }
    }

    private static function perintaRiviOlemassa($laskuTunnus) {
        $query = "SELECT lasku_tunnus FROM perinta WHERE lasku_tunnus=".$laskuTunnus;
        $vastaus = perinta_query($query);
        if(mysql_num_rows($vastaus) > 0) {
            return true;
        }

        return false;
    }

    private static function aloitaAtominen() {
        perinta_query("START TRANSACTION");
    }

    private static function lopetaAtominen() {
        perinta_query("COMMIT");
    }

    private static function tarkistaTaulut() {
        $query = "SHOW TABLES LIKE 'perinta'";
        $vastaus = perinta_query($query);

        if(mysql_num_rows($vastaus) < 1) {
            throw new Exception("Talua perinta ei l�ytynyt.");
        }

        $query = "SHOW TABLES LIKE 'perinta_muutoshistoria'";
        $vastaus = perinta_query($query);

        if(mysql_num_rows($vastaus) < 1) {
            throw new Exception("Taulua perinta_muutoshistoria ei l�ytynyt.");
        }

        $query = "SHOW TABLES LIKE 'perinta_suoritus'";
        $vastaus = perinta_query($query);

        if(mysql_num_rows($vastaus) < 1) {
            throw new Exception("Taulua perinta_suoritus ei l�ytynyt.");
        }

        $query = "SHOW TABLES LIKE 'perinta_tilitys'";
        $vastaus = perinta_query($query);

        if(mysql_num_rows($vastaus) < 1) {
            throw new Exception("Taulua perinta_tilitys ei l�ytynyt.");
        }

        return true;
    }
}
