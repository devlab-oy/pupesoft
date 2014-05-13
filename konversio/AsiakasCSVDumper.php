<?php

require_once('CSVDumper.php');

class AsiakasCSVDumper extends CSVDumper {

  protected $unique_values = array();

  public function __construct($kukarow) {
    parent::__construct($kukarow);

    $konversio_array = array(
      'asiakasnro'     => 'KOODI',
      'nimi'         => 'NIMI',
      'osoite'       => 'KATUOS',
      'postitp'       => 'POSTIOS',
      'postino'       => 'LISATIETO',
      'puhelin'       => 'PUHELIN',
      'myynti_kommentti1'   => 'PUHELIN1',
      'kuljetusohje'     => 'PUHELIN2',
    );
    $required_fields = array(
      'asiakasnro',
      'nimi',
    );

    $this->setFilepath("/tmp/konversio/ASIAKAS.csv");
    $this->setSeparator(';#x#');
    $this->setKonversioArray($konversio_array);
    $this->setRequiredFields($required_fields);
    $this->setTable('asiakas');
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
        $rivi_temp[$konvertoitu_header] = $rivi[$csv_header];
      }
    }

    return $rivi_temp;
  }

  protected function validoi_rivi(&$rivi, $index) {
    $valid = true;
    foreach ($rivi as $key => $value) {
      if (in_array($key, $this->required_fields) and $value == '') {
        $this->errors[$index][] = t('Pakollinen kenttä')." <b>{$key}</b> ".t('puuttuu');
        $valid = false;
      }
    }

    if (!in_array($rivi['asiakasnro'], $this->unique_values)) {
      $this->unique_values[] = $rivi['asiakasnro'];
    }
    else {
      $this->errors[$index][] = t('Uniikki kenttä asiakasnro')." <b>{$rivi['asiakasnro']}</b> ".t('löytyy jo aineistosta');
      $valid = false;
    }

    return $valid;
  }

  protected function lisaa_pakolliset_kentat($rivi) {
    $rivi = parent::lisaa_pakolliset_kentat($rivi);
    $rivi['maa'] = 'FI';
    $rivi['toimitustapa'] = 'Nouto';

    return $rivi;
  }

  protected function tarkistukset() {
    echo "Ei tarkistuksia";
  }

}
