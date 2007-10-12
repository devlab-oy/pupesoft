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
			echo t("Ilman hakukriteerejä ei voida jatkaa")."!";
			exit;
		}


		if ($where == '') {
			echo t("Et syöttänyt mitään järkevää")."!<br>";
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
					FROM lasku 
					WHERE tila    = 'U' 
					and alatila   = 'X' 
					and sisainen != ''
					$where
					and yhtio ='$kukarow[yhtio]'
					ORDER BY laskunro";
		$laskurrrresult = mysql_query ($query) or die ("".t("Kysely ei onnistu")." $query");

		while ($laskurow = mysql_fetch_array($laskurrrresult)) {
						
			$otunnus = $laskurow["tunnus"];
			
			// haetaan maksuehdon tiedot
			$query  = "	select * 
						from maksuehto 
						left join pankkiyhteystiedot on (pankkiyhteystiedot.yhtio=maksuehto.yhtio and pankkiyhteystiedot.tunnus=maksuehto.pankkiyhteystiedot)
						where maksuehto.yhtio='$kukarow[yhtio]' and maksuehto.tunnus='$laskurow[maksuehto]'";
			$result = mysql_query($query) or pupe_error($query);
			
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
			$maksuehto      = $masrow["teksti"]." ".$masrow["kassa_teksti"];
			$kateistyyppi   = $masrow["kateinen"];
			
			if ($yhtiorow['laskutyyppi'] == 3) {
				require_once ("tulosta_lasku_simppeli.inc");
				tulosta_lasku($otunnus, $komento["Lasku"], $kieli, $toim, $tee);
				$tee = '';
			}
			else {
				require_once("tulosta_lasku.inc");

				if ($laskurow["tila"] == 'U') {
					$where = " uusiotunnus='$otunnus' ";
				}
				else {
					$where = " otunnus='$otunnus' ";
				}

				// katotaan miten halutaan sortattavan
				$sorttauskentta = generoi_sorttauskentta($yhtiorow["laskun_jarjestys"]);

				// haetaan tilauksen kaikki rivit
				$query = "  SELECT *, $sorttauskentta
							FROM tilausrivi
							WHERE $where
							and yhtio  = '$kukarow[yhtio]'
							and tyyppi = 'L'
							ORDER BY otunnus, sorttauskentta $yhtiorow[laskun_jarjestys_suunta], tilausrivi.tunnus";
				$result = mysql_query($query) or pupe_error($query);

				//kuollaan jos yhtään riviä ei löydy
				if (mysql_num_rows($result) == 0) {
					echo t("Laskurivejä ei löytynyt");
					exit;
				}

				$sivu 	= 1;
				$summa 	= 0;
				$arvo 	= 0;

				// aloitellaan laskun teko
				$page[$sivu] = alku();

				while ($row = mysql_fetch_array($result)) {
					rivi($page[$sivu]);
				}

				alvierittely($page[$sivu]);

				//keksitään uudelle failille joku varmasti uniikki nimi:
				list($usec, $sec) = explode(' ', microtime());
				mt_srand((float) $sec + ((float) $usec * 100000));
				$pdffilenimi = "/tmp/Lasku_Kopio-".md5(uniqid(mt_rand(), true)).".pdf";

				//kirjoitetaan pdf faili levylle..
				$fh = fopen($pdffilenimi, "w");
				if (fwrite($fh, $pdf->generate()) === FALSE) die("PDF kirjoitus epäonnistui $pdffilenimi");
				fclose($fh);

				// itse print komento...
				if ($komento["Lasku"] == 'email') {
					$liite = $pdffilenimi;
					$kutsu = "Lasku";

					require("../inc/sahkoposti.inc");
				}
				elseif ($komento["Lasku"] != '' and $komento["Lasku"] != 'edi') {
					$line = exec("$komento[Lasku] $pdffilenimi");
				}
				
				echo t("Sisäiset laskut tulostuu")."....<br>";

				//poistetaan tmp file samantien kuleksimasta...
				system("rm -f $pdffilenimi");

				unset($pdf);
				unset($page);					

			}
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
