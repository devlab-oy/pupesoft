<?php

require_once('CSVDumper.php');

class LaiteCSVDumper extends CSVDumper {

  protected $unique_values = array();

  public function __construct($kukarow) {
    parent::__construct($kukarow);

    $konversio_array = array(
        'tuoteno'      => 'MALLI',
        'nimitys'      => 'NIMI',
        'tyyppi'       => 'TYYPPI',
        'koko'         => 'PAINO',
        'sarjanro'     => 'MITAT',
        'valm_pvm'     => 'DATA10',
        'oma_numero'   => 'DATA20',
        'paikka'       => 'LISASIJAINTI',
        'sijainti'     => 'LISASIJAINTI',
        'koodi'        => 'KOODI',
        'olosuhde'     => 'DATA7',
        'kohde'        => 'SIJAINTI', //jos paikalla ei ole nimeä, laitetaan laite Paikaton ulkona / sisällä
        'asiakas_nimi' => 'KUSTPAIKKA', //koska paikan ja kohteen nimien yhdistelmä ei ole uniikki pitää aineistosta lukea myös asiakkaan nimi, jonka avulla laite saadaa lisättyä paikkaan. nämä pitää unsettaa ennen dumppia
    );
    $required_fields = array(
        'tuoteno',
        'paikka',
    );

    $this->setFilepath("/tmp/konversio/LAITE.csv");
    $this->setSeparator(';#x#');
    $this->setKonversioArray($konversio_array);
    $this->setRequiredFields($required_fields);
    $this->setTable('laite');
  }

  protected function konvertoi_rivit() {
    $progressbar = new ProgressBar(t('Konvertoidaan rivit'));
    $progressbar->initialize(count($this->rivit));

    foreach ($this->rivit as $index => &$rivi) {
      $rivi = $this->konvertoi_rivi($rivi);
      $rivi = $this->lisaa_pakolliset_kentat($rivi);

      //index + 2, koska eka rivi on header ja laskenta alkaa riviltä 0
      $valid = $this->validoi_rivi($rivi, $index + 2);

      if (!$valid) {
        unset($this->rivit[$index]);
      }

      $progressbar->increase();
    }
  }

  protected function konvertoi_rivi($rivi) {
    $rivi_temp = array();

    foreach ($this->konversio_array as $konvertoitu_header => $csv_header) {
      if (array_key_exists($csv_header, $rivi)) {
        if ($konvertoitu_header == 'tyyppi') {
          $rivi_temp[$konvertoitu_header] = strtolower($rivi[$csv_header]) . 'sammutin';
        }
        else if ($konvertoitu_header == 'paino') {
          $rivi_temp[$konvertoitu_header] = $rivi[$csv_header];
        }
        else if ($konvertoitu_header == 'tuoteno') {
          if (strtoupper($rivi[$csv_header]) == 'A990001') {
            $tuoteno = 'MUISTUTUS';
          }
          else {
            $tuoteno = str_replace(' ', '', strtoupper($rivi[$csv_header]));
          }

          $rivi_temp[$konvertoitu_header] = $tuoteno;
        }
        else if ($konvertoitu_header == 'valm_pvm') {
          $rivi_temp[$konvertoitu_header] = "{$rivi[$csv_header]}-01-01";
        }
        else if ($konvertoitu_header == 'paikka') {
          if ($rivi[$csv_header] == '') {
            if ($rivi['DATA7'] == '12') {
              $rivi_temp[$konvertoitu_header] = 'Paikaton ulkona / tärinässä';
            }
            else if ($rivi['DATA7'] == '24') {
              $rivi_temp[$konvertoitu_header] = 'Paikaton sisällä';
            }
            else {
              $rivi_temp[$konvertoitu_header] = 'Paikaton sisällä';
            }
          }
          else {
            $rivi_temp[$konvertoitu_header] = trim($rivi[$csv_header]);
          }
        }
        else {
          $rivi_temp[$konvertoitu_header] = $rivi[$csv_header];
        }
      }
    }

    $rivi_temp['tila'] = 'N';

    return $rivi_temp;
  }

