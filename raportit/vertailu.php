<?php
///* Tämä skripti käyttää slave-tietokantapalvelinta *///
$useslave = 1;
require('../inc/parametrit.inc');
echo "<font class='head'>".t("Tuoteryhmävertailu").":";

if (($kausi1!='') and ($kausi2!='') and ($osasto!='') and ($try!='')) {
	echo " ".t("Kausi")." 1 ($kausi1-$kausi1l) vs. ".t("Kausi")." 2 ($kausi2-$kausi2l)";
}


echo "</font><hr>";

$kausi1  = $_POST['kausi1'];
$kausi1l = $_POST['kausi1l'];
$kausi2  = $_POST['kausi2'];
$kausi2l = $_POST['kausi2l'];
$osasto  = $_POST['osasto'];
$osastol = $_POST['osastol'];
$try     = $_POST['try'];
$tryl    = $_POST['tryl'];
$nuolet  = $_POST['nuolet'];

if ($kausi1l=='') $kausi1l=$kausi1;
if ($kausi2l=='') $kausi2l=$kausi2;
if ($osastol=='') $osastol=$osasto;
if ($tryl=='')    $tryl=$try;

$kausi1  = substr($kausi1,0,4)."-".substr($kausi1,4,2)."-01";
$kausi1l = substr($kausi1l,0,4)."-".substr($kausi1l,4,2)."-31";
$kausi2  = substr($kausi2,0,4)."-".substr($kausi2,4,2)."-01";
$kausi2l = substr($kausi2l,0,4)."-".substr($kausi2l,4,2)."-31";


