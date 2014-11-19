<?php

// Kutsutaanko CLI:stä
if (php_sapi_name() != 'cli') {
  require "inc/parametrit.inc";
}
else {
  error_reporting(E_ALL);
  ini_set("display_errors", 1);

  require "inc/connect.inc";
  require "inc/functions.inc";

  if ($argv[1] == '') {
    echo "Yhtiötä ei ole annettu, ei voida toimia\n";
    die;
  }
  else {
    $kukarow["yhtio"] = $argv[1];
  }
}

// määritellään polut
if (!isset($teccomkansio)) {
  $teccomkansio = "/home/teccom";
}
if (!isset($teccomkansio_valmis)) {
  $teccomkansio_valmis = "/home/teccom/ok";
}
if (!isset($teccomkansio_error)) {
  $teccomkansio_error = "/home/teccom/error";
}

// setataan käytetyt muuttujat:
$asn_numero               = "";
$kukarow["kuka"]          = "admin";
$poikkeukset              = array("123001", "123067", "123310", "123312", "123342", "123108", "123036", "123049", "123317", "123441", "123080", "123007", "123453", "123506", "123110");
$tavarantoimittajanumero  = "";
$tiedosto_sisalto         = "";
$toimituspvm              = "";
$vastaanottaja            = "";
$_yhtion_toimipaikka      = 0;

if (!function_exists('teccom_asn_paketti')) {
  function teccom_asn_paketti($element, $tavarantoimittajanumero, $asn_numero) {

    $_sscc = "";
    $_laatikkoind = "";

    if (isset($element->PkgId->PkgIdentNumber) and $tavarantoimittajanumero != "123007") {
      $laatikko = (string) $element->PkgId->PkgIdentNumber;
      $laatikko = utf8_decode($laatikko);
      $koodi = $laatikko;

      $_toimittajat_1 = array("123001", "123049", "123108", "123506", "123110");
      $_onko_toimittaja_1 = in_array($tavarantoimittajanumero, $_toimittajat_1);

      $_toimittajat_2 = array("123001", "123108", "123506", "123110");
      $_onko_toimittaja_2 = in_array($tavarantoimittajanumero, $_toimittajat_2);

      if ($_onko_toimittaja_1 and strlen($laatikko) >10) {
        $_sscc = $laatikko;
        $_laatikkoind = substr($laatikko, 10);
      }
      elseif ($_onko_toimittaja_2 and strlen($laatikko) < 10) {
        $_sscc = $laatikko;
        $_laatikkoind = '0'.$laatikko;
      }
      elseif ($tavarantoimittajanumero == "123342") {
        $_sscc = $laatikko;
        $_laatikkoind = substr($laatikko, 8);
      }
      else {
        $_sscc = $koodi;
        $_laatikkoind  = $laatikko;
      }
    }
    elseif ($tavarantoimittajanumero == "123441" and !isset($element->PkgId->PkgIdentNumber)) {
      $_laatikkoind = $asn_numero;
      $_sscc = $asn_numero;
    }
    elseif ($tavarantoimittajanumero == "123007") {
      $laatikko = $asn_numero;

      foreach ($element->PkgId as $pkg) {
        if (isset($pkg->PkgIdentSystem) and (int) $pkg->PkgIdentSystem == 17) {
          $laatikko = (string) $pkg->PkgIdentNumber;
          break;
        }
      }

      $_laatikkoind = $laatikko;
      $_sscc = $laatikko;
    }
    elseif ($tavarantoimittajanumero == "123220" or $tavarantoimittajanumero == "123080") {
      $_laatikkoind = $asn_numero;
      $_sscc = $asn_numero;
    }
    else {
      $_laatikkoind = $asn_numero;
      $_sscc = $asn_numero;
    }

    return array($_sscc, $_laatikkoind);
  }
}

