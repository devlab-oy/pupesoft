<?php

require 'inc/parametrit.inc';

// Ajax requestit
if ($ajax_request == 1) {
	require "valmistuslinjat_json.php";
	exit();
}

require 'valmistuslinjat.inc';
require 'valmistus.class.php';

echo "<link rel='stylesheet' type='text/css' href='fullcalendar.css' />
	<link rel='stylesheet' type='text/css' href='valmistuslinjat.css' />
	<script type='text/javascript' src='fullcalendar.js'></script>
	<script type='text/javascript' src='valmistuslinjat.js'></script>";


// Jos $teetä ei ole
if (!isset($tee)) $tee = '';

// Debug
if (isset($laske_kestot_uudelleen)) {
	rebuild_valmistuslinjat();
}

/** Valmistusten siirtäminen valmistuslinjalla */
if (isset($method) and $method == 'move') {

	// Haetaan valitun valmistuksen tiedot
	$query = "SELECT *, lasku.tunnus
				FROM kalenteri
				JOIN lasku on (kalenteri.yhtio=lasku.yhtio AND kalenteri.otunnus=lasku.tunnus)
				WHERE kalenteri.yhtio = '{$kukarow['yhtio']}'
				AND kalenteri.otunnus = '{$tunnus}'";
	$result = pupe_query($query);
	$valittu_valmistus = mysql_fetch_assoc($result);

	// Siiretään aiemmaksi
	if ($direction == 'left') {
		$edellinen = etsi_edellinen_valmistus($valittu_valmistus);
		if ($edellinen) {
			// HUOM, vasemmanpuoleinen aina ensin (eli aiempi valmistus)
			vaihda_valmistusten_paikkaa($edellinen, $valittu_valmistus, $valittu_valmistus['henkilo']);
		}
	}
	// Siirretään myöhäisemmäksi
	elseif ($direction == 'right') {
		$seuraava = etsi_seuraava_valmistus($valittu_valmistus);
		if ($seuraava) {
			vaihda_valmistusten_paikkaa($valittu_valmistus, $seuraava, $valittu_valmistus['henkilo']);
		}
	}
}

/** Poistetaan kalenterista kalenterimerkintä */
if (isset($tee) and $tee == 'poista' and is_numeric($tunnus)) {
	$poista_query = "DELETE FROM kalenteri WHERE yhtio='{$kukarow['yhtio']}' AND tunnus={$tunnus}";
	if (pupe_query($poista_query)) {
		echo "Poistettiin kalenterimerkintä!";
		$tee = '';
	}
}

/**
 * Valmistuksen tilan päivittäminen
 */