if (($kausi1!='') and ($kausi2!='') and ($osasto!='') and ($try!=''))
{
    $osastot     = '';
	$osastonimet = array();
	$tryt        = '';
    $trynimet    = array();


	$query = "	select distinct avainsana.selite, ".avain('select')."
				from avainsana
				".avain('join','OSASTO_')."
				where avainsana.yhtio='$kukarow[yhtio]' and avainsana.laji = 'OSASTO'
				and avainsana.selite>='$osasto' and avainsana.selite<='$osastol'
				order by avainsana.jarjestys";
	$res   = mysql_query ($query) or die("$query<br><br>".mysql_error());

	while ($row=mysql_fetch_array($res)) {
		$osastot .= "'$row[selite]',";
		$osastonimet[$row['selite']] = $row["selitetark"];
	}

	$query = "	select distinct avainsana.selite, ".avain('select')."
				from avainsana
				".avain('join','TRY_')."
				where avainsana.yhtio='$kukarow[yhtio]' and avainsana.laji = 'TRY'
				and avainsana.selite>='$try' and avainsana.selite<='$tryl'
				order by avainsana.jarjestys";
	$res   = mysql_query ($query) or die("$query<br><br>".mysql_error());

	while ($row=mysql_fetch_array($res)) {
		$tryt .= "'$row[selite]',";
		$trynimet[$row['selite']] = $row["selitetark"];
	}

	$osastot = substr($osastot,0,-1);
	$tryt    = substr($tryt,0,-1);

	echo "<table>\n";
	echo "<tr>
		<th><br>".t("Os")."</th>
		<th><br>".t("Tuoteryhmä")."</th>

		<th>".t("Kausi 1")."<br>".t("Myynti")."</th>
		<th>".t("Kausi 2")."<br>".t("Myynti")."</th>
		<th>".t("Ero")."<br>".t("Myynti")."</th>
		<th>".t("Ero-%")."<br>".t("Myynti")."</th>

		<th>".t("Kausi")." 1<br>".t("Kate")."</th>
		<th>".t("Kausi")." 2<br>".t("Kate")."</th>
		<th>".t("Ero")."<br>".t("Kate")."</th>
		<th>".t("Ero-%")."<br>".t("Kate")."</th>

		<th>".t("Kausi")." 1<br>".t("Katepros")."</th>
		<th>".t("Kausi")." 2<br>".t("Katepros")."</th>
		<th>".t("Ero")."<br>".t("Katepros")."</th>

		<th>".t("Kausi")." 1<br>".t("Kpl")."</th>
		<th>".t("Kausi")." 2<br>".t("Kpl")."</th>
		<th>".t("Ero")."<br>".t("Kpl")."</th>
		<th>".t("Ero")."-%<br>".t("Kpl")."</th>

		</tr>\n";


	// haetaan tietod molemmille kausille
	$query = "	SELECT
				sum(if(laskutettuaika>='$kausi1' and laskutettuaika<='$kausi1l', rivihinta,0)) myynti1,
	            sum(if(laskutettuaika>='$kausi1' and laskutettuaika<='$kausi1l', kate,0)) kate1,
				sum(if(laskutettuaika>='$kausi1' and laskutettuaika<='$kausi1l', kpl,0)) kpl1,
				sum(if(laskutettuaika>='$kausi2' and laskutettuaika<='$kausi2l', rivihinta,0)) myynti2,
	            sum(if(laskutettuaika>='$kausi2' and laskutettuaika<='$kausi2l', kate,0)) kate2,
				sum(if(laskutettuaika>='$kausi2' and laskutettuaika<='$kausi2l', kpl,0)) kpl2,
				osasto, try
				FROM tilausrivi use index (yhtio_tyyppi_osasto_try_laskutettuaika)
				WHERE yhtio='$kukarow[yhtio]'
				and try in ($tryt)
				and osasto in ($osastot)
				and tyyppi='L'
				and ((laskutettuaika>='$kausi1' and laskutettuaika<='$kausi1l') or (laskutettuaika>='$kausi2' and laskutettuaika<='$kausi2l'))
				GROUP BY osasto, try ";
	$res  = mysql_query ($query) or die("$query<br><br>".mysql_error());

	$kate1 ='';
	$kate2 ='';
	$myyproero ='';
	$katproero ='';
	$kplproero ='';

    while ($row = mysql_fetch_array($res)) {
		// nollataan muuttujat
		$kate1 = $kate2 = $myyproero = $katproero = $kplproero = 0;

		// lasketaan kate
		if ($row["myynti1"]!='' and $row["myynti1"]<>0) $kate1 = round($row["kate1"]/$row["myynti1"]*100,1);
		if ($row["myynti2"]!='' and $row["myynti2"]<>0) $kate2 = round($row["kate2"]/$row["myynti2"]*100,1);

		// lasketaan ero prossina
		if ($row["myynti2"]!='' and $row["myynti2"]<>0) $myyproero = round(($row["myynti1"]/$row["myynti2"]-1)*100,1);
		if ($row["kate2"]!=''   and $row["kate2"]<>0)   $katproero = round(($row["kate1"]/$row["kate2"]-1)*100,1);
		if ($row["kpl2"]!=''    and $row["kpl2"]<>0)    $kplproero = round(($row["kpl1"]/$row["kpl2"]-1)*100,1);

		$yhtmyynti1		+= $row["myynti1"];
		$yhtkate1		+= $row["kate1"];
		$yhtkpl1		+= $row["kpl1"];
		$yhtmyynti2		+= $row["myynti2"];
		$yhtkate2		+= $row["kate2"];
		$yhtkpl2		+= $row["kpl2"];
		$yhtmyyntiero	+= ($row["myynti1"]-$row["myynti2"]);
		$yhtkateero		+= ($row["kate1"]-$row["kate2"]);
		$yhtkplero		+= ($row["kpl1"]-$row["kpl2"]);
		$rivilaskuri	++;

		// lasketaan erotus
		$eromyynti		= str_replace(".", ",",sprintf("%01.2f", $row["myynti1"]-$row["myynti2"]));
		$erokate		= str_replace(".", ",",sprintf("%01.2f", $row["kate1"]-$row["kate2"]));
		$erokpl			= str_replace(".", ",",sprintf("%01.2f", $row["kpl1"]-$row["kpl2"]));
		$erokatepros	= $kate1-$kate2;

		$kuva1 = "class='green'";
		$kuva2 = "class='green'";
		$kuva3 = "class='green'";
		$kuva4 = "class='green'";

		if ($eromyynti<0)	$kuva1 = "class='red'";
		if ($erokate<0)		$kuva2 = "class='red'";
		if ($erokpl<0)		$kuva3 = "class='red'";
		if ($erokatepros<0)	$kuva4 = "class='red'";

		// muutetaan pisteet pilkuiks
		$row["myynti1"] = str_replace(".", ",", $row["myynti1"]);
		$row["kate1"] 	= str_replace(".", ",", $row["kate1"]);
		$row["kpl1"] 	= str_replace(".", ",", $row["kpl1"]);
		$row["myynti2"] = str_replace(".", ",", $row["myynti2"]);
		$row["kate2"] 	= str_replace(".", ",", $row["kate2"]);
		$row["kpl2"] 	= str_replace(".", ",", $row["kpl2"]);

		if ($class=='')
			$class='rivi2';
		else
			$class='';

		// jos joku tieto löytyy niin tulostetaan rivi..
		if (($row["myynti1"]!='') or ($row["kate1"]!='') or ($row["kpl1"]!='') or ($row["myynti2"]!='') or ($row["kate2"]!='') or ($row["kpl2"]!='')) {

			echo "<tr class='$class'>
				<td align='right' >$row[osasto] ".$osastonimet[$row['osasto']]."</td>
				<td align='right'  nowrap style='text-align: left;'>$row[try] ".$trynimet[$row['try']]."</td>

				<td align='right' >$row[myynti1]</td>
				<td align='right' >$row[myynti2]</td>
				<td align='right' $kuva1>$eromyynti</td>
				<td align='right' $kuva1>$myyproero</td>

				<td align='right' >$row[kate1]</td>
				<td align='right' >$row[kate2]</td>
				<td align='right' $kuva2>$erokate</td>
				<td align='right' $kuva2>$katproero</td>

				<td align='right' >$kate1</td>
				<td align='right' >$kate2</td>
				<td align='right' $kuva4>$erokatepros</td>

				<td align='right' >$row[kpl1]</td>
				<td align='right' >$row[kpl2]</td>
				<td align='right' $kuva3>$erokpl</td>
				<td align='right' $kuva3>$kplproero</td>

				</tr>\n";
		}
	}

	$yhtmyynti1		= str_replace(".", ",",sprintf("%01.2f", $yhtmyynti1));
	$yhtkate1		= str_replace(".", ",",sprintf("%01.2f", $yhtkate1));
	$yhtkpl1		= str_replace(".", ",",sprintf("%01.2f", $yhtkpl1));
	$yhtmyynti2		= str_replace(".", ",",sprintf("%01.2f", $yhtmyynti2));
	$yhtkate2		= str_replace(".", ",",sprintf("%01.2f", $yhtkate2));
	$yhtkpl2		= str_replace(".", ",",sprintf("%01.2f", $yhtkpl2));
	$yhtmyyntiero	= str_replace(".", ",",sprintf("%01.2f", $yhtmyyntiero));
	$yhtkateero		= str_replace(".", ",",sprintf("%01.2f", $yhtkateero));
	$yhtkplero		= str_replace(".", ",",sprintf("%01.2f", $yhtkplero));

	if ($rivilaskuri!='')
	{
		if ($yhtmyynti1 <> 0)  $yhtkatepro1 = round($yhtkate1/$yhtmyynti1*100,1);
		if ($yhtmyynti2 <> 0)  $yhtkatepro2 = round($yhtkate2/$yhtmyynti2*100,1);
		if ($yhtmyynti2 <> 0)  $yhtmyyproero = round(($yhtmyynti1/$yhtmyynti2-1)*100,1);
		if ($yhtkate2 <> 0)    $yhtkatproero = round(($yhtkate1/$yhtkate2-1)*100,1);
		if ($yhtkpl2 <> 0)     $yhtkplproero = round(($yhtkpl1/$yhtkpl2-1)*100,1);
		if ($yhtkatepro2 <> 0) $yhtkateproero = round($yhtkatepro1-$yhtkatepro2,1);
	}

	$kuva1 = "class='green'";
	$kuva2 = "class='green'";
	$kuva3 = "class='green'";
	$kuva4 = "class='green'";

	if ($yhtmyyntiero<0)	$kuva1 = "class='red'";
	if ($yhtkateero<0)		$kuva2 = "class='red'";
	if ($yhtkplero<0)		$kuva3 = "class='red'";
	if ($yhtkateproero<0)	$kuva4 = "class='red'";

	echo "
		<tr><td align='right'  colspan='17' class='back'><hr></td></tr>
		<tr class='rivi2'><th colspan='2'>".t("Yhteensä")."</th>

		<td align='right'  nowrap>$yhtmyynti1</td>
		<td align='right'  nowrap>$yhtmyynti2</td>
		<td align='right'  $kuva1 nowrap>$yhtmyyntiero</td>
		<td align='right'  $kuva1>$yhtmyyproero</td>

		<td align='right'  nowrap>$yhtkate1</td>
		<td align='right'  nowrap>$yhtkate2</td>
		<td align='right'  $kuva2 nowrap>$yhtkateero</td>
		<td align='right'  $kuva2>$yhtkatproero</td>

		<td align='right'  nowrap>$yhtkatepro1</td>
		<td align='right'  nowrap>$yhtkatepro2</td>
		<td align='right'  $kuva4 nowrap>$yhtkateproero</td>

		<td align='right'  nowrap>$yhtkpl1</td>
		<td align='right'  nowrap>$yhtkpl2</td>
		<td align='right'  $kuva3 nowrap>$yhtkplero</td>
		<td align='right'  $kuva3>$yhtkplproero</td>
		</tr>\n";

	echo "</table>\n";
}
else
{
	echo "
	<form method='post' action='vertailu.php'>

	<table border='0'>
		<tr>
			<td nowrap>".t("Osasto Alku").":</td>
			<td><input maxlength='6' size='10' value='$osasto' name='osasto' type='text'></td>
			<td nowrap>".t("Loppu").":</td>
			<td><input maxlength='6' size='10' value='$osastol' name='osastol' type='text'></td>
		</tr>
		<tr>
			<td nowrap>".t("Tuoteryhmä Alku").":</td>
			<td><input maxlength='6' size='10' value='$try' name='try' type='text'></td>
			<td nowrap>".t("Loppu").":</td>
			<td><input maxlength='6' size='10' value='$tryl' name='tryl' type='text'></td>
		</tr>
		<tr>
			<td nowrap>".t("Kausi")." 1 (".t("vvvvkk").") ".t("Alku").":</td>
			<td><input maxlength='6' size='10' value='' name='kausi1' type='text'></td>
			<td nowrap>".t("Loppu").":</td>
			<td><input maxlength='6' size='10' value='' name='kausi1l' type='text'></td>
		</tr>
		<tr>
			<td nowrap>".t("Kausi")." 2 (".t("vvvvkk").") ".t("Alku").":</td>
			<td><input maxlength='6' size='10' value='' name='kausi2' type='text'></td>
			<td nowrap>".t("Loppu").":</td>
			<td><input maxlength='6' size='10' value='' name='kausi2l' type='text'></td>
		</tr>
	</table>
	<br><input type='submit' name='submit' value='".t("Suorita Haku")."'>

	</form>";

if ($submit!='')
{
	echo "<font color='#ff0000'><b>".t("Kaikkiin Alku-kenttiin on syötettävä jotain...")."</b></font>";
}

}

require ("../inc/footer.inc");

?>