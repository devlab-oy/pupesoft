<?php

class TuotteenavainsanaToimenpideCSVDumper extends CSVDumper{

  protected $unique_values = array();

  public function __construct($kukarow) {
    parent::__construct($kukarow);

    $konversio_array = array(
      'tuoteno'   => 'KOODI',
      'selite'   => 'DATA1',
      'try'     => 'RYHMA',
    );
    $required_fields = array(
      'tuoteno',
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
        if ($konvertoitu_header == 'selite') {
          if ($rivi[$csv_header] == '3' or $rivi[$csv_header] == 'tarkastus') {
            $rivi_temp[$konvertoitu_header] = 'tarkastus';
            $rivi_temp['selitetark'] = 3;
          }
          else if ($rivi[$csv_header] == '2' or $rivi[$csv_header] == 'huolto') {
            $rivi_temp[$konvertoitu_header] = 'huolto';
            $rivi_temp['selitetark'] = 2;
          }
          else if ($rivi[$csv_header] == '1' or $rivi[$csv_header] == 'koeponnistus') {
            $rivi_temp[$konvertoitu_header] = 'koeponnistus';
            $rivi_temp['selitetark'] = 1;
          }
          else {
            $rivi_temp[$konvertoitu_header] = '';
          }
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
        $this->errors[$index][] = t('Pakollinen kenttä')." <b>{$key}</b> ".t('puuttuu');
        $valid = false;
      }

      if ($key == 'selite' and empty($value)) {
        $valid = false;
      }
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
              "'.$rivi['yhtio'].'",
              "'.$rivi['tuoteno'].'",
              "'.$rivi['kieli'].'",
              "tyomaarayksen_ryhmittely",
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
    $query = "  SELECT tuote.tuoteno,
          tuote.nimitys,
          t.tuoteno
          FROM   tuote
          LEFT JOIN tuotteen_avainsanat AS t
          ON ( t.yhtio = tuote.yhtio
            AND t.tuoteno = tuote.tuoteno
            AND t.laji = 'tyomaarayksen_ryhmittely' )
          WHERE tuote.yhtio = '{$this->kukarow['yhtio']}'
          AND tuote.tuotetyyppi = 'K'
          AND t.tuoteno IS NULL
          ORDER BY tuote.tuoteno ASC;";
    $result = pupe_query($query);
    echo "Seuraavilta tuotteilta puuttuu tyomaarayksen ryhmittely (".mysql_num_rows($result).")";
    echo "<br/>";
    echo "<br/>";
    while($rivi = mysql_fetch_assoc($result)) {
      echo "{$rivi['nimitys']} - {$rivi['tuoteno']}";
      echo "<br/>";
    }
  }

}
