<?php

require_once('CSVDumper.php');

class KohdeCSVDumper extends CSVDumper {

  protected $unique_values = array();

  public function __construct($kukarow) {
    parent::__construct($kukarow);

    $konversio_array = array(
        'asiakas'      => 'ASIAKAS',
        'asiakas_nimi' => 'NIMI', //tämä tarvitaan, jos kohteen asiakasta ei löydy. Tällöin asiakas pitää perustaa. Asiakkaan nimi pitää olla uudella asiakkaalla, koska muuten paikka import ei löydä kohdetta. Unsettaa tämä ennen dumppia
        'nimi'         => 'LINJA',
        'nimitark'     => 'KOODI',
        'osoite'       => 'KATUOS',
        'postitp'      => 'POSTIOS',
        'postino'      => 'LISATIETO',
        'puhelin'      => 'PUHELIN2',
        'fax'          => 'FAX',
        'email'        => 'LISATIETO2',
        'yhteyshlo'    => 'YHTHENK2',
        'kommentti'    => 'PUHELIN1',
    );
    $required_fields = array(
        'asiakas',
        'asiakas_nimi',
        'nimi',
    );

    $this->setFilepath("/tmp/konversio/LINJA.csv");
    $this->setSeparator(';#x#');
    $this->setKonversioArray($konversio_array);
    $this->setRequiredFields($required_fields);
    $this->setTable('kohde');
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
      if ($key == 'asiakas') {
        $asiakas_tunnus = $this->hae_asiakas_tunnus($value);
        if ($asiakas_tunnus == 0 and in_array($key, $this->required_fields)) {
          if (!in_array($value, $this->unique_values) and is_numeric($value)) {
            $this->unique_values[] = $value;
            $asiakas_tunnus = $this->luo_asiakas($value, $rivi['asiakas_nimi']);
          }
          $rivi[$key] = $asiakas_tunnus;
        }
        else {
          $rivi[$key] = $asiakas_tunnus;
        }
      }
      else {
        if (in_array($key, $this->required_fields) and $value == '') {
          $this->errors[$index][] = t('Asiakasta') . " <b>{$rivi['asiakas']} - {$rivi['asiakas_nimi']} - {$rivi['nimi']}</b> " . t('ei löydy');
          $valid = false;
        }
      }
    }

    if (!$this->all_required_keys_found($rivi)) {
      $this->errors[$index][] = t('Epävalidi rivi') . " <b>{$rivi['asiakas']} - {$rivi['asiakas_nimi']} - {$rivi['nimi']}</b> ";
      $valid = false;
    }

    unset($rivi['asiakas_nimi']);

    return $valid;
  }

  private function hae_asiakas_tunnus($asiakasnro) {
    $query = "SELECT tunnus
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

  private function luo_asiakas($asiakasnumero, $asiakas_nimi) {
    $query = "INSERT INTO asiakas
              SET ytunnus = '',
              nimi = '{$asiakas_nimi}',
              asiakasnro = '{$asiakasnumero}',
              toimitustapa = 'Nouto',
              maa = 'FI',
              laatija = 'import',
              luontiaika = NOW(),
              yhtio = '{$this->kukarow['yhtio']}'";
    pupe_query($query);

    return mysql_insert_id();
  }

  protected function tarkistukset() {
    echo "Ei tarkistuksia";
  }
}