  protected function validoi_rivi(&$rivi, $index) {
    $valid = true;

    foreach ($rivi as $key => $value) {
      if ($key == 'paikka') {
        $paikka_tunnus = $this->hae_paikka_tunnus($value, $rivi['kohde'], $rivi['asiakas_nimi']);
        $paikan_nimi = $value;
        
        if ($paikka_tunnus == 0 and in_array($key, $this->required_fields)) {
          $this->errors[$index][] = t('Paikkaa') . " <b>{$paikan_nimi}</b> " . t('ei löydy') . ' ' . $rivi['kohde'] . ' ' . $rivi['asiakas_nimi'];
          $this->errors[$index][] = $rivi['koodi'];
          $valid = false;
        }
        else {
          $rivi[$key] = $paikka_tunnus;
        }
      }
      else if ($key == 'tuoteno') {
        if ($valid) {
          $valid = $this->loytyyko_tuote($rivi[$key]);
          if (!$valid) {
            list($valid, $tuoteno) = $this->loytyyko_tuote_nimella($rivi['nimitys']);
            if (!$valid) {
              $this->luo_tuote($rivi['tuoteno'], $rivi['tyyppi'], $rivi['koko']);
              $rivi[$key] = $rivi['tuoteno'];
              $valid = true;
            }
            else {
              $rivi[$key] = $tuoteno;
            }
          }
        }
      }
      else {
        if (in_array($key, $this->required_fields) and $value == '') {
          $valid = false;
        }
      }
    }

    unset($rivi['tyyppi']);
    unset($rivi['koko']);
    unset($rivi['nimitys']);
    unset($rivi['kohde']);
    unset($rivi['olosuhde']);
    unset($rivi['asiakas_nimi']);

    return $valid;
  }

  private function loytyyko_tuote($tuoteno) {
    $query = "SELECT tunnus
              FROM tuote
              WHERE yhtio = '{$this->kukarow['yhtio']}'
              AND tuoteno = '{$tuoteno}'";
    $result = pupe_query($query);
    if (mysql_num_rows($result) == 1) {
      return true;
    }

    return false;
  }

  private function loytyyko_tuote_nimella($nimitys) {
    $query = "SELECT tuoteno
              FROM tuote
              WHERE yhtio = '{$this->kukarow['yhtio']}'
              AND nimitys = '{$nimitys}'";
    $result = pupe_query($query);

    if (mysql_num_rows($result) == 1) {
      $tuote = mysql_fetch_assoc($result);
      return array(true, $tuote['tuoteno']);
    }

    return array(false, '');
  }

  private function luo_tuote($tuoteno, $tyyppi, $koko) {
    $query = "INSERT INTO tuote
              SET yhtio = '{$this->kukarow['yhtio']}',
              tuoteno = '{$tuoteno}',
              nimitys = '{$tuoteno}',
              try = '80',
              tuotetyyppi = '',
              ei_saldoa = '',
              laatija = 'import',
              luontiaika = NOW()";
    pupe_query($query);

    $query = "SELECT *
              FROM tuotteen_avainsanat
              WHERE tuoteno = '{$tuoteno}'
              AND selite = '" . strtolower($tyyppi) . "'";
    $result = pupe_query($query);
    if (mysql_num_rows($result) == 0) {
      $mahdolliset_sammutin_tyypit = hae_mahdolliset_sammutin_tyypit();
      if (in_array($tyyppi, array_keys($mahdolliset_sammutin_tyypit))) {
        $query = 'INSERT INTO tuotteen_avainsanat
                  (
                    yhtio,
                    tuoteno,
                    kieli,
                    laji,
                    selite,
                    laatija,
                    luontiaika
                  )
                  VALUES
                  (
                    "' . $this->kukarow['yhtio'] . '",
                    "' . $tuoteno . '",
                    "fi",
                    "sammutin_tyyppi",
                    "' . strtolower($tyyppi) . '",
                    "import",
                    NOW()
                  )';
        pupe_query($query);
      }
    }

    $query = "SELECT *
              FROM tuotteen_avainsanat
              WHERE tuoteno = '{$tuoteno}'
              AND selite = '{$koko}'";
    $result = pupe_query($query);
    if (mysql_num_rows($result) == 0) {
      $query = 'INSERT INTO tuotteen_avainsanat
                (
                  yhtio,
                  tuoteno,
                  kieli,
                  laji,
                  selite,
                  laatija,
                  luontiaika
                )
                VALUES
                (
                  "' . $this->kukarow['yhtio'] . '",
                  "' . $tuoteno . '",
                  "fi",
                  "sammutin_koko",
                  "' . $koko . '",
                  "import",
                  NOW()
                )';
      pupe_query($query);
    }
  }

