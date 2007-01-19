<?php

	require('../inc/parametrit.inc');

 	echo "<font class='head'>".t("Tulosta sisäisiä laskuja").":</font><hr><br>";

	if ($tee == 'TULOSTA') {

		//valitaan tulostin
		$tulostimet[0] = 'Lasku';

		if (count($komento) == 0) {
			require("../inc/valitse_tulostin.inc");
		}


		if ($tila == 'yksi') {
			if ($laskunro != '') {
				$where = " and laskunro='$laskunro' ";
			}
		}
		elseif ($tila == 'monta') {
			if ($vva != '' and $vvl != '' and $kka != '' and $kkl != '') {
				$where = "	and lasku.tapvm >='$vva-$kka-$ppa 00:00:00'
							and lasku.tapvm <='$vvl-$kkl-$ppl 23:59:59' ";
			}
		}
		else {
			echo "".t("Ilman hakukriteerejä ei voida jatkaa")."!";
			exit;
		}


		if ($where == '') {
			echo "".t("Et syöttänyt mitään järkevää")."!<br>";
			exit;
		}

		if ($raportti == "k") {
			$where .= " and vienti!='' ";
		}
		else {
			$where .= " and vienti='' ";
		}

		//hateaan laskun kaikki tiedot
		$query = "	SELECT *
					FROM lasku WHERE tila='U' and alatila='X' and sisainen='o'
					$where
					and yhtio ='$kukarow[yhtio]'
					ORDER BY laskunro";
		$laskurrrresult = mysql_query ($query) or die ("".t("Kysely ei onnistu")." $query");

		while ($laskurow = mysql_fetch_array($laskurrrresult)) {
			$otunnus = $laskurow["tunnus"];

			// haetaan maksuehdon tiedot
			$query  = "select * from maksuehto where yhtio='$kukarow[yhtio]' and tunnus='$laskurow[maksuehto]'";
			$result = mysql_query ($query) or die ("$query<br><br>".mysql_error());

			if (mysql_num_rows($result) == 0) {
				$masrow = array();
			 	if ($laskurow["erpcm"] == "0000-00-00") {
					echo "<font class='error'>".t("VIRHE: Maksuehtoa ei löydy")."! $laskurow[maksuehto]!</font>";
				}
			}
			else {
				$masrow = mysql_fetch_array($result);
			}

			//maksuehto tekstinä
			$maksuehto = $masrow["teksti"]." ".$masrow["kassa_teksti"];

			if ($yhtiorow['laskutyyppi'] == 0) {
				require_once("tulosta_lasku.inc");
			}
			elseif ($yhtiorow['laskutyyppi'] == 2) {
				require_once("tulosta_lasku_perhe.inc");
			}
			else {
				require_once("tulosta_lasku_plain.inc");
			}

			// defaultteja tulostukseen
			$kala = 540;
			$lask = 1;
			$sivu = 1;

			// haetaan tilauksen kaikki rivit
			$query = "	SELECT *
									FROM tilausrivi
									WHERE uusiotunnus = '$otunnus' and yhtio='$kukarow[yhtio]'
									ORDER BY toimaika, kerayspvm, tunnus";
			$result = mysql_query($query)
				or die ("$query<br><br>".mysql_error());

			//kuollaan jos yhtään riviä ei löydy
			if (mysql_num_rows($result) == 0) die("".t("Laskurivejä ei löytynyt")."");

			// aloitellaan laskun teko
			$firstpage = alku();

			while ($row = mysql_fetch_array($result)) {
				rivi($firstpage);
			}
			loppu($firstpage);
			alvierittely ($firstpage, $kala);

			//keksitään uudelle failille joku varmasti uniikki nimi:
			list($usec, $sec) = explode(' ', microtime());
			mt_srand((float) $sec + ((float) $usec * 100000));
			$pdffilenimi = "/tmp/".md5(uniqid(mt_rand(), true)).".pdf";

			//kirjoitetaan pdf faili levylle..
			$fh = fopen($pdffilenimi, "w+");
			if (fwrite($fh, $pdf->generate()) === FALSE) die("".t("PDF kirjoitus epäonnistui")." $pdffilenimi");
			fclose($fh);

			// itse print komento...
			$line = exec("$komento[Lasku] $pdffilenimi");

			//poistetaan tmp file samantien kuleksimasta...
			system("rm -f $pdffilenimi");

			echo "".t("Lasku")." $laskurow[laskunro]".t("tulostuu")."...<br>";

			unset($pdf);

			$fh = fopen($pdffilenimi, "w+");

			//PDF parametrit
			$pdf = new pdffile;

			$pdf->set_default('margin-top', 	0);
			$pdf->set_default('margin-bottom', 	0);
			$pdf->set_default('margin-left', 	0);
			$pdf->set_default('margin-right', 	0);
			//* PDF-kikkailut loppuu tähän*///
		}
		$tee = '';
		echo "<br>";
	}

	if ($tee == '') {
		//syötetään tilausnumero
		echo "<table>";
		echo "<form action = '$PHP_SELF' method = 'post'>";
		echo "<input type='hidden' name='tee' value='TULOSTA'>";
		echo "<input type='hidden' name='tila' value='yksi'>";
		echo "<tr><th colspan='2'>".t("Laskunumero")."</th></tr>";
		echo "<tr>";
		echo "<td><input type='text' size='10' name='laskunro'></td>";
		echo "<td><input type='submit' value='".t("Tulosta")."'></td></tr>";
		echo "</form>";
		echo "</table><br>";

		if (!isset($kka))
			$kka = date("m");
		if (!isset($vva))
			$vva = date("Y");
		if (!isset($ppa))
			$ppa = date("d");

		if (!isset($kkl))
			$kkl = date("m");
		if (!isset($vvl))
			$vvl = date("Y");
		if (!isset($ppl))
			$ppl = date("d");
		echo "<table>";
		echo "<form action = '$PHP_SELF' method = 'post'>";
		echo "<input type='hidden' name='tee' value='TULOSTA'>";
		echo "<input type='hidden' name='tila' value='monta'>";
		echo "<tr><th>".t("Syötä alkupäivämäärä (pp-kk-vvvv)")."</th>
				<td><input type='text' name='ppa' value='$ppa' size='3'></td>
				<td><input type='text' name='kka' value='$kka' size='3'></td>
				<td><input type='text' name='vva' value='$vva' size='5'></td></tr>
				<tr><th>".t("Syötä loppupäivämäärä (pp-kk-vvvv)")."</th>
				<td><input type='text' name='ppl' value='$ppl' size='3'></td>
				<td><input type='text' name='kkl' value='$kkl' size='3'></td>
				<td><input type='text' name='vvl' value='$vvl' size='5'></td></tr>";
		echo "<tr><th>".t("Vain kotimaiset laskut")."</th><td colspan='3'><input type='radio' name='raportti' value='e' checked></td></tr>";
		echo "<tr><th>".t("Vain vientilaskut")."</th><td colspan='3'><input type='radio' name='raportti' value='k'></td></tr>";
		echo "<tr><th></th><td colspan='3'><input type='submit' value='".t("Tulosta")."'></td></tr>";
		echo "</table>";
		echo "</form>";

	}

?>
