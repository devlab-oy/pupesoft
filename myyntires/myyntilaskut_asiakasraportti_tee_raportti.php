<?php
///* Tämä skripti käyttää slave-tietokantapalvelinta *///
$useslave = 1;
// estetään sivun lataus suoraan
if (!empty($HTTP_GET_VARS["oikeus"]) ||
    !empty($HTTP_POST_VARS["oikeus"]) ||
    !empty($HTTP_COOKIE_VARS["oikeus"]) ||
    !isset($oikeus)) {

  echo "<p>".t("Kielletty toiminto")."!</p>";
  exit;
}

//yleiset tiedot asiakkaasta
$tila='tee_raportti';

if (isset($tunnus)) {
	$haku_sql = "tunnus='$tunnus'";
} else {
	$haku_sql = "ytunnus='$ytunnus'";
}

$query = "	SELECT tunnus, ytunnus, nimi, osoite, postino, postitp 
			FROM asiakas 
			WHERE yhtio='$kukarow[yhtio]' AND $haku_sql";
$result = mysql_query($query) or pupe_error($query);

if ($kalarow=mysql_fetch_array ($result)) {
  $tunnus = $kalarow['tunnus'];
  $ytunnus = $kalarow['ytunnus'];
}

//kaatotilin saldo
$query = "	SELECT SUM(summa) summa
			FROM suoritus
			WHERE yhtio='$kukarow[yhtio]' AND ltunnus<>0 AND asiakas_tunnus in (select tunnus from asiakas where yhtio='$kukarow[yhtio]' and ytunnus='$ytunnus')";
$asiakas_tunnus=$tunnus;

$result = mysql_query($query) or pupe_error($query);
$kaato = mysql_fetch_array($result);

/* avoinna olevien laskujen kokonaissumma ja määrä, myöhässä olevien
laskujen kokonaissumma ja määrä (sekä kaikkien lähetettyjen laskujen
kokonaissumma ja määrä) 

$query = "SELECT COUNT(l.tunnus) as maara, SUM(l.summa-l.saldo_maksettu) as summa FROM lasku l WHERE l.tila = 'U' AND l.yhtio='$kukarow[yhtio]' AND l.liitostunnus in (select tunnus from asiakas where yhtio='$kukarow[yhtio]' and ytunnus='$ytunnus')";
$result = mysql_query($query) or pupe_error($query);
$kok = mysql_fetch_array($result);

$query = "SELECT COUNT(l.tunnus) as maara, SUM(l.summa-l.saldo_maksettu) as summa FROM lasku l WHERE l.tila = 'U' AND mapvm='0000-00-00' AND l.yhtio='$kukarow[yhtio]' AND liitostunnus in (select tunnus from asiakas where yhtio='$kukarow[yhtio]' and ytunnus='$ytunnus')";
$result = mysql_query($query) or pupe_error($query);
$av = mysql_fetch_array($result);

$query = "SELECT COUNT(l.tunnus) as maara, SUM(l.summa-l.saldo_maksettu) as summa FROM lasku l WHERE l.tila = 'U' AND mapvm='0000-00-00' AND erpcm < now() AND l.yhtio='$kukarow[yhtio]' AND liitostunnus in (select tunnus from asiakas where yhtio='$kukarow[yhtio]' and ytunnus='$ytunnus')";
$result = mysql_query($query) or pupe_error($query);
$myoh = mysql_fetch_array($result);


echo "
<table>
<tr><th><a href='../crm/asiakasmemo.php?ytunnus=$ytunnus'>$kalarow[nimi]</a></td>
<td>".t("Kaatotilillä")."							</td><td>					</td><td>$kaato[summa]</td></tr>
<tr><th>$ytunnus</td>
<td>".t("Myöhässä olevia laskuja yhteensä")."		</td><td>$myoh[maara] kpl	</td><td>$myoh[summa]</td></tr>
<tr><th>$kalarow[osoite]</td>
<td>".t("Avoimia laskuja yhteensä")."				</td><td>$av[maara] kpl		</td><td>$av[summa]</td></tr>
<tr><th>$kalarow[postino] $kalarow[postitp]</td>
<td>".t("Laskuja yhteensä")."						</td><td>$kok[maara] kpl	</td><td>$kok[summa]</td></tr>
<tr><th></th><td><a href='../raportit/asiakasinfo.php?ytunnus=$ytunnus'>".t("Asiakkaan myyntitiedot")."</a></td><td></td><td></td></tr>";

*/