  private function hae_paikka_tunnus($paikan_nimi, $kohde_nimi, $asiakas_nimi) {
    $asiakas_join = ' JOIN asiakas
                      ON ( asiakas.yhtio = kohde.yhtio
                        AND asiakas.tunnus = kohde.asiakas
                        AND asiakas.nimi = "' . $asiakas_nimi . '" )';
    $query = 'SELECT paikka.tunnus
              FROM paikka
              JOIN kohde
              ON ( kohde.yhtio = paikka.yhtio
                AND kohde.tunnus = paikka.kohde
                AND kohde.nimi = "' . $kohde_nimi . '" )
              WHERE paikka.yhtio = "' . $this->kukarow['yhtio'] . '"
              AND paikka.nimi = "' . $paikan_nimi . '"';
    $result = pupe_query($query);

    if (mysql_num_rows($result) == 0) {
      return 0;
    }

    //jos paikan ja kohteen nimen yhdistelmä ei ole uniikki niin, kokeillaan lisätä asiakkaan nimi
    if (mysql_num_rows($result) != 1) {
      $query = 'SELECT paikka.tunnus
                FROM paikka
                JOIN kohde
                ON ( kohde.yhtio = paikka.yhtio
                  AND kohde.tunnus = paikka.kohde
                  AND kohde.nimi = "' . $kohde_nimi . '" )
                ' . $asiakas_join . '
                WHERE paikka.yhtio = "' . $this->kukarow['yhtio'] . '"
                AND paikka.nimi = "' . $paikan_nimi . '"';
      $result = pupe_query($query);
    }

    $paikkarow = mysql_fetch_assoc($result);

    if (!empty($paikkarow)) {
      return $paikkarow['tunnus'];
    }

    return 0;
  }

  protected function tarkistukset() {
    $query = "SELECT paikka.tunnus
              FROM paikka
              WHERE yhtio = '{$this->kukarow['yhtio']}'";
    $result = pupe_query($query);

    $paikat = array();
    while ($paikka = mysql_fetch_assoc($result)) {
      $paikat[] = $paikka;
    }

    $query = "SELECT DISTINCT laite.paikka
              FROM laite
              WHERE yhtio = '{$this->kukarow['yhtio']}'";
    $result = pupe_query($query);
    $laitteiden_paikat = array();
    while ($laitteen_paikka = mysql_fetch_assoc($result)) {
      $laitteiden_paikat[] = $laitteen_paikka;
    }

    $kpl = count($paikat) - count($laitteiden_paikat);

    echo "{$kpl} paikkaa ilman laitetta!!!!";

    /*
     *
      //Kuinka monta laitetta per asiakkaan kohde
      SELECT asiakas.tunnus,
      asiakas.nimi,
      kohde.tunnus,
      kohde.nimi,
      Count(*) AS laite_kpl
      FROM   laite
      JOIN paikka
      ON ( paikka.yhtio = laite.yhtio
      AND paikka.tunnus = laite.paikka )
      JOIN kohde
      ON ( kohde.yhtio = paikka.yhtio
      AND kohde.tunnus = paikka.kohde )
      JOIN asiakas
      ON ( asiakas.yhtio = kohde.yhtio
      AND asiakas.tunnus = kohde.asiakas )
      WHERE  laite.yhtio = '{$this->kukarow['yhtio']}'
      GROUP  BY 1,
      2,
      3,
      4
      ORDER  BY laite_kpl DESC;
     */
  }
}
