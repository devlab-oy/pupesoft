<?php

class TuoteryhmaCSVDumper extends CSVDumper {

  protected $unique_values = array();

  public function __construct($kukarow) {
    parent::__construct($kukarow);

    $konversio_array = array(
      'selite'   => 'LUOKKA',
    );
    $required_fields = array(
      'selite',
    );

    $this->setFilepath("/tmp/konversio/VARAOSA.csv");
    $this->setSeparator(';#x#');
    $this->setKonversioArray($konversio_array);
    $this->setRequiredFields($required_fields);
    $this->setTable('avainsana');
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
        if ($konvertoitu_header == 'selite') {
          $rivi_temp[$konvertoitu_header] = substr($rivi[$csv_header], 0, 2);
          $selitetark = $rivi[$csv_header];
          $rivi_temp['selitetark'] = preg_replace("/[^a-zA-ZäÄöÖåÅ]/", "", $selitetark);
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
        //Skipataan insert
        $valid = false;
      }

      if ($key == 'selite' and empty($value)) {
        $valid = false;
      }
    }

    if (!in_array($rivi['selite'], $this->unique_values)) {
      $this->unique_values[] = $rivi['selite'];
    }
    else {
      //Skipataan insert jos try on jo konvertoitu ennen tätä riviä
      $valid = false;
    }

    return $valid;
  }

  protected function lisaa_pakolliset_kentat($rivi) {
    $rivi = parent::lisaa_pakolliset_kentat($rivi);
    $rivi['kieli'] = 'fi';

    return $rivi;
  }

  protected function dump_data() {
    $progress_bar = new ProgressBar(t('Ajetaan rivit tietokantaan').' : '.count($this->rivit));
    $progress_bar->initialize(count($this->rivit));
    foreach ($this->rivit as $rivi) {
      $query = '  INSERT INTO '.$this->table.'
            (
              yhtio,
              kieli,
              laji,
              selite,
              selitetark,
              laatija,
              luontiaika
            )
            VALUES
            (
              "'.$rivi['yhtio'].'",
              "'.$rivi['kieli'].'",
              "TRY",
              "'.$rivi['selite'].'",
              "'.$rivi['selitetark'].'",
              "'.$rivi['laatija'].'",
              '.$rivi['luontiaika'].'
            )';
      pupe_query($query);

      $progress_bar->increase();
    }
  }

  protected function tarkistukset() {
    echo "Ei tarkistuksia";
  }

}