$query = "SELECT count(l.tunnus) as maara, sum(if(mapvm='0000-00-00',1,0)) avoinmaara, sum(if(erpcm < now() and mapvm = '0000-00-00',1,0)) eraantynytmaara,
sum(l.summa-l.saldo_maksettu) as summa,  sum(if(mapvm='0000-00-00',l.summa-l.saldo_maksettu,0)) avoinsumma, sum(if(erpcm < now() and mapvm = '0000-00-00',l.summa-l.saldo_maksettu,0)) eraantynytsumma,
sum(if(mapvm = '0000-00-00' and TO_DAYS(NOW())-TO_DAYS(erpcm) <= -3,1,0)) maara1,
sum(if(mapvm = '0000-00-00' AND TO_DAYS(NOW())-TO_DAYS(erpcm) > -3 AND TO_DAYS(NOW())-TO_DAYS(erpcm) <= -1,1,0)) maara2,
sum(if(mapvm = '0000-00-00' AND TO_DAYS(NOW())-TO_DAYS(erpcm) > -1 AND TO_DAYS(NOW())-TO_DAYS(erpcm) <= 15,1,0)) maara3,
sum(if(mapvm = '0000-00-00' AND TO_DAYS(NOW())-TO_DAYS(erpcm) > 15 AND TO_DAYS(NOW())-TO_DAYS(erpcm) <= 30,1,0)) maara4,
sum(if(mapvm = '0000-00-00' AND TO_DAYS(NOW())-TO_DAYS(erpcm) > 30 AND TO_DAYS(NOW())-TO_DAYS(erpcm) <= 60,1,0)) maara5,
sum(if(mapvm = '0000-00-00' AND TO_DAYS(NOW())-TO_DAYS(erpcm) > 60,1,0)) maara6
FROM lasku l use index (yhtio_liitostunnus),
(select tunnus from asiakas where yhtio='$kukarow[yhtio]' and ytunnus='$ytunnus') as valitut
WHERE l.tila = 'U' AND l.yhtio='$kukarow[yhtio]' AND liitostunnus=valitut.tunnus";

$result = mysql_query($query) or pupe_error($query);
$kok = mysql_fetch_array($result);

echo "
<table>
<tr><th><a href='../crm/asiakasmemo.php?ytunnus=$ytunnus'>$kalarow[nimi]</a></td>
<td>".t("Kaatotilillä")."							</td><td>					</td><td>$kaato[summa]</td></tr>
<tr><th>$ytunnus</td>
<td>".t("Myöhässä olevia laskuja yhteensä")."		</td><td>$kok[eraantynytmaara] kpl	</td><td>$kok[eraantynytsumma]</td></tr>
<tr><th>$kalarow[osoite]</td>
<td>".t("Avoimia laskuja yhteensä")."				</td><td>$kok[avoinmaara] kpl		</td><td>$kok[avoinsumma]</td></tr>
<tr><th>$kalarow[postino] $kalarow[postitp]</td>
<td>".t("Laskuja yhteensä")."						</td><td>$kok[maara] kpl	</td><td>$kok[summa]</td></tr>
<tr><th></th><td><a href='../raportit/asiakasinfo.php?ytunnus=$ytunnus'>".t("Asiakkaan myyntitiedot")."</a></td><td></td><td></td></tr>";

echo "<table><tr><td class='back'>";
// ikäanalyysi

echo "<table><tr><th>&lt;-2</th><th>-2- -1</th><th>0-15</th><th>16-30</th><th>31-60</th><th>&gt;60</th></tr>";

$palkki_html = '';
$palkki_korkeus = 20;
$palkki_leveys = 300;

$kuvaurl[0] = "../pics/vaaleanvihrea.png";
$kuvaurl[1] = "../pics/vihrea.png";
$kuvaurl[2] = "../pics/keltainen.png";
$kuvaurl[3] = "../pics/oranssi.png";
$kuvaurl[4] = "../pics/oranssihko.png";
$kuvaurl[5] = "../pics/punainen.png";

$yhtmaara = $kok['avoinmaara'];

echo "<tr>";
echo "<td>$kok[maara1]</td>";
echo "<td>$kok[maara2]</td>";
echo "<td>$kok[maara3]</td>";
echo "<td>$kok[maara4]</td>";
echo "<td>$kok[maara5]</td>";
echo "<td>$kok[maara6]</td>";
if ($yhtmaara != 0) {
	$palkki_html .= "<img src='$kuvaurl[0]' height='$palkki_korkeus' width='" . ($kok['maara1']/$yhtmaara) * $palkki_leveys ."'>";
	$palkki_html .= "<img src='$kuvaurl[1]' height='$palkki_korkeus' width='" . ($kok['maara2']/$yhtmaara) * $palkki_leveys ."'>";
	$palkki_html .= "<img src='$kuvaurl[2]' height='$palkki_korkeus' width='" . ($kok['maara3']/$yhtmaara) * $palkki_leveys ."'>";
	$palkki_html .= "<img src='$kuvaurl[3]' height='$palkki_korkeus' width='" . ($kok['maara4']/$yhtmaara) * $palkki_leveys ."'>";
	$palkki_html .= "<img src='$kuvaurl[4]' height='$palkki_korkeus' width='" . ($kok['maara5']/$yhtmaara) * $palkki_leveys ."'>";
	$palkki_html .= "<img src='$kuvaurl[5]' height='$palkki_korkeus' width='" . ($kok['maara6']/$yhtmaara) * $palkki_leveys ."'>";
}

echo "</tr>";

