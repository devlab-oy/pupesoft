<?php

$filepath = dirname(__FILE__);
require_once("{$filepath}/../inc/ProgressBar.class.php");

abstract class CSVDumper {

  protected $filepath = "";
  protected $separator = "";
  protected $konversio_array = array();
  protected $required_fields = array();
  protected $table = "";
  protected $rivit = array();
  protected $kukarow = array();
  protected $errors = array();
  protected $column_count = null;
  protected $is_proggressbar_on = true;
  private $mandatory_fields = array();

  public function __construct($kukarow) {
    $this->kukarow = $kukarow;

    $this->mandatory_fields = array(
      'yhtio'     => $this->kukarow['yhtio'],
      'laatija'   => 'import',
      'luontiaika' => 'now()',
    );
  }

  protected function setKonversioArray($konversio_array) {
    $this->konversio_array = $konversio_array;
  }

  protected function setRequiredFields($required_fields) {
    $this->required_fields = $required_fields;
  }

  protected function setTable($table) {
    $this->table = $table;
  }

  protected function setKukaRow($kukarow) {
    $this->kukarow = $kukarow;
  }

  protected function setMandatoryFields($mandatory_fields) {
    $this->mandatory_fields = $mandatory_fields;
  }

  protected function setFilepath($filepath) {
    $this->filepath = $filepath;
  }

  protected function setSeparator($separator) {
    $this->separator = $separator;
  }

  protected function getErrors() {
    return $this->errors;
  }

  protected function setColumnCount($column_count) {
    $this->column_count = $column_count;
  }

  protected function setProggressBar($is_on) {
    $this->is_proggressbar_on = $is_on;
  }

  public function aja() {
    if ($this->filepath == '') {
      throw new Exception('Filepath on tyhjä');
    }

    if ($this->separator == '') {
      throw new Exception('Separator on tyhjä');
    }

    if ($this->table == '') {
      throw new Exception('Table on tyhjä');
    }

    if ($this->konversio_array == '') {
      throw new Exception('Konversio_array on tyhjä');
    }

    if (empty($this->kukarow)) {
      throw new Exception('Kukarow on tyhjä');
    }

    $this->lue_csv_tiedosto();

    $this->konvertoi_rivit();

//    echo "<br/>";

    $this->dump_data();

//    echo "<br/>";
//
//    if (empty($this->errors)) {
//      echo t('Kaikki ok ajetaan data kantaan');
//      echo "<br/>";
//    }
//    else {
//      echo t('Virheiden lukumäärä').': '.count($this->errors);
//      echo "<br/>";
//      foreach ($this->errors as $rivinumero => $row_errors) {
//        echo t('Rivillä')." {$rivinumero} ".t('oli seuraavat virheet').":";
//        echo "<br/>";
//        foreach ($row_errors as $row_error) {
//          echo $row_error;
//          echo "<br/>";
//        }
//        echo "<br/>";
//      }
//    }
//
//    echo "<br/>";

    $this->tarkistukset();
  }

  protected function lue_csv_tiedosto() {
//    if ($this->is_proggressbar_on) {
//      $number_of_lines = intval(exec("wc -l '{$this->filepath}'"));
//      $progress_bar = new ProgressBar(t('Luetaan rivit'));
//      $progress_bar->initialize(count($number_of_lines));
//    }

    $csv_headerit = $this->lue_csv_tiedoston_otsikot();
    $file = fopen($this->filepath, "r") or die("Ei aukea!\n");

    $rivit = array();
    $i = 1;
    while ($rivi = fgets($file)) {
      if ($i == 1) {
        $i++;
        continue;
      }

      $rivi = explode($this->separator, $rivi);

      if (!empty($this->column_count) and count($rivi) != $this->column_count) {
        $i++;
        echo "Rivi: {$i} Aineisto viallinen riviä ei konvertoida <br/>";
        if ($this->is_proggressbar_on) {
//          $progress_bar->increase();
        }
        continue;
      }

      $rivi = $this->to_assoc($rivi, $csv_headerit);

      array_walk($rivi, array($this, 'escape_single_quotes'));
      array_walk($rivi, 'trim');

      $rivit[] = $rivi;

      $i++;

//      if ($this->is_proggressbar_on and $progress_bar instanceof ProgressBar) {
//        $progress_bar->increase();
//      }
    }

    fclose($file);

    $this->rivit = $rivit;

    echo "<br/>";
  }

  private function to_assoc($rivi, $csv_headerit) {
    $rivi_temp = array();
    foreach ($rivi as $index => $value) {
      $rivi_temp[trim(strtoupper($csv_headerit[$index]))] = $value;
    }

    return $rivi_temp;
  }

  private function lue_csv_tiedoston_otsikot() {
    $file = fopen($this->filepath, "r") or die("Ei aukea!\n");
    $header_rivi = fgets($file);
    if ($this->onko_tiedosto_utf8_bom($header_rivi)) {
      $header_rivi = substr($header_rivi, 3);
    }
    $header_rivi = explode($this->separator, $header_rivi);
    fclose($file);

    array_walk($header_rivi, 'trim');

    return $header_rivi;
  }

  private function onko_tiedosto_utf8_bom($str) {
    $bom = pack("CCC", 0xef, 0xbb, 0xbf);
    if (0 == strncmp($str, $bom, 3)) {
      return true;
    }
    return false;
  }

  protected function dump_data() {
//    if ($this->is_proggressbar_on) {
//      $progress_bar = new ProgressBar(t('Ajetaan rivit tietokantaan').' : '.count($this->rivit));
//      $progress_bar->initialize(count($this->rivit));
//    }
    foreach ($this->rivit as $rivi) {
      $query = "INSERT INTO {$this->table}
                (".implode(", ", array_keys($rivi)).")
                VALUES
                ('".implode("', '", array_values($rivi))."')";

      //Purkka fix
      $query = str_replace("'now()'", 'now()', $query);
      pupe_query($query);

//      if ($this->is_proggressbar_on and $progress_bar instanceof ProgressBar) {
//        $progress_bar->increase();
//      }
    }
  }

  protected function decode_to_utf8($rivi) {
    foreach ($rivi as $header => &$value) {
      $value = utf8_decode($value);
    }

    return $rivi;
  }

  protected function lisaa_pakolliset_kentat($rivi) {
    foreach ($this->mandatory_fields as $header => $pakollinen_kentta) {
      $rivi[$header] = $pakollinen_kentta;
    }

    return $rivi;
  }

  private function escape_single_quotes(&$item, $key) {
    $item = str_replace("'", "\'", $item);
  }

  private function decode_utf8(&$item, $key) {
    $item = utf8_decode($item);
  }

  protected function all_required_keys_found($rivi) {
    if (count(array_intersect_key(array_flip($this->required_fields), $rivi)) !== count($this->required_fields)) {
      return false;
    }

    return true;
  }

  abstract protected function konvertoi_rivit();

  abstract protected function konvertoi_rivi($rivi);

  abstract protected function validoi_rivi(&$rivi, $index);

  abstract protected function tarkistukset();
}
