<?php

/*
  Katso asetukset alla
*/

// Kutsutaanko CLI:stה
if (php_sapi_name() != 'cli') {
  die("Tהtה scriptiה voi ajaa vain komentoriviltה!");
}

if (!isset($argv[1]) || !$argv[1]) {
  echo "Anna yhtio";
  exit;
}

date_default_timezone_set('Europe/Helsinki');

require "inc/connect.inc";
require "inc/functions.inc";

// ytiorow. Jos ei lצydy, lopeta cron
$yhtiorow = hae_yhtion_parametrit(pupesoft_cleanstring($argv[1]));
if (!$yhtiorow) {
  echo "Vaara yhtio";
  exit;
}

// Logitetaan ajo
cron_log();

ini_set('memory_limit', '6000M');
ini_set('max_execution_time', 30000);

$ftptiedot = array(
  "hosts" => $ftphosts_impsaloh
);

if (isset($argv[2]) and $argv[2]) {
  $toimittajen_rajoitus = array_flip(explode(",",$argv[2]));
} else {
  $toimittajen_rajoitus = false;
}

/*
  Main class
*/
class ImportSaldoHinta
{


  /*
    Laitetaan kaikki muuttujat kuntoon
  */
  public function __construct($yhtiorow, $ftptiedot, $suosittu_toimittajan_varasto, $toimittajen_rajoitus)
  {
    $this->ftptiedot = $ftptiedot;
    if($this->suosittu_toimittajan_varasto = $suosittu_toimittajan_varasto) {
    } else {
      $this->suosittu_toimittajan_varasto = false;
    }

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

    /*
      Poistetaan kaikki ja luodaan rakenne uudestaan
    */
    system("rm -rf ".$impsaloh_csv_cron_dirname."/*");

    $this->impsaloh_polku_in     = $impsaloh_csv_cron_dirname;
    $this->impsaloh_polku_ok     = $impsaloh_csv_cron_dirname."/ok";
    $this->impsaloh_polku_orig   = $impsaloh_csv_cron_dirname."/orig";
    $this->impsaloh_polku_orig_stocks   = $impsaloh_csv_cron_dirname."/orig/stocks";
    $this->impsaloh_polku_orig_prices   = $impsaloh_csv_cron_dirname."/orig/prices";
    $this->impsaloh_polku_error  = $impsaloh_csv_cron_dirname."/error";

    mkdir($this->impsaloh_polku_ok, 0755, true);
    mkdir($this->impsaloh_polku_orig, 0755, true);
    mkdir($this->impsaloh_polku_orig_stocks, 0755, true);
    mkdir($this->impsaloh_polku_orig_prices, 0755, true);
    mkdir($this->impsaloh_polku_error, 0755, true);

    $this->ftp_exclude_files = array_diff(scandir($this->impsaloh_polku_orig), array('..', '.', '.DS_Store'));

    $this->eankoodi_otsikot = array("GTIN");


    /*------------------------ASETUKSET-ALKAVAT------------------------------*/

    /*
      Tiedostonimet (saldo) => toimittajan ID
    */
    $this->toimittajat_tiedostot = array(
      "kavoparts.csv" => "1474",
      "60046_ce.csv" => "1432",
      "meatdoria.csv" => "1525",
      "STANY.csv" => "1048",
      "ItemsInStock.txt" => "101",
      "motoprofil.csv" => "943",
      "gordon_database.csv" => "1407",
      "30803_ce.csv" => "1598",
      "orum.csv" => "200"
    );

    $this->toimittajen_rajoitus = $toimittajen_rajoitus;

    /*
      Otsikot etsitההn tiedostossa.
      $tuotekoodi_otsikot rakenne on: stocks prices tiedoston otsikko => stocks tiedoston otsikko / prices hinta
    */
    $this->tuotekoodi_otsikot = array(
      1474 => array("Product code" =>
        array(
          "tuotekoodi" => "Item No",
          "hinta" => "Mercantile Price",
          "saldo" => "Inventory QTY"
        )
      ),
      1525 => array("Product code" =>
        array(
          "tuotekoodi" => "code",
          "hinta" => "Mercantile price",
          "saldo" => "level"
        )
      ),
      1432 => array("Product code" =>
        array(
          "tuotekoodi" => "Item No",
          "hinta" => "hinta",
          "saldo" => "saldo"
        )
      ),
      1048 => array("Product code" =>
        array(
          "tuotekoodi" => "code",
          "hinta" => "Mercantile price",
          "saldo" => "saldo"
        )
      ),
      1598 => array("Product code" =>
        array(
          "tuotekoodi" => "code",
          "hinta" => "Mercantile price",
          "saldo" => "saldo"
        )
      ),
      101 => array("Product code" =>
        array(
          "tuotekoodi" => "Item No",
          "warehouse1" => "warehouse1",
          "warehouse2" => "warehouse2"
        )
      ),
      200 => array("Product code" =>
        array(
          "tuotekoodi" => "code",
          "hinta" => "Mercantile price",
          "saldo" => "saldo"
        )
      ),
      943 => array("Product code" =>
        array(
          "tuotekoodi" => "Product code",
          "hinta" => "Price",
          "warehouse1" => "Chorz?w saldo",
          "warehouse2" => "HUB saldo",
          "tuotemerkki" => "Brand"
        )
      ),
      1407 => array("Product code" =>
        array(
          "tuotekoodi" => "Gordon Code",
          "hinta" => "Price no VAT",
          "warehouse1" => "Kaunas (HUB)",
          "warehouse2" => "Poland (CN)",
        )
      )
    );

    /*
      Annetaan price - tiedoston erikoisnimet, muuten price tiedoston nimi "prices_*"
    */
    $this->erikois_price_nimet = array(
      "STANY.csv" => "2945497.csv",
    );

    /*
      Jos tiedostossa ei ole mitההn otsikoita, lisהtההn ne tהstה.
    */
    $this->lisaa_otsikot = array(
      "orum.csv" => array(
        'stocks' => array(
          'columns' => array(0,4),
          'titles' => array('code','saldo')
        ),
        'prices' => array(
          'columns' => array(0,1),
          'titles' => array('Product code','Mercantile price')
        ),
      ),
      "STANY.csv" => array(
        'stocks' => array(
          'columns' => array(0,1,2),
          'titles' => array('code','saldo','warehouse')
        ),
        'prices' => array(
          'columns' => array(0,5),
          'titles' => array('Product code','Mercantile price')
        )
      ),
      "30803_ce.csv" => array(
        'stocks' => array(
          'columns' => array(2,3),
          'titles' => array('code','saldo')
        ),
        'prices' => array(
          'columns' => array(2,4),
          'titles' => array('Product code','Mercantile price')
        )
      ),
    );

    /*
      Ohita hintojen siirto kokonaan
    */
    $this->ohita_hinnat = array(
      "ItemsInStock.txt" => true,
      "orum.csv" => true
    );

    /*
      Resetoi saldot, jos tuote ei enהה listassa. Avain: toimittaja ID
    */
    $this->resetoittavat = array(
      "1048" => true
    );

    /*
      Ohitetaan tiedostot ftp palvelimista.
    */
    $this->ohita_tiedostot = array(
      "2945497_KAUCJE.csv",
      "INDEKS_PARAMETR.csv"
    );

    /*
      Vain yksi tiedosto, ei ole price_tiedostoa.
      Valitaan oikeat kolumnit.
      Ensimmהinen: price_tiedoston - product_code, sitten hinta ja saldo kolumnit
      Toinen: stocks_tiedoston - product_code
    */
    $this->yksittaiset_tiedostot = array(
      "60046_ce.csv" => array(
        array(0,4,3),
        array(0)
      ),
      "30803_ce.csv" => array(
        array(2,4,3),
        array(2)
      ),
      "ItemsInStock.txt" => array(
        array(1,2,4),
        array(1)
      ),
      "orum.csv" => array(
        array(0,1,4),
        array(0)
      ),
      "motoprofil.csv" => array(
        array(0,3,4,5,2),
        array(0)
      ),
      "gordon_database.csv" => array(
        array(1,5,7,8),
        array(1)
      )
    );

    /*
      Toimittajan saldoluokat muutetaan tavallisille saldolle.
      Se voi olla esim. luokka A, tai jotain muuta.
    */
    $this->saldo_levels = array(
      "0" => 0,
      "1" => 2,
      "2" => 5
    );
  }

