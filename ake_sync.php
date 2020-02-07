<?php

//require ("/Users/juppe/Sites/devlab/pupesoft/inc/connect.inc");
//require ("/Users/juppe/Sites/devlab/pupesoft/inc/functions.inc");

require "/var/www/html/pupesoft/inc/connect.inc";
require "/var/www/html/pupesoft/inc/functions.inc";

$timeparts = explode(" ", microtime());
$starttime = $timeparts[1].substr($timeparts[0], 1);

$kuka  = "akesync";

// funktio joka muuttaa ajoneuvon lajin rekkarin vanhanmalliseksi ja valitsee yhtiön jolle se lisätään.
if (!function_exists('ajoneuvokonversio')) {
  function ajoneuvokonversio($laji, $reknro, $kayttoonottopvm, $omamassa, $merkki, $iskutilavuus) {

    $yhtio = "";

    /* Uus malli
    L1  Mopo
    L1e  Mopo
    L2  Mopo (Kolmipyörä)
    L2e  Mopo (Kolmipyörä)
    L3  Moottoripyörä
    L3e  Moottoripyörä
    L4  Moottoripyörä (sivuvaunu)
    L4e  Moottoripyörä (sivuvaunu)
    L5  Kolmipyörä
    L5e  Kolmipyörä
    L6e  Kevyt nelipyörä
    L7e  Nelipyörä
    M1  Henkilöauto
    M1G  Henkilöauto (Maastoauto)
    M2  Linja-auto (enintään 5 tonnia)
    M2G  Linja-auto (enintään 5 tonnia) (Maastoauto)
    M3  Linja-auto (yli 5 tonnia)
    MA  Maastoajoneuvo (esim. Kelkat)
    MUU  Muut (esim. Kelkat)
    N1  Paketti-auto
    N1G  Paketti-auto (Maastoauto)
    N2  Kuorma-auto (enintään 12 tonnia)
    N2G  Kuorma-auto (enintään 12 tonnia) (Maastoauto)
    N3  Kuorma-auto  (yli 12 tonnia)
    N3G  Kuorma-auto (yli 12 tonnia) (Maastoauto)
    */

    /* Vanha malli
    4  Paketti-auto
    5  Henkilöauto
    9  Moottoripyörä
    B  Mopo
    E  Mönkijä/Kolmipyörä
    C  Mönkijä/Kolmipyörä
    D   Mönkijä/Kolmipyörä
    A  Mönkijä/Kolmipyörä
    8  Kelkka
    */

    $ret_laji = "";

    // Vaihdetaan laji vanhaan muotoon
    if ($laji != "") {

      if (substr($laji, 0, 2) == 'M1') {
        $ret_laji = "5"; //Henkilöautot
      }
      elseif (substr($laji, 0, 2) == 'M2' or substr($laji, 0, 2) == 'N1' or substr($laji, 0, 2) == 'N2') {
        $ret_laji = "4"; //Paketti-autot
      }
      elseif (substr($laji, 0, 2) == 'L1' or substr($laji, 0, 2) == 'L2') {
        $ret_laji = "B"; //Mopot
      }
      elseif (substr($laji, 0, 2) == 'L3' or substr($laji, 0, 2) == 'L4') {
        $ret_laji = "9"; //Moottoripyörät
      }
      elseif (substr($laji, 0, 2) == 'L6' or substr($laji, 0, 2) == 'L7') {
        $ret_laji = "A"; //Mönkkärit
      }
      elseif ($laji == 'MA') {
        $ret_laji = "8"; //Kelkat
      }
      elseif ($laji == 'MUU') {
        $rekarray = explode("-", $reknro);

        if (is_numeric($rekarray[0])) {
          $ret_laji = "8"; //Kelkat
        }
        elseif ((int) substr($kayttoonottopvm, 0, 4) >= 1990) {

          if (strlen($rekarray[0]) == 2 and is_numeric($rekarray[1])) {
            $query = "SELECT tunnus
                      FROM yhteensopivuus_mp
                      WHERE yhtio = 'allr'
                      AND merkki  = '$merkki'
                      LIMIT 1";
            $rekres = mysql_query($query) or pupe_error($query);

            if (mysql_num_rows($rekres) > 0) {
              if ((int) $iskutilavuus > 50) {
                $ret_laji = "9"; //Moottoripyörät
              }
              else {
                $ret_laji = "B"; //Mopot
              }
            }
          }

          if ($ret_laji == "" and (int) $omamassa > 500 and (int) $omamassa < 5000) {
            $query = "SELECT tunnus
                      FROM yhteensopivuus_auto
                      WHERE yhtio = 'artr'
                      AND merkki  = '$merkki'
                      LIMIT 1";
            $rekres = mysql_query($query) or pupe_error($query);

            if (mysql_num_rows($rekres) > 0) {
              $ret_laji = "5"; //Henkilöautot
            }
          }
        }
      }
    }

    if ($ret_laji != '') {
      // Annetaan yhtiö lajin perusteella
      if (in_array($ret_laji, array('9', 'B', '8', 'A'))) {
        $yhtio = 'allr';
      }
      else {
        $yhtio = 'artr';
      }
    }

    // echo "$ret_laji, $reknro, $yhtio\n";
    return  array($ret_laji, $yhtio);
  }


}

