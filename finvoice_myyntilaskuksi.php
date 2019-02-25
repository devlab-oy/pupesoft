<?php

ini_set("memory_limit", "5G");

if (php_sapi_name() == 'cli') {
  // otetaan includepath aina rootista
  $pupe_root_polku = dirname(dirname(__FILE__));
  //  //for debug
  // $pupe_root_polku = "/Users/satu/Sites/pupesoft";
  ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.$pupe_root_polku.PATH_SEPARATOR."/usr/share/pear");
  error_reporting(E_ALL ^ E_WARNING ^ E_NOTICE);
  ini_set("display_errors", 0);

  require "inc/connect.inc";
  require "inc/functions.inc";

  // Logitetaan ajo
  cron_log();

  $yhtio = trim($argv[1]);

  //yhtiötä ei ole annettu
  if (empty($yhtio)) {
    echo "\nUsage: php ".basename($argv[0])." yhtio\n\n";
    die;
  }

  $yhtiorow = hae_yhtion_parametrit($yhtio);

  // Haetaan käyttäjän tiedot
  $query = "SELECT *
            FROM kuka
            WHERE yhtio = '$yhtio'
            AND kuka    = 'admin'";
  $result = pupe_query($query);

  if (mysql_num_rows($result) == 0) {
    die("User admin not found");
  }

  // Adminin oletus
  $kukarow = mysql_fetch_assoc($result);
  $yhtiorow = hae_yhtion_parametrit($yhtio);
}
else {
  die("Ohjelmaa voi ajaa vain komentoriviltä");
}

if ($finvoice_myyntilasku_kansio == "" or $finvoice_myyntilasku_kansio_valmis == "" or $finvoice_myyntilasku_kansio_error == "") {
  die("Kansiot määriteltävä!");
}

if (!is_dir($finvoice_myyntilasku_kansio)) {
  die("Kansio $finvoice_myyntilasku_kansio virheellinen!");
}
if (!is_dir($finvoice_myyntilasku_kansio_valmis)) {
  die("Kansio $finvoice_myyntilasku_kansio_valmis virheellinen!");
}
if (!is_dir($finvoice_myyntilasku_kansio_error)) {
  die("Kansio $finvoice_myyntilasku_kansio_error virheellinen!");
}

$kukarow["kuka"] = "finvoice_myyntilasku";
$tiedostot = lue_tiedostot($finvoice_myyntilasku_kansio);

if (!empty($tiedostot)) {
  foreach ($tiedostot as $tiedosto) {
    $tiedosto_polku = $finvoice_myyntilasku_kansio.$tiedosto;

    if (file_exists($tiedosto_polku)) {
      $xml = simplexml_load_file($tiedosto_polku);
      if (!$xml) {
        // File read failure, siirretään tiedosto error kansioon
        siirra_tiedosto_kansioon($tiedosto_polku, $finvoice_myyntilasku_kansio_error);

        die("Tiedoston {$tiedosto_polku} lukeminen epäonnistui\n");
      }

      kasittele_xml_tiedosto($xml, $tiedosto_polku);
      unset($xml);

      echo "Käsiteltiin lasku: {$tiedosto_polku}\n";

      if (empty($_REQUEST['debug']) and file_exists($tiedosto_polku)) {
        siirra_tiedosto_kansioon($tiedosto_polku, $finvoice_myyntilasku_kansio_valmis);
      }
    }
    else {
      die("Tiedostoa ei ole olemassa\n");
    }
  }
}
else {
  echo "Yhtään tiedostoa ei löytynyt\n";
}

function lue_tiedostot($polku) {
  $tiedostot = array();
  if ($handle = opendir($polku)) {
    while (false !== ($tiedosto = readdir($handle))) {
      if ($tiedosto != "." && $tiedosto != "..") {
        if (is_file($polku.$tiedosto)) {
            $tiedostot[] = $tiedosto;
        }
      }
    }
    closedir($handle);
  }

  return $tiedostot;
}

function siirra_tiedosto_kansioon($tiedosto_polku, $kansio) {
  $tiedosto_array = explode('.', $tiedosto_polku);
  if (!empty($tiedosto_array)) {
    $tiedosto_array2 = explode('/', $tiedosto_array[0]);
    $hakemiston_syvyys = count($tiedosto_array2);

    $uusi_filename = $tiedosto_array2[$hakemiston_syvyys - 1].'_'.date('YmdHis').'.'.$tiedosto_array[1];
  }

  rename($tiedosto_polku, $kansio.$uusi_filename);
  system("chown -R :apache ".$kansio.$uusi_filename.";");
}

