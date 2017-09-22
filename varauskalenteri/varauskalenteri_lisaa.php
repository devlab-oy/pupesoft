<?php

$date    = $year."-".sprintf('%02d', $month)."-".sprintf('%02d', $day);
$dateloppu   = $lyear."-".sprintf('%02d', $lmonth)."-".sprintf('%02d', $lday);

$date2     = $year.sprintf('%02d', $month).sprintf('%02d', $day);
$dateloppu2 = $lyear.sprintf('%02d', $lmonth).sprintf('%02d', $lday);

$mylmonth   = sprintf('%02d', $lmonth);
$mylday   = sprintf('%02d', $lday);

// en jaksa muuttaa idiootteja formeja, joten vertailu nyt näillä muuttujulla uusiks
list($vertailu_alkutunti, $vertailu_alkuminuutti) = explode(":", $kello);
list($vertailu_lopputunti, $vertailu_loppuminuutti) = explode(":", $lkello);

$vertailu_alkuaika = mktime($vertailu_alkutunti, $vertailu_alkuminuutti, 0, $month, $day, $year);
$vertailu_loppuaika = mktime($vertailu_lopputunti, $vertailu_loppuminuutti, 0, $lmonth, $lday, $lyear);

//tarkistetaan, etta alku ja loppu ovat eri..
if ($vertailu_alkuaika >= $vertailu_loppuaika) {
  echo "<br><br>".t("VIRHE: Päättymisjankohta on aikaisempi kuin alkamisajankohta")."!";
  exit;
}

//Tarkisetetaan päällekkäisyys konsernikohtaisesti
$query = "SELECT distinct yhtio FROM yhtio WHERE (konserni = '$yhtiorow[konserni]' and konserni != '') or (yhtio = '$yhtiorow[yhtio]')";
$result = pupe_query($query);
$konsernit = "";

while ($row = mysql_fetch_array($result)) {
  $konsernit .= " '".$row["yhtio"]."' ,";
}
$konsernit = " and yhtio in (".substr($konsernit, 0, -1).") ";

$query = "  select tunnus
      from kalenteri
      where tapa    = '$toim'
      and ((pvmloppu > '$year-$month-$day $kello' and pvmloppu < '$lyear-$mylmonth-$mylday $lkello')
      or (pvmalku > '$year-$month-$day $kello' and pvmalku < '$lyear-$mylmonth-$mylday $lkello')
      or (pvmalku < '$year-$month-$day $kello' and pvmloppu > '$year-$month-$day $kello'))
      $konsernit";
$result = pupe_query($query);

if (mysql_num_rows($result) > 0) {
  echo "<br><br>".t("VIRHE: Päällekkäisiä tapahtumia")."!";
  exit;
}

if ($toim != "") {
  if ($kentta03 != '') {
    $meili = "\n\n$kukarow[nimi] on varannut \n$toim ajalle:";
    $meili .= "\n$day-$month-$year Klo: \n$kello --> \n$lkello\n\n";
    $meili .= "Yhtiö:\n$kentta01\n";
    $meili .= "Osasto:\n$kentta02\n\n";
    $meili .= "Lisätiedot:\n$kentta05\n\n";
    $meili .= "Tilaisuus:\n$kentta03 $kentta04\n\n";
    $meili .= "Isäntä:\n$kentta06\n\n";
    $meili .= "Vieraat:\n$kentta07\n\n";
    $meili .= "Vieraslukumäärä:\n$kentta08\n\n\n";
    $meili .= "Juomatoivomus:\n$kentta10\n";

    $tulos = mail("$yhtiorow[varauskalenteri_email]", mb_encode_mimeheader($toim, "ISO-8859-1", "Q"), $meili, "From: ".mb_encode_mimeheader($kukarow["nimi"], "ISO-8859-1", "Q")." <".$kukarow["eposti"].">\nReply-To: ".mb_encode_mimeheader($kukarow["nimi"], "ISO-8859-1", "Q")." <".$row["eposti"].">\n", "-f $yhtiorow[postittaja_email]");
  }
  else {
    echo "<br><br>Tarkista, ett&auml; kaikki tiedot on sy&ouml;tetty!";
    exit;
  }
}

$query = "INSERT into kalenteri SET
          kuka     = '$kukarow[kuka]',
          yhtio    = '$kukarow[yhtio]',
          pvmalku  = '$year-$month-$day $kello',
          pvmloppu = '$lyear-$lmonth-$lday $lkello',
          tapa     = '$toim',
          kentta01 = '$kentta01',
          kentta02 = '$kentta02',
          kentta03 = '$kentta03',
          kentta04 = '$kentta04',
          kentta05 = '$kentta05',
          kentta06 = '$kentta06',
          kentta07 = '$kentta07',
          kentta08 = '$kentta08',
          kentta09 = '$kentta09',
          kentta10 = '$kentta10',
          tyyppi   = 'varauskalenteri'";
$result = pupe_query($query);

echo "<br><br>".t("Tapahtuma lisätty varauskalenteriin")."!";

if ($lopetus != "") {
  lopetus($lopetus, "META");
}
