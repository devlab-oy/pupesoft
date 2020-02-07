<?php

/*
TRAFI IMPORT

Toimii näin:
1.) poistetaan kaikki tiedot rekkarikannasta
2.) luetaan aken datatiedostot
3.) tutkitaan yhteensopivuus_rekisteri -taulu ja päivitetään sen mukaisesti liitetty='X'
*/

// rivi 384 antaa usein undefined offsettiin joten siksi tama
error_reporting(E_ALL ^ E_NOTICE);

// Kutsutaanko CLI:stä
if (php_sapi_name() != 'cli') {
  die ("Tätä scriptiä voi ajaa vain komentoriviltä!");
}

echo "Trafi import\n\n";

$scriptpath = "/var/www/html/pupesoft/";

require $scriptpath."inc/connect.inc";
require $scriptpath."inc/functions.inc";

// Logitetaan ajo
cron_log();

// funktio joka muuttaa ajoneuvon lajin rekkarin vanhanmalliseksi
if (!function_exists('ajoneuvokonversio')) {
  function ajoneuvokonversio($laji) {

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
        $ret_laji = "5";
      }
      else {
        $ret_laji = "5";
      }
    }

    return  $ret_laji;
  }


}



// tarkistetaan inputti
if (!isset($argv[1]) or $argv[1] == '') {
  echo "Aineistopolku puuttuu\n";
  echo "php trafi_import.php [aineistopolku] [aineistonimi] [yhtiokoodi] [trafi | ahvenanmaa] [poistettava_aineistonimi]\n\n";
  die;
}

if (!isset($argv[2]) or $argv[2] == '') {
  echo "Aineiston nimi puuttuu\n";
  echo "php trafi_import.php [aineistopolku] [aineistonimi] [yhtiokoodi] [trafi | ahvenanmaa] [poistettava_aineistonimi]\n\n";
  die;
}

if (!isset($argv[3]) or $argv[3] == '') {
  echo "Yhtiokoodi puuttuu\n";
  echo "php trafi_import.php [aineistopolku] [aineistonimi] [yhtiokoodi] [trafi | ahvenanmaa] [poistettava_aineistonimi]\n\n";
  die;
}

if (!isset($argv[4]) or ($argv[4] != 'trafi' and $argv[4] != 'ahvenanmaa')) {
  echo "Aineiston tyyppi puuttuu. Vaihtoehdot ovat 'trafi' tai 'ahvenanmaa'.\n";
  echo "php trafi_import.php [aineistopolku] [aineistonimi] [yhtiokoodi] [trafi | ahvenanmaa] [poistettava_aineistonimi]\n\n";
  die;
}

if (!isset($argv[5]) or $argv[5] == '') {
  echo "Poistettavan aineiston nimi puuttuu.\n";
  echo "Voit ohittaa aineiston poiston antamalla poistettavan aineiston nimeksi 'ohita' (tarvitaan aineiston ensimmäisellä ajokerralla).\n";
  echo "php trafi_import.php [aineistopolku] [aineistonimi] [yhtiokoodi] [trafi | ahvenanmaa] [poistettava_aineistonimi]\n\n";
  die;
}

// setataan oletusarvoja
$kuka   = "akesync";
$yhtio   = mysql_real_escape_string($argv[3]);
$maa   = 'FI';
$poista = array("'", "\\", "\"");
$valuecache = array();
$rekisteritiedot_columns = "yhtio, maa, rekno, ajoneuvolaji, laji, valmistenumero, tyyppikoodi, kayttoonotto, merkki, malli, rinnakkaistuonti, teho, kok_massa, oma_massa, pituus, k_voima, moottorin_til, vahapaastoisyys, vetavat_akselit, renkaat, EU_tyyppinumero, variantti, versio, moottoritunnus, ake_aineisto, laatija, luontiaika";
$cachesize  = 0;
$maxcachesize = 3000;
$trafi_vai_ahvenanmaa = $argv[4];
$poistettava_aineistonimi = mysql_real_escape_string($argv[5]);
$argv[2] = mysql_real_escape_string($argv[2]); // aineiston nimi

