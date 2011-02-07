<?php

	require('inc/parametrit.inc');

	if ($toim == "ASIAKAS") {
		echo "<font class='head'>".t("Asiakaspuu")."</font><hr><br>";
	}
	else {
		echo "<font class='head'>".t("Tuotepuu")."</font><hr><br>";
	}

	$oikeus = '';

	if (tarkista_oikeus('dynaaminen_puu.php', $toim, 1)) {
		$oikeus = 'joo';
	}

	// T‰m‰ lis‰‰ tiedot kantaa ja sen j‰lkeen passaa parametrej‰ yllapitoon samaan tuotteeseen mist‰ l‰hdettiin.... toivottavasti..
	if (isset($tee) AND $tee == 'valitse' AND isset($toim) AND isset($liitos)) {

		if ($mista == 'autodata_tuote') {
			$tunnus = $ttunnus;
		}
		else {

			foreach ($id as $node) {
				TuotteenAlkiot($toim, $liitos, $node, $kieli);
			}

			if ($mista == "tuote") {
				$q = "	SELECT tunnus
						FROM tuote
						WHERE yhtio = '$kukarow[yhtio]'
						AND tuoteno = '$liitos'";
				$r = mysql_query($q) or pupe_error($q);
				$rivi = mysql_fetch_assoc($r);

				$tunnus = $rivi['tunnus'];
			}
			else {
				$tunnus = $liitos;
			}
		}

		$toim = $mista;

		require('yllapito.php');
		exit;
	}

	// T‰m‰ luo p‰‰kategorian
	if (isset($KatNimi) AND trim($KatNimi) != '' AND $tee == 'paakat' AND isset($toim) AND trim($toim)) {
		LisaaPaaKat($toim, $KatNimi);
		$tee = '';
	}

	// poistaa ja upgradettaa alemmat lapset isommaksi.
	if (isset($tee) and $tee == 'poista' and isset($lft) AND trim($lft) != ''){
		PoistaLapset($toim, $lft);
		$tee = '';
	}

	// lis‰t‰‰n kategorialle lapsi
	if (isset($tee) and $tee == 'lisaa' AND isset($uusi_nimi) AND trim($uusi_nimi) != "" AND isset($toim) AND trim($toim) != "") {

		if (trim($uusi_nimi) == "") {
			echo "<font class='error'>", t('Et voi antaa tyhj‰‰ arvoa uudeksi tason nimeksi')," !!</font>";
		}
		else {
			LisaaLapsi($toim, $lft, $syvyys, $uusi_koodi, $uusi_nimi);
		}

		$tee = '';
	}

	//  T‰m‰ luo lomakkeen alikategorian lis‰‰miseen
	if (isset($nimi) AND trim($nimi) != "" AND isset($tee) and $tee == 'lisaa') {
		echo "<form method='POST' autocomplete='off'>";
		echo "<table><tr><th>",t('Ylemm‰n Kategorian nimi'),":</th><td>$nimi</td></tr>";
		echo "<tr><th>",t('Alakategorian nimi'),":</th><td><input type='text' size='30' name='uusi_nimi' /></td></tr>";
		echo "<tr><th>",t('Alakategorian koodi'),":</th><td><input type='text' size='30' name='uusi_koodi' /></td></tr>";
		echo "</table><br>";
		echo "<input type='hidden' name='tee'    value='lisaa' />";
		echo "<input type='hidden' name='lft'    value='$lft' />";
		echo "<input type='hidden' name='syvyys' value='$syvyys' />";
		echo "<input type='submit' value='",t('Tallenna Alakategoria'),"' />";
		echo "</form><br><br>";
	}

	// Lisˆt‰‰n uusi taso ja tarkistetaan ettei nimi ole tyhj‰.
	if (isset($tee) and $tee == 'taso' AND isset($uusi_nimi) AND trim($uusi_nimi) != "" AND isset($toim) AND trim($toim) != "") {

		if (trim($uusi_nimi) == "") {
			echo "<font class='error'>", t('Et voi antaa tyhj‰‰ arvoa uudeksi tason nimeksi')," !!</font>";
		}
		else {
			LisaaTaso($toim, $lft, $uusi_koodi, $uusi_nimi);
		}

		$tee = '';
	}

	// t‰m‰ tulostaa Tason-lis‰ys lomakkeen
	if (isset($nimi) AND trim($nimi) != "" AND isset($tee) and $tee == 'taso') {
		echo "<form method='POST' autocomplete='off'>";
		echo "<table>";
		echo "<tr><th>",t('Kategorian nimi'),":</th><td>$nimi</td></tr>";
		echo "<tr><th>",t('Uuden kategorian nimi'),":</th><td><input type='text' size='30' name='uusi_nimi' /></td></tr>";
		echo "<tr><th>",t('Uuden kategorian koodi'),":</th><td><input type='text' size='30' name='uusi_koodi' /></td></tr>";
		echo "</table><br>";
		echo "<input type='hidden' name='tee' value='taso' />";
		echo "<input type='hidden' name='lft' value='".$lft."' />";
		echo "<input type='submit' value='",t('Tallenna taso'),"' />";
		echo "</form><br><br>";
	}

	// muutetaan kategorian nime‰ uusiksi
	if (isset($tee) and $tee == 'muokkaa' and isset($uusi_nimi) AND trim($uusi_nimi) != "" AND isset($toim) AND trim($toim) != "") {

		if (trim($uusi_nimi) == "") {
			echo "<font class='error'>", t('Et voi laittaa tyhj‰‰ arvoa uudeksi arvoksi')," !</font>";
		}
		else {
			paivitakat($toim, $uusi_koodi, $uusi_nimi, $kategoriaid);
		}
		$tee = '';
	}

	// t‰m‰ tulostaa nimen-muutos lomakkeen
	if (isset($nimi) AND trim($nimi) != "" AND isset($tee) and $tee == 'muokkaa') {
		echo "<form method='POST' autocomplete='off'>";
		echo "<table><tr><th>",t('Kategorian nimi'),":</th><td>$nimi</td></tr>";
		echo "<tr><th>",t('Kategorian nimi'),":</th><td><input type='text' size='30' name='uusi_nimi' value='$nimi'/></td></tr>";
		echo "<tr><th>",t('Kategorian koodi'),":</th><td><input type='text' size='30' name='uusi_koodi' value='$koodi'/></td></tr>";
		echo "</table><br>";
		echo "<input type='hidden' name='tee' value='muokkaa' />";
		echo "<input type='hidden' name='laji' value='$toim' />";
		echo "<input type='hidden' name='kategoriaid' value='$kategoriaid' />";
		echo "<input type='submit' value='",t('Tallenna kategoria'),"' />";
		echo "</form><br><br>";
	}

	if (isset($toim)) {

		$query = "	SELECT
					node.lft AS lft,
					node.rgt AS rgt,
					node.nimi AS node_nimi,
					node.koodi AS node_koodi,
					node.tunnus AS node_tunnus,
					(COUNT(node.tunnus) - 1) AS syvyys
					FROM dynaaminen_puu AS node
					JOIN dynaaminen_puu AS parent ON node.yhtio=parent.yhtio and node.laji=parent.laji AND node.lft BETWEEN parent.lft AND parent.rgt
					WHERE node.yhtio = '{$kukarow["yhtio"]}'
					AND node.laji = '{$toim}'
					GROUP BY node.lft
					ORDER BY node.lft";
		$result = mysql_query($query) or pupe_error($query);

		// Mik‰li sivulle tullaan ensimm‰isen kerran ja p‰‰kategoriaa ei ole niin t‰m‰ luo kyseisen kategorian.
		if (mysql_num_rows($result) == 0) {
			echo "<form method='POST'>";
			echo "<table><tr><th>",t('Perusta'),"</th><th>$toim</th>";
			echo "<input type='hidden' size='30' name='KatNimi' value='$toim'/></tr>";
			echo "<tr><td></td><td><input type='submit' value='",t('Perusta kategoria'),"' /></td></tr>";
			echo "<input type='hidden' name='tee' value='paakat' />";
			echo "<input type='hidden' name='toim' value='$toim' />";
			echo "</table></form>";

		}
		else {

			if ($tee == 'valitsesegmentti') {
				echo "<form method='POST'>";
			}

			echo "<table>";

			while ($row = mysql_fetch_assoc($result)) {
				echo "\n<tr>";

				for ($i = 0; $i < $row['syvyys']; $i++) {
					echo "\n<td width='0' class='back'>&nbsp;</td>"; // tulostaa taulun syvyytt‰
				}

				if ($row['node_koodi'] == 0) $row['node_koodi'] = '';

				$lastenmaara = lapset($toim, $row['lft']);

				if ($row['lft'] == 1) {
					$rowspan = $lastenmaara+1;
				}
				else {
					$rowspan = $lastenmaara;
				}

				echo "\n<td rowspan='$rowspan'>",$row['node_koodi'] ,' ',ucwords(strtolower(str_replace('/', ', ', $row['node_nimi'])))," ($row[node_tunnus])<hr>";

				if ($tee == "valitsesegmentti") {
					$check = '';
					if (in_array($row['node_tunnus'], explode(",", $puun_tunnus))) {
						$check = 'checked';
					}

					echo "\n<input type='checkbox' name='id[]' value='{$row["node_tunnus"]}' $check />";
				}
				elseif ($oikeus != '') {
					echo "\n<a href='?toim=$toim&laji=$toim&nimi={$row['node_nimi']}&lft={$row['lft']}&syvyys={$row['syvyys']}&tee=lisaa'><img src='{$palvelin2}pics/lullacons/add.png' alt='",t('Lis‰‰ lapsikategoria'),"'/></a>";
				 	if ($row['lft'] > 1) echo "\n&nbsp;<a href='?toim=$toim&laji=$toim&nimi={$row['node_nimi']}&lft={$row['lft']}&tee=poista'><img src='{$palvelin2}pics/lullacons/remove.png' alt='",t('Poista lapsikategoria'),"'/></a>";
				 	echo "\n&nbsp;<a href='?toim=$toim&laji=$toim&nimi={$row['node_nimi']}&koodi={$row['node_koodi']}&lft={$row['lft']}&tee=muokkaa&kategoriaid={$row['node_tunnus']}'><img src='{$palvelin2}pics/lullacons/document-properties.png' alt='",t('Muokkaa lapsikategoriaa'),"'/></a>";

					if ($lastenmaara > 1) {
						echo "\n&nbsp;<a href='?toim=$toim&laji=$toim&nimi={$row['node_nimi']}&lft={$row['lft']}&tee=taso'><img src='{$palvelin2}pics/lullacons/folder-new.png' alt='",t('Lis‰‰ taso'),"'/></a>";
					}
				}

				echo "</td></tr>";

				if ($row['lft'] == 1) echo "\n<tr><td class='back'>&nbsp;</td></tr>";
			}

			echo "</table><br /><br />";

			if ($tee == 'valitsesegmentti') {
				echo "<input type='hidden' name='toim' 		value='$toim' />";
				echo "<input type='hidden' name='tee' 		value='valitse' />";
				echo "<input type='hidden' name='kieli' 	value='$kieli' />";
				echo "<input type='hidden' name='liitos'	value='$liitos' />";
				echo "<input type='hidden' name='ttunnus'	value='$ttunnus' />";
				echo "<input type='hidden' name='mista' 	value='$mista' />";
				echo "<input type='submit' name='valitse'	value='",t('Tallenna valinnat'),"'>";
				echo "</form>";
			}
		}
	}

	echo "<br />";

	require('inc/footer.inc');