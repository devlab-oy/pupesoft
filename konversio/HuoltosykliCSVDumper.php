<?php

require_once('CSVDumper.php');

class HuoltosykliCSVDumper extends CSVDumper {

  protected $unique_values = array();

  public function __construct($kukarow) {
    parent::__construct($kukarow);

    $konversio_array = array(
        'laite'      => 'LAITE',
        'toimenpide' => 'TUOTENRO',
        'nimitys'    => 'NIMIKE',
        'huoltovali' => 'VALI',
        'hinta'      => 'HINTA', //Unset ennen dumppia
    );
    $required_fields = array(
        'laite',
        'toimenpide',
    );

    $this->setFilepath("/tmp/konversio/TUOTETARK.csv");
    $this->setSeparator(';#x#');
    $this->setKonversioArray($konversio_array);
    $this->setRequiredFields($required_fields);
    $this->setTable('huoltosykli');
  }

  protected function konvertoi_rivit() {
    $progressbar = new ProgressBar(t('Konvertoidaan rivit'));
    $progressbar->initialize(count($this->rivit));

    foreach ($this->rivit as $index => &$rivi) {
      $rivi = $this->konvertoi_rivi($rivi);
      $rivi = $this->lisaa_pakolliset_kentat($rivi);

      //index + 2, koska eka rivi on header ja laskenta alkaa rivilt‰ 0
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
        if ($konvertoitu_header == 'huoltovali') {
          $huoltovalit = huoltovali_options();
          foreach ($huoltovalit as $huoltovali => $value) {
            if ($value['months'] == $rivi[$csv_header]) {
              $rivi_temp[$konvertoitu_header] = (int) $huoltovali;
              break;
            }
          }

          //failsafe
          if (empty($rivi_temp[$konvertoitu_header])) {
            $rivi_temp[$konvertoitu_header] = 365;
          }
        }
        else {
          $rivi_temp[$konvertoitu_header] = $rivi[$csv_header];
        }
      }
    }

