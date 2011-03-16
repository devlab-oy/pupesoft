<?php

	$query  = "SELECT * FROM lasku WHERE tunnus = '{$otunnus}' AND yhtio = '{$kukarow['yhtio']}' AND tila = 'K'";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) == 1) {
		$laskurow = mysql_fetch_assoc($result);
	}
	else {
		echo "<font class='error'>",t("VIRHE: Tilausta ei enää löydy"),"!</font>";
		exit;
	}

	if (!isset($kappalemaara)) $kappalemaara = '';
	if (!isset($kirjoitin)) $kirjoitin = '';

	if ($tee == 'tulosta_sscc') {

		$kappalemaara = trim($kappalemaara);
		$kirjoitin = trim($kirjoitin);

		$query = "	SELECT komento
					FROM kirjoittimet 
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND tunnus = '{$kirjoitin}'
					ORDER BY kirjoitin";
		$kires = mysql_query($query) or pupe_error($query);
		$kirow = mysql_fetch_assoc($kires);
		$komento = $kirow['komento'];

		if (is_numeric($kappalemaara) and $kappalemaara > 0 and trim($komento) != '') {

			// koko korkeus 92mm
			// printin korkeus 73mm
			// leveys 105mm

			for ($i = 0; $i < $kappalemaara; $i++) {
				$query = "SELECT selite FROM avainsana WHERE yhtio = '{$kukarow['yhtio']}' AND laji='SSCC'";
				$result = mysql_query($query) or pupe_error($query);
				$row = mysql_fetch_assoc($result);

				$sscc = is_numeric($row['selite']) ? (int) $row['selite'] + 1 : 1;

				$nimi = str_replace(array('Ä','ä','Ö','ö'), array('A','a','O','o'), $yhtiorow['nimi']);

				$viivakoodi  = ">6";
				$viivakoodi .= substr($sscc,0,1);
				$viivakoodi .= ">5";
				$viivakoodi .= substr($sscc,1);

				// TODO pitää tehdä locktable ettei vaan saada kahta samaa SSCC-koodia

				$sivu = "*** ETIKETIN KOKO ***
		                 {D2280,1040,2250|}
		                 {AX;+000,+000,+00|}
		                 {AY;+05,0|}
		                 {C|}

						 *** YHTIÖN NIMI ***
						 {PC001;0100,0100,1,1,J,00,B|}
			             {RC001;$nimi|}

						 {LC;0050,0130,1000,0130,0,2|}

						 {PC002;0100,0180,05,05,J,00,B|}
			             {RC002;SSCC|}

						 {PC003;0100,0260,1,1,J,00,B|}
			             {RC003;$sscc|}

						 {LC;0050,0310,1000,0310,0,2|}

		                 {XB00;0100,0350,A,3,04,0,0350,+0000000000,000,0,00|}
		                 {RB00;$viivakoodi|}

						 *** TULOSTUS  ***
	                 	 {XS;I,0001,0002C3210|}
	                 	 ".chr(12);

				$sivu = escapeshellarg($sivu);

				if ($komento == "email") {
					echo t("SSCC-tarraa ei voida tulostaa sähköpostiin")."<br>";
				}
				else {
					$line = exec(" echo \"$sivu\" | $komento");
				}

				if (trim($row['selite']) == '') {

					// haetaan aluksi max perhe
					$query = "	SELECT max(perhe)+1 perhe
								FROM avainsana
								WHERE yhtio = '{$kukarow['yhtio']}'";
					$max_perhe_res = mysql_query($query) or pupe_error($query);
					$max_perhe_row = mysql_fetch_assoc($max_perhe_res);

					$query = "	INSERT INTO avainsana SET 
								yhtio = '{$kukarow['yhtio']}',
								perhe = '{$max_perhe_row['perhe']}',
								kieli = '{$kukarow['kieli']}',
								laji = 'SSCC',
								nakyvyys = '',
								selite = '{$sscc}',
								selitetark = '',
								selitetark_2 = '',
								selitetark_3 = '',
								jarjestys = 0,
								laatija = '{$kukarow['kuka']}',
								luontiaika = now(),
								muutospvm = now(),
								muuttaja = '{$kukarow['kuka']}'";
					$insert_res = mysql_query($query) or pupe_error($query);
				}
				else {
					$query = "UPDATE avainsana SET selite = '{$sscc}' WHERE yhtio = '{$kukarow['yhtio']}' AND laji='SSCC'";
					$update_res = mysql_query($query) or pupe_error($query);
				}
			}

			$tee = $lopetus = '';

			echo "<font class='message'>",t("Käytettävä SSCC-koodi"),": $sscc</font><br /><br />";

			require('suuntalavat.inc');
			require('../inc/footer.inc');
			exit;
		}
		else {
			$tee = '';
		}

	}

	if ($tee == '') {
		echo "<form action='' method='post'>";
		echo "<input type='hidden' name='toimittajaid' value='{$toimittajaid}'>";
		echo "<input type='hidden' name='toiminto' value='tulosta_sscc'>";
		echo "<input type='hidden' name='otunnus' value='{$otunnus}'>";
		echo "<input type='hidden' name='ytunnus' value='{$laskurow['ytunnus']}'>";
		echo "<input type='hidden' name='tee' value='tulosta_sscc' />";
		echo "<input type='hidden' name='lopetus' value='{$palvelin2}tilauskasittely/keikka.php////tee=//toimittajaid={$toimittajaid}//toiminto=suuntalavat//otunnus={$otunnus}//ytunnus={$laskurow['ytunnus']}//suuntalavan_tunnus='>";

		echo "<table>";
		echo "<tr><th>",t("Kpl"),"</th><td><select name='kappalemaara' />";
		echo "<option value='1'>1</option>";
		echo "<option value='2'>2</option>";
		echo "<option value='3'>3</option>";
		echo "<option value='4'>4</option>";
		echo "<option value='5'>5</option>";
		echo "</select></td></tr>";

		echo "<tr><th>",t("Valitse tulostin"),"</th><td><select name='kirjoitin'>";
		echo "<option value=''>",t("Ei kirjoitinta"),"</option>";

		$query = "	SELECT * 
					FROM kirjoittimet 
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND komento != 'email'
					ORDER BY kirjoitin";
		$kires = mysql_query($query) or pupe_error($query);

		while ($kirow = mysql_fetch_assoc($kires)) {
			if ($kirow['tunnus'] == $kirjoitin) $select = ' selected';
			else $select = '';
			echo "<option value='{$kirow['tunnus']}'{$select}>{$kirow['kirjoitin']}</option>";
		}

		echo "</select></td></tr>";

		echo "<tr><td class='back' colspan='2'><input type='submit' value='",t("Tulosta"),"' /></td></tr>";
		echo "</table>";

		echo "</form>";
	}
