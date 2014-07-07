<?php

require_once('CSVDumper.php');

class PaikkaCSVDumper extends CSVDumper {

  protected $unique_values = array();

  public function __construct($kukarow) {
    parent::__construct($kukarow);

    $konversio_array = array(
        'kohde'      => 'SIJAINTI',
        'kohde_tark' => 'KUSTPAIKKA', //koska kohteen nimi ei ole uniikki niin paikka pitää liittää kohteeseen asiakkaan ja kohteen nimien avulla, unsettaa tämä ennen dumppia
        'nimi'       => 'LISASIJAINTI',
        'osoite'     => 'SIJAINTI',
        'kuvaus'     => 'SIJAINTI',
        'olosuhde'   => 'DATA7',
    );
    $required_fields = array(
        'kohde',
    );

    $this->setFilepath("/tmp/konversio/LAITE.csv");
    $this->setSeparator(';#x#');
    $this->setKonversioArray($konversio_array);
    $this->setRequiredFields($required_fields);
    $this->setTable('paikka');
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
        if ($konvertoitu_header == 'olosuhde') {
          if ($rivi[$csv_header] == '12') {
            $rivi_temp[$konvertoitu_header] = 'X';
          }
          else if ($rivi[$csv_header] == '24') {
            $rivi_temp[$konvertoitu_header] = 'A';
          }
          else {
            $rivi_temp[$konvertoitu_header] = 'A';
          }
        }
        else if ($konvertoitu_header == 'nimi') {
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

    return $rivi_temp;
  }

  protected function validoi_rivi(&$rivi, $index) {
    $valid = true;
    foreach ($rivi as $key => $value) {
      if ($key == 'kohde') {
        $kohde_tunnus = $this->hae_kohde_tunnus($value, $rivi['kohde_tark']);
        if ($kohde_tunnus == 0 and in_array($key, $this->required_fields)) {
          $this->errors[$index][] = t('Kohdetta') . " <b>{$value}</b> " . t('ei löydy');
          $valid = false;
        }
        else {
          $rivi[$key] = $kohde_tunnus;
        }
      }
      else {
        if (in_array($key, $this->required_fields) and $value == '') {
          $valid = false;
        }
      }
    }

    //Valitoidaan löytyykö kohteelle jo LISASIJAINTI niminen paikka
    $paikat = $this->unique_values[$rivi['kohde']];
    if (!empty($paikat)) {
      foreach ($paikat as $paikka) {
        if (trim(strtolower($paikka)) === trim(strtolower($rivi['nimi']))) {
          //kyseinen paikka on jo kohteella
          $valid = false;
          break;
        }
      }
    }
//    Jos paikka on validi niin se voidaan lisätä kohteen paikkoihin
    if ($valid) {
      $this->unique_values[$rivi['kohde']][] = $rivi['nimi'];
    }

    unset($rivi['kohde_tark']);

    return $valid;
  }

  private function hae_kohde_tunnus($kohde_nimi, $asiakas_nimi) {
    $query = 'SELECT kohde.tunnus
              FROM kohde
              JOIN asiakas
              ON ( asiakas.yhtio = kohde.yhtio
                AND asiakas.tunnus = kohde.asiakas
                AND asiakas.nimi = "' . $asiakas_nimi . '" )
              WHERE kohde.yhtio = "' . $this->kukarow['yhtio'] . '"
              AND kohde.nimi = "' . $kohde_nimi . '"
              LIMIT 1';
    $result = pupe_query($query);
    $kohderow = mysql_fetch_assoc($result);

    if (!empty($kohderow)) {
      return $kohderow['tunnus'];
    }

    //Kokeillaan hakea suoraan kohteen nimellä, jos se olisi uniikki

    $query = "SELECT kohde.tunnus
              FROM kohde
              WHERE kohde.yhtio = '{$this->kukarow['yhtio']}'
              AND kohde.nimi = '{$kohde_nimi}'";
    $result = pupe_query($query);

    if (mysql_num_rows($result) == 1) {
      $kohderow = mysql_fetch_assoc($result);

      return $kohderow['tunnus'];
    }

    return 0;
  }

  protected function tarkistukset() {
    $query = "SELECT count(*) as kpl
              FROM paikka
              WHERE yhtio = '{$this->kukarow['yhtio']}'
              AND olosuhde = ''";
    $result = pupe_query($query);
    $ilman_olosuhdetta = mysql_fetch_assoc($result);

    echo "{$ilman_olosuhdetta['kpl']} paikkaa ilman olosuhdetta!!";

    echo "<br/>";

    $query = "SELECT count(*) as kpl
              FROM paikka
              WHERE yhtio = '{$this->kukarow['yhtio']}'
              AND nimi = ''";
    $result = pupe_query($query);
    $ilman_nimea = mysql_fetch_assoc($result);

    echo "{$ilman_nimea['kpl']} paikkaa ilman nimeä!!";
  }
}