  /*------------------------ASETUKSET-LOPPUVAT------------------------------*/


  public function korjaa_csvt($tiedostonimi,$reset_tuotenimet=false)
  {
    $input = $this->impsaloh_polku_in."/".$tiedostonimi;
    $input2 = $this->impsaloh_polku_in."/prices_".$tiedostonimi;

    if (isset($this->erikois_price_nimet[$tiedostonimi])) {
      rename($this->impsaloh_polku_in."/".$this->erikois_price_nimet[$tiedostonimi], $input2);
    }

    $output = $this->impsaloh_polku_in."/"."uusi_".$tiedostonimi;
    $output2 = $this->impsaloh_polku_in."/"."uusi_prices_".$tiedostonimi;

    $stocks_titles = $this->lisaa_otsikot[$tiedostonimi]['stocks'];
    $prices_titles = $this->lisaa_otsikot[$tiedostonimi]['prices'];
    
    $tuotenumerot_saldo = array();

    if($ih = fopen($input, "r")) {
      $i=0;
      $oh = fopen($output, "w+");
      while (false !== ($data = fgetcsv($ih, 100000, ";"))) {
        if ($i==0) {
         $outputData = $stocks_titles['titles'];
          fputcsv($oh, $outputData, ";");
        }
        $outputData = array(
          (string) $data[$stocks_titles['columns'][0]],
          $data[$stocks_titles['columns'][1]], preg_replace(
            "/[^0-9 ]/",
            '',
            $data[$stocks_titles['columns'][2]]
          )
        );
        $tuotenumerot_saldo[$outputData[0]] = '';
        fputcsv($oh, $outputData, ";");
        $i++;
      }
      fclose($ih);
      fclose($oh);
    } else {
      return false;
    }

    if($ih2 = fopen($input2, "r")) {
      $oh2 = fopen($output2, "w+");
      $i=0;
      while (false !== ($data2 = fgetcsv($ih2, 100000, ";"))) {
        if ($i==0) {
          $outputData2 = $prices_titles['titles'];
          fputcsv($oh2, $outputData2, ";");
        }
        $outputData2 = array(
          (string) $data2[$prices_titles['columns'][0]],
          $data2[$prices_titles['columns'][1]]
        );
        if($reset_tuotenimet and !isset($tuotenumerot_saldo[$outputData2[0]])) {
          fputcsv($oh, array($outputData2[0], 0, 1), ";");
          fputcsv($oh, array($outputData2[0], 0, 72), ";");
        }
        fputcsv($oh2, $outputData2, ";");
        $i++;
      }
      fclose($ih2);
      fclose($oh2);
    }

    copy($output, $this->impsaloh_polku_in."/../debug.csv");

    rename($output, $input);
    rename($output2, $input2);
    return true;
  }

