<?php
// Kutsutaanko CLI:stä
if (php_sapi_name() != 'cli') {
  die("Tätä scriptiä voi ajaa vain komentoriviltä!");
}

if (!isset($argv[1]) || !$argv[1]) {
  echo "Anna yhtio";
  exit;
}

date_default_timezone_set('Europe/Helsinki');

require "inc/connect.inc";
require "inc/functions.inc";

// ytiorow. Jos ei l?ydy, lopeta cron¨
$yhtiorow = hae_yhtion_parametrit(pupesoft_cleanstring($argv[1]));
if (!$yhtiorow) {
  echo "Vaara yhtio";
  exit;
}

// Logitetaan ajo
cron_log();

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('memory_limit', '512M');

/*
  Main class
*/
class ImportSaldoHinta
{


  /*
    Laitetaan kaikki muuttujat kuntoon
  */
  public function __construct($yhtiorow)
  {
    $php_cli = true;
    $impsaloh_csv_cron = true;
    $impsaloh_csv_cron_dirname = realpath('datain/saldo_ostohinta_import');

    $this->kukarow = hae_kukarow('admin', $yhtiorow['yhtio']);
    $this->yhtiorow  = $yhtiorow;

    $this->impsaloh_csv_cron_tiedot = array(
      "yhtiorow" => $yhtiorow,
      "kukarow" => $this->kukarow,
      "yhtio" => $yhtiorow['yhtio']
    );

    $this->yhtio = $yhtiorow['yhtio'];

    $this->impsaloh_polku_in     = $impsaloh_csv_cron_dirname;
    $this->impsaloh_polku_ok     = $impsaloh_csv_cron_dirname."/ok";
    $this->impsaloh_polku_orig   = $impsaloh_csv_cron_dirname."/orig";
    $this->impsaloh_polku_orig_stocks   = $impsaloh_csv_cron_dirname."/orig/stocks";
    $this->impsaloh_polku_orig_prices   = $impsaloh_csv_cron_dirname."/orig/prices";
    $this->impsaloh_polku_error  = $impsaloh_csv_cron_dirname."/error";
    
    $this->impsaloh_csv_files = scandir($this->impsaloh_polku_in);
    $this->ftp_exclude_files = array_diff(scandir($this->impsaloh_polku_orig), array('..', '.', '.DS_Store'));

    /*
      Otsikot etsitään tiedostossa.
      $tuotekoodi_otsikot rakenne on: stocks prices tiedoston otsikko => stocks tiedoston otsikko / prices hinta
    */
    $this->tuotekoodi_otsikot = array(
      "Product code" =>
        array(
          "tuotekoodi" => "Item No",
          "hinta" => "Mercantile Price"
        )
    );

    $this->eankoodi_otsikot = array("GTIN");

    $this->toimittajat_tiedostot = array(
      "kavoparts.csv" => "1474",
      "60046_ce.csv" => "1432"
    );

    $this->yksittaiset_tiedostot = array(
      "60046_ce.csv" => array(
        array(0,4,3),
        array(0,9)
      )
    );

    // Montako kolumneja on stocks tiedostoissa - näin ohejlma tunnistaa sen.
    $this->prices_tiedoston_kolumneja_max = array(
      "kavoparts.csv" => "4",
      "60046_ce.csv" => "1"
    );
  }