function loop_packet($xml_element, $parameters) {
  global $kukarow;
  static $paketti_nro = 0;
  static $tunnus_liitetiedostoon = 0;

  $tavarantoimittajanumero = $parameters["tavarantoimittajanumero"];
  $asn_numero              = $parameters["asn_numero"];
  $toimituspvm             = $parameters["toimituspvm"];
  $vastaanottaja           = $parameters["vastaanottaja"];
  $pakkauslista            = $parameters["pakkauslista"];
  $sscc                    = $parameters["sscc"];
  $laatikkoind             = $parameters["laatikkoind"];

  foreach ($xml_element as $key => $element) {

    // Tämä on tuote-elementti
    if ($key == "PkgItem") {

      $tuote          = (string) $element->ProductId->ProductNumber;
      $tuote          = utf8_decode(trim($tuote));
      $tuote2          = (string) $element->ProductId->BuyerProductNumber;
      $tuote2          = utf8_decode(trim($tuote2));
      $kpl          = (float) $element->DeliveredQuantity->Quantity;
      $tilausrivinpositio    = (int) $element->OrderItemRef->BuyerOrderItemRef;
      $tuotteelta_tilausno  = (int) $element->OrderRef->BuyerOrderNumber;

      if ($kpl > 0.0 and $tuote != "") {

        $toim_tuoteno_wherelisa = trim($tuote2) != "" ? "AND tt.toim_tuoteno IN ('{$tuote}','{$tuote2}')" : "AND tt.toim_tuoteno = '{$tuote}'";

        $query = "SELECT tt.*
                  FROM tuotteen_toimittajat AS tt
                  JOIN toimi ON (toimi.yhtio = tt.yhtio AND toimi.tunnus = tt.liitostunnus AND toimi.toimittajanro = '{$tavarantoimittajanumero}' AND toimi.tyyppi != 'P')
                  WHERE tt.yhtio = 'artr'
                  {$toim_tuoteno_wherelisa}";
        $chk_res = pupe_query($query);

        if (mysql_num_rows($chk_res) != 0) {
          $chk_row = mysql_fetch_assoc($chk_res);
          $tuote = $chk_row['toim_tuoteno'];
        }
        elseif (mysql_num_rows($chk_res) == 0) {
          // haetaan vaihtoehtoisten tuotenumeroiden (tuotteen_toimittajat_tuotenumerot) kautta tuotteen_toimittajat.toim_tuoteno. Osataan myös hakea vaihtoehtoinen tuotenumero ilman että
          $chk_res = tuotteen_toimittajat_tuotenumerot_haku($tuote, $tavarantoimittajanumero);

          if (mysql_num_rows($chk_res) != 0) {
            $chk_row = mysql_fetch_assoc($chk_res);
            $tuote = $chk_row['toim_tuoteno'];
          }
          else {

            if (trim($tuote2) != "") {
              $chk_res = tuotteen_toimittajat_tuotenumerot_haku($tuote2, $tavarantoimittajanumero);

              if (mysql_num_rows($chk_res) != 0) {
                $chk_row = mysql_fetch_assoc($chk_res);
                $tuote = $chk_row['toim_tuoteno'];
              }
            }
          }
        }

        $query = "SELECT tuotteen_toimittajat.tuotekerroin
                  FROM toimi
                  JOIN tuotteen_toimittajat ON (tuotteen_toimittajat.yhtio = toimi.yhtio
                                AND tuotteen_toimittajat.liitostunnus  = toimi.tunnus
                                AND tuotteen_toimittajat.toim_tuoteno  = '{$tuote}'
                                AND tuotteen_toimittajat.toim_tuoteno != '')
                  JOIN tuote ON (tuote.yhtio = tuotteen_toimittajat.yhtio
                          AND tuote.tuoteno                            = tuotteen_toimittajat.tuoteno
                          AND tuote.status                            != 'P')
                  WHERE toimi.yhtio                                    = '{$kukarow['yhtio']}'
                  AND toimi.toimittajanro                              = '{$tavarantoimittajanumero}'
                  AND toimi.tyyppi                                    != 'P'
                  AND toimi.asn_sanomat                                = 'K'";
        $tuotekerroin_chk_res = pupe_query($query);

        if (mysql_num_rows($tuotekerroin_chk_res) > 0) {
          $tuotekerroin_chk_row = mysql_fetch_assoc($tuotekerroin_chk_res);

          if ($tuotekerroin_chk_row['tuotekerroin'] != 0) {
            $kpl /= $tuotekerroin_chk_row['tuotekerroin'];
          }
        }

        // tämä siksi ettei haluta tallentaa 0 rivejä kantaan.
        $sqlinsert =  "INSERT INTO asn_sanomat SET
                       yhtio              = '$kukarow[yhtio]',
                       laji               = 'asn',
                       toimittajanumero   = '$tavarantoimittajanumero',
                       asn_numero         = '$asn_numero',
                       sscc_koodi         = '$sscc',
                       saapumispvm        = '$toimituspvm',
                       vastaanottaja      = '$vastaanottaja',
                       tilausnumero       = '$tuotteelta_tilausno',
                       paketinnumero      = '$paketti_nro',
                       paketintunniste    = '$laatikkoind',
                       lahetyslistannro   = '$pakkauslista',
                       toim_tuoteno       = '$tuote',
                       toim_tuoteno2      = '$tuote2',
                       kappalemaara       = '$kpl',
                       tilausrivinpositio = '$tilausrivinpositio',
                       laatija            = '$kukarow[kuka]',
                       luontiaika         = now()";
        $result = pupe_query($sqlinsert);
        $tunnus_liitetiedostoon = mysql_insert_id($GLOBALS["masterlink"]);
      }
    }

    // Tämä on paketti-elementti
    if ($key == "Package") {
      $paketti_nro++;

      $parameters = array(
        "tavarantoimittajanumero"   => $tavarantoimittajanumero,
        "asn_numero"                => $asn_numero,
        "toimituspvm"               => $toimituspvm,
        "vastaanottaja"             => $vastaanottaja,
        "pakkauslista"              => $pakkauslista,
      );

      list($_sscc, $_laatikkoind) = teccom_asn_paketti($element, $tavarantoimittajanumero, $asn_numero);

      $parameters['sscc'] = $_sscc;
      $parameters['laatikkoind'] = $_laatikkoind;

      loop_packet($element, $parameters);
    }
  }

  return $tunnus_liitetiedostoon;
}

