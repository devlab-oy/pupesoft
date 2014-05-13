<?php

require_once('CSVDumper.php');

class YhteyshenkiloCSVDumper extends CSVDumper {

  protected $unique_values = array();

  public function __construct($kukarow) {
    parent::__construct($kukarow);

    $konversio_array = array(
      'tyyppi'     => 'KOODI', //tyyppi hardcodataan konvertoi_rivi funktiossa 'A'
      'nimi'       => 'YHTHENK1',
      'liitostunnus'   => 'KOODI', //Liitos tunnus kenttään mäpätään KOODI, koska sitä käytetään konvertoi_rivi funktiossa asiakas.tunnus hakuun
    );
    $required_fields = array(
      'nimi',
      'liitostunnus'
    );

    $this->setFilepath("/tmp/konversio/ASIAKAS.csv");
    $this->setSeparator(';#x#');
    $this->setKonversioArray($konversio_array);
    $this->setRequiredFields($required_fields);
    $this->setTable('yhteyshenkilo');
  }

  protected function konvertoi_rivit() {
    $progressbar = new ProgressBar(t('Konvertoidaan rivit'));
    $progressbar->initialize(count($this->rivit));

    foreach ($this->rivit as $index => &$rivi) {
      if ($rivi['YHTHENK1'] != '') {
        //Asiakas aineistossa on kahta data. Asiakkaita sekä yhteyshenkilöitä.
        //$rivi['YHTEYSHENK1'] == '' niin kyseessä on asiakas
        $rivi = $this->konvertoi_rivi($rivi);
        $rivi = $this->lisaa_pakolliset_kentat($rivi);

        //index + 2, koska eka rivi on header ja laskenta alkaa riviltä 0
        $valid = $this->validoi_rivi($rivi, $index + 2);

        if (!$valid) {
          unset($this->rivit[$index]);
        }
      }
      else {
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
          $rivi_temp[$konvertoitu_header] = 'A';
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
      if ($key == 'liitostunnus') {
        $asiakas_tunnus = $this->hae_asiakas_tunnus($value);
        if ($asiakas_tunnus == 0 and in_array($key, $this->required_fields)) {
          $this->errors[$index][] = t('Asiakasta')." {$value} ".t('ei löydy');
          $valid = false;
        }
        else {
          $rivi[$key] = $asiakas_tunnus;
        }
      }
      else {
        if (in_array($key, $this->required_fields) and $value == '') {
          $valid = false;
        }
      }
    }

    return $valid;
  }

  private function hae_asiakas_tunnus($asiakasnro) {
    $query = "  SELECT tunnus
          FROM asiakas
          WHERE yhtio = '{$this->kukarow['yhtio']}'
          AND asiakasnro = '{$asiakasnro}'";
    $result = pupe_query($query);
    $asiakasrow = mysql_fetch_assoc($result);

    if (!empty($asiakasrow)) {
      return $asiakasrow['tunnus'];
    }

    return 0;
  }

  protected function tarkistukset() {
    echo "Ei tarkistuksia";
  }

}