  public function jakaa_yksittaiset_tiedostot() {
    foreach ($this->yksittaiset_tiedostot as $tiedostonimi => $tiedostokolumnit) {
      $input = $this->impsaloh_polku_in."/".$tiedostonimi;
      $output = $this->impsaloh_polku_in."/"."prices_".$tiedostonimi;
      if(file_exists($output)) {
        continue;
      }
      $output2 = $this->impsaloh_polku_in."/uusi_".$tiedostonimi;
      if (false !== ($ih = fopen($input, 'r'))) {
        $oh = fopen($output, 'w');
        $oh2 = fopen($output2, 'w');
        $i=0;
        while (false !== ($data = fgetcsv($ih, 0, ";"))) {
          if ($data[$tiedostokolumnit[0][2]] == "-" or $data[$tiedostokolumnit[0][2]] == "") {
            $data[$tiedostokolumnit[0][2]] = 0;
          }
          if ($i==0) {
            $outputData = array('Product code', 'Mercantile Price', '');
            fputcsv($oh, $outputData);
            $outputData2 = array('Item No', 'GTIN');
            fputcsv($oh2, $outputData2);
          }
          $outputData = array($data[$tiedostokolumnit[0][0]], $data[$tiedostokolumnit[0][1]], preg_replace("/[^0-9 ]/", '', $data[$tiedostokolumnit[0][2]]));
          fputcsv($oh, $outputData);
          $outputData2 = array($data[$tiedostokolumnit[1][0]], $data[$tiedostokolumnit[1][1]]);
          fputcsv($oh2, $outputData2);
          $i++;
        }
      }
      fclose($ih);
      fclose($oh);
      fclose($oh2);
      rename($output2, $input);
    }
  }

  
  /*
    Ensin haetaan tiedostot, sortataan niitä
    Sitten käsitellään stocks kansiossa olevat tiedostot.
  */
  public function aloita()
  {
    $this->jakaa_yksittaiset_tiedostot();
    $this->hae_tiedostot();
    
    foreach (scandir($this->impsaloh_polku_orig_stocks) as $impsaloh_csv_file_name) {
      $impsaloh_csv_file = $this->impsaloh_polku_orig_stocks."/".$impsaloh_csv_file_name;

      // skipataan kansiot, orig kansiossa olevat tiedostot sekä pisteet
      if (is_dir($impsaloh_csv_file) or
        substr($impsaloh_csv_file_name, 0, 1) == '.' or
        substr($impsaloh_csv_file_name, 0, 7) == 'prices_' or
        in_array($impsaloh_csv_file_name, $this->ftp_exclude_files)) {
        continue;
      }

      // Haetaan samalla prices tiedosto
      $this->kasittele_tiedosto($impsaloh_csv_file, $this->impsaloh_polku_orig_prices."/prices_".$impsaloh_csv_file_name);
    }
  }


