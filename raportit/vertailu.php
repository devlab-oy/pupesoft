<?php

	//* Tämä skripti käyttää slave-tietokantapalvelinta *//
	$useslave = 1;

	require('../inc/parametrit.inc');

	echo "<font class='head'>".t("Tuoteryhmävertailu").": ";

	if ($kausi1!='' and $kausi2!='' and $osasto!='' and $try!='') {
		echo t("Kausi")." 1 ($kausi1-$kausi1l) vs. ".t("Kausi")." 2 ($kausi2-$kausi2l)";
	}

	echo "</font><hr>";

	if ($kausi1!='' and $kausi2!='' and $osasto!='' and $try!='') {

		if ($kausi1l=='') $kausi1l=$kausi1;
		if ($kausi2l=='') $kausi2l=$kausi2;
		if ($osastol=='') $osastol=$osasto;
		if ($tryl=='')    $tryl=$try;

		$mkausi1  = substr($kausi1,0,4)."-".substr($kausi1,4,2)."-01";
		$mkausi1l = substr($kausi1l,0,4)."-".substr($kausi1l,4,2)."-".date("d",mktime(0, 0, 0, substr($kausi1l,4,2)+1, 0, substr($kausi1l,0,4)));
		$mkausi2  = substr($kausi2,0,4)."-".substr($kausi2,4,2)."-01";
		$mkausi2l = substr($kausi2l,0,4)."-".substr($kausi2l,4,2)."-".date("d",mktime(0, 0, 0, substr($kausi2l,4,2)+1, 0, substr($kausi2l,0,4)));

		$osastot     = '';
		$osastonimet = array();
		$tryt        = '';
	    $trynimet    = array();

		$res = t_avainsana("OSASTO", "", "and avainsana.selite+0 >= '$osasto' and avainsana.selite+0 <= '$osastol'");

		while ($row = mysql_fetch_array($res)) {
			$osastot .= "'$row[selite]',";
			$osastonimet[$row['selite']] = $row["selitetark"];
		}

		$res = t_avainsana("TRY", "", "and avainsana.selite+0 >= '$try' and avainsana.selite+0 <= '$tryl'");

		while ($row = mysql_fetch_array($res)) {
			$tryt .= "'$row[selite]',";
			$trynimet[$row['selite']] = $row["selitetark"];
		}

		$osastot = substr($osastot,0,-1);
		$tryt    = substr($tryt,0,-1);

		if ($tryt != "" and $osastot != "") {

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
						sum(if(laskutettuaika>='$mkausi1' and laskutettuaika<='$mkausi1l', rivihinta,0)) myynti1,
			            sum(if(laskutettuaika>='$mkausi1' and laskutettuaika<='$mkausi1l', kate,0)) kate1,
						sum(if(laskutettuaika>='$mkausi1' and laskutettuaika<='$mkausi1l', kpl,0)) kpl1,
						sum(if(laskutettuaika>='$mkausi2' and laskutettuaika<='$mkausi2l', rivihinta,0)) myynti2,
			            sum(if(laskutettuaika>='$mkausi2' and laskutettuaika<='$mkausi2l', kate,0)) kate2,
						sum(if(laskutettuaika>='$mkausi2' and laskutettuaika<='$mkausi2l', kpl,0)) kpl2,
						osasto, try
						FROM tilausrivi use index (yhtio_tyyppi_osasto_try_laskutettuaika)
						WHERE yhtio='$kukarow[yhtio]'
						and try in ($tryt)
						and osasto in ($osastot)
						and tyyppi='L'
						and ((laskutettuaika>='$mkausi1' and laskutettuaika<='$mkausi1l') or (laskutettuaika>='$mkausi2' and laskutettuaika<='$mkausi2l'))
						GROUP BY osasto, try ";
			$res  = mysql_query ($query) or pupe_error($query);

			$kate1 ='';
			$kate2 ='';
			$myyproero ='';
			$katproero ='';
			$kplproero ='';

		    while ($row = mysql_fetch_array($res)) {
				// nollataan muuttujat
				$kate1 = $kate2 = $myyproero = $katproero = $kplproero = 0;

				// lasketaan kate
				if ($row["myynti1"]!='' and $row["myynti1"]<>0) $kate1 = round(($row["kate1"]/$row["myynti1"])*100, 2);
				if ($row["myynti2"]!='' and $row["myynti2"]<>0) $kate2 = round(($row["kate2"]/$row["myynti2"])*100, 2);

				// lasketaan ero prossina
				if ($row["myynti2"]!='' and $row["myynti2"]<>0) $myyproero = round(($row["myynti1"]/$row["myynti2"]-1)*100, 2);
				if ($row["kate2"]!=''   and $row["kate2"]<>0)   $katproero = round(($row["kate1"]/$row["kate2"]-1)*100, 2);
				if ($row["kpl2"]!=''    and $row["kpl2"]<>0)    $kplproero = round(($row["kpl1"]/$row["kpl2"]-1)*100, 2);

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
				$eromyynti		= sprintf("%.2f", $row["myynti1"]-$row["myynti2"]);
				$erokate		= sprintf("%.2f", $row["kate1"]-$row["kate2"]);
				$erokpl			= sprintf("%.2f", $row["kpl1"]-$row["kpl2"]);
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
				$row["myynti1"] = sprintf("%.2f", $row["myynti1"]);
				$row["kate1"] 	= sprintf("%.2f", $row["kate1"]);
				$row["kpl1"] 	= sprintf("%.2f", $row["kpl1"]);
				$row["myynti2"] = sprintf("%.2f", $row["myynti2"]);
				$row["kate2"] 	= sprintf("%.2f", $row["kate2"]);
				$row["kpl2"] 	= sprintf("%.2f", $row["kpl2"]);

				// jos joku tieto löytyy niin tulostetaan rivi..
				if ($row["myynti1"]!='' or $row["kate1"]!='' or $row["kpl1"]!='' or $row["myynti2"]!='' or $row["kate2"]!='' or $row["kpl2"]!='') {

					echo "<tr class='aktiivi'>
						<td>$row[osasto] ".$osastonimet[$row['osasto']]."</td>
						<td>$row[try] ".$trynimet[$row['try']]."</td>

						<td align='right'>$row[myynti1]</td>
						<td align='right'>$row[myynti2]</td>
						<td align='right' $kuva1>$eromyynti</td>
						<td align='right' $kuva1>$myyproero</td>

						<td align='right'>$row[kate1]</td>
						<td align='right'>$row[kate2]</td>
						<td align='right' $kuva2>$erokate</td>
						<td align='right' $kuva2>$katproero</td>

						<td align='right'>$kate1</td>
						<td align='right'>$kate2</td>
						<td align='right' $kuva4>$erokatepros</td>

						<td align='right'>$row[kpl1]</td>
						<td align='right'>$row[kpl2]</td>
						<td align='right' $kuva3>$erokpl</td>
						<td align='right' $kuva3>$kplproero</td>

						</tr>\n";
				}
			}

			$yhtmyynti1		= sprintf("%.2f", $yhtmyynti1);
			$yhtkate1		= sprintf("%.2f", $yhtkate1);
			$yhtkpl1		= sprintf("%.2f", $yhtkpl1);
			$yhtmyynti2		= sprintf("%.2f", $yhtmyynti2);
			$yhtkate2		= sprintf("%.2f", $yhtkate2);
			$yhtkpl2		= sprintf("%.2f", $yhtkpl2);
			$yhtmyyntiero	= sprintf("%.2f", $yhtmyyntiero);
			$yhtkateero		= sprintf("%.2f", $yhtkateero);
			$yhtkplero		= sprintf("%.2f", $yhtkplero);

			if ($rivilaskuri!='') {
				if ($yhtmyynti1 <> 0)  $yhtkatepro1   =sprintf("%.2f", $yhtkate1/$yhtmyynti1*100);
				if ($yhtmyynti2 <> 0)  $yhtkatepro2   =sprintf("%.2f", $yhtkate2/$yhtmyynti2*100);
				if ($yhtmyynti2 <> 0)  $yhtmyyproero  =sprintf("%.2f", ($yhtmyynti1/$yhtmyynti2-1)*100);
				if ($yhtkate2 <> 0)    $yhtkatproero  =sprintf("%.2f", ($yhtkate1/$yhtkate2-1)*100);
				if ($yhtkpl2 <> 0)     $yhtkplproero  =sprintf("%.2f", ($yhtkpl1/$yhtkpl2-1)*100);
				if ($yhtkatepro2 <> 0) $yhtkateproero =sprintf("%.2f", $yhtkatepro1-$yhtkatepro2);
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
				<tr class='spec'><td colspan='2'>".t("Yhteensä")."</th>

				<td align='right' nowrap>$yhtmyynti1</td>
				<td align='right' nowrap>$yhtmyynti2</td>
				<td align='right' $kuva1 nowrap>$yhtmyyntiero</td>
				<td align='right' $kuva1>$yhtmyyproero</td>

				<td align='right' nowrap>$yhtkate1</td>
				<td align='right' nowrap>$yhtkate2</td>
				<td align='right' $kuva2 nowrap>$yhtkateero</td>
				<td align='right' $kuva2>$yhtkatproero</td>

				<td align='right' nowrap>$yhtkatepro1</td>
				<td align='right' nowrap>$yhtkatepro2</td>
				<td align='right' $kuva4 nowrap>$yhtkateproero</td>

				<td align='right' nowrap>$yhtkpl1</td>
				<td align='right' nowrap>$yhtkpl2</td>
				<td align='right' $kuva3 nowrap>$yhtkplero</td>
				<td align='right' $kuva3>$yhtkplproero</td>
				</tr>\n";

			echo "</table><br><br>\n";
		}
		else {
			echo "<font class='error'>".t("Yhtään osastoa tai tuoteryhmää ei löytynyt")."</font><br><br>";
		}
	}

	echo "<form method='post'>";

	$query = "	SELECT min(selite+0) minosasto, max(selite+0) maxosasto
				FROM avainsana
				WHERE yhtio = '$kukarow[yhtio]'
				and laji	= 'OSASTO'";
	$al_res = mysql_query($query) or pupe_error($query);
	$os_row = mysql_fetch_array($al_res);

	$query = "	SELECT min(selite+0) minosasto, max(selite+0) maxosasto
				FROM avainsana
				WHERE yhtio = '$kukarow[yhtio]'
				and laji	= 'TRY'";
	$al_res = mysql_query($query) or pupe_error($query);
	$try_row = mysql_fetch_array($al_res);

	if ($osasto == "")	$osasto = $os_row["minosasto"];
	if ($osastol == "")	$osastol = $os_row["maxosasto"];

	if ($try == "")		$try = $try_row["minosasto"];
	if ($tryl == "")	$tryl = $try_row["maxosasto"];

	if ($kausi1 == "")	$kausi1 = date("Ym", mktime(0, 0, 0, 1, 1, date("Y")));
	if ($kausi1l == "")	$kausi1l = date("Ym", mktime(0, 0, 0, date("m"), 1, date("Y")));

	if ($kausi2 == "")	$kausi2 = date("Ym", mktime(0, 0, 0, 1, 1, date("Y")-1));
	if ($kausi2l == "")	$kausi2l = date("Ym", mktime(0, 0, 0, date("m"), 1, date("Y")-1));

	echo "<table>
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
			<td><input maxlength='6' size='10' value='$kausi1' name='kausi1' type='text'></td>
			<td nowrap>".t("Loppu").":</td>
			<td><input maxlength='6' size='10' value='$kausi1l' name='kausi1l' type='text'></td>
		</tr>
		<tr>
			<td nowrap>".t("Kausi")." 2 (".t("vvvvkk").") ".t("Alku").":</td>
			<td><input maxlength='6' size='10' value='$kausi2' name='kausi2' type='text'></td>
			<td nowrap>".t("Loppu").":</td>
			<td><input maxlength='6' size='10' value='$kausi2l' name='kausi2l' type='text'></td>
		</tr>
	</table>
	<br><input type='submit' name='submit' value='".t("Suorita Haku")."'>

	</form>";

	require ("../inc/footer.inc");

?>