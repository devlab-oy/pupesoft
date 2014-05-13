<?php

require ("../inc/parametrit.inc");
require_once('CSVDumper.php');

class TuoteCSVDumper extends CSVDumper {

  protected $unique_values = array();

  public function __construct($kukarow) {
    parent::__construct($kukarow);

    $konversio_array = array(
      'tuoteno'     => 'KOODI',
      'myyntihinta'   => 'HINTA',
    );
    $required_fields = array(
    );

    $this->setFilepath("/tmp/konversio/VARAOSA.csv");
    $this->setSeparator(';#x#');
    $this->setKonversioArray($konversio_array);
    $this->setRequiredFields($required_fields);
    $this->setTable('tuote');
  }
  protected function konvertoi_rivit() {
    $progressbar = new ProgressBar(t('Konvertoidaan rivit'));
    $progressbar->initialize(count($this->rivit));

    foreach ($this->rivit as $index => &$rivi) {
      $rivi   = $this->konvertoi_rivi($rivi);
      $rivi   = $this->lisaa_pakolliset_kentat($rivi);

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
        if ($konvertoitu_header == 'tuoteno') {
          $rivi_temp[$konvertoitu_header] = str_replace(' ', '', strtoupper($rivi[$csv_header]));
        }
        else if ($konvertoitu_header == 'myyntihinta') {
          $rivi_temp[$konvertoitu_header] = str_replace(',', '.', strtoupper($rivi[$csv_header]));
        }
        else {
          $rivi_temp[$konvertoitu_header] = $rivi[$csv_header];
        }
      }
    }

    return $rivi_temp;
  }
  protected function validoi_rivi(&$rivi, $index) {
    return true;
  }
  protected function tarkistukset() {
    
  }
  protected function dump_data() {
    $paivitetty_kpl = 0;
    foreach ($this->rivit as $rivi) {
      $tuote = hae_tuote($rivi['tuoteno']);
      if (!empty($tuote)) {
        $query = "  UPDATE tuote
              SET myyntihinta = '{$rivi['myyntihinta']}'
              WHERE yhtio = '{$this->kukarow['yhtio']}'
              AND tuoteno = '{$rivi['tuoteno']}'";
        pupe_query($query);
        $paivitetty_kpl++;
        echo "Paivitettiin {$rivi['tuoteno']} {$rivi['myyntihinta']} {$paivitetty_kpl} kpl hinnasta {$tuote['myyntihinta']}<br/>";
      }
    }
  }
}

$dumper = new TuoteCSVDumper($kukarow);
$dumper->aja();
