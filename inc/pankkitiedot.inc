<?php

/*
  Tunnistetaan pankki tilinumeron perusteella palautetaan
  $pankki Jossa pankin tietoja arrayssa

  et = erätietue
  mt = maksutapahtumatietue
  st = summatietue

  $lum_eumaksu = "jos pankki vaatii erillisen eumaksun eikä osaa kääntää maksumääräystä eumaksuksi vaikka sen kuuluisi se tehdä..."
  $lum_maksutapa = "ulkomaan maksutavan tunnus"
  $pankki["pankkilaitos"] = "Pankin nimi"
  $pankki["rahalaitos"] = "Pankin rahalaitostunnus"
  $lmp_st_4kentt = LM03 summatietueen kenttä 4
  $lmp_mt_4kentt = LM03 maksutietueen kenttä 4
  $swift = Pankin swift-koodi
*/

if (!function_exists("pankkitiedot")) {
  function pankkitiedot($tilino, $asiakastunnus) {
    global $yhtiorow;

    // Onko IBAN?
    if (substr($tilino, 0, 2) == "FI") {
      $rahalaitoskoodi1 = substr($tilino, 4, 1);
      $rahalaitoskoodi2 = substr($tilino, 5, 1);
      $rahalaitoskoodi3 = substr($tilino, 4, 3);
    }
    else {
      $rahalaitoskoodi1 = substr($tilino, 0, 1);
      $rahalaitoskoodi2 = substr($tilino, 1, 1);
      $rahalaitoskoodi3 = substr($tilino, 0, 3);
    }

    // oletuksia
    // väliviivallinen ytunnus 11 merkkiä
    $ytunnus = sprintf("%08.8s", str_replace('-', '', $yhtiorow["ytunnus"]));
    $ytunnus = substr($ytunnus, 0, 7) . "-" . substr($ytunnus, -1);
    $ytunnus_valiviivalla = sprintf('%-11.11s', $ytunnus);
    $ytunnus = $yhtiorow["ytunnus"];

    $pankki = array();

    $pankki["lum_maksutapa"] = "M"; // maksumääräys
    $pankki["ytunnus"] = $ytunnus;
    $pankki["lmp_mt_4kentta"] = "$tilino";
    $pankki["lmp_st_4kentta"] = "$tilino";
    $pankki["tiedostonimi"] = "XXXXXXXX.DAT";
    $pankki["tiedostonimilum2"] = "XXXXLUM2.DAT";

    switch ($rahalaitoskoodi1) {
    case (1) : // Nordea
      $pankki["rahalaitos"] = '1 ';
      $pankki["pankkilaitos"] = "Nordea";
      $pankki["tiedostonimi"] = "SOLOLMP.DAT";
      $pankki["swift"] = "NDEAFIHH";
      break;
    case (2) : // Nordea
      $pankki["rahalaitos"] = '2 ';
      $pankki["pankkilaitos"] = "Nordea";
      $pankki["tiedostonimi"] = "SOLOLMP.DAT";
      $pankki["swift"] = "NDEAFIHH";
      break;
    case (3) :
      switch ($rahalaitoskoodi2) {
      case (1) : // Handelsbanken
        $pankki["rahalaitos"] = '  ';
        $pankki["pankkilaitos"] = "Handelsbanken";
        $pankki["swift"] = "HANDFIHH";
        break;
      case (3) : // Skandinaviska Enskilda Banken
        $pankki["rahalaitos"] = '  ';
        $pankki["pankkilaitos"] = "Skandinaviska Enskilda Banken";
        $pankki["swift"] = "ESSEFIHX";
        break;
      case (4) : // Danske Bank
        $pankki["rahalaitos"] = '  ';
        $pankki["pankkilaitos"] = "Danske Bank";
        $pankki["swift"] = "DABAFIHX";
        break;
      case (7) : // DNB Bank ASA
        $pankki["rahalaitos"] = '  ';
        $pankki["pankkilaitos"] = "DNB Bank ASA";
        $pankki["swift"] = "DNBAFIHX";
        break;
      case (8) : // Swedbank
        $pankki["rahalaitos"] = '  ';
        $pankki["pankkilaitos"] = "Swedbank";
        $pankki["swift"] = "SWEDFIHH";
        break;
      case (6) : // S-Pankki
      case (9) : // S-Pankki
        $pankki["rahalaitos"] = '  ';
        $pankki["pankkilaitos"] = "S-Pankki";
        $pankki["swift"] = "SBANFIHH";
        break;
      }
      break;
    case (4) :
      switch ($rahalaitoskoodi3) {
      case (470) :
      case (471) :
      case (472) :
      case (473) :
      case (474) :
      case (475) :
      case (476) :
      case (477) :
      case (478) :
      case (479) :
        // Paikallisosuuspankit (POP) ja Bonum pankki
        $pankki["rahalaitos"] = '4 ';
        $pankki["pankkilaitos"] = "Paikallisosuuspankit ja Bonum pankki";
        $pankki["tiedostonimilum2"] = "XXXXLUM2.DAT";
        $pankki["ytunnus"] = sprintf("%010.10s", $yhtiorow["ytunnus"])." ";
        $pankki["swift"] = "POPFFI22";
        break;
      case (405) :
      case (497) :
        // Aktia Pankki
        $pankki["rahalaitos"] = '4 ';
        $pankki["pankkilaitos"] = "Aktia";
        $pankki["tiedostonimilum2"] = "XXXXLUM2.DAT";
        $pankki["ytunnus"] = sprintf("%010.10s", $yhtiorow["ytunnus"])." ";
        $pankki["swift"] = "HELSFIHH";
        break;
      default :
        // Säästöpankit (Sp)
        $pankki["rahalaitos"] = '4 ';
        $pankki["pankkilaitos"] = "Säästöpankit";
        $pankki["tiedostonimilum2"] = "XXXXLUM2.DAT";
        $pankki["ytunnus"] = sprintf("%010.10s", $yhtiorow["ytunnus"])." ";
        $pankki["swift"] = "ITELFIHH";
        break;
      }
      break;
    case (5) : // osuuspankit (Op), OKO ja Okopankki
      $pankki["rahalaitos"] = '5 ';
      $pankki["pankkilaitos"] = "Pohjola Pankki";
      $pankki["ytunnus"] = $ytunnus_valiviivalla;
      $pankki["lum_eumaksu"] = "B";
      $pankki["lum_et_5kentta"] = sprintf('%05s', $asiakastunnus);
      $pankki["lum_st_5kentta"] = sprintf('%05s', $asiakastunnus);
      $pankki["lmp_mt_4kentta"] = "$tilino";
      $pankki["lmp_st_4kentta"] = "$tilino";
      $pankki["tiedostonimi"] = "OKOMP.DAT";
      $pankki["swift"] = "OKOYFIHH";
      break;
    case (6) : //Ålandsbanken
      $pankki["rahalaitos"] = '  ';
      $pankki["pankkilaitos"] = "Ålandsbanken";
      $pankki["swift"] = "AABAFI22";
      break;
    case (7) :
      switch ($rahalaitoskoodi3) {
      case (711) : // Calyon Pankki
        $pankki["rahalaitos"] = '  ';
        $pankki["pankkilaitos"] = "Calyon";
        $pankki["swift"] = "BSUIFIHH";
        break;
      case (715) : // Itella Pankki
        $pankki["rahalaitos"] = '  ';
        $pankki["pankkilaitos"] = "Itella Pankki";
        $pankki["swift"] = "ITELFIHH";
        break;
      case (713) : // Citibank
        $pankki["rahalaitos"] = '  ';
        $pankki["pankkilaitos"] = "Citibank";
        $pankki["swift"] = "CITIFIHX";
        break;
      case (799) : // Holvi
        $pankki["rahalaitos"] = '  ';
        $pankki["pankkilaitos"] = "Holvi";
        $pankki["swift"] = "HOLVFIHH";
        break;
      }
      break;
    case (8) : // Sampo Pankki
      $pankki["rahalaitos"] = '8 ';
      $pankki["pankkilaitos"] = "Sampo Pankki";
      $pankki["tiedostonimi"] = "LEOL2L.DAT";
      $pankki["tiedostonimilum2"] = "LEUM2L.DAT";
      $pankki["swift"] = "DABAFIHH";
      break;
    default :
      $_tilino = substr($tilino, 0, 1);

      // Maksetaan kassasta tai selvittelytililtä
      if (trim($_tilino) != "" and !is_numeric($_tilino)) {
        $pankki["rahalaitos"] = '  ';
        $pankki["pankkilaitos"] = "Kassa_Selivittely";
        $pankki["tiedostonimi"] = "Kassa_Selivittely.DAT";
        $pankki["tiedostonimilum2"] = "Kassa_Selivittely_ulk.DAT";
      }
      break;
    }

    if ($pankki["rahalaitos"] == "" or strlen($pankki["rahalaitos"]) != 2) {
      echo "Tilinumerosta $tilino ei voitu tunnistaa rahalaitoskoodia!\n";
      return false;
    }
    else {
      return $pankki;
    }
  }
}
