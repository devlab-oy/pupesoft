<?php
//Kun tullaan t�nne tarvitaan $komento joka on se komento jolla tulostetaan esim. lpr -P tarrakirjoitin
//ja tarvitaan my�s $tuoteno
$query = "select * from tuote where yhtio = '$kukarow[yhtio]' and tuoteno = '$tuoteno'";
$tarrares = pupe_query($query);
//tuote pit�� l�yty�
if (mysql_num_rows($tarrares) == 1) {
  $tarrarow = mysql_fetch_array($tarrares);
  //jos tullaan yksinkertaisesta n�kym�st� tulostetaan tuoteno eankoodin sijaan
  if ($toim== 'YKS') {
    $tarrarow['eankoodi'] = $tarrarow['tuoteno'];
  }
  //jos eankoodi kentt� on tyhj� ei viittit� edes l�hte� tulostelemaan
  if ($tarrarow['eankoodi']!= '0') {
    $eankoodi = trim($tarrarow['eankoodi']);
    $nimitys = $tarrarow['nimitys'];
    $nimitys2 = '';
    //jos nimitys on pidempi kuin 22 merkki� niin se ei mahdu yhdelle riville vaan pit�� jakaa kahtia
    if (strlen($nimitys) > 30) {
      $nimitys = substr($tarrarow['nimitys'], 0, 30);
    }
    $tuoteno = $tarrarow['tuoteno'];
    //t�ss� arvataan merkkim��r�n perusteella mink�lainen viivakoodi pit�� tehd�
    $countti = strlen($eankoodi);
    if ($countti== 13) {
      $laji = 'EAN13';
    }
    elseif ($countti== 12) {
      $laji = 'UPC12';
    }
    else {
      $laji = '';
    }

    $tulos = false;
    //t�ss� lasketaan koodin virallisuus  vaan positiivinen numeerinen tietue
    if (is_numeric($eankoodi) and $eankoodi >= 0) {
      $pit  = strlen($eankoodi);
      $factor    = 3;
      $summa    = 0;
      $ajetaanko  = false;
      if (!empty($laji)) $laji = strtoupper($laji);

      if (($laji=="EAN8") && ($pit==8))              $ajetaanko = true;
      elseif ((($laji=="EAN13") || ($laji=="ILN")) && ($pit==13))  $ajetaanko = true;
      elseif (($laji=="EAN14") && ($pit==14))            $ajetaanko = true;
      elseif (($laji=="UPC8") && ($pit==8))            $ajetaanko = true;
      elseif (($laji=="UPC12") && ($pit==12))            $ajetaanko = true;
      elseif (($laji=="NVE18") && ($pit==18))            $ajetaanko = true;

      if ($ajetaanko) {
        $rest = substr($eankoodi, -1);
        $wert = substr($eankoodi, 0, $pit-1);
        $sum = '';
        for ($i=($pit-1);$i>0;--$i) {
          $sum  = $sum + substr($wert, $i-1, 1) * $factor;
          $factor  = 4 - $factor;
        }
        if ($rest==((1000-$sum)%10))  $tulos = true;
      }
    }
    //EAN-13
    if ($laji== 'EAN13' and $tulos== true) {
      //echo "on ean<br>";
      $koodi = 'EAN13';
      $eankoodi = substr($eankoodi, 0, -1);
    }
    //UPC-A
    elseif ($laji== 'UPC12' and $tulos== true) {
      //echo "on UPC<br>";
      $koodi = 'UPCA';
      $eankoodi = substr($eankoodi, 0, -1);
    }
    //JOTAIN MUUTA
    else {
      $koodi = 'CODE128';
      //echo "ei ole MIKAAN $laji<br>";
    }
    //t�ss� tehd��n aineisto jota Intermec tulostin tajuaa
    $sivu = '
BF ON
BF \"Swiss 721 BT\",10,0
BH 80
PP 42, 320
FT \"Swiss 721 Bold BT\",15,0
PT \"'.$yhtiorow["nimi"].'\"
PP 42, 250
FT \"Swiss 721 BT\",15,0
PT \"'.$tuoteno.'\"
PP 42, 200
FT \"Swiss 721 BT\",11,0
PT \"'.$nimitys.'\"
PP 50, 20
BT \"'.$koodi.'\"
PB \"'.$eankoodi.'\"
PF
';

    $sivu = escapeshellarg($sivu);
    $line = exec(" echo \"$sivu\" | $komento");
  }
  else {
    echo "<font class='error'>".t("Tuotteella")." $tuoteno ".t("ei ole viivakoodia j�rjestelm�ss� joten tarrojen tulostus taitaa olla turhaa")."!!!<br></font>";
  }
}
else {
  echo "<font class='error'>".t("Tuotetta")." $tuoteno ".t("ei l�ydy j�rjestelm�st�")."!!!<br></font>";
}
