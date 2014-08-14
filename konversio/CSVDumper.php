<?php

require_once 'inc/ProgressBar.class.php';

abstract class CSVDumper {

  protected $filepath = '';
  protected $separator = '';
  protected $konversio_array = array();
  protected $required_fields = array();
  protected $table = '';
  protected $rivit = array();
  protected $kukarow = array();
  protected $errors = array();
  protected $folder = '';
  protected $error_folder = '';
  protected $valmis_folder = '';
  protected $mandatory_fields = array();
  private $csv_is_assoc = null;

  public function __construct(array &$kukarow, $csv_is_assoc = true) {
    $kukarow['kesken'] = 0;
    $this->kukarow = &$kukarow;

    $this->mandatory_fields = array(
      'yhtio'     => $this->kukarow['yhtio'],
      'laatija'   => 'import',
      'luontiaika' => 'now()',
    );

    if (!is_bool($csv_is_assoc)) {
      throw new Exception('csv_is_assoc pitää olla tyyppiä boolean');
    }

    $this->csv_is_assoc = $csv_is_assoc;
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

  protected function addMandatoryFields($mandatory_fields) {
    if (!empty($this->mandatory_fields)) {
      $this->mandatory_fields = array_merge($this->mandatory_fields, $mandatory_fields);
    }
    else {
      $this->mandatory_fields = $mandatory_fields;
    }
  }

  protected function setFolder($folder) {
    $this->folder = $folder;
  }

  protected function setErrorFolder($error_folder) {
    $this->error_folder = $error_folder;
  }

  protected function setValmisFolder($valmis_folder) {
    $this->valmis_folder = $valmis_folder;
  }

  public function aja() {
    if (!empty($this->folder)) {
      $this->tarkista(true);
      $tiedostot = $this->lue_tiedostot($this->folder);
      if (!empty($tiedostot)) {
        foreach ($tiedostot as $tiedosto_polku) {
          if (file_exists($tiedosto_polku)) {
            $this->kasittele_tiedosto($tiedosto_polku);

            //jos halutaan jonkun sortin error handläys niin uncomment this
            //            if (!empty($this->errors)) {
            //              $this->siirra_tiedosto_kansioon($tiedosto_polku, $this->error_folder);
            //              throw new Exception('Aineistossa oli virheitä');
            //            }

            $this->siirra_tiedosto_kansioon($tiedosto_polku, $this->valmis_folder);
          }
          else {
            throw new Exception('Tiedosto ei ole olemassa');
          }

          $this->errors = array();
        }
      }
      else {
        throw new Exception('Yhtään tiedostoa ei löytynyt');
      }
    }
    else {
      $this->tarkista(false);
      $this->kasittele_tiedosto($this->filepath);
    }
  }

  private function kasittele_tiedosto($filepath) {
    if ($this->csv_is_assoc) {
      $this->lue_csv_tiedosto_assoc($filepath);
    }
    else {
      $this->lue_csv_tiedosto_not_assoc($filepath);
    }

    $this->konvertoi_rivit();

    echo "<br/>";

    $this->dump_data();

    echo "<br/>";

    if (empty($this->errors)) {
      echo t('Kaikki ok ajetaan data kantaan');
      echo "<br/>";
    }
    else {
      foreach ($this->errors as $rivinumero => $row_errors) {
        echo t('Rivillä')." {$rivinumero} ".t('oli seuraavat virheet').":";
        echo "<br/>";
        foreach ($row_errors as $row_error) {
          echo $row_error;
          echo "<br/>";
        }
        echo "<br/>";
      }
    }
  }

  private function tarkista($onko_folder) {

    if ($this->separator == '') {
      throw new Exception('Separator on tyhjä');
    }

    if ($this->table == '') {
      throw new Exception('Table on tyhjä');
    }

    if ($this->konversio_array == '') {
      throw new Exception('Konversio_array on tyhjä');
    }

    if ($onko_folder) {
      if ($this->folder == '') {
        throw new Exception('Folder on tyhjä');
      }

      if ($this->error_folder == '') {
        throw new Exception('Error folder on tyhjä');
      }

      if ($this->valmis_folder == '') {
        throw new Exception('Valmis folder on tyhjä');
      }
    }
    else {
      if ($this->filepath == '') {
        throw new Exception('Filepath on tyhjä');
      }
    }
  }

  protected function lue_csv_tiedosto_assoc($filepath) {
    $csv_headerit = $this->lue_csv_tiedoston_otsikot($filepath);
    $file = fopen($filepath, "r") or die("Ei aukea!\n");

    $rivit = array();
    $i = 1;
    while ($rivi = fgets($file)) {
      if ($i == 1) {
        $i++;
        continue;
      }

      $rivi = explode($this->separator, $rivi);
      $rivi = $this->to_assoc($rivi, $csv_headerit);

      $rivit[] = $rivi;

      $i++;
    }

    fclose($file);

    $this->rivit = $rivit;
  }

  private function to_assoc($rivi, $csv_headerit) {
    $rivi_temp = array();
    foreach ($rivi as $index => $value) {
      $rivi_temp[strtoupper($csv_headerit[$index])] = $value;
    }

    return $rivi_temp;
  }

  private function lue_csv_tiedoston_otsikot($filepath) {
    $file = fopen($filepath, "r") or die("Ei aukea!\n");
    $header_rivi = fgets($file);
    if ($this->onko_tiedosto_utf8_bom($header_rivi)) {
      $header_rivi = substr($header_rivi, 3);
    }
    $header_rivi = explode($this->separator, $header_rivi);
    fclose($file);

    return $header_rivi;
  }

  protected function lue_csv_tiedosto_not_assoc($filepath) {
    $file = fopen($filepath, "r") or die("Ei aukea!\n");

    $rivit = array();
    while ($rivi = fgets($file)) {
      $rivi = explode($this->separator, $rivi);
      $rivit[] = $rivi;
    }

    fclose($file);

    $this->rivit = $rivit;
  }

  private function onko_tiedosto_utf8_bom($str) {
    $bom = pack("CCC", 0xef, 0xbb, 0xbf);
    if (0 == strncmp($str, $bom, 3)) {
      return true;
    }
    return false;
  }

  protected function dump_data() {
    $progress_bar = new ProgressBar(t('Ajetaan rivit tietokantaan'));
    $progress_bar->initialize(count($this->rivit));
    foreach ($this->rivit as $rivi) {
      $query = '  INSERT INTO '.$this->table.'
          ('.implode(", ", array_keys($rivi)).')
          VALUES
          ("'.implode('", "', array_values($rivi)).'")';

      pupe_query($query);
      $progress_bar->increase();
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

  private function lue_tiedostot($polku) {
    $tiedostot = array();
    $handle = opendir($polku);
    if ($handle) {
      while (false !== ($tiedosto = readdir($handle))) {
        if ($tiedosto != "." && $tiedosto != "..") {
          $tiedosto_osat = explode('.', $tiedosto);
          if (is_file($polku.$tiedosto) and $tiedosto_osat[count($tiedosto_osat) - 1] == 'csv') {
            $tiedostot[] = $polku.$tiedosto;
          }
        }
      }
      closedir($handle);
    }

    return $tiedostot;
  }

  private function siirra_tiedosto_kansioon($tiedosto_polku, $kansio) {
    $tiedosto_array = explode('.', $tiedosto_polku);
    if (!empty($tiedosto_array)) {
      $tiedosto_array2 = explode('/', $tiedosto_array[0]);
      $hakemiston_syvyys = count($tiedosto_array2);

      $uusi_filename = $tiedosto_array2[$hakemiston_syvyys - 1].'_'.date('YmdHis').'.'.$tiedosto_array[1];
    }

    //    exec('cp "'.$tiedosto_polku.'" "'.$kansio.$uusi_filename.'"');
    //    unlink($tiedosto_polku);
  }

  abstract protected function konvertoi_rivit();

  abstract protected function konvertoi_rivi($rivi);

  abstract protected function validoi_rivi(&$rivi, $index);
}
