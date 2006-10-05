<?php
///* Tämä skripti käyttää slave-tietokantapalvelinta *///
$useslave = 1;
require ("../inc/parametrit.inc");

echo "<font class='head'>".t("Rivilaskuri")."</font><hr>";


if (($ppa!='') and ($kka!='') and ($vva!=''))
{
	$query = "select left(date_format(laadittu,'%H:%i'),4),
				count(*),
				sum(kpl),
				sum(IF(lasku.vienti='E',1,0)),
				sum(IF(lasku.vienti='E',kpl,0)),
				sum(IF(lasku.vienti='K',1,0)),
				sum(IF(lasku.vienti='K',kpl,0)),
				sum(IF(lasku.laatija = 'EDI',1,0)),
				sum(IF(lasku.laatija = 'EDI',kpl,0))
				from tilausrivi, lasku
				where tilausrivi.yhtio='$kukarow[yhtio]'
				and tilausrivi.yhtio=lasku.yhtio
				and tilausrivi.otunnus=lasku.tunnus
				and laadittu>='$vva-$kka-$ppa 00:00:00'
				and laadittu<='$vvl-$kkl-$ppl 23:59:59'
				and tila='L'
				group by 1
				order by 1";
	$res   = mysql_query($query) or pupe_error($query);

	echo "<br><table>";
	echo "<tr><th>$ppa.$kka.$vva-$ppl.$kkl.$vvl</th><th colspan='2' align='center'>".t("Yhteensä")."</th><th colspan='2' align='center'>".t("Vienti EU")."</th><th colspan='2' align='center'>".t("ei-EU")."</th><th colspan='2' align='center'>".t("EDI")."</th></tr>
		  <tr><th>".t("Kello")."</th><th>".t("Rivejä")."</th><th>".t("Kpl")."</th><th>".t("Rivejä")."</th><th>".t("Kpl")."</th><th>".t("Rivejä")."</th><th>".t("Kpl")."</th><th>".t("Rivejä")."</th><th>".t("Kpl")."</th></tr>";

	while ($row = mysql_fetch_array($res))
	{
		echo "<tr>
			<td>$row[0]0 - $row[0]9</td>
			<td align='right'>".str_replace(".",",",$row['1'])."</td>
			<td align='right'>".str_replace(".",",",$row['2'])."</td>
			<td align='right'>".str_replace(".",",",$row['3'])."</td>
			<td align='right'>".str_replace(".",",",$row['4'])."</td>
			<td align='right'>".str_replace(".",",",$row['5'])."</td>
			<td align='right'>".str_replace(".",",",$row['6'])."</td>
			<td align='right'>".str_replace(".",",",$row['7'])."</td>
			<td align='right'>".str_replace(".",",",$row['8'])."</td>
			</tr>";
	}

	///* Yhteensärivi, annetaan tietokannan tehä työ, en jakssa summata while loopissa t. juppe*///
	$query = "select
				count(*),
				sum(kpl),
				sum(IF(lasku.vienti='E',1,0)),
				sum(IF(lasku.vienti='E',kpl,0)),
				sum(IF(lasku.vienti='K',1,0)),
				sum(IF(lasku.vienti='K',kpl,0)),
				sum(IF(lasku.laatija = 'EDI',1,0)),
				sum(IF(lasku.laatija = 'EDI',kpl,0))
				from tilausrivi, lasku
				where tilausrivi.yhtio='$kukarow[yhtio]'
				and tilausrivi.yhtio=lasku.yhtio
				and tilausrivi.otunnus=lasku.tunnus
				and laadittu>='$vva-$kka-$ppa 00:00:00'
				and laadittu<='$vvl-$kkl-$ppl 23:59:59'
				and tila='L'";

	$res   = mysql_query($query) or pupe_error($query);
	$row = mysql_fetch_array($res);

	echo "<tr>
		<td>Yhteensä:</td>
		<td align='right'>".str_replace(".",",",$row[0])."</td>
		<td align='right'>".str_replace(".",",",$row[1])."</td>
		<td align='right'>".str_replace(".",",",$row[2])."</td>
		<td align='right'>".str_replace(".",",",$row[3])."</td>
		<td align='right'>".str_replace(".",",",$row[4])."</td>
		<td align='right'>".str_replace(".",",",$row[5])."</td>
		<td align='right'>".str_replace(".",",",$row[6])."</td>
		<td align='right'>".str_replace(".",",",$row[7])."</td>
		</tr>";


	echo "</table>";
}

if (!isset($kka))
	$kka = date("m",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
if (!isset($vva))
	$vva = date("Y",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
if (!isset($ppa))
	$ppa = date("d",mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));

if (!isset($kkl))
	$kkl = date("m");
if (!isset($vvl))
	$vvl = date("Y");
if (!isset($ppl))
	$ppl = date("d");

echo "<br>
<form action='$PHP_SELF' method='post' autocomplete='off'>
<table>";

echo "<tr><th>Syötä alkupäivämäärä (pp-kk-vvvv)</th>
		<td><input type='text' name='ppa' value='$ppa' size='3'></td>
		<td><input type='text' name='kka' value='$kka' size='3'></td>
		<td><input type='text' name='vva' value='$vva' size='5'></td>
		</tr><tr><th>Syötä loppupäivämäärä (pp-kk-vvvv)</th>
		<td><input type='text' name='ppl' value='$ppl' size='3'></td>
		<td><input type='text' name='kkl' value='$kkl' size='3'></td>
		<td><input type='text' name='vvl' value='$vvl' size='5'></td></tr>";
echo "<tr><td class='back'><input type='Submit' value = '".t("Aja raportti")."'></td>
</tr></table>
</form>";

require ("../inc/footer.inc");

?>