  /*
    Tunnistaa mmikä tiedosto on kyseessä
    Jos tiedostossa on vähän revejä, se on stock tiedosto
    Siirtää tiedostot oikeaan kansioon.
  */
  public function hae_tiedostot()
  {
    /*
    $ftphost = $ftphost_impsaloh;
    $ftpuser = $ftpuser_impsaloh;
    $ftppass = $ftppass_impsaloh;
    $ftpport = $ftpport_impsaloh;
    $ftppath = $ftppath_impsaloh;
    $ftpdest = $ftpdest_impsaloh;
    require 'sftp-get.php';
    */
    
    foreach ($this->impsaloh_csv_files as $impsaloh_csv_file_name) {
      $impsaloh_csv_file = $this->impsaloh_polku_in."/".$impsaloh_csv_file_name;
      $impsaloh_csv_file_prices = $this->impsaloh_polku_in."/prices_".$impsaloh_csv_file_name;

      // skipataan kansiot, orig kansiossa olevat tiedostot sekä pisteet
      if (is_dir($impsaloh_csv_file) or
        substr($impsaloh_csv_file_name, 0, 1) == '.' or
        substr($impsaloh_csv_file_name, 0, 7) == 'prices_' or
        in_array($impsaloh_csv_file_name, $this->ftp_exclude_files)) {
        continue;
      }

      // Selvitetään mikä tyyppinen tiedosto on - prices tai stocks
      $impsaloh_csv_tarkista = fopen($impsaloh_csv_file, 'r');
      $csv_hae_kolumnit = $this->csv_jakajaa_ja_kolumnit($impsaloh_csv_tarkista, $impsaloh_csv_file);
      fclose($impsaloh_csv_tarkista);

      // Siirettään ja kopioidaan tiedostot oikeaan kansioon
      if ($csv_hae_kolumnit['kolumneja'] > $this->prices_tiedoston_kolumneja_max[$impsaloh_csv_file_name]) {
        copy($impsaloh_csv_file, $this->impsaloh_polku_orig."/".$impsaloh_csv_file_name);
        copy($impsaloh_csv_file_prices, $this->impsaloh_polku_orig."/prices_".$impsaloh_csv_file_name);

        if(isset($this->toimittajat_tiedostot[$impsaloh_csv_file_name])) {
          $toimittaja_id = $this->toimittajat_tiedostot[$impsaloh_csv_file_name];
        } else {
          echo $toimittaja_id." toimittaja ei löydy!";
          continue;
        }

        // Otetaan pois rename testauksessa.
        //rename($impsaloh_csv_file, $this->impsaloh_polku_orig_stocks."/".$toimittaja_id."___".$csv_hae_kolumnit['kolumneja'].$csv_hae_kolumnit['riveja'].$impsaloh_csv_file_name);
        //rename($impsaloh_csv_file_prices, $this->impsaloh_polku_orig_prices."/prices_".$toimittaja_id."___".$csv_hae_kolumnit['kolumneja'].$csv_hae_kolumnit['riveja'].$impsaloh_csv_file_name);
        copy($impsaloh_csv_file, $this->impsaloh_polku_orig_stocks."/".$toimittaja_id."___".$csv_hae_kolumnit['kolumneja'].$csv_hae_kolumnit['riveja'].$impsaloh_csv_file_name);
        copy($impsaloh_csv_file_prices, $this->impsaloh_polku_orig_prices."/prices_".$toimittaja_id."___".$csv_hae_kolumnit['kolumneja'].$csv_hae_kolumnit['riveja'].$impsaloh_csv_file_name);
      }
    }
  }


  /*
    Selvittää että mikä jakajaa on CSV tiedostossa, "," tai ";"
    laskee kolumnit tiedostossa sekä rivit
  */
  public function csv_jakajaa_ja_kolumnit($impsaloh_csv, $impsaloh_csv_file)
  {
    $yrita_csv_pilkku = fgetcsv($impsaloh_csv, 1000, ",");
    $count_yrita_csv_pilkku = count($yrita_csv_pilkku);
    rewind($impsaloh_csv);
    $yrita_csv_pistepilkku = fgetcsv($impsaloh_csv, 1000, ";");
    $count_yrita_csv_pistepilkku = count($yrita_csv_pistepilkku);
    rewind($impsaloh_csv);
  
    if ($count_yrita_csv_pilkku > 1) {
      $kolumneja = $count_yrita_csv_pilkku;
      $csv_jakajaa = ",";
    } elseif ($count_yrita_csv_pistepilkku > 1) {
      $kolumneja = $count_yrita_csv_pistepilkku;
      $csv_jakajaa = ";";
    }

    $impsaloh_csv_riveja = intval(exec("wc -l '$impsaloh_csv_file'"));

    return array(
      "jakajaa" => $csv_jakajaa,
      "kolumneja" => $kolumneja,
      "riveja" => $impsaloh_csv_riveja
    );
  }