if ($tee == 'paivita' and isset($method) and $method == 'update') {
	$valmistus = Valmistus::find($tunnus);

	// Keskeytä työ (TK) ja Valmis tarkastukseen (VT) kysyy lisäformilla tiedot valmistuksesta
	if (!isset($varmistus) and ($tila == 'VT') and $valmistus->getTila() == 'VA') {

		// VALMISTA FORMI
		echo "<font class='head'>" . t("Valmista tarkastukseen") . "</font>";

		echo "<form method='POST'>";
		echo "<input type='hidden' name='tunnus' value='$tunnus'>";
		echo "<input type='hidden' name='tila' value='$tila'>";
		echo "<input type='hidden' name='tee' value='paivita'>";
		echo "<input type='hidden' name='varmistus' value='ok'>";

		// Valmistuksen valmisteet
		echo "<table>";
		echo "<tr><th>".t("Valmistus")."</th><td>{$valmistus->tunnus()}</td></tr>";

		// Mahdollisuus muuttaa valmistuksen päivämääriä
		echo "<tr><th>".t("Aloitusaika")."</th>";
		echo "<td><input type='text' name='pvmalku' value='{$valmistus->pvmalku}'></td></tr>";
		echo "<tr><th>".t("Lopetusaika")."</th>";
		echo "<td><input type='text' name='pvmloppu' value='".date('Y-m-d H:i:s', round_time(strtotime('now')))."'>";
		echo"</td></tr>";

		// Haetaan valmisteet
		foreach($valmistus->tuotteet() as $valmiste) {
			echo "<tr>
				<th>".t("Tuoteno")."</th>
				<td>{$valmiste['tuoteno']}
				</tr>";
			echo "<tr>
				<th>".t("Valmistettava määrä")."</th>
				<td><input type='text' name='valmisteet[{$valmiste['tunnus']}][maara]' value='{$valmiste['varattu']}'></td>
				</tr>";
		}

		echo "<tr>
			<th>".t("Ylityötunnit")."</th>
			<td><input type='text' name='ylityotunnit' value='{$valmistus->ylityotunnit}'></td>
			</tr>";
		echo "<tr>
			<th>".t("Kommentit")."</th>
			<td><input type='text' name='kommentti' value='{$valmistus->kommentti}'></td>
			</tr>";
		echo "</table>";

		echo "<br>";
		echo "<a href='tuotannonsuunnittelu.php'>Takaisin</a> ";
		echo "<input type='submit' value='Valmis'>";
		echo "</form>";
	}
	// KESKEYTÄ FORMI
	else if (!isset($varmistus) and ($tila == 'TK') and $valmistus->getTila() == 'VA') {
		echo "<font class='head'>" . t("Keskeytä työ") . "</font>";

		echo "<form method='POST'>";
		echo "<input type='hidden' name='tunnus' value='$tunnus'>";
		echo "<input type='hidden' name='tila' value='$tila'>";
		echo "<input type='hidden' name='tee' value='paivita'>";
		echo "<input type='hidden' name='varmistus' value='ok'>";

		// Valmistuksen valmisteet
		echo "<table>";
		echo "<tr><th>".t("Valmistus")."</th><td>{$valmistus->tunnus()}</td></tr>";

		// Haetaan valmisteet
		foreach($valmistus->tuotteet() as $valmiste) {
			echo "<tr>
				<th>".t("Tuoteno")."</th>
				<td>{$valmiste['tuoteno']}
				</tr>";
		}

		echo "<tr>
			<th>".t("Ylityötunnit")."</th>
			<td><input type='text' name='ylityotunnit' value='{$valmistus->ylityotunnit}'></td>
			</tr>";
		echo "<tr>
			<th>".t("Käytetyt tunnit")."</th>
			<td><input type='text' name='kaytetyttunnit' value='{$valmistus->kaytetyttunnit}'></td>
			</tr>";
		echo "<tr><th>".t("Kommentit")."</th><td><input type='text' name='kommentti' value='{$valmistus->kommentti}'></td></tr>";
		echo "</table>";

		echo "<br>";
		echo "<a href='tuotannonsuunnittelu.php'>".t("Takaisin")."</a> ";
		echo "<input type='submit' value='".t("Valmis")."'>";
		echo "</form>";
	}
	else {
		// Muut tilat päivitetään suoraan
		$varmistus = 'ok';
	}

	// Jos kaikki ok, päivitetään valmistuksen tiedot
	if ($varmistus == 'ok') {

		// Splitatanko valmistus flag
		$splitataan = false;

		// Tarkistetaan syötetyt määrät ja verrataan valmisteen tilauksen määriin
		foreach ($valmistus->tuotteet() as $valmiste) {

			// Tarkastetaan tarvitseeko valmistusta splitata
			if ($valmiste['varattu'] > $valmisteet[$valmiste['tunnus']]['maara'] and ($tila == 'VT')) {
				#echo $valmiste['varattu']. " > " . $valmisteet[$valmiste['tunnus']['maara']] . "<br>";
				$jaettavat_valmisteet[$valmiste['tunnus']] = $valmisteet[$valmiste['tunnus']]['maara'];
				$splitataan = true;
			}
		}

		// Valmistetta on valmistettu vähemmin kuin sitä on tilattu.
		if ($splitataan) {
			// Yritetään jakaa valmistus
			try {
				$kopion_id = jaa_valmistus($valmistus->tunnus(), $jaettavat_valmisteet);
			} catch (Exception $e) {
				$errors = "<font class='error'>".t("Virhe valmistuksen jakamisessa").", " . $e->getMessage() . "</font>";
			}
		}

		// Jos valmistuksessa oleva työ keskeytetään tai merkataan valmiiksi
		if ($tila=='TK' or $tila=='VT' and $valmistus->getTila() == 'VA') {

			if ($pvmalku == '') {
				$pvmalku = $valmistus->alkupvm();
			}

			if ($pvmloppu == '') {
				$pvmloppu = $valmistus->loppupvm();
			}

			if ($kaytetyttunnit > $valmistus->kesto()) {
				$errors .= "<font class='error'>" . t("Käytetty enemmän kuin valmistuksen kesto") . "</font>";
			}

			if (empty($errors)) {
				// Tarkistetaan ja päivitetään käytetyt tunnit, ylityötunnit ja kommentti
				$query = "UPDATE kalenteri SET
							pvmalku  = '$pvmalku',
							pvmloppu = '$pvmloppu',
							kentta01 = '{$ylityotunnit}',
							kentta02 = '{$kommentti}',
							kentta03 = '{$kaytetyttunnit}'
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND otunnus = '{$tunnus}'";
				pupe_query($query);
			}
		}

		// Jos ei oo virheitä yritetään vaihtaa valmistuksen tilaa
		if (empty($errors)) {
			try {
				$valmistus->setTila($tila);
			} catch (Exception $e) {
				$errors .= "<font class='error'>".t("Valmistuksen tilan muuttaminen epäonnistui").". <br>{$e->getMessage()}</font>";
			}
		}

		$tee = '';
	}

	// Rakennetaan valmistuslinja uudelleen
	rebuild_valmistuslinjat();
}