  public function jakaa_yksittaiset_tiedostot()
  {
    $laske_tiedostot = 0;
    
    foreach ($this->yksittaiset_tiedostot as $tiedostonimi => $tiedostokolumnit) {

      $input = $this->impsaloh_polku_in."/".$tiedostonimi;
      $output = $this->impsaloh_polku_in."/"."prices_".$tiedostonimi;

      if (!file_exists($input)) {
        continue;
      }

      if (isset($this->toimittajat_tiedostot[$tiedostonimi])) {
        $toimittaja_id = $this->toimittajat_tiedostot[$tiedostonimi];
      } else {
        echo $tiedostonimi." toimittaja ei lצydy!";
        continue;
      }

      if($this->toimittajen_rajoitus and !isset($this->toimittajen_rajoitus[$toimittaja_id])) {
        continue;
      }

      $output2 = $this->impsaloh_polku_in."/uusi_".$tiedostonimi;
      if (false !== ($ih = fopen($input, 'r'))) {

        $oh = fopen($output, 'w');
        $oh2 = fopen($output2, 'w');
        $i=0;

        $product_code_header = array_keys($this->tuotekoodi_otsikot[$toimittaja_id]); $product_code_header = $product_code_header[0];
        $product_data = $this->tuotekoodi_otsikot[$toimittaja_id][$product_code_header];
        
        $product_data_headers = array_keys($product_data);
        
        $outputData = array(
          $product_code_header, 
          $product_data_headers[1], 
          $product_data_headers[2]
        );

        if(count($product_data_headers) == 4) {
          $outputData[] = $product_data_headers[3];
        }

        if(count($product_data_headers) == 5) {
          $outputData[] = $product_data_headers[3];
          $outputData[] = $product_data_headers[4];
        }

        $outputData2 = array($product_data['tuotekoodi']);

        $yrita_csv_pilkku = fgetcsv($ih, 1000, ",");
        $count_yrita_csv_pilkku = count($yrita_csv_pilkku);
        rewind($ih);
        $yrita_csv_pistepilkku = fgetcsv($ih, 1000, ";");
        $count_yrita_csv_pistepilkku = count($yrita_csv_pistepilkku);
        rewind($ih);
        $yrita_csv_tab = fgetcsv($ih, 1000, "\t");
        $count_yrita_csv_tab = count($yrita_csv_tab);
        rewind($ih);
    
        if ($count_yrita_csv_pistepilkku > 1) {
          $csv_jakajaa = ";";
        } elseif ($count_yrita_csv_pilkku > 1) {
          $csv_jakajaa = ",";
        } elseif ($count_yrita_csv_tab > 1) {
          $csv_jakajaa = "\t";
        } elseif($count_yrita_csv_pilkku == 1 or $count_yrita_csv_pistepilkku == 1 or $count_yrita_csv_tab == 1) {
          $csv_jakajaa = ";";
        }

        while (false !== ($data = fgetcsv($ih, 0, $csv_jakajaa))) {

          if ($data[$tiedostokolumnit[0][2]] == "-" or $data[$tiedostokolumnit[0][2]] == "") {
            $data[$tiedostokolumnit[0][2]] = 0;
          }
          if ($i==0) {
            fputcsv($oh, $outputData, ";");
            fputcsv($oh2, $outputData2, ";");
          }

          if(count($product_data_headers) == 3) {
            $data[$tiedostokolumnit[0][2]] = str_replace("> ", "", $data[$tiedostokolumnit[0][2]]);
            $outputData = array($data[$tiedostokolumnit[0][0]], $data[$tiedostokolumnit[0][1]], preg_replace("/[^0-9 ]/", '', round($data[$tiedostokolumnit[0][2]])));
          }
          if(count($product_data_headers) == 4) {
            $outputData = array($data[$tiedostokolumnit[0][0]], $data[$tiedostokolumnit[0][1]], preg_replace("/[^0-9 ]/", '', round($data[$tiedostokolumnit[0][2]])), preg_replace("/[^0-9 ]/", '', round($data[$tiedostokolumnit[0][3]])));
          }
          if(count($product_data_headers) == 5) {
            $outputData = array($data[$tiedostokolumnit[0][0]], $data[$tiedostokolumnit[0][1]], preg_replace("/[^0-9 ]/", '', round($data[$tiedostokolumnit[0][2]])), preg_replace("/[^0-9 ]/", '', round($data[$tiedostokolumnit[0][3]])), $data[$tiedostokolumnit[0][4]]);
          }

          fputcsv($oh, $outputData, ";");
          $outputData2 = array($data[$tiedostokolumnit[1][0]]);
          fputcsv($oh2, $outputData2, ";");
          $i++;
        }
        $laske_tiedostot++;
      }
      fclose($ih);
      fclose($oh);
      fclose($oh2);
      rename($output2, $input);
    }
  }
  
