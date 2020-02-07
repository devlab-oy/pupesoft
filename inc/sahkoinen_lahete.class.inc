<?php

class SahkoinenLahete {

  private $_info;
  private static $_state = false;
  private $_required;

  private $_rows = array();

  public function __construct(array $args) {

    $this->_required = array('ytunnus', 'asiakasnro', 'lahetenro_toim');

    if (count(array_diff_key($this->_required, array_keys($args))) == 0 and array_search('', $args, true) === false) {
      $this->_info = $args;
      self::$_state = true;
    }
  }


  public function setRow(array $args) {

    $this->_required = array(
      'tuoteno',
      'toimittajanro',
      'nimitys',
      'myyntihinta',
      'alennus',
      'kpl',
      'rivisumma',
      'alv',
      'reknro',
    );

    self::$_state = false;

    $_args_orig = $args;
    unset($args['reknro']); // rekisterinumero voi olla tyhjää

    if (count(array_diff($this->_required, array_keys($_args_orig))) == 0 and array_search('', $args, true) === false) {
      array_push($this->_rows, $_args_orig);
      self::$_state = true;
    }
  }


  public static function getState() {
    return self::$_state;
  }


  public function getRowCount() {
    return count($this->_rows);
  }


  public function generateXML() {

    $xml = simplexml_load_string("<?xml version='1.0' encoding='UTF-8'?><Tapahtuma></Tapahtuma>");

    $perustiedot = $xml->addChild('Perustiedot');
    $perustiedot->addChild('Y-tunnus', $this->_info['ytunnus']);         // Myyjän y-tunnus
    $perustiedot->addChild('Asiakasnro', $this->_info['asiakasnro']);       // Korjaamon asiakasnumero
    $perustiedot->addChild('Ostotilausnro', 0);                 // Ei pakollinen
    $perustiedot->addChild('Lahetenro_toim', $this->_info['lahetenro_toim']);   // Toimittajan lähetenumero
    $perustiedot->addChild('HintojenEsitystapa', 0);               // 0 = verolliset hinnat

    $tuoterivit = $xml->addChild('Tuoterivit');

    foreach ($this->_rows as $row) {

      $tuote = $tuoterivit->addChild('Tuote');
      $tuote->addChild('Tuotekoodi', $row['tuoteno']);               // Tuotekoodi
      $tuote->addChild('Toimittajanro', $row['toimittajanro']);           // Toimittajanumero
      $tuote->addChild('Tyyppikoodi');                       // Tyyppikoodi, Ei pakollinen
      $tuote->addChild('Nimi', $row['nimitys']);                   // Tuotenimi
      $tuote->addChild('Myyntihinta', str_replace(".", ",", $row['myyntihinta'])); // Myyntihinta, sis. ALV
      $tuote->addChild('Alennus', str_replace(".", ",", $row['alennus']));     // Alennus prosentteina
      $tuote->addChild('Maara', str_replace(".", ",", $row['kpl']));         // Määrä, tuetaan desimaaleja
      $tuote->addChild('Rivisumma', str_replace(".", ",", $row['rivisumma']));   // Rivisumma, sis. ALV
      $tuote->addChild('Verokanta', str_replace(".", ",", $row['alv']));       // Verokanta
      $tuote->addChild('Viite', $row['reknro']);                   // Viitteeseen rekisterinumero
      $tuote->addChild('Tulostustapa', 0);                     // vakio
    }

    return utf8_encode($xml->asXML());
  }


}