/**
 * Lisää valmistus työjonoon
 */
if ($tee == 'lisaa_tyojonoon') {

	if (!$valmistuslinja) {
		$errors .= "<font class='error'>".t("Valitse valmistuslinja")."</font>";
	}
	else {
		// Lisätään valmistus valmistuslinjalle
		lisaa_valmistus($valmistus, $valmistuslinja);
	}

	$tee = '';
}

/**
 * Lisää kalenteriin muun merkinnän
 */
if ($tee == 'lisaa_kalenteriin') {

	#echo "valmistuslinja: $valmistuslinja tyyppi: $tyyppi";

	// Alkuaika on pakko syöttää
	if (empty($pvmalku)) {
		$errors .= "<font class='error'>" . t("Alkuaika ei voi olla tyhjä") . "</font>";
	}
	// Tarkestataan kalenterimerkinnän tyyppi
	// Yhtiökohtaisia voi olla PYhä tai Muu Työ
	elseif ($valmistuslinja == '' and !in_array($tyyppi, array('PY', 'MT'))) {
		$errors .= "<font class='error'>" . t("Valitse joku valmistuslinja") . ".<br>" . t("Vain pyhä tai muu työ voi olla yhtiökohtainen.") . "</font>";
	}
	else {
		// Jos loppuaika on jätetty tyhjäksi, setatan se alkuajan päivän loppuun
		if (!empty($pvmloppu)) {
			$pvmloppu = strtotime($pvmloppu);
		}
		// Jos pvmloppu on tyhjä, setataan se alkupäivän loppuun
		else {
			$pvmloppu = mktime(23, 59, 59, date('m', $pvmalku), date('d', $pvmalku), date('Y', $pvmalku));
		}

		// Alkuaika timestampiksi
		$pvmalku = strtotime($pvmalku);

		// Jos alkuaika on pienempi kuin loppuaika, lisätään tapahtuma kalenteriin
		if ($pvmalku < $pvmloppu) {
			// päivämäärät ok
			$pvmalku = date('Y-m-d H:i:s', $pvmalku);
			$pvmloppu = date('Y-m-d H:i:s', $pvmloppu);

			// Lisätään tietokantaan
			$query = "INSERT INTO kalenteri SET yhtio='{$kukarow['yhtio']}', tyyppi='$tyyppi', pvmalku='$pvmalku', pvmloppu='$pvmloppu', henkilo='$valmistuslinja'";
			pupe_query($query);
		}
		else {
			$errors .= "<font class='error'>" . t("Loppuajan on oltava suurempi kuin alkuajan") . "</font>";
		}
	}

	// jatketaan
	$tee = '';
}

rebuild_valmistuslinjat();