if ($poistettava_aineistonimi != 'ohita') {

  $query = "SELECT tunnus
            FROM rekisteritiedot
            WHERE yhtio      = '{$yhtio}'
            AND maa          = '{$maa}'
            AND ake_aineisto = '{$poistettava_aineistonimi}'
            LIMIT 1";
  $chk_if_exists_res = pupe_query($query);

  if (mysql_num_rows($chk_if_exists_res) == 0) {
    echo "Poistettava aineisto ei löydy\n";
    echo "php trafi_import.php [aineistopolku] [aineistonimi] [yhtiokoodi] [trafi | ahvenanmaa] [poistettava_aineistonimi]\n\n";
    die;
  }
}

// haetaan filet ja tarkistetaan että on sopiva aineisto
if ($handle = opendir($argv[1])) {

  echo "Tarkistetaan hakemiston tiedostot: $handle - $argv[1]\n";
  echo "Tiedoston nimessä pitää lukea TPERA tai AHVE...\n";

  $filet = array();

  while (false !== ($file = readdir($handle))) {

    if (substr($file, 0, 1) != "." and (strpos($file, "TPERA") !== FALSE or strpos(strtoupper($file), "AHVE") !== FALSE)) {
      echo "$file\n";

      $filet[] = $file;
    }
  }

  closedir($handle);
}

echo "\n\n";

if ($poistettava_aineistonimi != 'ohita') {

  // tyhjennetään rekisteritaulu vanhoista tiedoista
  echo "Poistetaan vanhat rekisteritiedot tietokannasta: ";

  $deletelisa = $poistettava_aineistonimi != "" ? "AND ake_aineisto = '{$poistettava_aineistonimi}'" : "";

  $qu = "DELETE FROM rekisteritiedot where yhtio = '{$argv[3]}' {$deletelisa}";
  $re = pupe_query($qu);

  if ($re) {
    echo "OK\n\n";
  }
  else {
    echo "Virhe!\n";
    exit;
  }
}