  /*
    Ensin haetaan tiedostot, sortataan niitה
    Sitten kהsitellההn stocks kansiossa olevat tiedostot.
  */
  public function aloita()
  {
    $argv[1] = 'external_partners';

    $ohita_tiedostot = $this->ohita_tiedostot;

    foreach ($this->ftptiedot['hosts'] as $ftp_tiedot_nimi => $ftp_tiedot) {
      $ftpget_host['external_partners'] = $ftp_tiedot['ftphost'];
      $ftpget_user['external_partners'] = $ftp_tiedot['ftpuser'];
      $ftpget_pass['external_partners'] = $ftp_tiedot['ftppass'];
      $ftpget_path['external_partners'] = $ftp_tiedot['ftppath'];
      $ftpget_dest['external_partners'] = $ftp_tiedot['ftpdest'];

      sleep(1);
      if ($ftp_tiedot_nimi == 'autopartner') {
        // AutoPartner
        require 'ftp-get.php';
        if(!$this->korjaa_csvt('STANY.csv', true)) {
          require 'ftp-get.php';
          $this->korjaa_csvt('STANY.csv', true);
        }
      }
      sleep(1);
      if ($ftp_tiedot_nimi == 'oletus') {
        // InterParts
        require 'ftp-get.php';
        exec('gunzip -fd '.$this->impsaloh_polku_in.'/*.gz');
        exec('mv '.$this->impsaloh_polku_in.'/60046_ce '.$this->impsaloh_polku_in.'/60046_ce.csv');
        exec('mv '.$this->impsaloh_polku_in.'/30803_ce '.$this->impsaloh_polku_in.'/30803_ce.csv');
      }
      sleep(1);
      if ($ftp_tiedot_nimi == 'triscan') {
        // Triscan
        require 'ftp-get.php';
      }
      sleep(1);
      if ($ftp_tiedot_nimi == 'gordon') {
        // Gordon
        require 'ftp-get.php';
      }
    }

    $this->jakaa_yksittaiset_tiedostot();
    //$this->korjaa_csvt('30803_ce.csv', true);
    $this->hae_tiedostot();

    foreach (scandir($this->impsaloh_polku_orig_stocks) as $impsaloh_csv_file_name) {
      $impsaloh_csv_file = $this->impsaloh_polku_orig_stocks."/".$impsaloh_csv_file_name;

      // skipataan kansiot, orig kansiossa olevat tiedostot sekה pisteet
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

  public function resetoi_saldot($toimittaja_id, $kasitelty_tuotteet) {

    if(isset($this->resetoittavat[$toimittaja_id])) {
      $query = "SELECT tunnus, toim_tuoteno 
                FROM tuotteen_toimittajat 
                WHERE yhtio = '".$this->yhtio."' 
                  AND liitostunnus = '".$toimittaja_id."' 
                  AND tehdas_saldo_varastot != '' 
                  AND tehdas_saldo != '' AND tehdas_saldo > 0 
                ";
      $resetoittavat = pupe_query($query);

      if (mysql_num_rows($resetoittavat) > 0) {
        while($resetoittava = mysql_fetch_assoc($resetoittavat)) {
          if(!isset($kasitelty_tuotteet[$resetoittava['toim_tuoteno']])) {
            $query = "UPDATE LOW_PRIORITY tuotteen_toimittajat
                      SET tehdas_saldo_paivitetty = NOW(), 
                        tehdas_saldo = 0, 
                        tehdas_saldo_varastot = '' 
                      WHERE yhtio = '".$this->yhtio."' 
                        AND tunnus = ".$resetoittava['tunnus']." 
                      ";
            pupe_query($query);
          }
        }
      }
      pupe_query($query);
    }
  }

  /*
    Tunnistaa mikה tiedosto on kyseessה
    Jos tiedostossa on vהhהn revejה, se on stock tiedosto
    Siirtהה tiedostot oikeaan kansioon.
  */
  public function hae_tiedostot()
  {
    $laske_tiedostot = 0;
    foreach (scandir($this->impsaloh_polku_in) as $impsaloh_csv_file_name) {
      $impsaloh_csv_file = $this->impsaloh_polku_in."/".$impsaloh_csv_file_name;
      $impsaloh_csv_file_prices = $this->impsaloh_polku_in."/prices_".$impsaloh_csv_file_name;

      // skipataan kansiot, orig kansiossa olevat tiedostot sekה pisteet
      if (is_dir($impsaloh_csv_file) or
        substr($impsaloh_csv_file_name, 0, 1) == '.' or
        substr($impsaloh_csv_file_name, 0, 7) == 'prices_' or
        in_array($impsaloh_csv_file_name, $this->ftp_exclude_files)) {
        continue;
      }
      
      // Selvitetההn mikה tyyppinen tiedosto on - prices tai stocks
      $impsaloh_csv_tarkista = fopen($impsaloh_csv_file, 'r');
      $csv_hae_kolumnit = $this->csv_jakajaa_ja_kolumnit($impsaloh_csv_tarkista, $impsaloh_csv_file);
      fclose($impsaloh_csv_tarkista);

      // Siirettההn ja kopioidaan tiedostot oikeaan kansioon
      copy($impsaloh_csv_file, $this->impsaloh_polku_orig."/".$impsaloh_csv_file_name);
      copy($impsaloh_csv_file_prices, $this->impsaloh_polku_orig."/prices_".$impsaloh_csv_file_name);

      if (isset($this->toimittajat_tiedostot[$impsaloh_csv_file_name])) {
        $toimittaja_id = $this->toimittajat_tiedostot[$impsaloh_csv_file_name];
      } else {
        echo $impsaloh_csv_file_name." toimittaja ei lצydy!";
        continue;
      }

      if($this->toimittajen_rajoitus and !isset($this->toimittajen_rajoitus[$toimittaja_id])) {
        continue;
      }
      $laske_tiedostot++;
      copy($impsaloh_csv_file, $this->impsaloh_polku_orig_stocks."/".$toimittaja_id."___".$csv_hae_kolumnit['kolumneja'].$csv_hae_kolumnit['riveja'].$impsaloh_csv_file_name);
      copy($impsaloh_csv_file_prices, $this->impsaloh_polku_orig_prices."/prices_".$toimittaja_id."___".$csv_hae_kolumnit['kolumneja'].$csv_hae_kolumnit['riveja'].$impsaloh_csv_file_name);
    }
  }


  /*
    Selvittהה ettה mikה jakajaa on CSV tiedostossa, "," tai ";"
    laskee kolumnit tiedostossa sekה rivit
  */
  public function csv_jakajaa_ja_kolumnit($impsaloh_csv, $impsaloh_csv_file)
  {
    $yrita_csv_pilkku = fgetcsv($impsaloh_csv, 1000, ",");
    $count_yrita_csv_pilkku = count($yrita_csv_pilkku);
    rewind($impsaloh_csv);
    $yrita_csv_pistepilkku = fgetcsv($impsaloh_csv, 1000, ";");
    $count_yrita_csv_pistepilkku = count($yrita_csv_pistepilkku);
    rewind($impsaloh_csv);
    $yrita_csv_tab = fgetcsv($impsaloh_csv, 1000, "\t");
    $count_yrita_csv_tab = count($yrita_csv_tab);
    rewind($impsaloh_csv);

    if ($count_yrita_csv_pilkku > 1) {
      $kolumneja = $count_yrita_csv_pilkku;
      $csv_jakajaa = ",";
    } elseif ($count_yrita_csv_pistepilkku > 1) {
      $kolumneja = $count_yrita_csv_pistepilkku;
      $csv_jakajaa = ";";
    } elseif ($count_yrita_csv_tab > 1) {
      $kolumneja = $count_yrita_csv_tab;
      $csv_jakajaa = "\t";
    } elseif($count_yrita_csv_pilkku == 1 or $count_yrita_csv_pistepilkku == 1 or $count_yrita_csv_tab == 1) {
      $kolumneja = 1;
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
    Kהsittelee tiedostot
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

    $toimittaja_id = explode("___", basename($impsaloh_csv_file));
    $toimittaja_id = $toimittaja_id[0];

    if($this->toimittajen_rajoitus and !isset($this->toimittajen_rajoitus[$toimittaja_id])) {
      return;
    }

    $query = "SELECT * FROM toimi 
                    WHERE tunnus = {$toimittaja_id}";
    $loydetty_toimittaja = pupe_query($query);
    if (mysql_num_rows($loydetty_toimittaja) > 0) {
      $toimittaja = mysql_fetch_assoc($loydetty_toimittaja);
    } else {
      echo "Toimittaja {$toimittaja_id} ei lצydy ! Oliko se poistettu?";
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
    Looppaa kaikki rivit tiedostossa ja jos kysesssה on stock tiedosto,
    Hakee myצs toisesta tiedostosta hinnat ja saldot.
    esim.:
    toimittajax.csv -> siirtהה sen stock kansioon ja etsii samassa kansiossa prices_toimittajatx.csv
    sen jהlkeen siirtהה sen prices kansioon jה kהsittelee prices_toimittajax.csv tiedosto
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
    $tuotekoodi_otsikot = $this->tuotekoodi_otsikot[$toimittaja_id];

    $eankoodi_otsikot = $this->eankoodi_otsikot;

    // Loopataan ja jהrjestetההn prices tiedoston data
    $rivit_prices = array();
    $otsikkotiedot = false;

    while ($rivi = fgetcsv($impsaloh_prices_csv, 100000, $csv_jakajaa_prices)) {

      if (!isset($rivit[0])) {

        // Hae tuotekoodin kolumni
        $kolumninro = 0;

        foreach ($rivi as $hae_otsikko) {
          $hae_otsikko = preg_replace("/[^A-Za-z0-9 ]/", '', $hae_otsikko);
          
          if (isset($tuotekoodi_otsikot[$hae_otsikko]) or $tuotekoodi_otsikot['Product code']['tuotekoodi'] == $hae_otsikko) {
            $tuotekoodin_kolumni = (string) $kolumninro;
            $otsikkotiedot = $tuotekoodi_otsikot['Product code'];
          }
          if ($hae_otsikko == 'warehouse1') {
            $warehouse_1_kolumni = $kolumninro;
          }
          if ($hae_otsikko == 'warehouse2') {
            $warehouse_2_kolumni = $kolumninro;
          }
          if ($hae_otsikko == 'tuotemerkki') {
            $tuotemerkki_kolumni = $kolumninro;
          }
          $kolumninro++;
        }

        // Jos lצydetty tuotekoodin kolumni, etsitההn hintakolumni ja skipataan koko otsikkorivi
        if (isset($tuotekoodin_kolumni)) {
          $hinta_kolumni = false;
          $kolumninro = 0;
          foreach ($rivi as $hae_otsikko) {
            
            $hae_otsikko = preg_replace("/[^A-Za-z0-9 ]/", '', $hae_otsikko);
            if ($otsikkotiedot['hinta'] == $hae_otsikko or $hae_otsikko == 'hinta') {
              break;
            }
            $kolumninro++;
          }
          $rivit[0] = false;
          continue;
        } 
      }
      
      $hinta_kolumni = $kolumninro;

      $rivi_saldo = false;
      $tuotemerkki = false;
      $warehouse_1 = false;
      $warehouse_2 = false;

      if (isset($rivi[2])) {
        $rivi_hinta = $rivi[$hinta_kolumni];
        $rivi_saldo = $rivi[$hinta_kolumni+1];
      }

      if(isset($warehouse_1_kolumni)) {
        $warehouse_1 = $rivi[$warehouse_1_kolumni];
      }
      if(isset($warehouse_2_kolumni)) {
        $warehouse_2 = $rivi[$warehouse_2_kolumni];
      }
      if(isset($tuotemerkki_kolumni)) {
        $tuotemerkki = $rivi[$tuotemerkki_kolumni];
      }

      $rivi_hinta = $rivi[$hinta_kolumni];

      $rivi_tuoteno = $rivi[$tuotekoodin_kolumni];

      unset($rivi[$hinta_kolumni]);
      unset($rivi[$tuotekoodin_kolumni]);

      if(!isset($rivit_prices[$rivi_tuoteno])) {
        $rivit_prices[$rivi_tuoteno] = array();
      }

      if ($rivi_saldo !== false and !$tuotemerkki and !isset($warehouse_1_kolumni) and !isset($warehouse_2_kolumni)) {
        
        $rivit_prices[$rivi_tuoteno][0] = array(
          "hinta" => $rivi_hinta,
          "saldo" => $rivi_saldo
        );
      } else if(!$tuotemerkki and $rivi_hinta and isset($warehouse_1_kolumni) and isset($warehouse_2_kolumni)) {
        $rivit_prices[$rivi_tuoteno][$tuotemerkki] = array(
          "hinta" => $rivi_hinta,
          'warehouse_1' => $warehouse_1,
          'warehouse_2' => $warehouse_2
        );
      } else if($tuotemerkki and $rivi_hinta) {
        $rivit_prices[$rivi_tuoteno][$tuotemerkki] = array(
          "hinta" => $rivi_hinta,
          "tuotemerkki" => $tuotemerkki,
          'warehouse_1' => $warehouse_1,
          'warehouse_2' => $warehouse_2
        );
      } else if($rivi_hinta and !$tuotemerkki) {
        $rivit_prices[$rivi_tuoteno][0] = array(
          "hinta" => $rivi_hinta
        );
      } else if(isset($warehouse_1_kolumni) and isset($warehouse_2_kolumni) and !$tuotemerkki) {
        $rivit_prices[$rivi_tuoteno][0] = array(
          'warehouse_1' => $warehouse_1,
          'warehouse_2' => $warehouse_2
        );
      }
    }

    // Kהsitellההn tiedostojen rivit ja etsitההn / muokataan tuotteet pupeessa
    $rivit = array();
    $loydetyt_tuotteet = array();
    $epaonnistuneet_tuotteet = array();
    $kasitelty_tuotteet = array();
    $laskerivit = 0;
    $varasto = false;
    $varastot = array();

    while ($rivi = fgetcsv($impsaloh_csv, 100000, $csv_jakajaa)) {
      usleep(500);
      // Skipataan tyhjהt rivit
      if ($rivi[0] == "" and $rivi[1] == "" and $rivi[2] == "") {
        continue;
      }

      $laskekolumnit = 0;
      // Jos ensimmהinen rivi

      if (!isset($rivit[0])) {
        // Hae tuotekoodin kolumni
        $tuotekoodin_kolumni = false;

        $kolumninro = 0;
        foreach ($rivi as $hae_otsikko) {
          $hae_otsikko = preg_replace("/[^A-Za-z0-9 ]/", '', $hae_otsikko);

          if ($hae_otsikko == 'warehouse') {
            $varasto = $kolumninro;
          }

          if ($tuotekoodi_otsikot["Product code"]['tuotekoodi'] == $hae_otsikko) {
            $tuotekoodin_kolumni = $kolumninro;
          }

          $etsi_saldo = strpos($hae_otsikko, $tuotekoodi_otsikot["Product code"]['saldo']);

          if (isset($tuotekoodi_otsikot["Product code"]['saldo']) and $etsi_saldo !== false) {
            $saldo_kolumni = $kolumninro;
            $saldo_kolumin_nimi = $hae_otsikko;
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

      $kasitelty_tuotteet[$tuotekoodi_tarkista1] = 1;

      if (!isset($rivit_prices[$tuotekoodi_tarkista1])) {
        $epaonnistuneet_tuotteet[] = $rivi;
        continue;
      }

      foreach($rivit_prices[$tuotekoodi_tarkista1] as $rivit_prices_l) {
        $tuotesaldo = 0;

        if (isset($rivit_prices_l['saldo'])) {
          $tuotesaldo = $rivit_prices_l['saldo'];
        } elseif (isset($saldo_kolumni)) {
          $tuotesaldo = $rivi[$saldo_kolumni];
          if ($saldo_kolumin_nimi == "level") {
            $tuotesaldo = $this->saldo_levels[$tuotesaldo];
          }
        }

        if ($varasto) {
          $varasto_nro = intval($rivi[$varasto]);
        
          if (!isset($varastot[$tuotekoodi_tarkista1])) {
            $varastot[$tuotekoodi_tarkista1] = array();
          }

          $varastot[$tuotekoodi_tarkista1][$varasto_nro] = intval($tuotesaldo);
        
          if($this->suosittu_toimittajan_varasto and isset($this->suosittu_toimittajan_varasto[$toimittaja_id]) and isset(
            $varastot[$tuotekoodi_tarkista1][$this->suosittu_toimittajan_varasto[$toimittaja_id]]
          )) {
            $tuotesaldo = $varastot[$tuotekoodi_tarkista1][$this->suosittu_toimittajan_varasto[$toimittaja_id]];
          }
        
          $varastot_serialized = json_encode($varastot[$tuotekoodi_tarkista1]);
          $tehdas_saldo_varastot_lisa = "tuotteen_toimittajat.tehdas_saldo_varastot = '".$varastot_serialized."',";
        }
        
        if(
          !$varasto and 
          isset($rivit_prices_l['warehouse_1']) and 
          isset($rivit_prices_l['warehouse_2'])
        ) {
          if($tuotesaldo == 0 and 
          ($rivit_prices_l['warehouse_1'] > 0 or $rivit_prices_l['warehouse_2'] > 0)) {
            if($rivit_prices_l['warehouse_1'] > 0) {
              $tuotesaldo = $rivit_prices_l['warehouse_1'];
            } else {
              $tuotesaldo = $rivit_prices_l['warehouse_2'];
            }
          }
          $varastot_serialized = json_encode(
            array(
              "1" => (int) $rivit_prices_l['warehouse_1'],
              "2" => (int) $rivit_prices_l['warehouse_2']
            )
          );
          $tehdas_saldo_varastot_lisa = "tuotteen_toimittajat.tehdas_saldo_varastot = '".$varastot_serialized."',";
        }
        
        // Haetaan hintatiedot ja saldo price array:ista
        if(isset($rivit_prices_l['hinta'])) {
          $tuotehinta = $rivit_prices_l['hinta'];
          $tuotehinta_lisa = "tuotteen_toimittajat.ostohinta = '".str_replace(",", ".", $tuotehinta)."',";
        }
        
        // Haetaan hintatiedot ja saldo price array:ista
        if(isset($rivit_prices_l['tuotemerkki'])) {
          $tuotemerkki = $rivit_prices_l['tuotemerkki'];
          $tuotemerkki_lisa = "AND tuotteen_toimittajat.tuotemerkki = '".utf8_decode($tuotemerkki)."'";
        }

        if(!is_numeric($tuotesaldo)) {
          continue;
        }

        $tuotesaldo = round($tuotesaldo);
        
        // yritetההn pהivittהה suoraan tuotenumerolla
        $query = "UPDATE LOW_PRIORITY tuotteen_toimittajat
                  SET 
                  $tuotehinta_lisa 
                  tehdas_saldo_paivitetty = NOW(), 
                  tuotteen_toimittajat.tehdas_saldo = ".$tuotesaldo.", 
                  $tehdas_saldo_varastot_lisa
                  tuotteen_toimittajat.myyntihinta_kerroin = 
                    CASE WHEN tuotteen_toimittajat.myyntihinta_kerroin > 0 THEN tuotteen_toimittajat.myyntihinta_kerroin 
                      ELSE ".$toimittaja_myyntikerroin."
                    END 
                  WHERE yhtio = '".$this->yhtio."'
                  AND tuotteen_toimittajat.liitostunnus = '".$toimittaja_id."' 
                  AND tuotteen_toimittajat.toim_tuoteno = '".mysql_real_escape_string($tuotekoodi_tarkista1)."' 
                  AND tuotteen_toimittajat.toim_nimitys != 'X' 
                  $tuotemerkki_lisa 
                  AND(last_insert_id(tuotteen_toimittajat.tunnus))
                ";
        
        pupe_query($query);
        $onnistunut_tuote = false;
        
        // onnistui
        if (mysql_insert_id()) {
          $loydetyt_tuotteet[] = $rivi;
          $onnistunut_tuote = true;
        }

        if (!$onnistunut_tuote) {
          $epaonnistuneet_tuotteet[] = $rivi;
        }
        
        echo $toimittaja_id."...Valmis: ".round($laskerivit/$impsaloh_csv_riveja, 2)*100;
        echo "%";
        echo "...Tuotteet OK: ".count($loydetyt_tuotteet);
        echo "...\r";
      }

    }

    $impsaloh_timestamp = $date = new DateTime();
    $impsaloh_timestamp = $impsaloh_timestamp->getTimestamp();

    if (count($loydetyt_tuotteet) > 0) {
      $loydetyt_tuotteet_csv = fopen($this->impsaloh_polku_ok."/".$impsaloh_timestamp."_".basename($stocks_file['filename']), 'w');
      foreach ($loydetyt_tuotteet as $loydetyt_tuotteet_fields) {
        fputcsv($loydetyt_tuotteet_csv, $loydetyt_tuotteet_fields, ";");
      }
      fclose($loydetyt_tuotteet_csv);
    }

    if (count($epaonnistuneet_tuotteet) > 0) {
      $epaonnistuneet_tuotteet_csv = fopen($this->impsaloh_polku_error."/".$impsaloh_timestamp."_".basename($stocks_file['filename']), 'w');
      foreach ($epaonnistuneet_tuotteet as $epaonnistuneet_tuotteet_fields) {
        fputcsv($epaonnistuneet_tuotteet_csv, $epaonnistuneet_tuotteet_fields, ";");
      }
      fclose($epaonnistuneet_tuotteet_csv);
    }

    echo "\n...Ei osunut:".count($epaonnistuneet_tuotteet)."...\n";

    $this->resetoi_saldot($toimittaja_id, $kasitelty_tuotteet);

    unset($rivit[0]);
  }
}

$execute = new ImportSaldoHinta(
  $yhtiorow,
  $ftptiedot,
  $suosittu_toimittajan_varasto,
  $toimittajen_rajoitus
);
$execute->aloita();
