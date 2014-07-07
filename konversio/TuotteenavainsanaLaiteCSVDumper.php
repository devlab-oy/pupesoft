<?php

require_once('CSVDumper.php');

class TuotteenavainsanaLaiteCSVDumper extends CSVDumper {

  protected $unique_values = array();

  public function __construct($kukarow) {
    parent::__construct($kukarow);

    $konversio_array = array(
        'tuoteno'     => 'MALLI',
        'tyyppi'      => 'TYYPPI',
        'paino'       => 'PAINO',
        'palo_luokka' => 'TOIMNRO'
    );
    $required_fields = array(
        'tuoteno',
        'tyyppi',
//      'paino', //paino ei ole sittenkään pakollinen koska palopostilla ei ole painoa
    );

    $this->setFilepath("/tmp/konversio/LAITE.csv");
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
          if (stristr($rivi[$csv_header], 'vesi')) {
            //kyseessä siis paloposti tyyppinen tuote
            $rivi_temp[$konvertoitu_header] = 'paloposti';
          }
          else {
            $rivi_temp[$konvertoitu_header] = strtolower($rivi[$csv_header] . 'sammutin');
          }
        }
        else if ($konvertoitu_header == 'paino') {
          $rivi_temp[$konvertoitu_header] = $rivi[$csv_header];
        }
        else if ($konvertoitu_header == 'tuoteno') {
          $rivi_temp[$konvertoitu_header] = str_replace(' ', '', strtoupper($rivi[$csv_header]));
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
    foreach ($rivi as $key => $value) {
      if (in_array($key, $this->required_fields) and $value == '') {
        $valid = false;
      }

      if ($key == 'tyyppi') {
        //Näiden iffien tarkoitus on filtteröidä duplikaatit, sekä ne joiden arvo on eri kuin ensimmäisen rivin.
        if ((isset($this->unique_values[$rivi['tuoteno']]['tyypit']) and in_array($value, $this->unique_values[$rivi['tuoteno']]['tyypit'])) or (isset($this->unique_values[$rivi['tuoteno']]['tyypit']) and $this->unique_values[$rivi['tuoteno']]['tyypit'][0] != $value)) {
          $valid = false;
        }
        else {
          $this->unique_values[$rivi['tuoteno']]['tyypit'][0] = $value;
        }
      }
      else if ($key == 'paino') {
        if ((isset($this->unique_values[$rivi['tuoteno']]['painot']) and in_array($value, $this->unique_values[$rivi['tuoteno']]['painot'])) or (isset($this->unique_values[$rivi['tuoteno']]['painot']) and $this->unique_values[$rivi['tuoteno']]['painot'][0] != $value)) {
          $valid = false;
        }
        else {
          $this->unique_values[$rivi['tuoteno']]['painot'][0] = $value;
        }
      }
    }

    $mahdolliset_sammutin_tyypit = hae_mahdolliset_sammutin_tyypit();
    if (!in_array($rivi['tyyppi'], array_keys($mahdolliset_sammutin_tyypit))) {
      return false;
    }

    return $valid;
  }

  protected function lisaa_pakolliset_kentat($rivi) {
    $rivi = parent::lisaa_pakolliset_kentat($rivi);
    $rivi['kieli'] = 'fi';

    return $rivi;
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

  protected function dump_data() {
    $progress_bar = new ProgressBar(t('Ajetaan rivit tietokantaan') . ' : ' . count($this->rivit));
    $progress_bar->initialize(count($this->rivit));
    foreach ($this->rivit as $rivi) {
      $query = '  INSERT INTO ' . $this->table . '
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
      pupe_query($query);

      $query = '  INSERT INTO ' . $this->table . '
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
              "' . $rivi['paino'] . '",
              "' . $rivi['laatija'] . '",
              ' . $rivi['luontiaika'] . '
            )';
      pupe_query($query);

      if (!$this->loytyyko_palo_luokka($rivi['tuoteno'])) {
        $query = '  INSERT INTO ' . $this->table . '
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
        pupe_query($query);
      }

      $progress_bar->increase();
    }
  }

  protected function tarkistukset() {
    echo "Ei tarkistuksia";
  }
}