echo "</table>";

echo "</td><td class='back' align='center'>";
//visuaalinen esitys maksunopeudesta (hymynaama)

list ($naama, $nopeushtml) = laskeMaksunopeus($ytunnus, $kukarow["yhtio"]);

echo "$nopeushtml<br>";
echo "$palkki_html";

echo "</td><td class='back'>$naama</td></tr></table><br>";

;

//avoimet laskut

$kentat = 'laskunro, tapvm, erpcm, summa, kapvm, kasumma, mapvm, ika, viikorkoeur, olmapvm, tunnus';
$kentankoko = array(5,8,8,10,8,10,8,5,10,8);

$array = split(",", $kentat);
$count = count($array);

for ($i=0; $i<=$count; $i++) {
  // tarkastetaan onko hakukentässä jotakin
  if (strlen($haku[$i]) > 0) {
    $lisa .= " and " . $array[$i] . " like '%" . $haku[$i] . "%'";
    $ulisa .= "&haku[" . $i . "]=" . $haku[$i];
  }
}
if (strlen($ojarj) > 0) {
  $jarjestys = $array[$ojarj];
}
else{
  $jarjestys = 'erpcm';
}


//näytetäänkö maksetut vai avoimet
$chk1 = $chk2 = '';

if ($valintra == '') {
	$chk1 = 'CHECKED';
	$kala = '';
}
else {
	$chk2 = 'CHECKED';
	$kala = "!";
}


$query = "	SELECT laskunro, tapvm, erpcm, summa-saldo_maksettu summa, kapvm, kasumma, mapvm, TO_DAYS(mapvm) - TO_DAYS(erpcm) ika, viikorkoeur korko, olmapvm korkolaspvm, lasku.tunnus, saldo_maksettu
			FROM lasku,
			(select tunnus from asiakas where yhtio='$kukarow[yhtio]' and ytunnus='$ytunnus') as valittu
			WHERE yhtio ='$kukarow[yhtio]' and tila = 'U' AND liitostunnus = valittu.tunnus AND mapvm $kala='0000-00-00' $lisa
			ORDER BY $jarjestys";
$result = mysql_query($query) or pupe_error($query);

echo "<table>";
echo "<form action = '$PHP_SELF?tila=$tila&tunnus=$tunnus&ojarj=".$ojarj.$ulisa."' method = 'post'>";
echo "<tr><th>".t("Näytä avoimet laskut")."</th><td><input type='radio' name='valintra' value='' $chk1 onclick='submit()'></td>
		 <th>".t("Näytä maksetut laskut")."</th><td><input type='radio' name='valintra' value='maksetut' $chk2 onclick='submit()'></td></tr>";
echo "</form>";
echo "</table><br>";

echo "<table>";
echo "<tr>";
echo "<form action = '$PHP_SELF?tila=$tila&tunnus=$tunnus&valintra=$valintra' method = 'post'>";

for ($i = 0; $i < mysql_num_fields($result)-2; $i++) {
  echo "<th><a href='$PHP_SELF?tunnus=$tunnus&tila=$tila&valintra=$valintra&ojarj=".$i.$ulisa."'>" . t(mysql_field_name($result,$i))."</a></th>";
}

echo "<td class='back'></td></tr>";
echo "<tr>";

for ($i = 0; $i < mysql_num_fields($result)-2; $i++) {
  echo "<td><input type='text' size='$kentankoko[$i]' name = 'haku[$i]' value = '$haku[$i]'></td>";
}

echo "<td class='back'><input type='submit' value='".t("Etsi")."'></td></tr>";
echo "</form>";

$summa = 0;

while ($maksurow=mysql_fetch_array ($result)) {

  echo "<tr>";
  for ($i=0; $i<mysql_num_fields($result)-2; $i++) {
    if (mysql_field_name($result,$i) == 'laskunro') {
		/* laskunrosta linkki laskun tietoihin */
    	echo "<td><a href=\"../muutosite.php?tee=E&tunnus=$maksurow[tunnus]\">$maksurow[$i]</a></td>";
    } 
    else {
    	if (mysql_field_name($result,$i) == 'summa') {
    		if (($maksurow["saldo_maksettu"] != 0) and ($valintra == '')) {
    			$maksurow[$i] .= "*";
    		}
    	}
		echo "<td>$maksurow[$i]</td>";
    }
  }
  $summa+=$maksurow["summa"];
  echo "<td class='back'></td></tr>";
}

echo "<tr><td class='back' colspan='2'></td><th>".t("Yhteensä").":</th><th>$summa</th></tr>";

echo "</table>";

echo "<br>";
if ($kaato["summa"]>0) {
	echo "<form action = 'maksa_kaatosumma.php?tunnus=$asiakas_tunnus' method = 'post'>";
	echo "<input type='submit' value='".t("Maksa kaatotilin summa asiakkaalle")."'></submit>";
	echo "</form>";
}

echo "<script LANGUAGE='JavaScript'>document.forms[0][0].focus()</script>";

?>