if ($tee == '') {

	// Valmistuslinjojen info popup
	echo "<div id='bubble'>";
	echo "<div id='header'></div>";
	echo "<div id='content' style='height:300px; overflow:auto;'></div>";
	echo "<form action='tuotannonsuunnittelu.php?method=update' method='post' id='toiminto' name='bubble'>
			<input type='hidden' name='tunnus' id='valmistuksen_tunnus'>
			<input type='hidden' name='tee' value='paivita'>
			<select name='tila' onchange='submit()'>
			<option value=''>Valitse</option>
			<option value='OV'>Siirä parkkiin</option>
			<option value='VA'>Aloita valmistus</option>
			<option value='TK'>Keskeytä valmistus</option>
			<option value='VT'>Valmis tarkistukseen</option>
			</select>
		</form>";
	echo "<br><a href='#' id='close_bubble'>".t("sulje")."</a>";
	echo "</div>";

	// html
	echo "<font class='head'>".t("Työjono työsuunnittelu")."</font><hr>";

	echo "<input type='hidden' id='yhtiorow' value='{$kukarow['yhtio']}'>";
	echo "<div id='calendar'></div>";

	echo "<br>";
	echo "<a href='tuotannonsuunnittelu.php?laske_kestot_uudelleen=true'>".t("Laske valmistuslinjojen kestot uudelleen")."</a>";
	echo "<br>";

	// Virheilmoitukset
	if (!empty($errors)) {
		echo $errors;
	}

	echo "<br>";

	/* PARKKI */
	echo "<br>";
	echo "<font class='head'>".t("Parkki")."</font>";
	echo "<hr>";

	echo "<table border=1>";
	echo "<tr>";
	echo "<th>".t("Valmistus")."</th>";
	echo "<th>".t("Tila")."</th>";
	echo "<th>".t("Nimitys")."</th>";
	echo "<th>".t("Määrä")."</th>";
	echo "<th>".t("Kesto")."</th>";
	echo "<th></th>";
	echo "</tr>";

	// Hetaan valmistuslinjat avainsanoista
	$linjat = hae_valmistuslinjat();

	// Haetaan valmistukset
	$valmistukset = Valmistus::all();

	//Listataan parkissa olevat valmistukset
	foreach($valmistukset as $valmistus) {

		echo "<tr>";
		echo "<td>" . $valmistus->tunnus() . "</td>";
		echo "<td>" . $valmistus->getTila() . "</td>";
		echo "<td>";

		// Tarkistetaan valmistuksella olevat tuotteet
		$kpl = '';
		foreach($valmistus->tuotteet() as $tuote) {
			echo $tuote['tuoteno'] . " "
				. $tuote['nimitys'] . "<br>";
			$kpl .= $tuote['varattu'] . " " . $tuote['yksikko'] . "<br>";
		}

		echo "</td>";

		echo "<td>{$kpl}</td>";
		echo "<td>" . ($valmistus->kesto() - $valmistus->kaytetty()) . "</td>";

		echo "<td>";

		// Valmistuslinjan valintalaatikko
		if ($valmistus->valmistuslinja() == 0 or $valmistus->valmistuslinja() == NULL) {
			echo "<form method='post' name='lisaa_tyojonoon'>";
			echo "<input type='hidden' name='tee' value='lisaa_tyojonoon'>";
			echo "<input type='hidden' name='valmistus' value='{$valmistus->tunnus()}'>";
			echo "<select name='valmistuslinja'>";
			echo "<option value=''>".t("Valitse linja")."</option>";

			foreach($linjat as $linja) {
				echo "<option value='$linja[selite]'>$linja[selitetark]</option>";
			}

			echo "</select>";
			echo "<input type='submit' value='".t("Aloita valmistus")."'>";
			echo "</form>";
		}
		else {
			echo t("Valmistuslinjalla") . "<br>" . $valmistus->alkupvm() . " - " . $valmistus->loppupvm();
		}
		echo "</td>";
		echo "</tr>";
	}

	echo "</table>";

	/* Muut kalenterimerkinnät*/
	echo "<br>";
	echo "<font class='head'>" . t("Muut") . "</font>";
	echo "<hr>";

	echo "<form method='POST'>";
	echo "<input type='hidden' name='tee' value='lisaa_kalenteriin'>";
	echo "<table>";
	echo "<tr>";
	echo "<th>".t("Valmistuslinja").":</th>";
	echo "<td>";
	echo "<select name='valmistuslinja'>";
	echo "<option value=''>".t("Yhtiökohtainen")."</option>";

	foreach($linjat as $linja) {
		echo "<option value='$linja[selite]'>$linja[selitetark]</option>";
	}

	echo "</select>";
	echo "</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<th>Tyyppi:</th>";
	echo "<td>";
	echo "<select name='tyyppi'>";
	echo "<option value='PY'>Pyhä (yhtiökohtainen)</option>";
	echo "<option value='PE'>Pekkaspäivä</option>";
	echo "<option value='SA'>Sairasloma</option>";
	echo "<option value='MT'>Muu työ</option>";
	echo "<option value='LO'>Loma</option>";
	echo "<option value='PO'>Vapaa/Poissa</option>";
	echo "</select>";
	echo "</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<th>".t("Alkuaika").":</th>";
	echo "<td><input type='text' name='pvmalku' value='" . date('d.m.Y'). " 07:00'></td><td class='back'>pp.kk.vvvv hh:mm</td>";
	echo "</tr></tr>";
	echo "<th>".t("Loppuaika").":</th>";
	echo "<td><input type='text' name='pvmloppu' value='" . date('d.m.Y'). " 15:00'></td><td class='back'>pp.kk.vvvv hh:mm</td>";
	echo "</tr>";
	echo "<table>";
	echo "<br>";
	echo "<input type='submit' value='".t("Lisää kalenteriin")."'>";
	echo "</form>";
}

// FOOTER
require ("inc/footer.inc");