// loopataan tiedostojen läpi ja ja parsataan fileet
foreach ($filet as $filename) {

  echo "Luetaan tiedosto: $filename:";

  // Ahvenanmaan rekkarit
  if ($trafi_vai_ahvenanmaa == 'ahvenanmaa') {

    $rekisteritiedot_columns = "yhtio, maa, rekno, ajoneuvolaji, laji, valmistenumero, kayttoonotto, merkki, malli, teho, kok_massa, k_voima, ake_aineisto, laatija, luontiaika";

    $path_parts = pathinfo($filename);
    $ext = strtoupper($path_parts['extension']);

    $argv[1] = substr($argv[1], -1, 1) != '/' ? $argv[1].'/' : $argv[1];

    $excelrivit = pupeFileReader("{$argv[1]}{$filename}", $ext);

    // poistetaan otsikot
    // otsikoita on 2 riviä
    unset($excelrivit[0], $excelrivit[1]);

    // rivimäärä excelissä
    $excelrivimaara = count($excelrivit);

    for ($excei = 2; $excei < $excelrivimaara; $excei++) {

      $ajoneuvolaji  = ajoneuvokonversio($excelrivit[$excei][0]);
      $laji      = $excelrivit[$excei][0];

      $rekno         = str_replace('&Aring', 'Å', $excelrivit[$excei][1]);

      /*  the date fields have integers that are the amount of days from 1900-00-00 */
      $kayttoonottopvm   = date('Ymd', mktime(0, 0, 0, 1, $excelrivit[$excei][2]-1, 1900));

      $merkki       = mysql_real_escape_string(strtoupper($excelrivit[$excei][3]));
      $malli         = mysql_real_escape_string($excelrivit[$excei][4]);
      $valmistenumero   = $excelrivit[$excei][5];
      $kayttovoima     = strpos(strtolower($excelrivit[$excei][6]), "b") !== FALSE ? 1 : 3;
      $teho         = str_replace('.0', '', $excelrivit[$excei][7]);
      $kokmassa       = $excelrivit[$excei][8];

      $valuecache[] = "('$yhtio','$maa','$rekno','$ajoneuvolaji','$laji','$valmistenumero','$kayttoonottopvm','$merkki','$malli','$teho','$kokmassa','$kayttovoima','$argv[2]','$kuka',now())";

      $qu = "  INSERT INTO rekisteritiedot ($rekisteritiedot_columns) VALUES ".join(",", $valuecache);
      pupe_query($qu);
      unset($valuecache);
    }
  }
  elseif ($trafi_vai_ahvenanmaa == 'trafi') {

    $file = fopen($argv[1].$filename, "r") or die ("$filename Ei aukea!\n");

    // otsikkorivi pois
    $rivi = fgets($file);

    $lask       = 0;
    $lisatty     = 0;
    $muutettu     = 0;
    //  $debugecho    = 0;

    //  if ($debugecho == 1) echo "yhtio\tmaa\trekkari\tlaji\tvnumero\ttyyppikoodi\tkottopvm\tmerkki\tmalli\trinnakkaismaat\tteho\tkokmassa\tomamassa\tpituus\tkvoima\ttilavuus\tvahapaasto\tvetakselit\trenkaat\teutyyppi\tvariantti\tversio\tmoottorintunnus\n";

    // Loopataan rivit ja parseroidaan
    while ($rivi = fgets($file)) {
      // skipataan tyhjät rivit
      if (strlen(trim($rivi)) == 0) { continue; }

      // resetoidaan arvot
      $ajoneuvo          = "";
      $ajoneuvolaji        = "";
      $laji            = "";
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
      $rekno            = "";
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

      // siivotaan vaaralliset merkit pois
      $rivi = str_replace($poista, "", $rivi);

      // muunnetaan datassa olevia erikoismerkkejä
      $rivi = str_replace('/;', '###', $rivi);
      $rivi = str_replace('/>', '%%%', $rivi);

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

      $tiedot = explode(">", $rivi);

      // yläluokka
      $ajoneuvo        = explode(";", $tiedot[0]);
      // alaluokat                tietotyyppi  esiintymiskerrat  koodisto
      $rekno          = trim($ajoneuvo[0]); //   string(9)  0-1
      $valmistenumero      = trim($ajoneuvo[1]); //   string(30)  1
      $ajoneuvoluokka      = trim($ajoneuvo[2]); //   koodi    1          kdtyytiajoneuvoluokka
      $merkki          = trim($ajoneuvo[3]); //   koodi    1
      $merkkiselvakielinen  = trim($ajoneuvo[4]); //   string(99)  1
      $mallimerkinta      = trim($ajoneuvo[5]); //   string(200)  0-1

      $ajoneuvolaji  = ajoneuvokonversio($ajoneuvoluokka);
      $laji      = $ajoneuvoluokka;

      // skipataan kaikki kaksipyöräiset vehkeet
      if (substr($laji, 0, 1) == 'L') {
        continue;
      }

      //$debugecho .="||AJONEUVO||\t$rekkari\t$valmistenumero\t$ajoneuvoluokka\t$merkki\t$merkkiselvakielinen\t$mallimerkinta\t";

      // yläluokka
      $ajoneuvoryhmat      = explode(";", $tiedot[1]);

      // tässä ei ole oikein järkeä..
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

      $kayttovoima = $kayttovoima * 1;

      if ($kayttovoima == 2) {
        $kayttovoima = 3;
      }

      if (trim($mallimerkinta) == "" and trim($kaupallinennimi) != "") {
        $mallimerkinta = $kaupallinennimi;
      }


      $vnumero    = $valmistenumero;
      $tyyppikoodi  = $tyyppikoodi;
      $kottopvm    = $kayttoonottopvm;
      $merkki      = strtoupper($merkkiselvakielinen);
      $malli      = $mallimerkinta;
      $rinnakkaismaat  = $yksittainmaahantuotu;
      $teho      = str_replace('.0', '', $suurinnettoteho);
      $kokmassa    = $tieliiksuursallkokmassa; // sprintf("%06s",$tieliiksuursallkokmassa);
      $omamassa    = $omamassa; // sprintf("%06s",$omamassa);
      $pituus      = $ajonkokpituus; //sprintf("%06s",$ajonkokpituus/10);
      $kvoima      = $kayttovoima;
      $tilavuus    = round($iskutilavuus/1000, 1);
      $vahapaasto    = $vahapaasto;
      $vetakselit    = $vetakselit;
      $renkaat    = $renkaat;
      $eutyyppi    = ""; //eioo millään
      $variantti    = $variantti;
      $versio      = $versio;
      $moottorintunnus= $moottorintunnus;

      // if ($debugecho == 1 and $lask % 1000 == 0) echo "$yhtio\t$maa\t$rekkari\t$laji/$ajoneuvoluokka_orig\t$vnumero\t$tyyppikoodi\t$kottopvm\t$merkki\t$malli\t$rinnakkaismaat\t$teho\t$kokmassa\t$omamassa\t$pituus\t$kvoima\t$tilavuus\t$vahapaasto\t$vetakselit\t$renkaat\t$eutyyppi\t$variantti\t$versio\t$moottorintunnus\n";

      // tuupataan tiedot cacheen odottamaan inserttiä
      $valuecache[] = "('$yhtio','$maa','$rekno','$ajoneuvolaji','$laji','$vnumero','$tyyppikoodi','$kottopvm','$merkki','$malli','$rinnakkaismaat','$teho','$kokmassa','$omamassa','$pituus','$kvoima','$tilavuus','$vahapaasto','$vetakselit','$renkaat','$eutyyppi','$variantti','$versio','$moottoritunnus','$argv[2]','$kuka',now())";
      $cachesize++;

      if ($cachesize == $maxcachesize) {
        $qu = "INSERT INTO rekisteritiedot ($rekisteritiedot_columns) VALUES ".join(",", $valuecache);
        $re = pupe_query($qu);
        unset($valuecache);
        $cachesize = 0;
        echo ".";
      }
    } // end while eof

    // laitetaan tiedostosta jääneet rippeet kantaan
    if ($cachesize > 0) {
      // laitetaan vielä rippeet kantaan... oiskohan tahan joku elegantimpi tapa
      $qu = "INSERT INTO rekisteritiedot ($rekisteritiedot_columns) VALUES ".join(",", $valuecache);
      $re = pupe_query($qu);
      unset($valuecache);
      $cachesize = 0;
    }

    fclose($file);
  }

  echo "\n";
}

echo "\nSisaanluku valmis! Paivitetaan linkitystiedot: ";

// katsotaan mille rekkareille loytyy liitos ja paivitetaan rekisteritiedot -tauluun...

$qu = "UPDATE rekisteritiedot AS r
       JOIN yhteensopivuus_rekisteri AS y
       ON (r.yhtio = y.yhtio AND r.rekno = y.rekno)
       SET r.kohdistettu = 'X'
       where r.yhtio = '$yhtio'";
$re = pupe_query($qu);
if ($re) {
  echo "kaikki valmista!\n";
}
else {
  echo "Jokin virhe...\n";
}


if ($trafi_vai_ahvenanmaa == 'trafi') {

  echo "\nLuodaan dummy-rekkari vanne-1: ";

  $qu = "INSERT INTO rekisteritiedot ($rekisteritiedot_columns)
         VALUES ('$yhtio','FI','vanne-1','5','M1',
             '-','-','20120101','-',
             '-','','1','1',
             '1','1','3','1',
             '1','1','','',
             '','','','$argv[2]',
             '$kuka',now())";

  $re = pupe_query($qu);

  if ($re) {
    echo "Ok!";
  }
  else {
    echo "Virhe...";
  }
}
