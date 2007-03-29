<?php

	if (file_exists("../inc/parametrit.inc")) {
		require ("../inc/parametrit.inc");
		$post_myynti = "tilaus_myynti.php";
		if ($toim_kutsu == "") $toim_kutsu = "RIVISYOTTO";
	}
	else {
		require ("parametrit.inc");
		$post_myynti = "tilaus_myynti.php";
		$toim_kutsu = "EXTRANET";
	}

	if ($toim == '') {
		$selain = "varaosaselain";
	}
	else {
		$selain = $toim."selain";
	}


	echo "<font class='head'>".t(ucfirst(strtolower($selain)))."</font><hr>";

	if (strtoupper($yhtiorow['kieli']) != 'FI') {
		$selain .= $yhtiorow['kieli'];
	}

	if ($kukarow["kesken"] != 0) {
		echo "	<form method='post' action='$post_myynti'>
				<input type='hidden' name='toim' value='$toim_kutsu'>
				<input type='hidden' name='aktivoinnista' value='true'>
				<input type='hidden' name='tilausnumero' value='$kukarow[kesken]'>
				<input type='submit' value='".t("Takaisin tilaukselle")."'>
				</form>";
		
		$query = "select toim_maa from lasku where yhtio = '$kukarow[yhtio]' and tunnus = '$kukarow[kesken]'";
		$maaresult = mysql_query($query) or pupe_error($query);
		$maarow = mysql_fetch_array($result);
		
	}
	elseif ($kukarow["extranet"] != "") {
		$query = "select if(toim_nimi='',maa,toim_maa) toim_maa from asiakas where yhtio = '$kukarow[yhtio]' and tunnus = '$kukarow[oletus_asiakas]'";
		$maaresult = mysql_query($query) or pupe_error($query);
		$maarow = mysql_fetch_array($result);
	}

	if ($toiminto == "LISAARIVI" and $kukarow["kesken"] != 0) {

		// haetaan avoimen tilauksen otsikko
		$query    = "select * from lasku where yhtio='$kukarow[yhtio]' and tunnus='$kukarow[kesken]'";
		$laskures = mysql_query($query);

		if (mysql_num_rows($laskures) == 0) {
			echo "<font class='error'>Sinulla ei ole avointa tilausta!</font><br>";
		}
		else {

			// tilauksen tiedot
			$laskurow = mysql_fetch_array($laskures);

			echo "<font class='message'>Lisätään tuotteita tilaukselle $kukarow[kesken].</font><br>";

			// käydään läpi formin kaikki rivit
			foreach ($tilkpl as $yht_i => $kpl) {

				if ((float) $kpl > 0) {

					// haetaan tuotteen tiedot
					$query    = "select * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$tiltuoteno[$yht_i]'";
					$tuoteres = mysql_query($query);

					if (mysql_num_rows($tuoteres) == 0) {
						echo "<font class='error'>Tuotetta $tiltuoteno[$yht_i] ei löydy!</font><br>";
					}
					else {

						// tuote löytyi ok, lisätään rivi
						$trow = mysql_fetch_array($tuoteres);

						$ytunnus         = $laskurow["ytunnus"];
						$kpl             = (float) $kpl;
						$tuoteno         = $trow["tuoteno"];
						$toimaika 	     = $laskurow["toimaika"];
						$kerayspvm	     = $laskurow["kerayspvm"];
						$hinta 		     = "";
						$netto 		     = "";
						$ale 		     = "";
						$var		     = "";
						$alv		     = "";
						$paikka		     = "";
						$varasto 	     = "";
						$rivitunnus		 = "";
						$korvaavakielto	 = "";
						$varataan_saldoa = "";

						require ("lisaarivi.inc");

						echo "<font class='message'>Lisättiin $kpl kpl tuotetta $trow[tuoteno].</font><br>";

					} // tuote ok else

				} // end kpl > 0

			} // end foreach

		} // end tuotelöytyi else

		echo "<br>";
	}

	//Otetaan yhteys varaosaselaimen tietokantapalvelimeen
	$con   = mysql_connect($dbhostvosa, $dbuservosa, $dbpassvosa) or die("Yhteys tietokantaan epaonnistui!");
	$boo   = mysql_select_db ($dbkantavosa) or die ("Tietokanta ei löydy palvelimelta..");

	$query = "select distinct merkki from $selain order by merkki";
	$res   = mysql_query($query,$con);

	echo "<form action='$PHP_SELF' name='varaosaselain' method='post'>";
	echo "<input type='hidden' name='toim' value='$toim'>";
	echo "<table><tr>";
	echo "<td><select name='merkki' onchange='submit()'>";

	echo "<option value=''>".t("Valitse merkki")."</option>";

	while ($rivi = mysql_fetch_array($res)) {
		$selected='';
		if ($merkki==$rivi[0]) $selected=' SELECTED';
		echo "<option value='$rivi[0]'$selected>$rivi[0]</option>";
	}

	echo "</select></td>";

	if ($merkki!=$oldmerkki) {
		$cc='';
		$malli='';
		$vm='';
	}

	echo "<td><select name='cc' onchange='submit()'>";
	echo "<option value=''>".t("Valitse CC")."</option>";

	if ($merkki!='') {
		$query = "select distinct cc from $selain where merkki='$merkki' order by cc";
		$res   = mysql_query($query,$con);

		while ($rivi=mysql_fetch_array($res)) {
			$selected='';
			if ($cc==$rivi[0]) $selected=' SELECTED';
			echo "<option value='$rivi[0]'$selected>$rivi[0]</option>";
		}
	}
	echo "</select></td>";

	if ($cc!=$oldcc) {
		$malli='';
		$vm='';
	}

	echo "<td><select name='malli' onchange='submit()'>";
	echo "<option value=''>".t("Valitse Malli")."</option>";

	if ($cc!='') {
		$query = "select distinct malli from $selain where merkki='$merkki' and cc='$cc' order by malli";
		$res   = mysql_query($query,$con);

		while ($rivi=mysql_fetch_array($res)) {
			$selected='';
			if ($malli==$rivi[0]) $selected=' SELECTED';
			echo "<option value='$rivi[0]'$selected>$rivi[0]</option>";
		}
	}
	echo "</select></td>";

	if ($malli!=$oldmalli) {
		$vm='';
	}

	echo "<td><select name='vm' onchange='submit()'>";
	echo "<option value=''>".t("Valitse Vuosi")."</option>";

	if ($malli!='') {
		$query = "select distinct vm from $selain where merkki='$merkki' and cc='$cc' and malli='$malli' order by vm";
		$res   = mysql_query($query,$con);

		while ($rivi=mysql_fetch_array($res)){
			$selected='';
			if ($vm==$rivi[0]) $selected=' SELECTED';

			echo "<option value='$rivi[0]'$selected>$rivi[0]</option>";
		}
	}
	echo "</select></td>";

	echo "<input type='hidden' name='oldmerkki' value='$merkki'>";
	echo "<input type='hidden' name='oldcc' value='$cc'>";
	echo "<input type='hidden' name='oldmalli' value='$malli'>";
	echo "<input type='hidden' name='oldvm' value='$vm'>";
	echo "</tr></table>";
	echo "</form>\n";

	if ($vm!='') {
		echo "<br><font class='header'>".t("Tuotteet").":</font><br><br>";

		if ($kukarow['extranet'] != '') {
			require ("connect.inc");
		}
		else {
			require ("../inc/connect.inc");
		}

		echo "<table>";
		echo "<tr><th>".t("Tuoteryhmä")."</th><th>".t("Nimitys")."</th><th>".t("Tuotenumero")."</th><th>".t("Myyntihinta")."</th><th>".t("Varastossa")."</th>";

		if ($kukarow["kesken"] != 0) {
			echo "<th>".t("Lisää tilaukseen")."</th>";
		}

		echo "</tr>";

		//Otetaan konserniyhtiöt hanskaan jotta voidaan laskea konserniyhtiöiden saldoja
		$query	= "	SELECT GROUP_CONCAT(distinct yhtio) yhtiot
					from yhtio
					where yhtio='$kukarow[yhtio]' or (konserni = '$yhtiorow[konserni]' and konserni != '')";
		$pres = mysql_query($query, $link) or pupe_error($query);
		$prow = mysql_fetch_array($pres);

		$konsyhtiot = explode(",", $prow["yhtiot"]);


		//Otetaan rumasti tuottet muuttujaan koska ne on nyt eri tietokannassa
		$query = "	SELECT group_concat(distinct concat(\"'\", tuoteno, \"'\")) tuoteno
					FROM $selain
					WHERE merkki='$merkki' and cc='$cc' and malli='$malli' and vm='$vm'";
		$res = mysql_query($query, $con);
		$row = mysql_fetch_array($res);

		// Joinataan korvaavat mukaan
		$query = "	SELECT valitut.sorttauskentta, tuote_wrapper.*, valitut.trynimi
					FROM tuote tuote_wrapper,
					(	SELECT if(korvaavat.id>0,concat(tuote.try,(select tuoteno from korvaavat korva2 where korva2.yhtio=korvaavat.yhtio and korva2.id=korvaavat.id ORDER BY jarjestys LIMIT 1)), concat(tuote.try,tuote.tuoteno)) sorttauskentta,
						ifnull(korvaavat.tuoteno, tuote.tuoteno) tuoteno, avainsana.selitetark trynimi
						FROM tuote
						LEFT JOIN avainsana ON avainsana.yhtio=tuote.yhtio and avainsana.selite=tuote.try and avainsana.laji='TRY'
						LEFT JOIN korvaavat ON korvaavat.yhtio=tuote.yhtio and korvaavat.id = (select id from korvaavat where korvaavat.yhtio=tuote.yhtio and korvaavat.tuoteno=tuote.tuoteno LIMIT 1)
						WHERE tuote.yhtio='$kukarow[yhtio]' and tuote.tuoteno in ($row[tuoteno])
						GROUP BY 1,2,3
						ORDER BY sorttauskentta, tuote.try, tuote.tuoteno
					) valitut
					WHERE tuote_wrapper.yhtio = '$kukarow[yhtio]'
					and valitut.tuoteno = tuote_wrapper.tuoteno";
		$ressu   = mysql_query($query,$link) or pupe_error($query);

		$yht_i = 0; // tää on meiän indeksi

		echo "<form action='$PHP_SELF' name='lisaa' method='post' autocomplete='off'>";
		echo "<input type='hidden' name='toim' value='$toim'>";
		echo "<input type='hidden' name='toim_kutsu' value='$toim_kutsu'>";
		echo "<input type='hidden' name='toiminto' value = 'LISAARIVI'>";
		echo "<input type='hidden' name='merkki' value='$merkki'>";
		echo "<input type='hidden' name='cc' value='$cc'>";
		echo "<input type='hidden' name='malli' value='$malli'>";
		echo "<input type='hidden' name='vm' value='$vm'>";
		echo "<input type='hidden' name='oldmerkki' value='$merkki'>";
		echo "<input type='hidden' name='oldcc' value='$cc'>";
		echo "<input type='hidden' name='oldmalli' value='$malli'>";
		echo "<input type='hidden' name='oldvm' value='$vm'>";

		while ($tuote = mysql_fetch_array($ressu)) {


			$class		= "";
			$lisakala 	= "";
			if ($tuote["sorttauskentta"] == $edtuoteno) {
				$lisakala = "* ";
				$class = 'spec';
			}

			// katotaan paljonko on myytävissä
			$saldo = 0;

			$link = mysql_connect ($dbhost, $dbuser, $dbpass) or die ("Ongelma tietokantapalvelimessa $dbhost");
			mysql_select_db ($dbkanta, $link) or die ("Tietokanta ei löydy palvelimelta..");

			foreach($konsyhtiot as $yhtio) {
				list(, , $myytavissa) = saldo_myytavissa($tuote["tuoteno"], "", 0, $yhtio, "", "", "", "", "$maarow[toim_maa]");
				$saldo += $myytavissa;
			}

			if ($tuote["myyntihinta"] > 0) {
				$ruuhinta = "$tuote[myyntihinta] $yhtiorow[valkoodi]";
			}
			else {
				$ruuhinta = '-';
			}

			$query = "	SELECT *
						FROM $selain
						WHERE merkki='$merkki' and cc='$cc' and malli='$malli' and vm='$vm' and tuoteno='$tuote[tuoteno]'";
			$res = mysql_query($query,$con);
			$row = mysql_fetch_array($res);


			echo "<tr>
					<td class='$class'>$tuote[trynimi]</td>
					<td class='$class'>$lisakala $row[tuoteryhma]</td>
					<td class='$class'>$tuote[tuoteno]</td>
					<td class='$class' align='right'>$ruuhinta</td>";

			if ($saldo > 0) {
				echo "<td align='center'><img width='12px' heigth='12px' src='pics/vihrea.png'></td>";
			}
			else {
				echo "<td align='center'><img width='12px' heigth='12px' src='pics/punainen.png'></td>";
			}

			if ($kukarow["kesken"] != 0) {
				echo "<td>";
				echo "<input type='hidden' name='tiltuoteno[$yht_i]' value = '$tuote[tuoteno]'>";
				echo "<input type='text' size='7' name='tilkpl[$yht_i]'>";
				echo "<input type='submit' value = 'Lisää'>";
				echo "</td>";
				$yht_i++;
			}

			$edtuoteno = $tuote["sorttauskentta"];

			echo "</tr>";

		} // end while tuote

		echo "</form>";
		echo "</table>";
	}

	if ($kukarow['extranet'] != '') {
		require ("footer.inc");
	}
	else {
		require ("../inc/footer.inc");
	}
?>