  /*
    Käsittelee tiedostot
  */
  public function kasittele_tiedosto($impsaloh_csv_file, $impsaloh_csv_prices_file)
  {

    // Avataan stocks ja prices tiedostot
    $impsaloh_csv = fopen($impsaloh_csv_file, 'r');
    if (!$impsaloh_csv) {
      die($php_errormsg);
    }
    $impsaloh_prices_csv = fopen($impsaloh_csv_prices_file, 'r');
    if (!$impsaloh_prices_csv) {
      die($php_errormsg);
    }
    
    $csv_jakajaa = $this->csv_jakajaa_ja_kolumnit($impsaloh_csv, $impsaloh_csv_file);
    $csv_jakajaa_prices = $this->csv_jakajaa_ja_kolumnit($impsaloh_prices_csv, $impsaloh_csv_prices_file);

    $toimittaja_id = explode("___",basename($impsaloh_csv_file));
    $toimittaja_id = $toimittaja_id[0];
    $query = "SELECT * FROM toimi 
                    WHERE tunnus = {$toimittaja_id}";
    $loydetty_toimittaja = pupe_query($query);
    if (mysql_num_rows($loydetty_toimittaja) > 0) {
      $toimittaja = mysql_fetch_assoc($loydetty_toimittaja);
    } else {
      echo "Toimittaja {$toimittaja_id} ei löydy ! Oliko se poistettu?";
      return;
    }
    
    $this->kasittele_rivit(
      array(
        "jakajaa" => $csv_jakajaa['jakajaa'],
        "file" => $impsaloh_csv,
        "riveja" => $csv_jakajaa['riveja'],
        "filename" => $impsaloh_csv_file,
        'toimittaja' => $toimittaja
      ),
      array(
        "jakajaa" => $csv_jakajaa_prices['jakajaa'],
        "file" => $impsaloh_prices_csv,
        "riveja" => $csv_jakajaa_prices['riveja'],
        "filename" => $impsaloh_csv_prices_file
      )
    );
  }


