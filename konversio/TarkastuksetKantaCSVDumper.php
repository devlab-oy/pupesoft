<?php

class TarkastuksetKantaCSVDumper extends CSVDumper {

  protected $unique_values = array();

  public function __construct($kukarow) {
    parent::__construct($kukarow);

    $konversio_array = array(
      'LAITE'     => 'LAITE',
      'TARKASTUS'   => 'TARKASTUS',
      'HUOLTO'   => 'HUOLTO',
      'TAYTTO'   => 'TAYTTO',
      'KOESTUS'   => 'KOESTUS',
      'TUOTENRO'   => 'TUOTENRO',
      'NIMIKE'   => 'NIMIKE',
      'LAATU'     => 'LAATU',
      'VIKATYYPPI' => 'VIKATYYPPI',
      'VIANPAIKKA' => 'VIANPAIKKA',
      'KPL'     => 'KPL',
      'HINTA'     => 'HINTA',
      'ALE'     => 'ALE',
      'HUOM'     => 'HUOM',
      'ED'     => 'ED',
      'SEUR'     => 'SEUR',
      'STATUS'   => 'STATUS',
      'TEKIJA'   => 'TEKIJA',
      'VALI'     => 'VALI',
      'DATA1'     => 'DATA1',
      'DATA2'     => 'DATA2',
      'DATA3'     => 'DATA3',
      'DATA4'     => 'DATA4',
      'DATA5'     => 'DATA5',
      'ALOITUS'   => 'ALOITUS',
      'ID'     => 'ID',
    );
    $required_fields = array(
      'ID',
    );

    $this->setFilepath("/tmp/konversio/TARKASTUKSET.CSV");
    $this->setSeparator(';#x#');
    $this->setKonversioArray($konversio_array);
    $this->setRequiredFields($required_fields);
    $this->setTable('tarkastukset');
  }

  protected function konvertoi_rivit() {
    $progressbar = new ProgressBar(t('Konvertoidaan rivit'));
    $progressbar->initialize(count($this->rivit));

    foreach ($this->rivit as $index => &$rivi) {
//      $rivi = $this->konvertoi_rivi($rivi);
//      $rivi = $this->lisaa_pakolliset_kentat($rivi);

      //index + 2, koska eka rivi on header ja laskenta alkaa riviltä 0
//      $valid = $this->validoi_rivi($rivi, $index + 2);

//      if (!$valid) {
//        unset($this->rivit[$index]);
//      }

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

    return $valid;
  }

  protected function lisaa_pakolliset_kentat($rivi) {
    $rivi = parent::lisaa_pakolliset_kentat($rivi);
    $rivi['kieli'] = 'fi';

    return $rivi;
  }

  protected function tarkistukset() {
    echo "Ei tarkistuksia";
  }

}