if ($handle = opendir($teccomkansio)) {
  while (($file = readdir($handle)) !== FALSE) {
    if (is_file($teccomkansio."/".$file)) {

      $tiedosto = $teccomkansio."/".$file;

      // Otetaan tiedoston sisältö muuttujaan
      $xml_content = file_get_contents($tiedosto);
      // Poistetaan kaikki "non-printable" merkit
      $xml_content = preg_replace("/[^[:print:]]/", "", $xml_content);
      // Korvataan "UTF-16" string "UTF-8":lla, koska XML pitää olla UTF-8
      $xml_content = str_replace("\"UTF-16\"", "\"UTF-8\"", $xml_content);
      // Muutetaan muuttujan enkoodaus vielä UTF-8:ksi
      $xml_content = iconv("UTF-8", "UTF-8//IGNORE", $xml_content);
      // Tehdään muuttujasta XML olio
      $xml = @simplexml_load_string($xml_content);

      $tiedosto_sisalto = file_get_contents($tiedosto);
      $tiedosto_sisalto = mysql_real_escape_string($tiedosto_sisalto);

      if ($xml !== FALSE) {

        // $tavarantoimittajanumero ja $asn_numero arvoa pitää olla tai ei tule toimimaan.
        $tavarantoimittajanumero = (string) $xml->DesAdvHeader->SellerParty->PartyNumber;
        $tavarantoimittajanumero = utf8_decode($tavarantoimittajanumero);

        if (strtoupper($tavarantoimittajanumero) == "ELRING") {
          $tavarantoimittajanumero = "123312";
        }
        elseif (strtoupper($tavarantoimittajanumero) == "BOSCH" or strtoupper($tavarantoimittajanumero) == "AA_FI") {
          $tavarantoimittajanumero = "123067";
        }
        elseif (strtoupper($tavarantoimittajanumero) == "NISSENS") {
          $tavarantoimittajanumero = "123403";
        }
        elseif ($tavarantoimittajanumero == "112") {
          $tavarantoimittajanumero = "123442";
        }
        elseif (strtoupper($tavarantoimittajanumero) == "LES-7") {
          $tavarantoimittajanumero = "123080";
        }
        elseif (strtoupper($tavarantoimittajanumero) == "123035") {
          $tavarantoimittajanumero = "123036";
        }

        $asn_numero  = (string) $xml->DesAdvHeader->DesAdvId;
        $asn_numero = utf8_decode($asn_numero);

        $toimituspvm = tv3dateconv($xml->DesAdvHeader->DeliveryDate->Date);
        $vastaanottaja = (string) $xml->DesAdvHeader->DeliveryParty->PartyNumber." , ".trim($xml->DesAdvHeader->DeliveryParty->Address->Name1);
        $vastaanottaja = utf8_decode($vastaanottaja);

        // Haetaan pakkauslistan referenssinumero, mikäli löytyy
        if (isset($xml->Package->Package->PkgRef->PkgRefNumber) and $xml->Package->Package->PkgRef->PkgRefNumber != "") {
          $pakkauslista = $xml->Package->Package->PkgRef->PkgRefNumber;
          $pakkauslista = utf8_decode($pakkauslista);
          // Mikäli paketin sisällä on paketti
        }
        elseif (isset($xml->Package->PkgRef->PkgRefNumber) and $xml->Package->PkgRef->PkgRefNumber != "") {
          $pakkauslista = $xml->Package->PkgRef->PkgRefNumber;
          $pakkauslista = utf8_decode($pakkauslista);
          // normaali tapaus
        }
        elseif (in_array($tavarantoimittajanumero, $poikkeukset)) {
          $pakkauslista = $asn_numero;
          // poikkeustapauksissa
        }
        elseif (isset($xml->Package->PkgInfo->PacketKind)) {
          $pakkauslista = (string) $xml->Package->PkgInfo->PacketKind;
          // poikkeuksen poikkeukset
        }
        else {
          $pakkauslista = $asn_numero;
          // jos mikään ei mätsää, niin laitetaan asn-numero
        }

        $parameters = array(
          "tavarantoimittajanumero" => $tavarantoimittajanumero,
          "asn_numero"              => $asn_numero,
          "toimituspvm"             => $toimituspvm,
          "vastaanottaja"           => $vastaanottaja,
          "pakkauslista"            => $pakkauslista,
        );

        // tässä kohdassa tarkistetaan että löytyykö ASN-sanoma jo kannasta, jos ei niin kutsutaan rekursiivista funkkaria.

        if ($tavarantoimittajanumero != "" and $asn_numero != "") {

          $tarkinsert = "SELECT tunnus
                         FROM asn_sanomat
                         WHERE yhtio          = '$kukarow[yhtio]'
                         AND toimittajanumero = '$tavarantoimittajanumero'
                         AND asn_numero       = '$asn_numero'";
          $checkinsertresult = pupe_query($tarkinsert);

          if (mysql_num_rows($checkinsertresult) > 0) {
            echo "Sanomalle $asn_numero ja toimittajalle $tavarantoimittajanumero löytyy tietokannasta jo sanomat, ei lisätä uudestaan sanomia\n";
            rename($teccomkansio."/".$file, $teccomkansio_error."/".$file);
          }
          else {

            if (isset($xml->Package->Package)) {
              $element = $xml->Package->Package;
            }
            else {
              $element = $xml->Package;
            }

            list($_sscc, $_laatikkoind) = teccom_asn_paketti($element, $tavarantoimittajanumero, $asn_numero);

            $parameters['sscc'] = $_sscc;
            $parameters['laatikkoind'] = $_laatikkoind;

            // loop_packet funktio tekee kaikki lisäykset asn-sanomatauluun ja palauttaa viimeisen lisätyn rivin mysql_id() joka laitetaan liitetiedostoon.
            $tunnus_liitetiedostoon = loop_packet($xml, $parameters);

            $tecquery = "INSERT INTO liitetiedostot SET
                         yhtio           = '$kukarow[yhtio]',
                         liitos          = 'asn_sanomat',
                         liitostunnus    = '$tunnus_liitetiedostoon',
                         data            = '$tiedosto_sisalto',
                         selite          = '$tavarantoimittajanumero ASN_sanoman $asn_numero tiedosto',
                         filename        = '$file',
                         filesize        = length(data),
                         filetype        = 'text/xml',
                         image_width     = '',
                         image_height    = '',
                         image_bits      = '',
                         image_channels  = '',
                         kayttotarkoitus = 'TECCOM-ASN',
                         jarjestys       = '1',
                         laatija         = '$kukarow[kuka]',
                         luontiaika      = now()";
            $Xresult = pupe_query($tecquery);
            rename($teccomkansio."/".$file, $teccomkansio_valmis."/".$file);

            // Logitetaan ajo
            cron_log($teccomkansio_valmis."/".$file);
          }
        }
        else {
          echo t("Virhe! Tavarantoimittajan numero puuttuu sekä ASN-numero puuttuu, tai materiaali ei ole ASN-sanoma")."\n";
          rename($teccomkansio."/".$file, $teccomkansio_error."/".$file);
        }
      }
      else {
        echo t("Tiedosto ei ole XML-sanoma").": $tiedosto\n\n";
        rename($teccomkansio."/".$file, $teccomkansio_error."/".$file);
      }
    }
  }

  require "inc/asn_kohdistus.inc";
  asn_kohdistus($tavarantoimittajanumero);
}
else {
  echo "Hakemistoa $teccomkansio ei löydy\n";
}