    return $rivi_temp;
  }

  protected function validoi_rivi(&$rivi, $index) {
    $valid = true;

//    if ($rivi['hinta'] == 0 or $rivi['huoltovali'] == 0) {
//      return false;
//    }

    foreach ($rivi as $key => $value) {
      if (in_array($key, $this->required_fields) and $value == '') {
        $this->errors[$index][] = t('Pakollinen kentt‰') . " <b>{$key}</b> " . t('puuttuu');
        $valid = false;
      }

      if ($key == 'laite') {
        $attrs = $this->hae_tuotteen_tyyppi_koko($rivi[$key]);

        if ($attrs == 1) {
          $this->errors[$index][] = t('Huoltosyklin laitteelle') . " <b>{$rivi[$key]}</b> " . t('lˆytyi enemm‰n kuin 2 laitteen koko tai tyyppi‰');
          $valid = false;
        }
        else if ($attrs == 0) {
          $loytyyko_laite_tuote = $this->loytyyko_tuote($rivi[$key]);
          if (!$loytyyko_laite_tuote) {
            list($tyyppi, $koko) = $this->luo_tuote($rivi['toimenpide'], $rivi['nimitys'], $rivi[$key]);
            $rivi['tyyppi'] = $tyyppi;
            $rivi['koko'] = $koko;

            $this->errors[$index][] = t('Laite tuote') . " <b>{$rivi[$key]}</b> " . t('perustettiin');
          }
          else {
            //T‰nne ei pit‰isi ikin‰ menn‰. Tuote siis lˆytyy mutta silt‰ puuttuu avainsanat.
          }
        }
        else {
          $rivi['tyyppi'] = $attrs['tyyppi'];
          $rivi['koko'] = $attrs['koko'];
        }
      }
      else if ($key == 'toimenpide') {
        $valid_temp = $this->loytyyko_tuote($rivi[$key]);
        if (!$valid_temp) {
          $nimitys = $this->luo_tuote($rivi[$key], $rivi['nimitys']);
          $this->errors[$index][] = t('Luotiin toimenpide tuote') . " <b>{$nimitys}</b> ";
        }
      }
    }

    return $valid;
  }

  protected function dump_data() {
    $progress_bar = new ProgressBar(t('Ajetaan rivit tietokantaan') . ' : ' . count($this->rivit));
    $progress_bar->initialize(count($this->rivit));
    foreach ($this->rivit as $rivi) {
      unset($rivi['hinta']);

      //Huom koko ei ole pakollinen koska paloposteilla ei ole kokoa
      if (empty($rivi['tyyppi']) or empty($rivi['toimenpide'])) {
        $progress_bar->increase();
        continue;
      }

      $huoltosykli_rivit = $this->huoltosykli_rivit($rivi['tyyppi'], $rivi['koko'], $rivi['toimenpide']);

      $nimitys_temp = $rivi['nimitys'];
      $laite_tuoteno_temp = $rivi['laite'];
      unset($rivi['nimitys']);
      unset($rivi['laite']);

      $huoltosykli_rivi_sisalla = search_array_key_for_value_recursive($huoltosykli_rivit, 'olosuhde', 'A');

      if (empty($huoltosykli_rivi_sisalla)) {
        $query = "INSERT INTO {$this->table}
                  (" . implode(", ", array_keys($rivi)) . ")
                  VALUES
                  ('" . implode("', '", array_values($rivi)) . "')";

        //Purkka fix
        $query = str_replace("'now()'", 'now()', $query);
        pupe_query($query);

        $huoltosykli_tunnus_sisalla = mysql_insert_id();
        $huoltosykli_huoltovali_sisalla = $rivi['huoltovali'];
      }
      else {
        $huoltosykli_tunnus_sisalla = $huoltosykli_rivi_sisalla[0]['tunnus'];
        $huoltosykli_huoltovali_sisalla = $huoltosykli_rivi_sisalla[0]['huoltovali'];
      }

      $huoltosykli_rivi_ulkona = search_array_key_for_value_recursive($huoltosykli_rivit, 'olosuhde', 'X');

      if (empty($huoltosykli_rivi_ulkona)) {
        $rivi['olosuhde'] = 'X';

        //Minimi huoltov‰li on 365 riippumatta onko laite ulkona vai sis‰ll‰
        if (stristr($nimitys_temp, 'tarkastus') and $rivi['huoltovali'] != 365) {
          $rivi['huoltovali'] = $rivi['huoltovali'] / 2;
        }

        $query = "INSERT INTO {$this->table}
                  (" . implode(", ", array_keys($rivi)) . ")
                  VALUES
                  ('" . implode("', '", array_values($rivi)) . "')";

        //Purkka fix
        $query = str_replace("'now()'", 'now()', $query);
        pupe_query($query);

        $huoltosykli_tunnus_ulkona = mysql_insert_id();
        $huoltosykli_huoltovali_ulkona = $rivi['huoltovali'];
      }
      else {
        $huoltosykli_tunnus_ulkona = $huoltosykli_rivi_ulkona[0]['tunnus'];
        $huoltosykli_huoltovali_ulkona = $huoltosykli_rivi_ulkona[0]['huoltovali'];
      }

      $this->liita_laitteet_huoltosykliin($laite_tuoteno_temp, $huoltosykli_huoltovali_sisalla, $huoltosykli_huoltovali_ulkona, $huoltosykli_tunnus_sisalla, $huoltosykli_tunnus_ulkona);

      $progress_bar->increase();
    }

    $this->liita_muistutus_laitteet_kaynti_toimenpiteeseen();
  }

  protected function lisaa_pakolliset_kentat($rivi) {
    $rivi = parent::lisaa_pakolliset_kentat($rivi);
    $rivi['pakollisuus'] = '1';
    $rivi['olosuhde'] = 'A'; //Olosuhde sis‰ll‰

    return $rivi;
  }

  private function hae_tuotteen_tyyppi_koko($tuoteno) {
    $query = "SELECT tuotteen_avainsanat.laji,
              tuotteen_avainsanat.selite
              FROM tuotteen_avainsanat
              WHERE tuotteen_avainsanat.yhtio = '{$this->kukarow['yhtio']}'
              AND tuotteen_avainsanat.laji IN ('sammutin_koko','sammutin_tyyppi')
              AND tuotteen_avainsanat.tuoteno = '{$tuoteno}'";
    $result = pupe_query($query);
    $attrs = array();

    if (mysql_num_rows($result) > 2) {
      return 1;
    }

    if (mysql_num_rows($result) == 0) {
      return 0;
    }

    while ($attr = mysql_fetch_assoc($result)) {
      $attrs[str_replace('sammutin_', '', $attr['laji'])] = $attr['selite'];
    }

    return $attrs;
  }

  private function loytyyko_tuote($tuoteno) {
    $query = "SELECT *
              FROM tuote
              WHERE tuote.yhtio = '{$this->kukarow['yhtio']}'
              AND tuote.tuoteno = '{$tuoteno}'";
    $result = pupe_query($query);

    if (mysql_num_rows($result) == 0) {
      return false;
    }

    return true;
  }

  protected function huoltosykli_rivit($tyyppi, $koko, $toimenpide) {
    $query = "SELECT *
              FROM huoltosykli
              WHERE yhtio = '{$this->kukarow['yhtio']}'
              AND tyyppi = '{$tyyppi}'
              AND koko = '{$koko}'
              AND toimenpide = '{$toimenpide}'";
    $result = pupe_query($query);
    $huoltosykli_rivit = array();
    while ($huoltosykli_rivi = mysql_fetch_assoc($result)) {
      $huoltosykli_rivit[] = $huoltosykli_rivi;
    }

    return $huoltosykli_rivit;
  }

  private function liita_laitteet_huoltosykliin($laite_tuoteno, $huoltosykli_huoltovali_sisalla, $huoltosykli_huoltovali_ulkona, $huoltosykli_tunnus_sisalla, $huoltosykli_tunnus_ulkona) {
    $query = "SELECT laite.tunnus,
              paikka.olosuhde
              FROM laite
              JOIN paikka
              ON ( paikka.yhtio = laite.yhtio
                AND paikka.tunnus = laite.paikka )
              WHERE laite.yhtio = '{$this->kukarow['yhtio']}'
              AND laite.tuoteno = '{$laite_tuoteno}'";
    $result = pupe_query($query);

    while ($laite = mysql_fetch_assoc($result)) {
      if ($laite['olosuhde'] == 'A') {
        $query = "INSERT INTO huoltosyklit_laitteet
                  SET yhtio = '{$this->kukarow['yhtio']}',
                  huoltosykli_tunnus = '{$huoltosykli_tunnus_sisalla}',
                  laite_tunnus = '{$laite['tunnus']}',
                  huoltovali = '{$huoltosykli_huoltovali_sisalla}',
                  pakollisuus = '1',
                  laatija = 'import',
                  luontiaika = NOW()";
      }
      else if ($laite['olosuhde'] == 'X') {
        $query = "INSERT INTO huoltosyklit_laitteet
                  SET yhtio = '{$this->kukarow['yhtio']}',
                  huoltosykli_tunnus = '{$huoltosykli_tunnus_ulkona}',
                  laite_tunnus = '{$laite['tunnus']}',
                  huoltovali = '{$huoltosykli_huoltovali_ulkona}',
                  pakollisuus = '1',
                  laatija = 'import',
                  luontiaika = NOW()";
      }
      else {
        //JOS ONGELMIA LAITETAAN SISƒLLE
        $query = "INSERT INTO huoltosyklit_laitteet
                  SET yhtio = '{$this->kukarow['yhtio']}',
                  huoltosykli_tunnus = '{$huoltosykli_tunnus_sisalla}',
                  laite_tunnus = '{$laite['tunnus']}',
                  huoltovali = '{$huoltosykli_huoltovali_sisalla}',
                  pakollisuus = '1',
                  laatija = 'import',
                  luontiaika = NOW()";
      }
      pupe_query($query);
    }
  }

  private function luo_tuote($toimenpide_tuoteno, $nimitys, $laite_tuoteno = '') {
    $nimitys = strtolower($nimitys);

    //Yritet‰‰n p‰‰tell‰ tuotenumerosta koko ja tyyppi
    $koko = preg_replace('/[^1-9]/', '', $toimenpide_tuoteno);

    //Tuotenumeron nimest‰ voidaan ottaa huollon tyyppi (2 merkki‰) sek‰ huollon kohteen koko (2 merkki‰) alusta ja lopusta pois
    $tuoteno_temp = substr($toimenpide_tuoteno, 2);
    $tuoteno_temp = substr($tuoteno_temp, 0, -2);

    //Jos tuoteno_temp on 4 merkki‰ kyseess‰ on paineellinen huoltosykli
    if (strlen($tuoteno_temp) == 4) {
      //Tyyppi on viimeiset 2
      $tyyppi = substr($tuoteno_temp, 2, 4);
      if ($tyyppi == 'ja') {
        $tyyppi = 'jauhesammutin';
      }
      $tyyppi2 = substr($tuoteno_temp, 0, 2);
      if ($tyyppi2 == 'pa') {
        $tyyppi2 = 'paineellinen';
      }
      else if ($tyyppi2 == 'ep') {
        $tyyppi2 = 'paineeton';
      }
    }
    else {
      if ($tuoteno_temp == 'co') {
        $tyyppi = 'hiilidioksidisammutin';
      }
    }

    if (!empty($tyyppi2)) {
      $nimitys = $nimitys . ' ' . $tyyppi2 . ' ' . $tyyppi . ' ' . $koko;
    }
    else {
      $nimitys = $nimitys . ' ' . $tyyppi . ' ' . $koko;
    }

    if (!empty($laite_tuoteno)) {
      $this->luo_laite_tuote($laite_tuoteno, $laite_tuoteno, $koko, $tyyppi);

      return array($tyyppi, $koko);
    }
    else {
      if ($nimitys == 'tarkastus') {
        $tarkastustyyppi = 'tarkastus';
        $prioriteetti = 3;
      }
      else if ($nimitys == 'huolto') {
        $tarkastustyyppi = 'huolto';
        $prioriteetti = 2;
      }
      else {
        $tarkastustyyppi = 'koeponnistus';
        $prioriteetti = 1;
      }

      $this->luo_toimenpide_tuote($toimenpide_tuoteno, $nimitys, $tarkastustyyppi, $prioriteetti);

      return $nimitys;
    }
  }

  private function luo_laite_tuote($tuoteno, $nimitys, $koko, $tyyppi) {
    $query = "INSERT INTO tuote
              SET yhtio = '{$this->kukarow['yhtio']}',
              tuoteno = '{$tuoteno}',
              nimitys = '{$nimitys}',
              try = '80',
              tuotetyyppi = '',
              ei_saldoa = '',
              laatija = 'import',
              luontiaika = NOW()";
    pupe_query($query);

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
                "' . $tyyppi . '",
                "import",
                NOW()
              )';
    pupe_query($query);

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

    return $nimitys;
  }

  private function luo_toimenpide_tuote($tuoteno, $nimitys, $tyyppi, $prioriteetti) {
    $query = "INSERT INTO tuote
              SET yhtio = '{$this->kukarow['yhtio']}',
              tuoteno = '{$tuoteno}',
              nimitys = '{$nimitys}',
              try = '10',
              tuotetyyppi = 'K',
              ei_saldoa = 'o',
              laatija = 'import',
              luontiaika = NOW()";
    pupe_query($query);

    $query = 'INSERT INTO tuotteen_avainsanat
              (
                yhtio,
                tuoteno,
                kieli,
                laji,
                selite,
                selitetark,
                laatija,
                luontiaika
              )
              VALUES
              (
                "' . $this->kukarow['yhtio'] . '",
                "' . $tuoteno . '",
                "fi",
                "tyomaarayksen_ryhmittely",
                "' . $tyyppi . '",
                "' . $prioriteetti . '",
                "import",
                NOW()
              )';
    pupe_query($query);

    return $nimitys;
  }

  protected function tarkistukset() {
    $query = "SELECT laite.tunnus,
              COUNT(*) AS kpl
              FROM   laite
              JOIN tuote
              ON ( tuote.yhtio = laite.yhtio
                AND tuote.tuoteno = laite.tuoteno
                AND tuote.try IN ( '21', '23', '30', '70', '80' ) )
              JOIN huoltosyklit_laitteet
              ON ( huoltosyklit_laitteet.yhtio = laite.yhtio
                AND huoltosyklit_laitteet.laite_tunnus = laite.tunnus )
              WHERE  laite.yhtio = '{$this->kukarow['yhtio']}'
              GROUP  BY laite.tunnus
              HAVING kpl != 3";
    $result = pupe_query($query);
    $kpl = mysql_num_rows($result);
    echo "Sammuttimia joilla on enemm‰n tai v‰hemm‰n kuin 3 huoltosykli‰ liitettyn‰ {$kpl}";

    echo "<br/>";

    $query = "SELECT laite.tunnus,
              laite.tuoteno,
              hl.tunnus
              FROM laite
              LEFT JOIN huoltosyklit_laitteet AS hl
              ON ( hl.yhtio = laite.yhtio
                AND hl.laite_tunnus = laite.tunnus )
              WHERE laite.yhtio = '{$this->kukarow['yhtio']}'
              AND laite.tuoteno != 'MUISTUTUS'
              AND hl.tunnus IS NULL";
    $result = pupe_query($query);
    $laitteita_joilla_ei_huoltosyklia = mysql_num_rows($result);

    echo "Sammuttimia joilla ei ole yht‰‰n huoltosykli‰ liitettyn‰ {$laitteita_joilla_ei_huoltosyklia}";

    echo "<br/>";

    $query = "SELECT tuoteno, laji, selite, COUNT(*) AS kpl
              FROM   tuotteen_avainsanat
              WHERE yhtio = '{$this->kukarow['yhtio']}'
              GROUP  BY tuoteno, laji,selite
              HAVING kpl != 1
              ORDER  BY kpl DESC";
    $result = pupe_query($query);

    echo mysql_num_rows($result) . " tuotteella on avainsana ongelma";

    /* Tarkastus queryita, laitteet joilla ei yht‰‰n huoltosykli‰
     *
      SELECT laite.tuoteno,
      count(*) AS kpl
      FROM   laite
      LEFT JOIN huoltosyklit_laitteet AS hl
      ON ( hl.yhtio = laite.yhtio
      AND hl.laite_tunnus = laite.tunnus )
      WHERE  laite.yhtio = '{$this->kukarow['yhtio']}'
      AND laite.tuoteno != 'MUISTUTUS'
      AND hl.tunnus IS NULL
      GROUP BY laite.tuoteno
      ORDER  BY kpl DESC, laite.tuoteno ASC;

      SELECT laite.tunnus,
      laite.tuoteno,
      hl.tunnus
      FROM   laite
      LEFT JOIN huoltosyklit_laitteet AS hl
      ON ( hl.yhtio = laite.yhtio
      AND hl.laite_tunnus = laite.tunnus )
      WHERE  laite.yhtio = '{$this->kukarow['yhtio']}'
      AND laite.tuoteno != 'MUISTUTUS'
      AND hl.tunnus IS NULL
      ORDER  BY laite.tuoteno ASC;
     */

//    $this->liita_puuttuvat_huoltosyklit();
  }

  private function liita_muistutus_laitteet_kaynti_toimenpiteeseen() {
    //LaiteCSVDumper forcettaa kaikki k‰yntituotteet (eli muistutukset) MUISTUTUS tuotenumerolle
    $query = "SELECT *
              FROM laite
              WHERE yhtio = '{$this->kukarow['yhtio']}'
              AND tuoteno = 'MUISTUTUS'";
    $result = pupe_query($query);
    while ($muistutus_laite = mysql_fetch_assoc($result)) {
      $kaynti_huoltosykli = $this->kaynti_huoltosykli();

      $query = "SELECT *
                FROM huoltosyklit_laitteet AS hl
                WHERE hl.yhtio = '{$this->kukarow['yhtio']}'
                AND hl.huoltosykli_tunnus = '{$kaynti_huoltosykli['tunnus']}'
                AND hl.laite_tunnus = '{$muistutus_laite['tunnus']}'";
      $result2 = pupe_query($query);

      if (mysql_num_rows($result2) == 0) {
        echo "muistutus laite {$muistutus_laite['tunnus']} liitettiin k‰ynti toimenpiteeseen {$kaynti_huoltosykli['tunnus']}";
        echo "<br/>";
        $this->liita_huoltosykli($muistutus_laite['tunnus'], $kaynti_huoltosykli);
      }
    }
  }

  private function kaynti_huoltosykli() {
    $query = "SELECT *
              FROM huoltosykli
              WHERE yhtio = '{$this->kukarow['yhtio']}'
              AND toimenpide = 'KAYNTI'
              AND tyyppi = 'muistutus'";
    $result = pupe_query($query);

    if (mysql_num_rows($result) != 1) {
      die('Liian monta k‰ynti huoltosykli‰');
    }

    return mysql_fetch_assoc($result);
  }

  protected function liita_puuttuvat_huoltosyklit() {
    //Haetaan laitteet, joilla on v‰hemm‰n kuin 3 huoltosykli‰ enemm‰n kuin 0 liitettyn‰
    $query = "SELECT laite.tunnus,
              laite.tuoteno,
              t1.selite AS sammutin_tyyppi,
              t2.selite AS sammutin_koko,
              a.nimi AS asiakas_nimi,
              k.nimi AS kohde_nimi,
              p.olosuhde,
              COUNT(*) AS kpl
              FROM laite
              JOIN tuote AS t
              ON ( t.yhtio = laite.yhtio
                AND t.tuoteno = laite.tuoteno )
              JOIN tuotteen_avainsanat AS t1
              ON ( t1.yhtio = t.yhtio
                AND t1.tuoteno = t.tuoteno
                AND t1.laji = 'sammutin_tyyppi' )
              JOIN tuotteen_avainsanat AS t2
              ON ( t2.yhtio = t.yhtio
                AND t2.tuoteno = t.tuoteno
                AND t2.laji = 'sammutin_koko' )
              JOIN paikka AS p
              ON ( p.yhtio = laite.yhtio
                AND p.tunnus = laite.paikka )
              JOIN kohde AS k
              ON ( k.yhtio = p.yhtio
                AND k.tunnus = p.kohde )
              JOIN asiakas AS a
              ON ( a.yhtio = k.yhtio
                AND a.tunnus = k.asiakas )
              JOIN tuote
              ON ( tuote.yhtio = laite.yhtio
                AND  tuote.tuoteno = laite.tuoteno )
              JOIN huoltosyklit_laitteet
              ON ( huoltosyklit_laitteet.yhtio = laite.yhtio
                AND huoltosyklit_laitteet.laite_tunnus = laite.tunnus )
              WHERE laite.yhtio = '{$this->kukarow['yhtio']}'
              GROUP BY 1,2,3,4,5,6,7
              HAVING kpl < 3
              ORDER BY laite.tuoteno ASC";
    $result = pupe_query($query);

    while ($laite = mysql_fetch_assoc($result)) {
      $mahdolliset_huoltosyklit = hae_laitteelle_mahdolliset_huoltosyklit($laite['sammutin_tyyppi'], $laite['sammutin_koko'], $laite['olosuhde']);
      $liitetyt_huoltosyklit = hae_laitteen_huoltosyklit($laite['tunnus']);

      foreach ($mahdolliset_huoltosyklit as $mahdollinen_huoltosykli) {
        $loytyyko = search_array_key_for_value_recursive($liitetyt_huoltosyklit, 'huoltosykli_tyyppi', $mahdollinen_huoltosykli['huoltosykli_tyyppi']);

        if (empty($loytyyko)) {
          $this->liita_huoltosykli($laite['laite_tunnus'], $mahdollinen_huoltosykli);
          echo "{$laite['laite_tunnus']} liitetty huoltosykli: {$mahdollinen_huoltosykli['tunnus']} {$mahdollinen_huoltosykli['huoltosykli_tyyppi']}";
          echo "<br/>";
        }
      }
    }
  }

  protected function liita_huoltosykli($laite_tunnus, $huoltosykli) {
    $query = "INSERT INTO huoltosyklit_laitteet
              SET yhtio = '{$this->kukarow['yhtio']}',
              huoltosykli_tunnus = '{$huoltosykli['tunnus']}',
              laite_tunnus = '{$laite_tunnus}',
              huoltovali = '{$huoltosykli['huoltovali']}',
              pakollisuus = '1',
              laatija = 'import',
              luontiaika = NOW()";
    pupe_query($query);
  }
}
