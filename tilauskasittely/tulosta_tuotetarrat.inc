<?php

//Ohjelma vatii muuttujan $otunnus joka viittaa ostolaskuun johon rivit on kohdistettu.

$query = "SELECT tuote.tuoteno, tuote.yksikko, if(tuote.tarrakpl>0,tuote.tarrakpl,1) tarrakpl, tilausrivi.varattu+tilausrivi.kpl varattu, tuote.tarrakerroin
          FROM tilausrivi
          JOIN tuote ON tilausrivi.yhtio=tuote.yhtio and tilausrivi.tuoteno=tuote.tuoteno
          WHERE tilausrivi.uusiotunnus = '$otunnus'
          and tilausrivi.yhtio='$kukarow[yhtio]'
          ORDER BY tuote.tuoteno";
$res= pupe_query($query);

list($usec, $sec) = explode(' ', microtime());
mt_srand((float) $sec + ((float) $usec * 100000));
$filenimi = "/tmp/Tuotetarrat-".md5(uniqid(mt_rand(), true)).".txt";

$fh = fopen($filenimi, "w+");
$sisalto1 = '';
$sisalto2 = '';
$sisalto3 = '';
$laskuri  = 0;

$sisalto = "\r\n";
fputs($fh, $sisalto);

while ($row = mysql_fetch_array($res)) {

  if ($row["tarrakerroin"] == 0) $row["tarrakerroin"] = 1;

  $tarrakpl = $row["varattu"] * $row["tarrakerroin"];

  //mahtuu 7 tarraa per rivi
  for ($a = 0; $a < $tarrakpl; $a++) {
    if ($laskuri % 7 == 0 and $laskuri > 0) {
      $sisalto  = sprintf('%-75.75s', $sisalto1);
      $sisalto .= sprintf('%-75.75s', $sisalto2);
      $sisalto .= sprintf('%-75.75s', $sisalto3);
      $sisalto .= "\r\n"."\r\n";

      if ($laskuri % 112 == 0 && $laskuri > 0) {
        $sisalto .= "\r\n";
      }

      fputs($fh, $sisalto);
      $sisalto1 = '';
      $sisalto2 = '';
      $sisalto3 = '';
    }

    $sisalto1 .= sprintf('%-11.9s', substr($row["tuoteno"], 0, 9));
    $sisalto2 .= sprintf('%-11.9s', substr($row["tuoteno"], 9, 9));
    $sisalto3 .= sprintf('%-11.9s', $row["tarrakpl"]." ".t_avainsana("Y", $kieli, "and avainsana.selite='$row[yksikko]'", "", "", "selite"));
    $laskuri++;
  }
}

if ($sisalto1 != "") {
  $sisalto  = sprintf('%-75.75s', $sisalto1);
  $sisalto .= sprintf('%-75.75s', $sisalto2);
  $sisalto .= sprintf('%-75.75s', $sisalto3);
  $sisalto .= "\r\n"."\r\n";
  fputs($fh, $sisalto);
}
fclose($fh);

//itse print komento...
if ($komento["Tuotetarrat"] == 'email') {
  $liite = $filenimi;
  $ctype = "TEXT";
  $kutsu = "tuotetarrat-".date("Y-m-d").".txt";
  require "inc/sahkoposti.inc";

  //p‰‰dyt‰‰n taas selailemaan toimittajia
  echo t("Tuotetarrat tulostuu")."...<br>";
}
else {
  //paperilista pit‰‰ saada kauniiksi
  $params = array(
    'chars'    => 75,
    'filename' => $filenimi,
    'margin'   => 0,
    'mode'     => 'portrait',
  );

  // konveroidaan postscriptiksi
  $filenimi_ps = pupesoft_a2ps($params);

  if ($tee == 'NAYTATILAUS') {
    //Tyˆnnet‰‰n tuo pdf vaan putkeen!
    echo file_get_contents($filenimi_ps);
  }
  else {
    //itse print komento tulee valitse_tulosin.incilt‰...
    $line = exec("$komento[Tuotetarrat] {$filenimi_ps}");

    //p‰‰dyt‰‰n taas selailemaan toimittajia
    echo t("Tuotetarrat tulostuu")."...<br>";
  }
  system("rm -f {$filenimi_ps}");
}