if (!function_exists('sahkoinen_lahete')) {
  function sahkoinen_lahete($laskurow) {
    global $kukarow, $yhtiorow, $pupe_root_polku, $futur_sahkoinen_lahete_host, $futur_sahkoinen_lahete_user, $futur_sahkoinen_lahete_pass, $futur_sahkoinen_lahete_path;

    $query = "SELECT asiakkaan_avainsanat.*
              FROM asiakkaan_avainsanat
              WHERE asiakkaan_avainsanat.yhtio       = '{$kukarow['yhtio']}'
              and asiakkaan_avainsanat.laji          = 'futur_sahkoinen_lahete'
              and asiakkaan_avainsanat.avainsana    != ''
              AND asiakkaan_avainsanat.liitostunnus  = '{$laskurow['liitostunnus']}'";
    $as_avain_chk_res = pupe_query($query);

    if (mysql_num_rows($as_avain_chk_res) > 0 and $futur_sahkoinen_lahete_host != "" and $futur_sahkoinen_lahete_user != "" and $futur_sahkoinen_lahete_pass != "" and $futur_sahkoinen_lahete_path != "") {

      $as_avain_chk_row = mysql_fetch_assoc($as_avain_chk_res);

      $aineistoon_laitettavat_rivit = array();
      $aineiston_kannassa_olevat_rivit = $poikkeavat_aineiston_kannassa_olevat_rivit = array();
      $tilauksella_olevat_rivit = $poikkeavat_tilauksella_olevat_rivit = array();

      $args = array(
        'ytunnus' => $laskurow['ytunnus'],
        'asiakasnro' => $as_avain_chk_row['avainsana'],
        'lahetenro_toim' => $laskurow['tunnus']
      );

      $_lahete_class = new SahkoinenLahete($args);

      if ($_lahete_class::getState()) {

        $query = "SELECT GROUP_CONCAT(tilausrivin_tunnus) AS rivit
                  FROM sahkoisen_lahetteen_rivit
                  WHERE yhtio  = '{$kukarow['yhtio']}'
                  AND otunnus  = '{$laskurow['tunnus']}'
                  AND status  != 'P'";
        $aineiston_rivit_res = pupe_query($query);
        $aineiston_rivit_row = mysql_fetch_assoc($aineiston_rivit_res);

        if (trim($aineiston_rivit_row['rivit']) != '') $aineiston_kannassa_olevat_rivit = explode(",", $aineiston_rivit_row['rivit']);

        $query = "SELECT GROUP_CONCAT(tunnus) AS rivit
                  FROM tilausrivi
                  WHERE yhtio  = '{$kukarow['yhtio']}'
                  AND otunnus  = '{$laskurow['tunnus']}'
                  AND tyyppi  != 'D'
                  AND var     != 'P'";
        $tilriv_res = pupe_query($query);
        $tilriv_row = mysql_fetch_assoc($tilriv_res);

        if (trim($tilriv_row['rivit']) != '') $tilauksella_olevat_rivit = explode(",", $tilriv_row['rivit']);

        if (count($aineiston_kannassa_olevat_rivit) != 0) {
          $poikkeavat_aineiston_kannassa_olevat_rivit = array_diff($aineiston_kannassa_olevat_rivit, $tilauksella_olevat_rivit);
        }

        $poikkeavat_tilauksella_olevat_rivit = array_diff($tilauksella_olevat_rivit, $aineiston_kannassa_olevat_rivit);

        /*
        Caset:
        #1 riviä ei ole sahkoisen_lahetteen_rivit-taulussa ==> laitetaan aineistoon
        #2 rivi on jo sahkoisen_lahetteen_rivit-taulussa ja tietoja ei ole muutettu verrattuna tilausriviin ==> ei laiteta aineistoon
        #3 rivi on jo sahkoisen_lahetteen_rivit-taulussa ja tietoja on muutettu verrattuna tilausriviin ==> tehdään kumoamisrivi, tehdään uusi rivi ja laitetaan molemmat rivit aineistoon
        #4 sahkoisen_lahetteen_rivit-taulussa on rivi joka ei ole enää tilauksella (tai tilausrivit-taulussa) ==> tehdään kumoamisrivi ja laitetaan aineistoon

        sahkoisen_lahetteen_rivit-taulun statukset:
        tyhjä = lähetetty
        P = poistettu
        */

        //3 rivi on jo sahkoisen_lahetteen_rivit-taulussa ja tietoja on muutettu verrattuna tilausriviin ==> tehdään kumoamisrivi, tehdään uusi rivi ja laitetaan molemmat rivit aineistoon
        if (count($tilauksella_olevat_rivit) != 0) {

          $query = "SELECT tilausrivi.*, tilausrivin_lisatiedot.rekisterinumero,
                    sahkoisen_lahetteen_rivit.tuoteno as sahk_tuoteno,
                    sahkoisen_lahetteen_rivit.kpl as sahk_kpl,
                    sahkoisen_lahetteen_rivit.tunnus as sahk_tunnus,
                    sahkoisen_lahetteen_rivit.rekisterinumero as sahk_rekisterinumero
                    FROM tilausrivi
                    JOIN sahkoisen_lahetteen_rivit ON (sahkoisen_lahetteen_rivit.yhtio = tilausrivi.yhtio AND sahkoisen_lahetteen_rivit.tilausrivin_tunnus = tilausrivi.tunnus AND sahkoisen_lahetteen_rivit.status != 'P')
                    LEFT JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus)
                    WHERE tilausrivi.yhtio               = '{$kukarow['yhtio']}'
                    AND (
                      sahkoisen_lahetteen_rivit.kpl     != (tilausrivi.varattu + tilausrivi.jt) OR
                      sahkoisen_lahetteen_rivit.tuoteno != tilausrivi.tuoteno
                    )
                    AND tilausrivi.tunnus                IN (".implode(",", $tilauksella_olevat_rivit).")";
          $compare_res = pupe_query($query);

          while ($compare_row = mysql_fetch_assoc($compare_res)) {

            $query = "SELECT toimi.toimittajanro, if (tuotteen_toimittajat.jarjestys = 0, 9999, tuotteen_toimittajat.jarjestys) sorttaus
                      FROM tuotteen_toimittajat
                      JOIN toimi ON (toimi.yhtio = tuotteen_toimittajat.yhtio AND toimi.tunnus = tuotteen_toimittajat.liitostunnus)
                      WHERE tuotteen_toimittajat.yhtio = '{$kukarow['yhtio']}'
                      AND tuotteen_toimittajat.tuoteno = '{$compare_row['sahk_tuoteno']}'
                      ORDER BY sorttaus";
            $toimittajanro_chk_res = pupe_query($query);
            $toimittajanro_chk_row = mysql_fetch_assoc($toimittajanro_chk_res);

            $query = "SELECT myyntihinta, nimitys
                      FROM tuote
                      WHERE yhtio = '{$kukarow['yhtio']}'
                      AND tuoteno = '{$compare_row['sahk_tuoteno']}'";
            $tuotenores = pupe_query($query);
            $tuotenorow = mysql_fetch_assoc($tuotenores);

            $ale_rivihinta = generoi_alekentta_php($compare_row, 'M', 'kerto');

            $compare_row['rivihinta'] = round($tuotenorow['myyntihinta'] * $compare_row['sahk_kpl'] * $ale_rivihinta, $yhtiorow['hintapyoristys']);

            // yhtiön hinnat alvittomia
            // lisätään alv
            if ($yhtiorow['alv_kasittely'] == 'o') {
              $tuotenorow['myyntihinta'] = round($tuotenorow['myyntihinta'] * (1+$compare_row['alv']/100), $yhtiorow['hintapyoristys']);
              $compare_row['rivihinta'] = round($compare_row['rivihinta'] * (1+$compare_row['alv']/100), $yhtiorow['hintapyoristys']);
            }

            // Euromääräinen alennus
            $ale = round(($tuotenorow['myyntihinta'] * $compare_row['sahk_kpl']) - $compare_row['rivihinta'], $yhtiorow['hintapyoristys']);

            $args = array(
              'tuoteno' => $compare_row['sahk_tuoteno'],
              'toimittajanro' => $toimittajanro_chk_row['toimittajanro'],
              'nimitys' => $tuotenorow['nimitys'],
              'myyntihinta' => $tuotenorow['myyntihinta'],
              'alennus' => $ale,
              'kpl' => ($compare_row['sahk_kpl'] * -1),
              'rivisumma' => $compare_row['rivihinta'],
              'alv' => $compare_row['alv'],
              'reknro' => $compare_row['sahk_rekisterinumero'],
            );

            $_lahete_class->setRow($args);

            if ($_lahete_class::getState()) {

              $query = "UPDATE sahkoisen_lahetteen_rivit SET
                        status      = 'P',
                        muuttaja    = '{$kukarow['kuka']}',
                        muutospvm   = now()
                        WHERE yhtio = '{$kukarow['yhtio']}'
                        AND tunnus  = '{$compare_row['sahk_tunnus']}'";
              $upd_res = pupe_query($query);

              $query = "SELECT toimi.toimittajanro, if (tuotteen_toimittajat.jarjestys = 0, 9999, tuotteen_toimittajat.jarjestys) sorttaus
                        FROM tuotteen_toimittajat
                        JOIN toimi ON (toimi.yhtio = tuotteen_toimittajat.yhtio AND toimi.tunnus = tuotteen_toimittajat.liitostunnus)
                        WHERE tuotteen_toimittajat.yhtio = '{$kukarow['yhtio']}'
                        AND tuotteen_toimittajat.tuoteno = '{$compare_row['tuoteno']}'
                        ORDER BY sorttaus";
              $toimittajanro_chk_res = pupe_query($query);
              $toimittajanro_chk_row = mysql_fetch_assoc($toimittajanro_chk_res);

              $query = "SELECT myyntihinta, nimitys
                        FROM tuote
                        WHERE yhtio = '{$kukarow['yhtio']}'
                        AND tuoteno = '{$compare_row['sahk_tuoteno']}'";
              $tuotenores = pupe_query($query);
              $tuotenorow = mysql_fetch_assoc($tuotenores);

              $ale_rivihinta = generoi_alekentta_php($compare_row, 'M', 'kerto');

              $compare_row['rivihinta'] = round($tuotenorow['myyntihinta'] * ($compare_row['varattu'] + $compare_row['jt']) * $ale_rivihinta, $yhtiorow['hintapyoristys']);

              // yhtiön hinnat alvittomia
              // lisätään alv
              if ($yhtiorow['alv_kasittely'] == 'o') {
                $tuotenorow['myyntihinta'] = round($tuotenorow['myyntihinta'] * (1+$compare_row['alv']/100), $yhtiorow['hintapyoristys']);
                $compare_row['rivihinta'] = round($compare_row['rivihinta'] * (1+$compare_row['alv']/100), $yhtiorow['hintapyoristys']);
              }

              // Euromääräinen alennus
              $ale = round(($tuotenorow['myyntihinta'] * ($compare_row['varattu'] + $compare_row['jt'])) - $compare_row['rivihinta'], $yhtiorow['hintapyoristys']);

              $args = array(
                'tuoteno' => $compare_row['tuoteno'],
                'toimittajanro' => $toimittajanro_chk_row['toimittajanro'],
                'nimitys' => $tuotenorow['nimitys'],
                'myyntihinta' => $tuotenorow['myyntihinta'],
                'alennus' => $ale,
                'kpl' => ($compare_row['varattu'] + $compare_row['jt']),
                'rivisumma' => $compare_row['rivihinta'],
                'alv' => $compare_row['alv'],
                'reknro' => $compare_row['rekisterinumero'],
              );

              $_lahete_class->setRow($args);

              if ($_lahete_class::getState()) {

                $query = "INSERT INTO sahkoisen_lahetteen_rivit SET
                          yhtio              = '{$kukarow['yhtio']}',
                          otunnus            = '{$compare_row['otunnus']}',
                          tilausrivin_tunnus = '{$compare_row['tunnus']}',
                          tuoteno            = '{$compare_row['tuoteno']}',
                          kpl                = '{$compare_row['varattu']}',
                          myyntihinta        = '{$tuotenorow['myyntihinta']}',
                          ale                = '{$ale}',
                          rekisterinumero    = '{$compare_row['rekisterinumero']}',
                          status             = '',
                          laatija            = '{$kukarow['kuka']}',
                          luontiaika         = now(),
                          muutospvm          = now(),
                          muuttaja           = '{$kukarow['kuka']}'";
                $insres = pupe_query($query);
              }
            }
          }
        }

        //4 sahkoisen_lahetteen_rivit-taulussa on rivi joka ei ole enää tilauksella (tai tilausrivit-taulussa) ==> tehdään kumoamisrivi ja laitetaan aineistoon
        if ($_lahete_class::getState() and count($poikkeavat_aineiston_kannassa_olevat_rivit) != 0) {

          $query = "SELECT sahkoisen_lahetteen_rivit.*, tuote.myyntihinta, tuote.nimitys, tuote.alv as tuote_alv
                    FROM sahkoisen_lahetteen_rivit
                    JOIN tuote ON (tuote.yhtio = sahkoisen_lahetteen_rivit.yhtio AND tuote.tuoteno = sahkoisen_lahetteen_rivit.tuoteno)
                    WHERE sahkoisen_lahetteen_rivit.yhtio             = '{$kukarow['yhtio']}'
                    AND sahkoisen_lahetteen_rivit.status             != 'P'
                    AND sahkoisen_lahetteen_rivit.tilausrivin_tunnus  IN (".implode(",", $poikkeavat_aineiston_kannassa_olevat_rivit).")";
          $sahkriv_res = pupe_query($query);

          while ($sahkriv_row = mysql_fetch_assoc($sahkriv_res)) {

            $query = "SELECT toimi.toimittajanro, if (tuotteen_toimittajat.jarjestys = 0, 9999, tuotteen_toimittajat.jarjestys) sorttaus
                      FROM tuotteen_toimittajat
                      JOIN toimi ON (toimi.yhtio = tuotteen_toimittajat.yhtio AND toimi.tunnus = tuotteen_toimittajat.liitostunnus)
                      WHERE tuotteen_toimittajat.yhtio = '{$kukarow['yhtio']}'
                      AND tuotteen_toimittajat.tuoteno = '{$sahkriv_row['tuoteno']}'
                      ORDER BY sorttaus";
            $toimittajanro_chk_res = pupe_query($query);
            $toimittajanro_chk_row = mysql_fetch_assoc($toimittajanro_chk_res);

            // Tarkistetaan onko tilausrivi olemassa
            $query = "SELECT *
                      FROM tilausrivi
                      WHERE yhtio = '{$kukarow['yhtio']}'
                      AND tunnus  = '{$sahkriv_row['tilausrivin_tunnus']}'";
            $tilriv_exists_res = pupe_query($query);

            if (mysql_num_rows($tilriv_exists_res) != 0) {

              $tilriv_exists_row = mysql_fetch_assoc($tilriv_exists_res);
              $sahkriv_row['alv'] = $tilriv_exists_row['alv'];
            }
            else {
              $sahkriv_row['alv'] = $sahkriv_row['tuote_alv'];
            }

            // tilausriviä ei välttämättä ole, joten alennus tulee semmoisenaan + alviton alennus laskentaan saadaan näin
            $ale = $sahkriv_row['ale'];
            if ($yhtiorow['alv_kasittely'] == 'o') $sahkriv_row['ale'] = $sahkriv_row['ale'] / 1.24;

            $sahkriv_row['rivihinta'] = round(($sahkriv_row['myyntihinta'] * $sahkriv_row['kpl']) - $sahkriv_row['ale'], $yhtiorow['hintapyoristys']);

            // yhtiön hinnat alvittomia
            // lisätään alv
            if ($yhtiorow['alv_kasittely'] == 'o') {
              $sahkriv_row['myyntihinta'] = round($sahkriv_row['myyntihinta'] * (1+$sahkriv_row['alv']/100), $yhtiorow['hintapyoristys']);
              $sahkriv_row['rivihinta'] = round($sahkriv_row['rivihinta'] * (1+$sahkriv_row['alv']/100), $yhtiorow['hintapyoristys']);
            }

            $args = array(
              'tuoteno' => $sahkriv_row['tuoteno'],
              'toimittajanro' => $toimittajanro_chk_row['toimittajanro'],
              'nimitys' => $sahkriv_row['nimitys'],
              'myyntihinta' => $sahkriv_row['myyntihinta'],
              'alennus' => $ale,
              'kpl' => ($sahkriv_row['kpl'] * -1),
              'rivisumma' => $sahkriv_row['rivihinta'],
              'alv' => $sahkriv_row['alv'],
              'reknro' => $sahkriv_row['rekisterinumero'],
            );

            $_lahete_class->setRow($args);

            if ($_lahete_class::getState()) {

              $query = "UPDATE sahkoisen_lahetteen_rivit SET
                        status      = 'P',
                        muuttaja    = '{$kukarow['kuka']}',
                        muutospvm   = now()
                        WHERE yhtio = '{$kukarow['yhtio']}'
                        AND tunnus  = '{$sahkriv_row['tunnus']}'";
              $upd_res = pupe_query($query);

            }
          }
        }

        //1 riviä ei ole sahkoisen_lahetteen_rivit-taulussa ==> laitetaan aineistoon
        if ($_lahete_class::getState() and count($poikkeavat_tilauksella_olevat_rivit) != 0) {

          $query = "SELECT tilausrivi.*, tilausrivin_lisatiedot.rekisterinumero, tuote.myyntihinta
                    FROM tilausrivi
                    JOIN tuote ON (tuote.yhtio = tilausrivi.yhtio AND tuote.tuoteno = tilausrivi.tuoteno)
                    LEFT JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = tilausrivi.yhtio AND tilausrivin_lisatiedot.tilausrivitunnus = tilausrivi.tunnus)
                    WHERE tilausrivi.yhtio = '{$kukarow['yhtio']}'
                    AND tilausrivi.otunnus = '{$laskurow['tunnus']}'
                    AND tilausrivi.tunnus  IN (".implode(",", $poikkeavat_tilauksella_olevat_rivit).")";
          $tilriv_res = pupe_query($query);

          while ($tilriv_row = mysql_fetch_assoc($tilriv_res)) {

            $query = "SELECT toimi.toimittajanro, if (tuotteen_toimittajat.jarjestys = 0, 9999, tuotteen_toimittajat.jarjestys) sorttaus
                      FROM tuotteen_toimittajat
                      JOIN toimi ON (toimi.yhtio = tuotteen_toimittajat.yhtio AND toimi.tunnus = tuotteen_toimittajat.liitostunnus)
                      WHERE tuotteen_toimittajat.yhtio = '{$kukarow['yhtio']}'
                      AND tuotteen_toimittajat.tuoteno = '{$tilriv_row['tuoteno']}'
                      ORDER BY sorttaus";
            $toimittajanro_chk_res = pupe_query($query);
            $toimittajanro_chk_row = mysql_fetch_assoc($toimittajanro_chk_res);

            $ale_rivihinta = generoi_alekentta_php($tilriv_row, 'M', 'kerto');

            $tilriv_row['rivihinta'] = round($tilriv_row['myyntihinta'] * ($tilriv_row['varattu'] + $tilriv_row['jt']) * $ale_rivihinta, $yhtiorow['hintapyoristys']);

            // yhtiön hinnat alvittomia
            // lisätään alv
            if ($yhtiorow['alv_kasittely'] == 'o') {
              $tilriv_row['myyntihinta'] = round($tilriv_row['myyntihinta'] * (1+$tilriv_row['alv']/100), $yhtiorow['hintapyoristys']);
              $tilriv_row['rivihinta'] = round($tilriv_row['rivihinta'] * (1+$tilriv_row['alv']/100), $yhtiorow['hintapyoristys']);
            }

            // Euromääräinen alennus
            $ale = round(($tilriv_row['myyntihinta'] * ($tilriv_row['varattu'] + $tilriv_row['jt'])) - $tilriv_row['rivihinta'], $yhtiorow['hintapyoristys']);

            $args = array(
              'tuoteno' => $tilriv_row['tuoteno'],
              'toimittajanro' => $toimittajanro_chk_row['toimittajanro'],
              'nimitys' => $tilriv_row['nimitys'],
              'myyntihinta' => $tilriv_row['myyntihinta'],
              'alennus' => $ale,
              'kpl' => $tilriv_row['varattu'],
              'rivisumma' => $tilriv_row['rivihinta'],
              'alv' => $tilriv_row['alv'],
              'reknro' => $tilriv_row['rekisterinumero'],
            );

            $_lahete_class->setRow($args);

            if ($_lahete_class::getState()) {

              $query = "INSERT INTO sahkoisen_lahetteen_rivit SET
                        yhtio              = '{$kukarow['yhtio']}',
                        otunnus            = '{$tilriv_row['otunnus']}',
                        tilausrivin_tunnus = '{$tilriv_row['tunnus']}',
                        tuoteno            = '{$tilriv_row['tuoteno']}',
                        kpl                = '{$tilriv_row['varattu']}',
                        myyntihinta        = '{$tilriv_row['myyntihinta']}',
                        ale                = '{$ale}',
                        rekisterinumero    = '{$tilriv_row['rekisterinumero']}',
                        status             = '',
                        laatija            = '{$kukarow['kuka']}',
                        luontiaika         = now(),
                        muutospvm          = now(),
                        muuttaja           = '{$kukarow['kuka']}'";
              $insres = pupe_query($query);
            }
          }
        }
      }

      if ($_lahete_class::getState() and $_lahete_class->getRowCount() > 0) {

        $xml = $_lahete_class->generateXML();

        $path = $pupe_root_polku."/dataout/";

        $toimipaikat_res = hae_yhtion_toimipaikat($kukarow['yhtio'], $laskurow['yhtio_toimipaikka']);
        $toimipaikat_row = mysql_fetch_assoc($toimipaikat_res);

        $filename = "{$toimipaikat_row['liiketunnus']}_{$as_avain_chk_row['avainsana']}_{$laskurow['tunnus']}_fsl.zip";

        if (file_put_contents($path.$filename, $xml) === FALSE) {
          echo "<br/><font class='error'>", t("Tiedoston kirjoitus epäonnistui"), " ({$filename})</font><br/>";
        }
        else {

          $ftphost = $futur_sahkoinen_lahete_host;
          $ftpuser = $futur_sahkoinen_lahete_user;
          $ftppass = $futur_sahkoinen_lahete_pass;
          $ftppath = $futur_sahkoinen_lahete_path;
          $ftpfile = $path.$filename;

          require "inc/ftp-send.inc";
        }
      }
    }
  }


}