function kasittele_xml_tiedosto(SimpleXMLElement $xml, $tiedosto_polku) {
  global $kukarow, $yhtiorow, $finvoice_myyntilasku_kansio_error;

  if ($xml !== FALSE) {
    $file = "";

    # Parsitaan finvoice
    require "inc/verkkolasku-in-finvoice.inc";

    // Nämä muuttujat kuuluisi olla setattuna:
    // kauniimpi linebreak
    #$_lb = "\n";
    #echo "{$_lb}{$_lb}LASKUNTIEDOT:{$_lb}";
    #echo "01: ".$yhtio."{$_lb}";
    #echo "02: ".$verkkotunnus_vas."{$_lb}";
    #echo "03: ".$laskun_tyyppi."{$_lb}";
    #echo "04: ".$laskun_numero."{$_lb}";
    #echo "05: ".$laskun_ebid."{$_lb}";
    #echo "06: ".$laskun_tapvm."{$_lb}";
    #echo "07: ".$laskun_lapvm."{$_lb}";
    #echo "08: ".$laskun_erapaiva."{$_lb}";
    #echo "09: ".$laskun_kapvm."{$_lb}";
    #echo "10: ".$laskun_kasumma."{$_lb}";
    #echo "11: ".$laskuttajan_ovt."{$_lb}";
    #echo "12: ".$laskuttajan_nimi."{$_lb}";
    #echo "13: ".$laskuttajan_vat."{$_lb}";
    #echo "14: ".$laskun_pankkiviite."{$_lb}";
    #echo "14.2: ".$laskun_iban."{$_lb}";
    #echo "14.4: ".$laskun_bic."{$_lb}";
    #echo "15: ".$laskun_asiakastunnus."{$_lb}";
    #echo "16: ".$laskun_summa_eur."{$_lb}";
    #echo "17: ".$laskun_tilausviite."{$_lb}";
    #echo "18: ".$kauttalaskutus."{$_lb}";
    #echo "19: ".$laskun_asiakkaan_tilausnumero."{$_lb}";
    #echo "20: ".$toim_asiakkaantiedot["toim_ovttunnus"]."{$_lb}";
    #echo "21: ".$toim_asiakkaantiedot["ytunnus"]."{$_lb}";
    #echo "22: ".$toim_asiakkaantiedot["nimi"]."{$_lb}";
    #echo "23: ".$toim_asiakkaantiedot["osoite"]."{$_lb}";
    #echo "24: ".$toim_asiakkaantiedot["postino"]."{$_lb}";
    #echo "25: ".$toim_asiakkaantiedot["postitp"]."{$_lb}";
    #echo "25: ".$toim_asiakkaantiedot["maa"]."{$_lb}";
    #echo "26: ".$ostaja_asiakkaantiedot["toim_ovttunnus"]."{$_lb}";
    #echo "27: ".$ostaja_asiakkaantiedot["ytunnus"]."{$_lb}";
    #echo "28: ".$ostaja_asiakkaantiedot["nimi"]."{$_lb}";
    #echo "29: ".$ostaja_asiakkaantiedot["osoite"]."{$_lb}";
    #echo "30: ".$ostaja_asiakkaantiedot["postino"]."{$_lb}";
    #echo "31: ".$ostaja_asiakkaantiedot["postitp"]."{$_lb}";
    #echo "31: ".$ostaja_asiakkaantiedot["maa"]."{$_lb}";
    #echo "32: ".$laskuttajan_toimittajanumero."{$_lb}";
    #echo "33: ".$laskuttajan_valkoodi."{$_lb}";
    #echo "34: ".$laskun_toimitunnus."{$_lb}";
    #echo "35: ".$laskun_asiakaspupetunnus."{$_lb}";

    $asiakas = finvoice_myyntilaskuksi_valitse_asiakas($toim_asiakkaantiedot, $ostaja_asiakkaantiedot, $laskun_asiakaspupetunnus);

    if (empty($asiakas)) {
      siirra_tiedosto_kansioon($tiedosto_polku, $finvoice_myyntilasku_kansio_error);
      echo "VIRHE: Sopivaa asiakasta ei löytynyt laskulle: $laskun_numero\n";
      return;
    }

    // Onko laskuriveillä useita alv-verokantoja?
    $ealvi = array_unique($ealvi);

    $alv = 0;
    foreach ($ealvi as $ealv) {
      if ($ealv > 0) {
        $alv = 24;
      }
    }

    $vienti = "";
    if (!empty($ostaja_asiakkaantiedot["maa"])) {
      $query = "SELECT distinct koodi, eu
                FROM maat
                WHERE koodi = '$ostaja_asiakkaantiedot[maa]'";
      $maaresult = pupe_query($query);

      if (mysql_num_rows($maaresult)) {
        $maarow = mysql_fetch_assoc($maaresult);

        if ($maarow["koodi"] == "FI") {
          $vienti = "";
        }
        elseif ($maarow["eu"] != ""){
          $vienti = "E";
        }
        else {
          $vienti = "K";
        }
      }
    }

    $laskunnro = preg_replace("/[^0-9]/", "", $laskun_numero);

    $laskun_tapvm = substr($laskun_tapvm, 0, 4)."-".substr($laskun_tapvm, 4, 2)."-".substr($laskun_tapvm, 6, 2);
    $laskun_lapvm = substr($laskun_lapvm, 0, 4)."-".substr($laskun_lapvm, 4, 2)."-".substr($laskun_lapvm, 6, 2);
    $laskun_erapaiva = substr($laskun_erapaiva, 0, 4)."-".substr($laskun_erapaiva, 4, 2)."-".substr($laskun_erapaiva, 6, 2);
    $laskun_kapvm = substr($laskun_kapvm, 0, 4)."-".substr($laskun_kapvm, 4, 2)."-".substr($laskun_kapvm, 6, 2);

    $maksuehto = finvoice_myyntilaskuksi_valitse_maksuehto($laskun_maksuehtoteksti, $laskun_lapvm, $laskun_erapaiva);

    // Käytetään Pupen asiakkaan tietoja, jos ei löydetty maksuehtoa
    if (empty($maksuehto)) {
      $query = "SELECT rel_pvm
                FROM maksuehto
                WHERE yhtio = '{$yhtiorow['yhtio']}'
                AND tunnus = '{$asiakas['maksuehto']}}'";
      $maksuehtores = pupe_query($query);

      if (mysql_num_rows($maksuehtores) == 1) {
        $maksuehtorow = mysql_fetch_assoc($maksuehtores);

        $query = "SELECT *
                  FROM maksuehto
                  WHERE yhtio = '{$yhtiorow['yhtio']}'
                  AND rel_pvm = '{$maksuehtorow['rel_pvm']}}'
                  AND kassa_relpvm = 0";
        $maksuehtores = pupe_query($query);

        if (mysql_num_rows($maksuehtores) == 1) {
          $maksuehto = mysql_fetch_assoc($maksuehtores);
        }
      }
    }

    if (empty($maksuehto)) {
      siirra_tiedosto_kansioon($tiedosto_polku, $finvoice_myyntilasku_kansio_error);
      echo "VIRHE: Sopivaa maksuehtoa ei löytynyt laskulle: $laskun_numero\n";
      return;
    }

    $query = "INSERT INTO lasku
              SET yhtio          = '{$yhtiorow['yhtio']}',
              yhtio_nimi         = '{$yhtiorow['nimi']}',
              yhtio_osoite       = '{$yhtiorow['osoite']}',
              yhtio_postino      = '{$yhtiorow['postino']}',
              yhtio_postitp      = '{$yhtiorow['postitp']}',
              yhtio_maa          = '{$yhtiorow['maa']}',
              liitostunnus       = '{$asiakas['tunnus']}',
              ytunnus            = '{$ostaja_asiakkaantiedot['ytunnus']}',
              nimi               = '{$ostaja_asiakkaantiedot['nimi']}',
              osoite             = '{$ostaja_asiakkaantiedot['osoite']}',
              postino            = '{$ostaja_asiakkaantiedot['postino']}',
              postitp            = '{$ostaja_asiakkaantiedot['postitp']}',
              maa                = '{$ostaja_asiakkaantiedot['maa']}',
              toim_nimi          = '{$toim_asiakkaantiedot['nimi']}',
              toim_osoite        = '{$toim_asiakkaantiedot['osoite']}',
              toim_postino       = '{$toim_asiakkaantiedot['postino']}',
              toim_postitp       = '{$toim_asiakkaantiedot['postitp']}',
              toim_maa           = '{$toim_asiakkaantiedot['maa']}',
              valkoodi           = '{$laskuttajan_valkoodi}',
              summa              = '{$laskun_summa_eur}',
              summa_valuutassa   = '{$laskun_summa_eur}',
              kapvm              = '{$laskun_kapvm}',
              kasumma            = '{$laskun_kasumma}',
              kasumma_valuutassa = '{$laskun_kasumma}',
              vienti_kurssi      = 1,
              laatija            = 'finvoice_myyntilasku',
              luontiaika         = now(),
              viite              = '{$laskun_pankkiviite}',
              laskunro           = '{$laskunnro}',
              maksuehto          = '{$maksuehto['tunnus']}',
              tapvm              = '{$laskun_tapvm}',
              erpcm              = '{$laskun_erapaiva}',
              lapvm              = '{$laskun_lapvm}',
              toimaika           = '{$laskun_lapvm}',
              kerayspvm          = '{$laskun_lapvm}',
              alv                = '{$alv}',
              vienti             = '$vienti',
              viesti             = '{$laskun_numero}',
              tila               = 'L',
              alatila            = 'X',
              viikorkopros       = '{$yhtiorow['viivastyskorko']}'";
    pupe_query($query);
    $tunnukset = mysql_insert_id();

    $query = "INSERT INTO laskun_lisatiedot
              SET otunnus       = '{$tunnukset}',
              yhtio             = '{$yhtiorow['yhtio']}',
              laskutus_nimi     = '{$ostaja_asiakkaantiedot['nimi']}',
              laskutus_osoite   = '{$ostaja_asiakkaantiedot['osoite']}',
              laskutus_postino  = '{$ostaja_asiakkaantiedot['postino']}',
              laskutus_postitp  = '{$ostaja_asiakkaantiedot['postitp']}',
              laskutus_maa      = '{$ostaja_asiakkaantiedot['maa']}',
              laatija           = 'finvoice_myyntilasku',
              luontiaika        = NOW()";
    pupe_query($query);

    // Luodaan rivit
    // Käytettävä tuote
    $query = "SELECT *
              FROM tuote
              WHERE yhtio = '{$yhtiorow['yhtio']}'
              AND tuoteno = 'finvoice_myyntilasku'";
    $tuoteres = pupe_query($query);

    if (mysql_num_rows($tuoteres) == 0) {
      $query = "INSERT INTO tuote SET
                yhtio       = '{$yhtiorow['yhtio']}',
                tuoteno     = 'finvoice_myyntilasku',
                ei_saldoa   = 'o',
                nimitys     = 'finvoice_myyntilasku'";
      pupe_query($query);
    }

    foreach ($rtuoteno as $tuoterivi) {
      // Nämä muuttujat ovat valinnaisia:
      #RIVINTIEDOT:
      #echo $tuoterivi["ale"]."\n";
      #echo $tuoterivi["alv"]."\n";
      #echo $tuoterivi["hinta"]."\n";
      #echo $tuoterivi["kauttalaskutus"]."\n";
      #echo $tuoterivi["kommentti"]."\n";
      #echo $tuoterivi["kpl"]."\n";
      #echo $tuoterivi["laskutettuaika"]."\n";
      #echo $tuoterivi["nimitys"]."\n";
      #echo $tuoterivi["rivihinta"]."\n";
      #echo $tuoterivi["rivihinta_verolli"]."\n";
      #echo $tuoterivi["riviinfo"]."\n";
      #echo $tuoterivi["riviviite"]."\n";
      #echo $tuoterivi["tilaajanrivinro"]."\n";
      #echo $tuoterivi["tuoteno"]."\n";
      #echo $tuoterivi["yksikko"]."\n";
      #echo $tuoterivi["tilinumero"]."\n";
      #echo $tuoterivi["rivihinta_valuutassa"]."\n";

      $query = "INSERT into tilausrivi set
                kpl                  = '{$tuoterivi['kpl']}',
                tilkpl               = '{$tuoterivi['kpl']}',
                otunnus              = {$tunnukset},
                kerayspvm            = '{$laskun_lapvm}',
                laatija              = 'finvoice_myyntilasku',
                laadittu             = {$laskun_lapvm},
                toimitettu           = 'finvoice_myyntilasku',
                toimitettuaika       = '{$laskun_lapvm}',
                keratty              = 'finvoice_myyntilasku',
                kerattyaika          = '{$laskun_lapvm}',
                toimaika             = '{$laskun_lapvm}',
                laskutettu           = 'finvoice_myyntilasku',
                laskutettuaika       = {$laskun_lapvm},
                yhtio                = '{$yhtiorow['yhtio']}',
                tuoteno              = 'finvoice_myyntilasku',
                ale1                 = '{$tuoterivi['ale']}',
                yksikko              = '{$tuoterivi['yksikko']}',
                try                  = '',
                osasto               = '',
                alv                  = '{$tuoterivi['alv']}',
                hinta                = '{$tuoterivi['hinta']}',
                nimitys              = '{$tuoterivi['tuoteno']} / {$tuoterivi['nimitys']}',
                kate                 = '0',
                rivihinta            = '{$tuoterivi['rivihinta']}',
                rivihinta_valuutassa = '{$tuoterivi['rivihinta']}',
                tyyppi               = 'L',
                kommentti            = '{$tuoterivi['kommentti']}'";
      pupe_query($query);
    }

    // Kustannuspaikkakäsittely
    $kateinen = "";

    // Tehdään ulasku ja tiliöidään lasku
    require "tilauskasittely/teeulasku.inc";

    $tquery = " UPDATE lasku
                SET alatila = 'X'
                WHERE tunnus = '{$uusiotunnus}'
                and yhtio = '{$yhtiorow['yhtio']}'";
    pupe_query($tquery);
  }
}
