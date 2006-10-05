<?php
include "inc/parametrit.inc";
require_once("inc/tilinumero.inc");

echo "<br><font class='head'>Ylim‰‰isen suorituksen palautus</font><hr>";


if($saajantilino=="") {
  echo "<b>Saajan pankkitili puuttuu</b><br>";
  $skip=1;
}
if($maksajanpankkitilitunnus=="") {
  echo "<b>Yrityksen pankkitili puuttuu</b><br>";
  $skip=1;
}
if($asiakas1=="") {
  echo "<b>Asiakkaan tiedot puuttuu</b><br>";
  $skip=1;
}
if($skip==1) {
  $tunnus=$asiakastunnus;
  include("maksa_kaatosumma.php");
  exit;
}



// tarkistetaan yhtio pankkitilin oikeellisuus
$query = "SELECT tilino, oletus_rahatili FROM yriti";
$query .= " WHERE yhtio = '$kukarow[yhtio]' and tunnus='$maksajanpankkitilitunnus'";

$result = mysql_query($query) or pupe_error($query);
while ($row = mysql_fetch_array($result)) {
  $maksajanpankkitili=$row[0];
  $kassatili=$row[1];
}



$pankkitili = $maksajanpankkitili;
include "inc/pankkitilinoikeellisuus.php";

if ($pankkitili == "") {
  echo "<b>Pankkitili $maksajanpankkitili on virheellinen</b><br>";
  $skip=1;
}
else {
  if ($maksajanpankkitili != $pankkitili) {
    $maksajanpankkitili=$pankkitili;
  }
}

if ($kassatili == "") {
  echo "<b>Rahatili‰ ei lˆydy tilille $maksajanpankkitili</b><br>";
  $skip=1;
}

// tarkistetaan asiakkaan pankkitilin oikeellisuus
$pankkitili = $saajantilino;
include "inc/pankkitilinoikeellisuus.php";

if ($pankkitili == "") {
  echo "<b>Asiakkaan pankkitili $saajantilino on virheellinen</b><br>";
  $skip=1;
}
else {
  if ($saajantilino != $pankkitili) {
    $saajantilino=$pankkitili;
  }
}

if($skip==1) {
  $tunnus=$asiakastunnus;
  include("maksa_kaatosumma.php");
  exit;
}



echo "<font class='head'>LM03-maksuaineisto</font><hr>";


// lukitaan taulut
$unlockquery = "UNLOCK TABLES";
$query = "LOCK TABLES yriti READ, yhtio READ, suoritus WRITE, tiliointi WRITE, lasku WRITE, sanakirja WRITE";
$result = mysql_query($query) or pupe_error($query);


$query = "
SELECT SUM(summa) summa
FROM suoritus
WHERE yhtio='$kukarow[yhtio]' AND ltunnus<>0 AND asiakas_tunnus=$asiakastunnus";

//echo "<font class='head'>kaato: $query</font>";

if (!($result = mysql_query($query))) {
  $result = mysql_query($unlockquery);
  die ("Kysely ei onnistu $query");
}

$kaato = mysql_fetch_object($result);
$kaatosumma=$kaato->summa;

$query = "SELECT ytunnus, pankki_polku, myyntisaamiset
                  FROM yhtio
                  WHERE yhtio ='$kukarow[yhtio]'";


if (!($result = mysql_query($query))) {
  $result = mysql_query($unlockquery);
  die ("Kysely ei onnistu $query");
  if (mysql_num_rows($result) != 1) {
    echo "Maksava yritys ei l‰ydy";
    $result = mysql_query($unlockquery);
    exit;
  }

}

$yritysrow=mysql_fetch_object($result);
$myyntisaamiset=$yritysrow->myyntisaamiset;
if ($myyntisaamiset=="") {
  echo "<b>Myyntisaamiset tili‰ ei l‰dy yritykselt‰</b>";
  $result = mysql_query($unlockquery);
  exit;
}



$nimi = "$yritysrow->pankki_polku/" . date("d.m.y.H.i.s") . "lm03.txt";
$toot = fopen($nimi,"w+");

$yritystilino =  $maksajanpankkitili;
$laskunimi1 = $asiakas1;
$laskunimi2 = $asiakas2;
$laskunimi3 = $asiakas3;
$laskusumma = $kaatosumma;

$laskutilno = $saajantilino;
$laskusis1  = '';
$laskusis2  = '';
$laskuvaluutta = 1; // euro
$laskutyyppi = 5; // k‰ytet‰‰n viesti‰
$laskuviesti = $viesti;

$yritystilino =  $maksajanpankkitili;
$yrityytunnus =  $yritysrow->ytunnus;
include "inc/lm03otsik.inc";
include "inc/lm03rivi.inc";
$makssumma += $laskusumma;

include "inc/lm03summa.inc";
fclose($toot);

$tanaan = time();
$tapvm = strftime("%Y-%m-%d", $tanaan);

// tehd‰‰n dummy-lasku johon liitet‰‰n kirjaukset
$query="INSERT into lasku set yhtio = '$kukarow[yhtio]', tapvm = '$tapvm', tila = 'X', laatija = '$kukarow[kuka]', luontiaika = now()";
if (!($result = mysql_query($query))) {
  $result = mysql_query($unlockquery);
  die ("Kysely ei onnistu $query");
}
$ltunnus = mysql_insert_id($link);


// tehd‰‰n kirjaus rahatililt‰

$query="INSERT INTO tiliointi(yhtio, laatija,laadittu,tapvm,ltunnus,tilino,summa,selite) values ('$kukarow[yhtio]','$kukarow[kuka]', now(), '$tapvm','$ltunnus','$kassatili',-1*$kaatosumma,'Suorituksen palautus asiakkaalle $asiakas1')";

//echo "<font class='head'>$query</font>";

if (!($result = mysql_query($query))) {
  $result = mysql_query($unlockquery);
  die ("Kysely ei onnistu $query");
}
// myyntisaamiset
$query="INSERT INTO tiliointi(yhtio, laatija,laadittu,tapvm,ltunnus,tilino,summa,selite) values ('$kukarow[yhtio]','$kukarow[kuka]',now(),'$tapvm','$ltunnus','$myyntisaamiset',$kaatosumma,'Suorituksen palautus asiakkaalle $asiakas1')";

//echo "<font class='head'>$query</font>";

if (!($result = mysql_query($query))) {
  $result = mysql_query($unlockquery);
  die ("Kysely ei onnistu $query");
}


// tyhjennet‰‰n kaatotili

$query = "UPDATE suoritus SET summa='0' WHERE yhtio='$kukarow[yhtio]' AND ltunnus<>0 AND asiakas_tunnus=$asiakastunnus";
//echo "<font class='head'>Suorituksen p‰ivitys: $query</font>";

if (!($result = mysql_query($query))) {
  $result = mysql_query($unlockquery);
  die ("Kysely ei onnistu $query");
}

$result = mysql_query($unlockquery);



echo "<br>";

echo "<a href=\"myyntilaskut_asiakasraportti.php?tila=tee_raportti&tunnus=$asiakastunnus\">Jatka</a><br>";
include "inc/footer.inc";

?>