if ($argv[1] == '' or $argv[2] == '' or $argv[3] == '') {
  echo "Anna tiedosto ja akedatan nimi!!! ja yhtiö(ide)n koodi(t)\n";
  die;
}
else {

  echo "Käsitellään tiedosto: $argv[1]\n";
  echo "Akedata: $argv[2]\n";

  $file = fopen($argv[1], "r") or die ("$argv[1] Ei aukea!\n");

  // luetaan tiedosto alusta loppuun...
  $rivi = fgets($file);

  $lask       = 0;
  $lisatty     = 0;
  $muutettu     = 0;
  $maa      = "";
  $poista        = array("'", "\\", "\"");
  $debugecho    = 0;

  if ($debugecho == 1) echo "yhtio\tmaa\trekkari\tlaji\tvnumero\ttyyppikoodi\tkottopvm\tmerkki\tmalli\trinnakkaismaat\tteho\tkokmassa\tomamassa\tpituus\tkvoima\ttilavuus\tvahapaasto\tvetakselit\trenkaat\teutyyppi\tvariantti\tversio\tmoottorintunnus\n";

  while ($rivi = fgets($file)) {

    $ajoneuvo          = "";
    $ajoneuvoluokka        = "";
    $ajoneuvoryhma        = "";
    $ajoneuvoryhmat        = "";
    $ajonkokpituus        = "";
    $akseli            = "";
    $akselienlkm        = "";
    $erikoisehdonosaalue    = "";
    $erikoisehdot        = "";
    $eutyyppi          = "";
    $iskutilavuus        = "";
    $kaupallinennimi      = "";
    $kayttoonottopvm      = "";
    $kayttovoima        = "";
    $kokmassa          = "";
    $kooditettuhuomautus    = "";
    $koritieto          = "";
    $korityyppi          = "";
    $kottopvm          = "";
    $kuormitusjanopeusluokka  = "";
    $kvoima            = "";
    $laite            = "";
    $laji            = "";
    $lisaa            = "";
    $malli            = "";
    $mallimerkinta        = "";
    $massatieto          = "";
    $merkki            = "";
    $merkkiselvakielinen    = "";
    $mittatieto          = "";
    $moottorintunnus      = "";
    $moottoritieto        = "";
    $offset            = "";
    $omamassa          = "";
    $pakokaasunpuhdistus    = "";
    $perustieto          = "";
    $pituus            = "";
    $rakennetieto        = "";
    $rekkari          = "";
    $rengaskoko          = "";
    $rengaslaji          = "";
    $renkaat          = "";
    $renkaat2          = "";
    $renkaat3          = "";
    $renkaat4          = "";
    $renkaatakseleittain    = "";
    $rinnakkaismaat        = "";
    $sijainti          = "";
    $suurinnettoteho      = "";
    $teho            = "";
    $teknsuursallkokmassa    = "";
    $teksti            = "";
    $tieliiksuursallkokmassa  = "";
    $tilavuus          = "";
    $tyyppihyvaksyntanro    = "";
    $tyyppikoodi        = "";
    $vahapaasto          = "";
    $vaihteisto          = "";
    $valmistajannimi      = "";
    $valmistenumero        = "";
    $vannekoko          = "";
    $variantti          = "";
    $versio            = "";
    $vetakselit          = "";
    $vetava            = "";
    $vnumero          = "";
    $yksittainmaahantuotu    = "";

    if ($maa == "" and trim($rivi) == "Regnr;Fabrikat;Benamning;Fordonsslag;Chassienr;Totalvikt;RegÅr;Årsmodell;CylVol;Kw;DäckDimBak;DäckDimFram") {
      // Skipataan headerrivi
      $rivi = fgets($file);
      $maa = "SE";
    }
    elseif ($maa == "") {
      $maa = "FI";
    }

    // luetaan rivi tiedostosta..
    $rivi = str_replace($poista, "", $rivi);

    if ($maa == "SE") {

      // Ruotsin UUSI FORBA formaatti
      // 0  Regnr;
      // 1  Fabrikat;
      // 2  Benamning;
      // 3  Fordonsslag;
      // 4  Chassienr;
      // 5  Totalvikt;
      // 6  RegÅr;
      // 7  Årsmodell;
      // 8  CylVol;
      // 9  Kw;
      // 10  DäckDimBak;
      // 11  DäckDimFram
      $tiedot    = explode("\t", $rivi);

      $yhtio       = "allr";
      $rekkari    = trim($tiedot[0]);

      if (trim($tiedot[3]) == "MC") {
        if ((int) trim($tiedot[8]) <= 50) {
          $laji  = "B";
        }
        else {
          $laji  = "9";
        }
      }
      else {
        $laji    = "8";
      }

      $vnumero    = trim($tiedot[4]);
      $tyyppikoodi  = "";

      if (trim($tiedot[7]) == "0000") {
        $kottopvm   = trim($tiedot[6]);
      }
      else {
        $kottopvm  = trim($tiedot[7]);
      }

      $merkki      = str_replace(".", "", trim($tiedot[2]));
      $malli      = str_replace($merkki, "", trim($tiedot[1]));
      $rinnakkaismaat  = "";
      $teho      = trim($tiedot[9]);
      $kokmassa    = trim($tiedot[5]);
      $omamassa    = trim($tiedot[5]);
      $pituus      = "";
      $kvoima      = 1;
      $tilavuus    = trim($tiedot[8]);
      $vahapaasto    = "";
      $vetakselit    = 1;
      $renkaat    = trim($tiedot[10]);
      $renkaat2    = trim($tiedot[11]);
      $renkaat3    = "";
      $renkaat4    = "";
      $eutyyppi    = "";
      $variantti    = "";
      $versio      = "";

    }
    else {

      $rivi = str_replace('/;', '###', $rivi);
      $rivi = str_replace('/>', '%%%', $rivi);

      $tiedot = explode(">", $rivi);

      /*
      Rakenne:
      Tietueet koostuvat erotinmerkeillä erotetuista kentistä. Erotinmerkkejä on kahdella tasolla.
      Uloimmalla erotinmerkillä (>) erotetaan erilaiset tietoryhmät toisistaan.
      Toisella tasolla erotinmerkkinä käytetään ;-merkkiä. Sillä erotellaan tietoryhmän kentät tai toistuvat tiedot tietoryhmässä. Erotinmerkki on aina kenttien välissä.
      Mikäli erotinmerkki on jossain välitettävässä tiedossa, on sen eteen lisätty merkki /. Eli /; tarkoittaa kentän tietoon kuuluvaa ;-merkkiä, eikä se toimi kenttien erotin-merkkinä.

      Tietotyypit:
      Täsmällinen päivämäärä esitetään muodossa date: yyyy-mm-dd. Päivämäärissä joissa sallitaan puutteellinen tieto (string(8)), esitys on muotoa yyyymmdd,
      yyyymm00, yyyy0000 tai 000000000.
      Datetime-tietoyyppi esitetään muodossa: 2001-12-17T09:30:47
      Boolean-tyyppinen tieto esitetään arvoina: true tai false
      Koodi on merkkimuotoinen(string) tieto.
      */

      // yläluokka
      $ajoneuvo        = explode(";", $tiedot[0]);
      // alaluokat                tietotyyppi  esiintymiskerrat  koodisto
      $rekkari        = trim($ajoneuvo[0]); //   string(9)  0-1
      $valmistenumero      = trim($ajoneuvo[1]); //   string(30)  1
      $ajoneuvoluokka      = trim($ajoneuvo[2]); //   koodi    1          kdtyytiajoneuvoluokka
      $merkki          = trim($ajoneuvo[3]); //   koodi    1
      $merkkiselvakielinen  = trim($ajoneuvo[4]); //   string(99)  1
      $mallimerkinta      = trim($ajoneuvo[5]); //   string(200)  0-1

      //$debugecho .="||AJONEUVO||\t$rekkari\t$valmistenumero\t$ajoneuvoluokka\t$merkki\t$merkkiselvakielinen\t$mallimerkinta\t";

      // yläluokka
      $ajoneuvoryhmat      = explode(";", $tiedot[1]);
      // alaluokat                  tietotyyppi  esiintymiskerrat  koodisto
      //$ajoneuvoryhma    = trim($ajoneuvoryhmat[0]); //  koodi    0-4

      if (count($ajoneuvoryhmat) > 0) {
        $tmp_ajoneuvoryhma = "";

        foreach ($ajoneuvoryhmat as $ajoneuvoryhma) {
          $tmp_ajoneuvoryhma .= "/".$ajoneuvoryhma;
        }

        $ajoneuvoryhma = substr($tmp_ajoneuvoryhma, 1);
      }

      //$debugecho .="||AJONEUVORYHMAT||\t$ajoneuvoryhma\t";

      // yläluokka
      $perustieto        = explode(";", $tiedot[2]);
      // alaluokat                tietotyyppi  esiintymiskerrat  koodisto
      $tyyppihyvaksyntanro  = trim($perustieto[0]); //  string(30)  0-1
      $variantti        = trim($perustieto[1]); //  string(40)  0-1
      $versio          = trim($perustieto[2]); //  string(40)   0-1
      $tyyppikoodi      = trim($perustieto[3]); //  string    0-1
      $kayttoonottopvm    = trim($perustieto[4]); //  string(8)  1
      $valmistajannimi    = trim($perustieto[5]); //  string(100)  0-1
      $yksittainmaahantuotu  = trim($perustieto[6]); //  koodi    0-1

      //$debugecho .="||PERUSTIETO||\t$tyyppihyvaksyntanro\t$variantti\t$versio\t$tyyppikoodi\t$kayttoonottopvm\t$valmistajannimi\t$yksittainmaahantuotu\t";

      // yläluokka
      $rakennetieto      = explode(";", $tiedot[3]);
      // alaluokat                  tietotyyppi  esiintymiskerrat  koodisto
      $akselienlkm      = trim($rakennetieto[0]); //  integer    1
      $vaihteisto         = trim($rakennetieto[1]); //  koodi    0-1
      $kaupallinennimi    = trim($rakennetieto[2]); //  string(100)  1

      //$debugecho .="||RAKENNETIETO||\t$akselienlkm\t$vaihteisto\t$kaupallinennimi\t";

      // yläluokka
      $koritieto        = explode(";", $tiedot[4]);
      // alaluokat                tietotyyppi  esiintymiskerrat  koodisto
      $korityyppi        = trim($koritieto[0]); //  koodi    0-1

      //$debugecho .="||KORITIETO||\t$korityyppi\t";

      // yläluokka
      $moottoritieto      = explode(";", $tiedot[5]);
      // alaluokat                  tietotyyppi  esiintymiskerrat  koodisto
      $kayttovoima      = trim($moottoritieto[0]); //  koodi    0-1
      $iskutilavuus      = trim($moottoritieto[1]); //  integer    0-1
      $suurinnettoteho    = trim($moottoritieto[2]); //  integer    0-1
      $moottorintunnus    = trim($moottoritieto[3]); //  string(99)  0-1

      //$debugecho .="||MOOTTORITIETO||\t$kayttovoima\t$iskutilavuus\t$suurinnettoteho\t$moottorintunnus\t";

      if ($tiedot[6] != '') {
        $vahapaasto = '1';
      }
      else {
        $vahapaasto = '';
      }

      //$debugecho .="||PAKOKAASUNPUHDISTUS||\t$vahapaasto\t";

      // yläluokka
      $massatieto          = explode(";", $tiedot[7]);
      // alaluokat                    tietotyyppi  esiintymiskerrat  koodisto
      $omamassa          = trim($massatieto[0]);  //  integer    0-1
      $teknsuursallkokmassa    = trim($massatieto[1]);  //  integer    0-1
      $tieliiksuursallkokmassa  = trim($massatieto[2]);   //  integer    0-1

      //$debugecho .="||MASSATIETO||\t$omamassa\t$teknsuursallkokmassa\t$tieliiksuursallkokmassa\t";

      // yläluokka
      $mittatieto        = explode(";", $tiedot[8]);
      // alaluokat                  tietotyyppi  esiintymiskerrat  koodisto
      $ajonkokpituus      = trim($mittatieto[0]); //  integer    0-1

      //$debugecho .="||MITTATIETO||\t$ajonkokpituus\t";

      // yläluokka
      $akseli          = explode(";", $tiedot[9]);
      // alaluokat                tietotyyppi  esiintymiskerrat  koodisto
      $sijainti        = trim($akseli[0]); //  integer
      $vetava          = trim($akseli[1]); //  boolean

      $vetakselit = 1;

      if (trim($akseli[1]) == 'true' and trim($akseli[3]) == 'true') {
        $vetakselit = 2;
      }

      //$debugecho .="||AKSELI||\t$sijainti\t$vetava\t";

      // yläluokka
      $renkaatakseleittain  = explode(";", $tiedot[10]);
      // alaluokat                        tietotyyppi  esiintymiskerrat  koodisto
      $kokolaskin   = 2;
      $luokkalaskin   = 5;
      $renkaat     = "";

      for ($i=0; $i < count($renkaatakseleittain); $i++) {

        if ($kokolaskin == $i) {
          $kokolaskin = $kokolaskin+6;
          $renkaat .= trim($renkaatakseleittain[$i]);
        }
        if ($luokkalaskin == $i) {
          $luokkalaskin = $luokkalaskin+6;
          $renkaat .= " ".trim($renkaatakseleittain[$i]).",";
        }
      }

      $sijainti           = trim($renkaatakseleittain[0]); //  integer
      $rengaslaji          = trim($renkaatakseleittain[1]); //  koodi
      $rengaskoko          = trim($renkaatakseleittain[2]); //  string(20)
      $vannekoko          = trim($renkaatakseleittain[3]); //  string(20)
      $offset            = trim($renkaatakseleittain[4]); //  string(15)
      $kuormitusjanopeusluokka  = trim($renkaatakseleittain[5]); //  string(10)

      //$debugecho .="||RENKAATAKSELEITTAIN||\t$sijainti\t$rengaslaji\t$rengaskoko\t$vannekoko\t$offset\t$kuomitusjanopeusluokka\t";

      // yläluokka
      $erikoisehdot      = explode(";", $tiedot[11]);
      // alaluokat                  tietotyyppi  esiintymiskerrat  koodisto
      $erikoisehdonosaalue  = trim($erikoisehdot[0]); //  koodi
      $kooditettuhuomautus  = trim($erikoisehdot[1]); //  koodi
      $teksti          = trim($erikoisehdot[2]); //  string(1500)

      // Rengastietoja voi olla erikoisehdoissa
      for ($i=0; $i < count($erikoisehdot); $i++) {
        if ($renkaatakseleittain[$i] == "2003" and $renkaatakseleittain[$i+1] == "0008/2003") {
          $renkaat .= trim($renkaatakseleittain[$i+2]).",";
        }
      }

      if (substr($renkaat, -1) == ',') {
        $renkaat = substr($renkaat, 0, -1);
      }

      $renkaat = implode(",", array_unique(explode(",", $renkaat)));

      //muutetaan ajoneuvoluokka vanhanmalliseksi.
      $yhtio = "";

      $ajoneuvoluokka_orig = $ajoneuvoluokka;

      list($ajoneuvoluokka, $yhtio) = ajoneuvokonversio($ajoneuvoluokka, $rekkari, $kayttoonottopvm, $omamassa, $merkkiselvakielinen, $iskutilavuus);

      //ohitetaan jos väärä yhtiö
      if (stripos($argv[3], $yhtio) === FALSE) continue;

      $kayttovoima = $kayttovoima * 1;

      if ($kayttovoima == 2) {
        $kayttovoima = 3;
      }

      if (trim($mallimerkinta) == "" and trim($kaupallinennimi) != "") {
        $mallimerkinta = $kaupallinennimi;
      }

      $yhtio      = $yhtio;
      $rekkari    = $rekkari;
      $laji      = $ajoneuvoluokka; // tämä muutetaan vanhaan muotoon
      $vnumero    = $valmistenumero;
      $tyyppikoodi  = $tyyppikoodi;
      $kottopvm    = $kayttoonottopvm;
      $merkki      = strtoupper($merkkiselvakielinen);
      $malli      = $mallimerkinta;
      $rinnakkaismaat  = $yksittainmaahantuotu;
      $teho      = str_replace('.0', '', $suurinnettoteho);
      $kokmassa    = sprintf("%06s", $tieliiksuursallkokmassa);
      $omamassa    = sprintf("%06s", $omamassa);
      $pituus      = sprintf("%06s", $ajonkokpituus/10);
      $kvoima      = $kayttovoima;
      $tilavuus    = $iskutilavuus/1000;
      $vahapaasto    = $vahapaasto;
      $vetakselit    = $vetakselit;
      $renkaat    = $renkaat;
      $eutyyppi    = ""; //eioo millään
      $variantti    = $variantti;
      $versio      = $versio;
      $moottorintunnus= $moottorintunnus; // uus kenttä
    }

    if ($debugecho == 1 and $lask % 1000 == 0) echo "$yhtio\t$maa\t$rekkari\t$laji/$ajoneuvoluokka_orig\t$vnumero\t$tyyppikoodi\t$kottopvm\t$merkki\t$malli\t$rinnakkaismaat\t$teho\t$kokmassa\t$omamassa\t$pituus\t$kvoima\t$tilavuus\t$vahapaasto\t$vetakselit\t$renkaat\t$eutyyppi\t$variantti\t$versio\t$moottorintunnus\n";

    if (trim($rekkari) != '' and $yhtio != '') {

      if ($yhtio == "allr") {
        $mplisa = " and left(kayttoonotto,4) = '".substr($kottopvm, 0, 4)."' ";
      }
      else {
        $mplisa = "";
      }

      $query = "SELECT *
                FROM rekisteritiedot
                WHERE yhtio      = '$yhtio'
                and maa          = '$maa'
                and rekno        = '$rekkari'
                and ajoneuvolaji = '$laji'";
      $result = mysql_query($query) or pupe_error($query);

      if (mysql_num_rows($result) > 0) {
        //tarkastetaan onko sama
        $row = mysql_fetch_array($result);

        // jos rekkari on eri autossa niin pitää tehdä jotain
        if (strtoupper($vnumero) != strtoupper($row["valmistenumero"])) {
          //echo "$rekkari eri auto\t";

          //pitää poistaa vanha
          $query = "DELETE
                    FROM rekisteritiedot
                    WHERE yhtio      = '$yhtio'
                    and maa          = '$maa'
                    and rekno        = '$rekkari'
                    and ajoneuvolaji = '$laji'";
          $result = mysql_query($query) or pupe_error($query);

          //poistaa automallilinkki
          $query = "DELETE
                    FROM yhteensopivuus_rekisteri
                    WHERE yhtio      = '$yhtio'
                    and maa          = '$maa'
                    and rekno        = '$rekkari'
                    and ajoneuvolaji = '$laji'";
          $result = mysql_query($query) or pupe_error($query);

          $lisaa = "X";

          $muutettu ++;
        }
        else {
          //päivitetään tietoja
          $query = "UPDATE rekisteritiedot
                    SET tyyppikoodi    = '$tyyppikoodi',
                    kayttoonotto     = '$kottopvm',
                    merkki           = '$merkki',
                    malli            = '$malli',
                    rinnakkaistuonti = '$rinnakkaismaat',
                    teho             = '$teho',
                    kok_massa        = '$kokmassa',
                    oma_massa        = '$omamassa',
                    pituus           = '$pituus',
                    k_voima          = '$kvoima',
                    moottorin_til    = '$tilavuus',
                    vahapaastoisyys  = '$vahapaasto',
                    vetavat_akselit  = '$vetakselit',
                    renkaat          = '$renkaat',
                    EU_tyyppinumero  = '$eutyyppi',
                    variantti        = '$variantti',
                    versio           = '$versio',
                    moottoritunnus   = '$moottorintunnus',
                    ake_aineisto     = '$argv[2]',
                    muutospvm        = now(),
                    muuttaja         = '$kuka'
                    WHERE yhtio      = '$yhtio'
                    and maa          = '$maa'
                    and rekno        = '$rekkari'
                    and ajoneuvolaji = '$laji'";
          $result = mysql_query($query) or pupe_error($query);
        }
      }
      else {
        $lisaa = "X";
      }

      if ($lisaa == "X") {
        //pitää tehä pari inserttiä ja kattoo jos sen voisi liittää automalliin.
        //echo "$rekkari Lisataan\n";

        $lisatty ++;

        //pitää lisätä uus
        $query = "INSERT INTO rekisteritiedot SET
                  yhtio            = '$yhtio',
                  maa              = '$maa',
                  rekno            = '$rekkari',
                  ajoneuvolaji     = '$laji',
                  valmistenumero   = '$vnumero',
                  tyyppikoodi      = '$tyyppikoodi',
                  kayttoonotto     = '$kottopvm',
                  merkki           = '$merkki',
                  malli            = '$malli',
                  rinnakkaistuonti = '$rinnakkaismaat',
                  teho             = '$teho',
                  kok_massa        = '$kokmassa',
                  oma_massa        = '$omamassa',
                  pituus           = '$pituus',
                  k_voima          = '$kvoima',
                  moottorin_til    = '$tilavuus',
                  vahapaastoisyys  = '$vahapaasto',
                  vetavat_akselit  = '$vetakselit',
                  renkaat          = '$renkaat',
                  EU_tyyppinumero  = '$eutyyppi',
                  variantti        = '$variantti',
                  versio           = '$versio',
                  moottoritunnus   = '$moottorintunnus',
                  kohdistettu      = '',
                  ake_aineisto     = '$argv[2]',
                  laatija          = '$kuka',
                  luontiaika       = now(),
                  muutospvm        = now(),
                  muuttaja         = '$kuka'";
        $insresult = mysql_query($query) or pupe_error($query);

        // Katotaan voidaanko automaattisesti liittää kohdistettuun automalliin
        // Liitetään aitomalliin jos löytyy toinen rekkari samoilla tiedoilla joka on jo liitetty onnistuneesti
        $query = "SELECT distinct autoid as iidee
                  FROM rekisteritiedot use index (merkki_malli)
                  JOIN yhteensopivuus_rekisteri use index (rekno_autoid) using (yhtio,maa,rekno,ajoneuvolaji)
                  WHERE rekisteritiedot.yhtio         = '$yhtio'
                  and rekisteritiedot.maa             = '$maa'
                  and rekisteritiedot.merkki          = '$merkki'
                  and rekisteritiedot.malli           = '$malli'
                  and rekisteritiedot.ajoneuvolaji    = '$laji'
                  and rekisteritiedot.k_voima         = '$kvoima'
                  and rekisteritiedot.moottorin_til   = '$tilavuus'
                  and rekisteritiedot.teho            = '$teho'
                  and rekisteritiedot.vetavat_akselit = '$vetakselit'
                  and rekisteritiedot.vahapaastoisyys = '$vahapaasto'
                  and rekisteritiedot.variantti       = '$variantti'
                  and rekisteritiedot.versio          = '$versio'
                  and rekisteritiedot.moottoritunnus  = '$moottorintunnus'
                  $mplisa
                  and rekisteritiedot.kohdistettu     = 'x'";
        $result = mysql_query($query) or pupe_error($query);

        // jos voi niin liitetään se
        if (mysql_num_rows($result) > 0) {
          while ($row = mysql_fetch_array($result)) {
            //insertti
            $query = "INSERT INTO yhteensopivuus_rekisteri SET
                      yhtio        = '$yhtio',
                      maa          = '$maa',
                      rekno        = '$rekkari',
                      autoid       = '$row[iidee]',
                      ajoneuvolaji = '$laji',
                      laatija      = '$kuka',
                      luontiaika   = now(),
                      muutospvm    = now(),
                      muuttaja     = '$kuka'";
            $insresult = mysql_query($query) or pupe_error($query);
          }

          //ja update
          $query = "UPDATE rekisteritiedot
                    SET kohdistettu = 'X'
                    where yhtio      = '$yhtio'
                    and maa          = '$maa'
                    and rekno        = '$rekkari'
                    and ajoneuvolaji = '$laji'";
          $upresult = mysql_query($query) or pupe_error($query);
        }
        else {
          // Katotaan voidaanko automaattisesti liittää hylättyyn automalliin
          // Liitetään aitomalliin jos löytyy toinen rekkari samoilla tiedoilla joka on jo liitetty
          $query = "SELECT distinct autoid as iidee
                    FROM rekisteritiedot use index (merkki_malli)
                    JOIN yhteensopivuus_rekisteri use index (rekno_autoid) using (yhtio,maa,rekno,ajoneuvolaji)
                    WHERE rekisteritiedot.yhtio         = '$yhtio'
                    and rekisteritiedot.maa             = '$maa'
                    and rekisteritiedot.merkki          = '$merkki'
                    and rekisteritiedot.malli           = '$malli'
                    and rekisteritiedot.ajoneuvolaji    = '$laji'
                    and rekisteritiedot.k_voima         = '$kvoima'
                    and rekisteritiedot.moottorin_til   = '$tilavuus'
                    and rekisteritiedot.teho            = '$teho'
                    and rekisteritiedot.vetavat_akselit = '$vetakselit'
                    and rekisteritiedot.vahapaastoisyys = '$vahapaasto'
                    and rekisteritiedot.variantti       = '$variantti'
                    and rekisteritiedot.versio          = '$versio'
                    and rekisteritiedot.moottoritunnus  = '$moottorintunnus'
                    $mplisa
                    and rekisteritiedot.kohdistettu     = 'y'";
          $result = mysql_query($query) or pupe_error($query);

          //jos voi niin liitetään se
          if (mysql_num_rows($result) > 0) {
            while ($row = mysql_fetch_array($result)) {
              //insertti
              $query = "INSERT INTO yhteensopivuus_rekisteri SET
                        yhtio        = '$yhtio',
                        maa          = '$maa',
                        rekno        = '$rekkari',
                        autoid       = '$row[iidee]',
                        ajoneuvolaji = '$laji',
                        laatija      = '$kuka',
                        luontiaika   = now(),
                        muutospvm    = now(),
                        muuttaja     = '$kuka'";
              $insresult = mysql_query($query) or pupe_error($query);
            }

            //ja update
            $query = "UPDATE rekisteritiedot
                      SET kohdistettu = 'Y'
                      where yhtio      = '$yhtio'
                      and maa          = '$maa'
                      and rekno        = '$rekkari'
                      and ajoneuvolaji = '$laji'";
            $upresult = mysql_query($query) or pupe_error($query);
          }
        }
      }
    }

    $lask++;

    if ($lask % 10000 == 0) {
      echo date("H:i:s")." Taalla mennaan: $lask\n";
    }
  } // end while eof

  $timeparts = explode(" ", microtime());
  $endtime   = $timeparts[1].substr($timeparts[0], 1);
  $aika      = round($endtime-$starttime, 4);

  echo "\n$lask rivia perattu! $lisatty lisatty! $muutettu muutettu! $aika\n\n";

  fclose($file);
}