  /*
    Looppaa kaikki rivit tiedostossa ja jos kysesssä on stock tiedosto,
    Hakee myös toisesta tiedostosta hinnat ja saldot.
    esim.:
    toimittajax.csv -> siirtää sen stock kansioon ja etsii samassa kansiossa prices_toimittajatx.csv
    sen jälkeen siirtää sen prices kansioon jä käsittelee prices_toimittajax.csv tiedosto
  */
  public function kasittele_rivit($stocks_file, $prices_file)
  {

    // Stocks tiedoston tiedot
    $csv_jakajaa = $stocks_file['jakajaa'];
    $impsaloh_csv = $stocks_file['file'];
    $impsaloh_csv_riveja = $stocks_file['riveja'];
    $toimittaja_id = (int) $stocks_file['toimittaja']['tunnus'];
    $toimittaja_myyntikerroin = (float) $stocks_file['toimittaja']['myyntihinta_kerroin'];

    // Price tiedoston tiedot
    $csv_jakajaa_prices = $prices_file['jakajaa'];
    $impsaloh_prices_csv = $prices_file['file'];
    $impsaloh_prices_riveja= $prices_file['riveja'];

    $yhtio = $this->yhtio;
    $tuotekoodi_otsikot = $this->tuotekoodi_otsikot;
    $eankoodi_otsikot = $this->eankoodi_otsikot;

    // Loopataan ja järjestetään prices tiedoston data
    $rivit_prices = array();
    $otsikkotiedot = false;
    while ($rivi = fgetcsv($impsaloh_prices_csv, 100000, $csv_jakajaa_prices)) {
      if (!isset($rivit[0])) {

        // Hae tuotekoodin kolumni
        $kolumninro = 0;
        foreach ($rivi as $hae_otsikko) {
          $hae_otsikko = preg_replace("/[^A-Za-z0-9 ]/", '', $hae_otsikko);

          if (isset($tuotekoodi_otsikot[$hae_otsikko])) {
            $tuotekoodin_kolumni = (string) $kolumninro;
            $otsikkotiedot = $tuotekoodi_otsikot[$hae_otsikko];
          }
          $kolumninro++;
        }

        // Jos löydetty tuotekoodin kolumni, etsitään hintakolumni ja skipataan koko otsikkorivi
        if (isset($tuotekoodin_kolumni)) {
          $hinta_kolumni = false;
          $kolumninro = 0;
          foreach ($rivi as $hae_otsikko) {
            $hae_otsikko = preg_replace("/[^A-Za-z0-9 ]/", '', $hae_otsikko);
        
            if ($otsikkotiedot['hinta'] == $hae_otsikko) {
              $hinta_kolumni = $kolumninro;
              break;
            }
            $kolumninro++;
          }

          $rivit[0] = false;
          continue;
        }
      }

      $rivi_hinta = $rivi[$hinta_kolumni];
      $rivi_tuoteno = $rivi[$tuotekoodin_kolumni];
      unset($rivi[$hinta_kolumni]);
      unset($rivi[$tuotekoodin_kolumni]);

      $array_keys_rivi = array_keys($rivi);
      $rivi_saldo = $rivi[$array_keys_rivi[0]];
      unset($rivi[$array_keys_rivi[0]]);

      $rivit_prices[$rivi_tuoteno] = array(
        "hinta" => $rivi_hinta,
        "saldo" => $rivi_saldo
      );
    }

    // Käsitellään tiedostojen rivit ja etsitään / muokataan tuotteet pupeessa
    $rivit = array();
    $loydetyt_tuotteet = array();
    $epaonnistuneet_tuotteet = array();
    $laskerivit = 0;
    while ($rivi = fgetcsv($impsaloh_csv, 100000, $csv_jakajaa)) {
    
      // Skipataan tyhjät rivit
      if ($rivi[0] == "" and $rivi[1] == "" and $rivi[2] == "") {
        continue;
      }

      $laskekolumnit = 0;
      // Jos ensimmäinen rivi

      if (!isset($rivit[0])) {
        // Hae tuotekoodin kolumni
        $tuotekoodin_kolumni = false;
        $kolumninro = 0;
        foreach ($rivi as $hae_otsikko) {
          $hae_otsikko = preg_replace("/[^A-Za-z0-9 ]/", '', $hae_otsikko);
          if (isset($tuotekoodi_otsikot[$hae_otsikko])) {
            $tuotekoodin_kolumni = $kolumninro;
          }
          if (in_array($hae_otsikko, $eankoodi_otsikot)) {
            $eankoodin_kolumni = $kolumninro;
          }
          $kolumninro++;
        }
      
        if (isset($tuotekoodin_kolumni) or isset($eankoodin_kolumni)) {
          $rivit[0] = false;
          continue;
        }
      } else {
        foreach ($rivi as $rividata) {
          $rivi[$laskekolumnit] = $rividata;
          $laskekolumnit++;
        }
        $laskerivit++;
      }

      $tuotekoodi_tarkista1 = $rivi[$tuotekoodin_kolumni];

      if (!isset($rivit_prices[$tuotekoodi_tarkista1])) {
        $epaonnistuneet_tuotteet[] = $rivi;
        continue;
      }
      //$tuotekoodi_tarkista2 = preg_replace("/[^A-Za-z0-9 ]/", '', $rivi[$tuotekoodin_kolumni]);

      $eankoodi_tarkista = $rivi[$eankoodin_kolumni];
      
      // Haetaan hintatiedot ja saldo price array:ista
      $tuotehinta = $rivit_prices[$tuotekoodi_tarkista1]['hinta'];
      $tuotesaldo = $rivit_prices[$tuotekoodi_tarkista1]['saldo'];

      if (empty($tuotesaldo)) {
        continue;
      }

      // yritetään päivittää suoraan tuotenumerolla
      $query = "UPDATE tuotteen_toimittajat
                  SET tuotteen_toimittajat.ostohinta = ".$tuotehinta.", tuotteen_toimittajat.tehdas_saldo = ".$tuotesaldo.", 
                  tuotteen_toimittajat.tehdas_saldo_paivitetty = 
                    CASE WHEN tuotteen_toimittajat.tehdas_saldo = ".$tuotesaldo." THEN tuotteen_toimittajat.tehdas_saldo_paivitetty 
                      ELSE NOW()
                    END,
                    tuotteen_toimittajat.myyntihinta_kerroin = 
                    CASE WHEN tuotteen_toimittajat.myyntihinta_kerroin > 0 THEN tuotteen_toimittajat.myyntihinta_kerroin 
                      ELSE ".$toimittaja_myyntikerroin."
                    END 
                  WHERE yhtio = 'mergr'
                  AND tuotteen_toimittajat.osto_era != '0.00' 
                  AND tuotteen_toimittajat.liitostunnus = '".$toimittaja_id."' 
                  AND tuotteen_toimittajat.tuoteno in('".$tuotekoodi_tarkista1."') 
                  AND(last_insert_id(tuotteen_toimittajat.tunnus))
                ";

      pupe_query($query);

      $onnistunut_tuote = false;

      // onnistui
      if (mysql_insert_id()) {
        $loydetyt_tuotteet[] = $rivi;
        $onnistunut_tuote = true;

      // ei onnistunut - yritetään etsiä tuote eri tavalla
      } else if($eankoodi_tarkista == "not_exist") {
        $query = "SELECT tuoteno 
                    FROM tuote 
                    WHERE yhtio = 'mergr' 
                    AND tuotemerkki != '' 
        	          AND tuote.status != 'P' 
                    AND eankoodi = '".$eankoodi_tarkista."'
                  ";

        $loydetty_tuote = pupe_query($query);

        if (mysql_num_rows($loydetty_tuote) > 0) {
          $tuoteno = mysql_fetch_assoc($loydetty_tuote);
          $tuoteno = $tuoteno["tuoteno"];
          $query = "UPDATE tuotteen_toimittajat 
                      SET ostohinta = ".$tuotehinta.", tehdas_saldo = ".$tuotesaldo.", 
                      tehdas_saldo_paivitetty = 
                        CASE WHEN tehdas_saldo = ".$tuotesaldo." THEN tehdas_saldo_paivitetty 
                          ELSE NOW()
                        END,
                      myyntihinta_kerroin = 
                        CASE WHEN myyntihinta_kerroin > 0 THEN myyntihinta_kerroin 
                          ELSE ".$toimittaja_myyntikerroin."
                        END 
                      WHERE yhtio = 'mergr'
                      AND liitostunnus = '".$toimittaja_id."'
                      AND tuoteno = '".$tuoteno."' 
                      AND(last_insert_id(tuotteen_toimittajat.tunnus))
                    ";
          pupe_query($query);
          if (mysql_insert_id()) {
            $loydetyt_tuotteet[] = $rivi;
            $onnistunut_tuote = true;
          }
        }
      }

      if(!$onnistunut_tuote) {
        $epaonnistuneet_tuotteet[] = $rivi;
      }
      
      echo "...Valmis: ".round($laskerivit/$impsaloh_csv_riveja, 2)*100;
      echo "%";
      echo "...Tuotteet OK: ".count($loydetyt_tuotteet);
      echo "...\r";
    }

    $impsaloh_timestamp = $date = new DateTime();
    $impsaloh_timestamp = $impsaloh_timestamp->getTimestamp();

    if(count($loydetyt_tuotteet) > 0) {
      $loydetyt_tuotteet_csv = fopen($this->impsaloh_polku_ok."/".$impsaloh_timestamp."_".basename($stocks_file['filename']), 'w');
      foreach ($loydetyt_tuotteet as $loydetyt_tuotteet_fields) {
        fputcsv($loydetyt_tuotteet_csv, $loydetyt_tuotteet_fields);
      }
      fclose($loydetyt_tuotteet_csv);
    }

    if(count($epaonnistuneet_tuotteet) > 0) {
      $epaonnistuneet_tuotteet_csv = fopen($this->impsaloh_polku_error."/".$impsaloh_timestamp."_".basename($stocks_file['filename']), 'w');
      foreach ($epaonnistuneet_tuotteet as $epaonnistuneet_tuotteet_fields) {
        fputcsv($epaonnistuneet_tuotteet_csv, $epaonnistuneet_tuotteet_fields);
      }
      fclose($epaonnistuneet_tuotteet_csv);
    }

    echo "\n...Ei osunut:".count($epaonnistuneet_tuotteet)."...\n";

    unset($rivit[0]);

  }
}

$execute = new ImportSaldoHinta($yhtiorow);
$execute->aloita();