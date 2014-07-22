<?php

require_once('CSVDumper.php');

class TuotteenavainsanaLaite2CSVDumper extends CSVDumper {

  protected $unique_values = array();

  public function __construct($kukarow) {
    parent::__construct($kukarow);

    $konversio_array = array(
        'tuoteno'     => 'KOODI',
        'tyyppi'      => 'DATA9',
        'koko'        => 'DATA7',
        'palo_luokka' => 'DATA10',
        'tuotetyyppi' => 'RYHMA'
    );
    $required_fields = array(
        'tuoteno',
        'tyyppi',
            //'koko', koko ei ole pakollinen koska palopostilla ei ole kokoa
    );

    $this->setFilepath("/tmp/konversio/VARAOSA.csv");
    $this->setSeparator(';#x#');
    $this->setKonversioArray($konversio_array);
    $this->setRequiredFields($required_fields);
    $this->setTable('tuotteen_avainsanat');
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
          if (!stristr($rivi[$csv_header], 'sammutin') and !stristr($rivi[$csv_header], 'paloposti')) {
            $rivi_temp[$konvertoitu_header] = strtolower(trim($rivi[$csv_header]) . 'sammutin');
          }
          else {
            $rivi_temp[$konvertoitu_header] = trim(strtolower($rivi[$csv_header]));
          }
        }
        else if ($konvertoitu_header == 'koko') {
          $rivi_temp[$konvertoitu_header] = trim($rivi[$csv_header]);
        }
        else if ($konvertoitu_header == 'tuoteno') {
          $rivi_temp[$konvertoitu_header] = str_replace(' ', '', trim(strtoupper($rivi[$csv_header])));
        }
        else {
          $rivi_temp[$konvertoitu_header] = trim($rivi[$csv_header]);
        }
      }
    }

    return $rivi_temp;
  }

  protected function validoi_rivi(&$rivi, $index) {
    $valid = true;
    foreach ($rivi as $key => $value) {
      if (in_array($key, $this->required_fields) and $value == '') {
        $valid = false;
        break;
      }
    }

    if ($valid and strtoupper($rivi['tuotetyyppi']) != 'LAITE') {
      $valid = false;
    }

    $mahdolliset_sammutin_tyypit = hae_mahdolliset_sammutin_tyypit();
    if ($valid and !in_array(trim(strtolower($rivi['tyyppi'])), array_keys($mahdolliset_sammutin_tyypit))) {
      $valid = false;
      $this->errors[$index][] = t('Virheellinen tyyppi') . " {$rivi['tyyppi']}";
    }

    $laitteen_avainsanat = array();
    if ($valid) {
      $laitteen_avainsanat = $this->hae_laitteen_avainsanat($rivi['tuoteno']);
    }

    if (count($laitteen_avainsanat) > 0) {
      if ($valid and $laitteen_avainsanat['sammutin_tyyppi'] != $rivi['tyyppi']) {
        $this->errors[$index][] = t('Kannassa eri tyyppi') . ": {$laitteen_avainsanat['sammutin_tyyppi']} aineisto -> {$rivi['tyyppi']}";
      }

      if ($valid and $laitteen_avainsanat['sammutin_koko'] != $rivi['koko']) {
        $this->errors[$index][] = t('Kannassa eri koko') . ": {$laitteen_avainsanat['sammutin_koko']} aineisto -> {$rivi['koko']}";
      }
    }

    if ($valid and $laitteen_avainsanat['sammutin_tyyppi'] == $rivi['tyyppi'] and $laitteen_avainsanat['sammutin_koko'] == $rivi['koko']) {
      //Rivi on validi eli laitetaan false, koska sitä ei haluta insertöidä uudelleen kantaan
      $valid = false;
    }

    if ($valid and count($laitteen_avainsanat) == 0) {
      $this->errors[$index][] = t('Kannassa ei kokoa tai tyyppiä') . " {$rivi['koko']} {$rivi['tyyppi']} {$rivi['tuoteno']} INSERTÖIDÄÄN";
    }

    return $valid;
  }

  protected function lisaa_pakolliset_kentat($rivi) {
    $rivi = parent::lisaa_pakolliset_kentat($rivi);
    $rivi['kieli'] = 'fi';

    return $rivi;
  }

  protected function hae_laitteen_avainsanat($tuoteno) {
    $query = "SELECT laji,
              selite
              FROM tuotteen_avainsanat
              WHERE yhtio = '{$this->kukarow['yhtio']}'
              AND tuoteno = '{$tuoteno}'";
    $result = pupe_query($query);
    $avainsanat = array();
    while ($avainsana = mysql_fetch_assoc($result)) {
      $avainsanat[$avainsana['laji']] = $avainsana['selite'];
    }

    return $avainsanat;
  }

  protected function loytyyko_palo_luokka($tuoteno) {
    $query = "SELECT *
              FROM tuotteen_avainsanat
              WHERE yhtio = '{$this->kukarow['yhtio']}'
              AND tuoteno = '{$tuoteno}'
              AND laji = 'palo_luokka'";
    $result = pupe_query($query);

    if (mysql_num_rows($result) > 0) {
      return true;
    }

    return false;
  }

  private function loytyyko_tyyppi($tuoteno) {
    $query = "SELECT *
              FROM tuotteen_avainsanat
              WHERE yhtio = '{$this->kukarow['yhtio']}'
              AND tuoteno = '{$tuoteno}'
              AND laji = 'sammutin_tyyppi'";
    $result = pupe_query($query);

    if (mysql_num_rows($result) > 0) {
      return true;
    }

    return false;
  }

  private function loytyyko_koko($tuoteno) {
    $query = "SELECT *
              FROM tuotteen_avainsanat
              WHERE yhtio = '{$this->kukarow['yhtio']}'
              AND tuoteno = '{$tuoteno}'
              AND laji = 'sammutin_koko'";
    $result = pupe_query($query);

    if (mysql_num_rows($result) > 0) {
      return true;
    }

    return false;
  }

  protected function dump_data() {
    $progress_bar = new ProgressBar(t('Ajetaan rivit tietokantaan') . ' : ' . count($this->rivit));
    $progress_bar->initialize(count($this->rivit));
    foreach ($this->rivit as $rivi) {
      $query = 'INSERT INTO ' . $this->table . '
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
                  "' . $rivi['yhtio'] . '",
                  "' . $rivi['tuoteno'] . '",
                  "' . $rivi['kieli'] . '",
                  "sammutin_tyyppi",
                  "' . $rivi['tyyppi'] . '",
                  "' . $rivi['laatija'] . '",
                  ' . $rivi['luontiaika'] . '
                )';
      if (!empty($rivi['tyyppi']) and !$this->loytyyko_tyyppi($rivi['tuoteno'])) {
        pupe_query($query);
      }

      $query = 'INSERT INTO ' . $this->table . '
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
                  "' . $rivi['yhtio'] . '",
                  "' . $rivi['tuoteno'] . '",
                  "' . $rivi['kieli'] . '",
                  "sammutin_koko",
                  "' . $rivi['koko'] . '",
                  "' . $rivi['laatija'] . '",
                  ' . $rivi['luontiaika'] . '
                )';
      if (!empty($rivi['koko']) and !$this->loytyyko_koko($rivi['koko'])) {
        pupe_query($query);
      }

      $query = 'INSERT INTO ' . $this->table . '
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
                  "' . $rivi['yhtio'] . '",
                  "' . $rivi['tuoteno'] . '",
                  "' . $rivi['kieli'] . '",
                  "palo_luokka",
                  "' . $rivi['palo_luokka'] . '",
                  "' . $rivi['laatija'] . '",
                  ' . $rivi['luontiaika'] . '
                )';
      if (!empty($rivi['palo_luokka']) and !$this->loytyyko_palo_luokka($rivi['tuoteno'])) {
        pupe_query($query);
      }

      $progress_bar->increase();
    }
  }

  protected function tarkistukset() {
    //Tuotteet joilta puuttuu sammun_tyyppi
    $query = "SELECT tuote.tuoteno AS tuoteno,
              tuote.nimitys,
              t.tuoteno AS t
              FROM tuote
              LEFT JOIN tuotteen_avainsanat AS t
              ON ( t.yhtio = tuote.yhtio
                AND t.tuoteno = tuote.tuoteno
                AND t.laji = 'sammutin_tyyppi' )
              WHERE tuote.yhtio = '{$this->kukarow['yhtio']}'
              AND tuote.tuotetyyppi = ''
              AND t.tuoteno IS NULL
              ORDER BY tuote.tuoteno ASC";
    $result = pupe_query($query);
    echo "Seuraavilta tuotteilta puuttuu sammutin tyyppi (" . mysql_num_rows($result) . ")";
    echo "<br/>";
    echo "<br/>";
    while ($rivi = mysql_fetch_assoc($result)) {
      echo "{$rivi['nimitys']} - {$rivi['tuoteno']}";
      echo "<br/>";
    }

    //Tuotteet joilta puuttuu sammutin_koko
    $query = "SELECT tuote.tuoteno AS tuoteno,
              tuote.nimitys,
              t.tuoteno AS t
              FROM tuote
              LEFT JOIN tuotteen_avainsanat AS t
              ON ( t.yhtio = tuote.yhtio
                AND t.tuoteno = tuote.tuoteno
                AND t.laji = 'sammutin_koko' )
              WHERE tuote.yhtio = '{$this->kukarow['yhtio']}'
              AND tuote.tuotetyyppi = ''
              AND t.tuoteno IS NULL
              ORDER BY tuote.tuoteno ASC";
    $result = pupe_query($query);
    echo "Seuraavilta tuotteilta puuttuu sammutin koko (" . mysql_num_rows($result) . ")";
    echo "<br/>";
    echo "<br/>";
    while ($rivi = mysql_fetch_assoc($result)) {
      echo "{$rivi['nimitys']} - {$rivi['tuoteno']}";
      echo "<br/>";
    }
  }
}
