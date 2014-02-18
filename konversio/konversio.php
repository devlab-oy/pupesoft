<?php

ini_set("memory_limit", "5G");

require("../inc/parametrit.inc");
require_once('TuoteCSVDumper.php');
require_once('AsiakasCSVDumper.php');
require_once('AsiakasalennusCSVDumper.php');
require_once('YhteyshenkiloCSVDumper.php');
require_once('KohdeCSVDumper.php');
require_once('PaikkaCSVDumper.php');
require_once('LaiteCSVDumper.php');
require_once('TuotteenavainsanaLaiteCSVDumper.php');
require_once('TuotteenavainsanaLaite2CSVDumper.php');
require_once('TuotteenavainsanaToimenpideCSVDumper.php');
require_once('TuoteryhmaCSVDumper.php');
require_once('HuoltosykliCSVDumper.php');
require_once('TarkastuksetKantaCSVDumper.php');
require_once('TarkastuksetCSVDumper.php');

$request = array(
	'action'			 => $action,
	'konversio_tyyppi'	 => $konversio_tyyppi,
	'kukarow'			 => $kukarow
);

$request['konversio_tyypit'] = array(
	'tuote'					 => t('Tuote'),
	'tuotteen_avainsanat'	 => t('Tuotteen avainsanat'),
	'tuotteen_avainsanat2'	 => t('Tuotteen avainsanat laite tarkistus'),
	'asiakas'				 => t('Asiakas'),
	'kohde'					 => t('Kohde'),
	'paikka'				 => t('Paikka'),
	'laite'					 => t('Laite'),
	'yhteyshenkilo'			 => t('Yhteyshenkilö'),
	'asiakasalennus'		 => t('Asiakasalennus'),
	'huoltosykli'			 => t('Huoltosykli'),
	'tarkastukset_kanta'	 => t('Tarkastukset kanta'),
	'tarkastukset'			 => t('Tarkastukset'),
	'kaikki'				 => t('Kaikki'),
);

if ($request['action'] == 'aja_konversio') {
	echo_kayttoliittyma($request);
	echo "<br/>";

	switch ($request['konversio_tyyppi']) {
		case 'tuote':
			$dumper = new TuoteCSVDumper($request['kukarow']);
			break;

		case 'tuotteen_avainsanat':
			$dumper = new TuotteenavainsanaLaiteCSVDumper($request['kukarow']);
			break;
		
		case 'tuotteen_avainsanat2':
			$dumper = new TuotteenavainsanaLaite2CSVDumper($request['kukarow']);
			break;

		case 'asiakas':
			$dumper = new AsiakasCSVDumper($request['kukarow']);
			break;

		case 'yhteyshenkilo':
			$dumper = new YhteyshenkiloCSVDumper($request['kukarow']);
			break;

		case 'kohde':
			$dumper = new KohdeCSVDumper($request['kukarow']);
			break;

		case 'asiakasalennus':
			$dumper = new AsiakasalennusCSVDumper($request['kukarow']);
			break;

		case 'paikka':
			$dumper = new PaikkaCSVDumper($request['kukarow']);
			break;

		case 'laite':
			$dumper = new LaiteCSVDumper($request['kukarow']);
			break;

		case 'huoltosykli':
			$dumper = new HuoltosykliCSVDumper($request['kukarow']);
			break;

		case 'tarkastukset_kanta':
			$dumper = new TarkastuksetKantaCSVDumper($request['kukarow']);
			break;

		case 'tarkastukset':
			$tiedostot = lue_tiedostot('/tmp/konversio/tarkastukset/');
			foreach ($tiedostot as $tiedosto) {
				echo $tiedosto.'<br/>';
//                exec("php tarkastukset.php {$tiedosto}");

				$dumper = new TarkastuksetCSVDumper($kukarow, $tiedosto);
				$dumper->aja();
			}
			break;

		case 'kaikki':
			luo_kaato_tiedot();
			echo t('Tuote').':';
			$dumper = new TuoteCSVDumper($request['kukarow']);
			$dumper->aja();
			echo "<br/>";
			echo "<br/>";
			echo t('Toimenpidetuotteiden avainsanat').':';
			$dumper = new TuotteenavainsanaToimenpideCSVDumper($request['kukarow']);
			$dumper->aja();
			echo "<br/>";
			echo "<br/>";
			echo t('Tuoteryhmät').':';
			$dumper = new TuoteryhmaCSVDumper($request['kukarow']);
			$dumper->aja();
			echo "<br/>";
			echo "<br/>";
			echo t('Laite tuotteiden avainsanat').':';
			$dumper = new TuotteenavainsanaLaiteCSVDumper($request['kukarow']);
			$dumper->aja();
			echo "<br/>";
			echo "<br/>";
			echo t('Laite tuotteiden avainsanat 2 (varmistus)').':';
			$dumper = new TuotteenavainsanaLaite2CSVDumper($request['kukarow']);
			$dumper->aja();
			echo "<br/>";
			echo "<br/>";
			echo t('Asiakkaat').':';
			$dumper = new AsiakasCSVDumper($request['kukarow']);
			$dumper->aja();
			echo "<br/>";
			echo "<br/>";
			echo t('Yhteyshenkilöt').':';
			$dumper = new YhteyshenkiloCSVDumper($request['kukarow']);
			$dumper->aja();
			echo "<br/>";
			echo "<br/>";
			echo t('Asiakasalennukset').':';
			$dumper = new AsiakasalennusCSVDumper($request['kukarow']);
			$dumper->aja();
			echo "<br/>";
			echo "<br/>";
			echo t('Kohteet').':';
			$dumper = new KohdeCSVDumper($request['kukarow']);
			$dumper->aja();
			echo "<br/>";
			echo "<br/>";
			echo t('Paikat').':';
			$dumper = new PaikkaCSVDumper($request['kukarow']);
			$dumper->aja();
			echo "<br/>";
			echo "<br/>";
			echo t('Laitteet').':';
			$dumper = new LaiteCSVDumper($request['kukarow']);
			$dumper->aja();
			echo "<br/>";
			echo "<br/>";
			echo t('Huoltosyklit').':';
//			$dumper = new HuoltosykliCSVDumper($request['kukarow']);
//			$dumper->aja();
			echo "<br/>";
			echo "<br/>";
			echo t('Tarkastukset').':';
//			$dumper = new TarkastuksetCSVDumper($request['kukarow']);
//			$dumper->aja();
			break;

		default:
			die('Ei onnistu tämä');
			break;
	}

	if (!in_array($request['konversio_tyyppi'], array('kaikki', 'tarkastukset')) and isset($dumper)) {
		$dumper->aja();
	}

	if ($request['konversio_tyyppi'] == 'tuote') {
		$dumper = new TuotteenavainsanaToimenpideCSVDumper($request['kukarow']);
		$dumper->aja();

		$dumper = new TuoteryhmaCSVDumper($request['kukarow']);
		$dumper->aja();

		$dumper = new TuotteenavainsanaLaiteCSVDumper($request['kukarow']);
		$dumper->aja();
	}
}
else if ($request['action'] == 'poista_konversio_aineisto_kannasta') {
	$query_array = array(
		'DELETE FROM asiakas WHERE yhtio = "'.$kukarow['yhtio'].'"',
		'DELETE FROM yhteyshenkilo WHERE yhtio = "'.$kukarow['yhtio'].'"',
		'DELETE FROM tuote WHERE yhtio = "'.$kukarow['yhtio'].'"',
		'DELETE FROM kohde WHERE yhtio = "'.$kukarow['yhtio'].'"',
		'DELETE FROM paikka WHERE yhtio = "'.$kukarow['yhtio'].'"',
		'DELETE FROM laite WHERE yhtio = "'.$kukarow['yhtio'].'"',
		'DELETE FROM asiakasalennus WHERE yhtio = "'.$kukarow['yhtio'].'"',
		'DELETE FROM tuotteen_avainsanat WHERE yhtio = "'.$kukarow['yhtio'].'"',
		'DELETE FROM avainsana WHERE yhtio = "'.$kukarow['yhtio'].'" AND laji = "TRY"',
		'DELETE FROM huoltosykli WHERE yhtio = "'.$kukarow['yhtio'].'"',
		'DELETE FROM huoltosyklit_laitteet WHERE yhtio = "'.$kukarow['yhtio'].'"',
		'DELETE FROM tyomaarays WHERE yhtio = "'.$kukarow['yhtio'].'"',
		'DELETE FROM lasku WHERE yhtio = "'.$kukarow['yhtio'].'"',
		'DELETE FROM laskun_lisatiedot WHERE yhtio = "'.$kukarow['yhtio'].'"',
		'DELETE FROM tilausrivi WHERE yhtio = "'.$kukarow['yhtio'].'"',
		'DELETE FROM tilausrivin_lisatiedot WHERE yhtio = "'.$kukarow['yhtio'].'"',
		'DELETE FROM tiliointi WHERE yhtio = "'.$kukarow['yhtio'].'"',
	);
	foreach ($query_array as $query) {
		pupe_query($query);
	}

	echo t('Poistettu');
	echo "<br/>";

	echo_kayttoliittyma($request);
}
else {
	echo_kayttoliittyma($request);
}

require('inc/footer.inc');

function echo_kayttoliittyma($request) {
	global $kukarow, $yhtiorow;

	echo "<form action='' method='POST'>";
	echo "<input type='hidden' name='action' value='aja_konversio' />";
	echo "<table>";

	echo "<tr>";
	echo "<th>".t('Tiedosto')."</th>";
	echo "<td>";
	echo "</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<th>".t('Konversio tyyppi')."</th>";
	echo "<td>";
	echo "<select name='konversio_tyyppi'>";
	foreach ($request['konversio_tyypit'] as $konversio_tyyppi => $selitys) {
		$sel = "";
		if ($request['konversio_tyyppi'] == $konversio_tyyppi) {
			$sel = "SELECTED";
		}
		echo "<option value='{$konversio_tyyppi}' {$sel}>{$selitys}</option>";
	}
	echo "</select>";
	echo "</td>";
	echo "</tr>";

	echo "</table>";

	echo "<input type='submit' value='".t('Lähetä')."' />";
	echo "</form>";

	echo "<form action='' method='POST'>";
	echo "<input type='hidden' name='action' value='poista_konversio_aineisto_kannasta' />";
	echo "<input type='submit' value='".t('Poista koko konversio aineisto')."' />";
	echo "</form>";
}

function lue_tiedostot($polku) {
	$tiedostot = array();
	$handle = opendir($polku);
	if ($handle) {
		while (false !== ($tiedosto = readdir($handle))) {
			if ($tiedosto != "." && $tiedosto != ".." && $tiedosto != '.DS_Store') {
				if (is_file($polku.$tiedosto)) {
					$tiedostot[] = $polku.$tiedosto;
				}
			}
		}
		closedir($handle);
	}

	return $tiedostot;
}

function luo_kaato_tiedot() {
	global $kukarow, $yhtiorow;

	$query_array = array(
		"	INSERT INTO `tuote` (`yhtio`, `tuoteno`, `nimitys`, `osasto`, `try`, `tuotemerkki`, `malli`, `mallitarkenne`, `kuvaus`, `lyhytkuvaus`, `mainosteksti`, `aleryhma`, `muuta`, `tilausrivi_kommentti`, `kerayskommentti`, `purkukommentti`, `myyntihinta`, `myyntihinta_maara`, `kehahin`, `vihahin`, `vihapvm`, `yksikko`, `ei_saldoa`, `kommentoitava`, `tuotetyyppi`, `myynninseuranta`, `hinnastoon`, `sarjanumeroseuranta`, `suoratoimitus`, `status`, `yksin_kerailyalustalle`, `keraysvyohyke`, `panttitili`, `tilino`, `tilino_eu`, `tilino_ei_eu`, `tilino_kaanteinen`, `tilino_marginaali`, `tilino_osto_marginaali`, `tilino_triang`, `kustp`, `kohde`, `projekti`, `laatija`, `luontiaika`, `muutospvm`, `muuttaja`, `eankoodi`, `epakurantti25pvm`, `epakurantti50pvm`, `epakurantti75pvm`, `epakurantti100pvm`, `myymalahinta`, `nettohinta`, `halytysraja`, `varmuus_varasto`, `tilausmaara`, `ostoehdotus`, `tahtituote`, `tarrakerroin`, `tarrakpl`, `myynti_era`, `minimi_era`, `valmistuslinja`, `tullikohtelu`, `tullinimike1`, `tullinimike2`, `toinenpaljous_muunnoskerroin`, `vienti`, `tuotekorkeus`, `tuoteleveys`, `tuotesyvyys`, `tuotemassa`, `tuotekuva`, `nakyvyys`, `kuluprosentti`, `vakkoodi`, `vakmaara`, `leimahduspiste`, `meria_saastuttava`, `vak_imdg_koodi`, `kuljetusohje`, `pakkausmateriaali`, `alv`, `myyjanro`, `ostajanro`, `tuotepaallikko`, `tunnus`)
			VALUES
			('{$kukarow['yhtio']}', 'kaato-tuote', 'Kaato-tuote', 0, 80, '', '', '', NULL, NULL, '', '', '', '', '', '', 0.000000, 0, 0.000000, 0.000000, '0000-00-00', '', '', '', '', '', '', '', '', '', '', 0, '', '', '', '', '', '', '', '', 0, 0, 0, 'import', NOW(), '0000-00-00 00:00:00', '', '', '0000-00-00', '0000-00-00', '0000-00-00', '0000-00-00', 0.000000, 0.000000, 0.00, 0.00, 0.00, '', '', 0.00, 0, 0.00, 0.00, '', '', '', '', 0.00, '', 0.0000, 0.0000, 0.0000, 0.0000, '', '', 0.000, '', '', '', '', 0, '', '', 0.00, 0, 0, 0, -1);",
		"	INSERT INTO `laite` (`yhtio`, `tuoteno`, `sarjanro`, `valm_pvm`, `oma_numero`, `omistaja`, `paikka`, `sijainti`, `tila`, `koodi`, `laatija`, `luontiaika`, `muutospvm`, `muuttaja`, `tunnus`)
			VALUES
			('{$kukarow['yhtio']}', 'kaato-tuoteno', '', '0000-00-00', '', 'kaato-omistaja', -1, '2 krs', 'N', 28130, 'import', NOW(), NULL, NULL, -1);",
		"	INSERT INTO `asiakas` (`yhtio`, `laji`, `tila`, `ytunnus`, `ovttunnus`, `nimi`, `nimitark`, `osoite`, `postino`, `postitp`, `kunta`, `laani`, `maa`, `kansalaisuus`, `tyonantaja`, `ammatti`, `email`, `lasku_email`, `puhelin`, `gsm`, `tyopuhelin`, `fax`, `toim_ovttunnus`, `toim_nimi`, `toim_nimitark`, `toim_osoite`, `toim_postino`, `toim_postitp`, `toim_maa`, `kolm_ovttunnus`, `kolm_nimi`, `kolm_nimitark`, `kolm_osoite`, `kolm_postino`, `kolm_postitp`, `kolm_maa`, `laskutus_nimi`, `laskutus_nimitark`, `laskutus_osoite`, `laskutus_postino`, `laskutus_postitp`, `laskutus_maa`, `maksukehotuksen_osoitetiedot`, `konserni`, `asiakasnro`, `piiri`, `ryhma`, `osasto`, `verkkotunnus`, `kieli`, `chn`, `konserniyhtio`, `fakta`, `sisviesti1`, `myynti_kommentti1`, `kuljetusohje`, `selaus`, `alv`, `valkoodi`, `maksuehto`, `toimitustapa`, `rahtivapaa`, `rahtivapaa_alarajasumma`, `kuljetusvakuutus_tyyppi`, `toimitusehto`, `tilausvahvistus`, `tilausvahvistus_jttoimituksista`, `jt_toimitusaika_email_vahvistus`, `toimitusvahvistus`, `kerayspoikkeama`, `keraysvahvistus_lahetys`, `keraysvahvistus_email`, `kerayserat`, `lahetetyyppi`, `lahetteen_jarjestys`, `lahetteen_jarjestys_suunta`, `laskutyyppi`, `laskutusvkopv`, `maksusopimus_toimitus`, `laskun_jarjestys`, `laskun_jarjestys_suunta`, `extranet_tilaus_varaa_saldoa`, `vienti`, `ketjutus`, `luokka`, `jtkielto`, `jtrivit`, `myyjanro`, `erikoisale`, `myyntikielto`, `myynninseuranta`, `luottoraja`, `luottovakuutettu`, `kuluprosentti`, `tuntihinta`, `tuntikerroin`, `hintakerroin`, `pientarvikelisa`, `laskunsummapyoristys`, `laskutuslisa`, `panttitili`, `tilino`, `tilino_eu`, `tilino_ei_eu`, `tilino_kaanteinen`, `tilino_marginaali`, `tilino_osto_marginaali`, `tilino_triang`, `kustannuspaikka`, `kohde`, `projekti`, `laatija`, `luontiaika`, `muutospvm`, `muuttaja`, `flag_1`, `flag_2`, `flag_3`, `flag_4`, `maa_maara`, `sisamaan_kuljetus`, `sisamaan_kuljetus_kansallisuus`, `sisamaan_kuljetusmuoto`, `kontti`, `aktiivinen_kuljetus`, `aktiivinen_kuljetus_kansallisuus`, `kauppatapahtuman_luonne`, `kuljetusmuoto`, `poistumistoimipaikka_koodi`, `tunnus`)
			VALUES
			('{$kukarow['yhtio']}', '', '', 'Kaato-asiakas', '', 'Kaato-asiakas', '', '', '', '', '', '', 'FI', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '2306', '', '', '', '', '', '', '', '', '', '', '', '', 0.00, '', 0, 'Nouto', '', 0.00, '', '', '', '', '', '', 0, '', '', '', '', '', '', 0, 0, '', '', '', '', '', '', '', '', 0, 0, 0.00, '', '', 0.00, '', 0.000, 0.00, 0.00, 0.00, 0.00, '', '', '', '', '', '', '', '', '', '', 0, 0, 0, 'import', NOW(), '0000-00-00 00:00:00', '', '', '', '', '', '', '', '', 0, 0, '', '', 0, 0, '', -1);",
		"	INSERT INTO `tyomaarays` (`yhtio`, `kotipuh`, `tyopuh`, `myyjaliike`, `ostopvm`, `rekno`, `mittarilukema`, `merkki`, `mallivari`, `valmnro`, `tuotu`, `luvattu`, `viite`, `komm1`, `komm2`, `viite2`, `tilno`, `suorittaja`, `laatija`, `luontiaika`, `vikakoodi`, `tyoaika`, `tyokoodi`, `tehdas`, `takuunumero`, `jalleenmyyja`, `tyojono`, `tyostatus`, `prioriteetti`, `hyvaksy`, `otunnus`)
			VALUES
			('{$kukarow['yhtio']}', '', '', '', '0000-00-00', '', '', '', '', '', '0000-00-00', '0000-00-00', '', '', '', '', '', '', 'import', NOW(), '', '', '', '', 0, '', 'import', 'X', '', '', -1);",
		"	INSERT INTO `lasku` (`yhtio`, `yhtio_nimi`, `yhtio_osoite`, `yhtio_postino`, `yhtio_postitp`, `yhtio_maa`, `yhtio_ovttunnus`, `yhtio_kotipaikka`, `yhtio_toimipaikka`, `nimi`, `nimitark`, `osoite`, `osoitetark`, `postino`, `postitp`, `maa`, `toim_nimi`, `toim_nimitark`, `toim_osoite`, `toim_postino`, `toim_postitp`, `toim_maa`, `pankki_haltija`, `tilinumero`, `swift`, `pankki1`, `pankki2`, `pankki3`, `pankki4`, `ultilno_maa`, `ultilno`, `clearing`, `maksutyyppi`, `valkoodi`, `alv`, `lapvm`, `tapvm`, `kapvm`, `erpcm`, `suoraveloitus`, `olmapvm`, `toimaika`, `toimvko`, `kerayspvm`, `keraysvko`, `summa`, `summa_valuutassa`, `kasumma`, `kasumma_valuutassa`, `hinta`, `kate`, `kate_korjattu`, `arvo`, `arvo_valuutassa`, `saldo_maksettu`, `saldo_maksettu_valuutassa`, `pyoristys`, `pyoristys_valuutassa`, `pyoristys_erot`, `pyoristys_erot_alv`, `laatija`, `luontiaika`, `maksaja`, `maksuaika`, `lahetepvm`, `lahetetyyppi`, `laskutyyppi`, `laskuttaja`, `laskutettu`, `hyvak1`, `h1time`, `hyvak2`, `h2time`, `hyvak3`, `h3time`, `hyvak4`, `h4time`, `hyvak5`, `hyvaksyja_nyt`, `h5time`, `hyvaksynnanmuutos`, `prioriteettinro`, `vakisin_kerays`, `viite`, `laskunro`, `viesti`, `sisviesti1`, `sisviesti2`, `sisviesti3`, `comments`, `ohjausmerkki`, `tilausyhteyshenkilo`, `asiakkaan_tilausnumero`, `kohde`, `myyja`, `allekirjoittaja`, `maksuehto`, `toimitustapa`, `toimitustavan_lahto`, `toimitustavan_lahto_siirto`, `rahtivapaa`, `rahtisopimus`, `ebid`, `ytunnus`, `verkkotunnus`, `ovttunnus`, `toim_ovttunnus`, `chn`, `mapvm`, `popvm`, `vienti_kurssi`, `maksu_kurssi`, `maksu_tili`, `alv_tili`, `tila`, `alatila`, `huolitsija`, `jakelu`, `kuljetus`, `maksuteksti`, `muutospvm`, `muuttaja`, `vakuutus`, `kassalipas`, `ketjutus`, `sisainen`, `osatoimitus`, `splittauskielto`, `jtkielto`, `tilaustyyppi`, `eilahetetta`, `tilausvahvistus`, `laskutusvkopv`, `toimitusehto`, `vienti`, `kolmikantakauppa`, `viitetxt`, `ostotilauksen_kasittely`, `erikoisale`, `erikoisale_saapuminen`, `kerayslista`, `liitostunnus`, `viikorkopros`, `viikorkoeur`, `varasto`, `tulostusalue`, `kirjoitin`, `noutaja`, `kohdistettu`, `rahti_huolinta`, `rahti`, `rahti_etu`, `rahti_etu_alv`, `osto_rahti_alv`, `osto_kulu_alv`, `osto_rivi_kulu_alv`, `osto_rahti`, `osto_kulu`, `osto_rivi_kulu`, `maa_lahetys`, `maa_maara`, `maa_alkupera`, `kuljetusmuoto`, `kauppatapahtuman_luonne`, `bruttopaino`, `sisamaan_kuljetus`, `sisamaan_kuljetus_kansallisuus`, `aktiivinen_kuljetus`, `kontti`, `valmistuksen_tila`, `aktiivinen_kuljetus_kansallisuus`, `sisamaan_kuljetusmuoto`, `poistumistoimipaikka`, `poistumistoimipaikka_koodi`, `lisattava_era`, `vahennettava_era`, `tullausnumero`, `vientipaperit_palautettu`, `piiri`, `pakkaamo`, `jaksotettu`, `factoringsiirtonumero`, `ohjelma_moduli`, `label`, `tunnusnippu`, `vanhatunnus`, `tunnus`)
			VALUES
			('{$kukarow['yhtio']}',
			'{$yhtiorow['nimi']}',
			'{$yhtiorow['osoite']}',
			'{$yhtiorow['postino']}',
			'{$yhtiorow['postitp']}',
			'FI',
			'{$yhtiorow['ytunnus']}',
			'{$yhtiorow['kotipaikka']}',
			0,
			'Kaato-asiakas',
			'',
			'',
			'', '', '',
			'FI',
			'Kaato-asiakas',
			'',
			'',
			'',
			'',
			'FI',
			'', '', '', '', '', '', '', '', '', '', '', 'EUR', 0.00, '0000-00-00', '0000-00-00', '0000-00-00', '0000-00-00', '', '0000-00-00', '2006-11-01', '', NOW(), '', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.000000, 0.00, 'import', NOW(), '', '0000-00-00 00:00:00', '0000-00-00 00:00:00', 'tulosta_lahete_eiale_eihinta.inc', 0, '', '0000-00-00 00:00:00', '', '0000-00-00 00:00:00', '', '0000-00-00 00:00:00', '', '0000-00-00 00:00:00', '', '0000-00-00 00:00:00', '', '', '0000-00-00 00:00:00', '', 9, '', '', 0, '', '', '', '', '', '', '', '', '', 104, 0, 103, 'Nouto', 0, 0, '', '', '', '', '', '', '', '100', '0000-00-00', '0000-00-00 00:00:00', 0.000000000, 0.000000000, '', '293', 'L', 'D', '', '', '', '', NOW(), '', '', '', '', '', '', '', '', 'A', '', '', 0, '', '', '', '', '', 0.00, 0.00, 0, 41694, 10.00, 0.00, 0, '', '', '', '', 0.000000, 0.00, 0.000000, 0.00, 0.00, 0.00, 0.00, 0.000000, 0.000000, 0.000000, '', '', '', 0, 0, 0.00, '', '', '', 0, '', '', 0, '', '', 0.00, 0.00, '', '', '', 0, 0, 0, 'PUPESOFT', 0, 0, 0, -1);",
			"	INSERT INTO `laskun_lisatiedot` (`yhtio`, `otunnus`, `rahlaskelma_rahoitettava_positio`, `rahlaskelma_jaannosvelka_vaihtokohteesta`, `rahlaskelma_perustamiskustannus`, `rahlaskelma_muutluottokustannukset`, `rahlaskelma_sopajankorko`, `rahlaskelma_maksuerienlkm`, `rahlaskelma_luottoaikakk`, `rahlaskelma_ekaerpcm`, `rahlaskelma_erankasittelymaksu`, `rahlaskelma_tilinavausmaksu`, `rahlaskelma_viitekorko`, `rahlaskelma_marginaalikorko`, `rahlaskelma_lyhennystapa`, `rahlaskelma_poikkeava_era`, `rahlaskelma_nfref`, `vakuutushak_vakuutusyhtio`, `vakuutushak_alkamispaiva`, `vakuutushak_kaskolaji`, `vakuutushak_maksuerat`, `vakuutushak_perusomavastuu`, `vakuutushak_runko_takila_purjeet`, `vakuutushak_moottori`, `vakuutushak_varusteet`, `vakuutushak_yhteensa`, `rekisteilmo_rekisterinumero`, `rekisteilmo_paakayttokunta`, `rekisteilmo_kieli`, `rekisteilmo_tyyppi`, `rekisteilmo_omistajienlkm`, `rekisteilmo_omistajankotikunta`, `rekisteilmo_lisatietoja`, `rekisteilmo_laminointi`, `rekisteilmo_suoramarkkinointi`, `rekisteilmo_veneen_nimi`, `rekisteilmo_omistaja`, `kolm_ovttunnus`, `kolm_nimi`, `kolm_nimitark`, `kolm_osoite`, `kolm_postino`, `kolm_postitp`, `kolm_maa`, `laskutus_nimi`, `laskutus_nimitark`, `laskutus_osoite`, `laskutus_postino`, `laskutus_postitp`, `laskutus_maa`, `toimitusehto2`, `kasinsyotetty_viite`, `asiakkaan_kohde`, `kantaasiakastunnus`, `ulkoinen_tarkenne`, `saate`, `yhteyshenkilo_kaupallinen`, `yhteyshenkilo_tekninen`, `rahlaskelma_hetu_tarkistus`, `rahlaskelma_hetu_tarkastaja`, `rahlaskelma_hetu_asiakirjamyontaja`, `rahlaskelma_hetu_asiakirjanro`, `rahlaskelma_hetu_kolm_tarkistus`, `rahlaskelma_hetu_kolm_tarkastaja`, `rahlaskelma_hetu_kolm_asiakirjanro`, `rahlaskelma_hetu_kolm_asiakirjamyontaja`, `rahlaskelma_takuukirja`, `rahlaskelma_huoltokirja`, `rahlaskelma_kayttoohjeet`, `rahlaskelma_opastus`, `rahlaskelma_kuntotestitodistus`, `rahlaskelma_kayttotarkoitus`, `sopimus_kk`, `sopimus_pp`, `sopimus_alkupvm`, `sopimus_loppupvm`, `sopimus_lisatietoja`, `projektipaallikko`, `seuranta`, `tunnusnippu_tarjous`, `projekti`, `rivihintoja_ei_nayteta`, `laatija`, `luontiaika`, `muutospvm`, `muuttaja`, `tunnus`)
				VALUES
				('{$kukarow['yhtio']}', -1, 0, 0.00, 0.00, 0.00, 0.00, 0, 0, '0000-00-00', 0.00, 0.00, '', 0.00, '', 0.00, 0, '', '0000-00-00', '', 0, 0.00, 0.00, 0.00, 0.00, 0.00, '', '', '', '', 0, '', '', '', '', '', '', '', '', '', '', '', '', '', 'Kaato-asiakas', '', '', '', '', '', '', '', 0, '', '', '', 0, 0, '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '0000-00-00', '0000-00-00', '', '', '', 0, 0, '', 'import', '2013-12-16 17:32:12', '0000-00-00 00:00:00', '', -1);",
			"	INSERT INTO `tilausrivi` (`yhtio`, `tyyppi`, `toimaika`, `kerayspvm`, `otunnus`, `tuoteno`, `try`, `osasto`, `nimitys`, `kpl`, `kpl2`, `tilkpl`, `yksikko`, `varattu`, `jt`, `hinta`, `hinta_valuutassa`, `hinta_alkuperainen`, `alv`, `rivihinta`, `rivihinta_valuutassa`, `erikoisale`, `erikoisale_saapuminen`, `ale1`, `ale2`, `ale3`, `kate`, `kate_korjattu`, `kommentti`, `laatija`, `laadittu`, `keratty`, `kerattyaika`, `toimitettu`, `toimitettuaika`, `laskutettu`, `laskutettuaika`, `var`, `var2`, `netto`, `perheid`, `perheid2`, `hyllyalue`, `hyllynro`, `hyllytaso`, `hyllyvali`, `suuntalava`, `varastoon`, `vahvistettu_maara`, `vahvistettu_kommentti`, `tilaajanrivinro`, `jaksotettu`, `uusiotunnus`, `tunnus`)
				VALUES
				('{$kukarow['yhtio']}', 'L', '2013-12-16', '2013-12-16', -1, 'kaato-tuoteno', 10, 0, 'Poikkeamarivi', 0.00, 0.00, 1.00, '', 1.00, 0.00, 37.000000, 0.000000, 0.000000, 0.00, 0.000000, 0.000000, 0.00, 0.00, 20.00, 0.00, 0.00, 0.000000, NULL, '', 'import', NOW(), 'saldoton', NOW(), 'import', '2006-01-01 00:00:00', '', '0000-00-00', '', '', '', 0, 0, '', '', '', '', 0, 1, NULL, NULL, 0, 0, 0, -1);",
			"	INSERT INTO `tilausrivin_lisatiedot` (`yhtio`, `tilausrivitunnus`, `tiliointirivitunnus`, `tilausrivilinkki`, `toimittajan_tunnus`, `kulun_kohdemaan_alv`, `kulun_kohdemaa`, `hankintakulut`, `asiakkaan_positio`, `positio`, `pituus`, `leveys`, `pidin`, `viiste`, `porauskuvio`, `niitti`, `autoid`, `rekisterinumero`, `ei_nayteta`, `osto_vai_hyvitys`, `sistyomaarays_sarjatunnus`, `suoraan_laskutukseen`, `erikoistoimitus_myynti`, `vanha_otunnus`, `omalle_tilaukselle`, `ohita_kerays`, `jt_manual`, `poikkeava_tulliprosentti`, `jarjestys`, `sopimus_alkaa`, `sopimus_loppuu`, `sopimuksen_lisatieto1`, `sopimuksen_lisatieto2`, `suoratoimitettuaika`, `toimitusaika_paivitetty`, `kohde_hyllyalue`, `kohde_hyllynro`, `kohde_hyllyvali`, `kohde_hyllytaso`, `luontiaika`, `laatija`, `muutospvm`, `muuttaja`, `tunnus`)
				VALUES
				('{$kukarow['yhtio']}', -1, 0, 0, 0, 0.00, '', '', 0, '', '', '', '', '', '', '', 0, '', '', '', '', '', 0, -1, '', '', '', NULL, 0, '0000-00-00', '0000-00-00', '', '', '0000-00-00', '0000-00-00 00:00:00', '', '', '', '', NOW(), 'import', '0000-00-00 00:00:00', '', -1);",
			"	INSERT INTO `tuote` (`yhtio`, `tuoteno`, `nimitys`, `osasto`, `try`, `tuotemerkki`, `malli`, `mallitarkenne`, `kuvaus`, `lyhytkuvaus`, `mainosteksti`, `aleryhma`, `muuta`, `tilausrivi_kommentti`, `kerayskommentti`, `purkukommentti`, `myyntihinta`, `myyntihinta_maara`, `kehahin`, `vihahin`, `vihapvm`, `yksikko`, `ei_saldoa`, `kommentoitava`, `tuotetyyppi`, `myynninseuranta`, `hinnastoon`, `sarjanumeroseuranta`, `suoratoimitus`, `status`, `yksin_kerailyalustalle`, `keraysvyohyke`, `panttitili`, `tilino`, `tilino_eu`, `tilino_ei_eu`, `tilino_kaanteinen`, `tilino_marginaali`, `tilino_osto_marginaali`, `tilino_triang`, `kustp`, `kohde`, `projekti`, `laatija`, `luontiaika`, `muutospvm`, `muuttaja`, `eankoodi`, `epakurantti25pvm`, `epakurantti50pvm`, `epakurantti75pvm`, `epakurantti100pvm`, `myymalahinta`, `nettohinta`, `halytysraja`, `varmuus_varasto`, `tilausmaara`, `ostoehdotus`, `tahtituote`, `tarrakerroin`, `tarrakpl`, `myynti_era`, `minimi_era`, `valmistuslinja`, `valmistusaika_sekunneissa`, `tullikohtelu`, `tullinimike1`, `tullinimike2`, `toinenpaljous_muunnoskerroin`, `vienti`, `tuotekorkeus`, `tuoteleveys`, `tuotesyvyys`, `tuotemassa`, `tuotekuva`, `nakyvyys`, `kuluprosentti`, `vakkoodi`, `vakmaara`, `leimahduspiste`, `meria_saastuttava`, `vak_imdg_koodi`, `kuljetusohje`, `pakkausmateriaali`, `alv`, `myyjanro`, `ostajanro`, `tuotepaallikko`)
				VALUES
				('{$kukarow['yhtio']}', 'MUISTUTUS', 'Muistutus', 0, 0, '', '', '', '', '', '', '', '', '', '', '', 0.000000, 0, 0.000000, 0.000000, '0000-00-00', 'V', 'o', '', '', '', '', '', '', 'N', '', 0, '', '', '', '', '', '', '', '', 0, 0, 0, 'import', NOW(), NOW(), 'import', '', '0000-00-00', '0000-00-00', '0000-00-00', '0000-00-00', 0.000000, 0.000000, 0.00, 0.00, 0.00, '', '', 0.00, 0, 0.00, 0.00, '', 0, '', '', '', 0.00, '', 0.0000, 0.0000, 0.0000, 0.0000, '', '', 0.000, '', '', '', '', 0, '', '', 23.00, 0, 0, 0);",
			"	INSERT INTO `tuote` (`yhtio`, `tuoteno`, `nimitys`, `osasto`, `try`, `tuotemerkki`, `malli`, `mallitarkenne`, `kuvaus`, `lyhytkuvaus`, `mainosteksti`, `aleryhma`, `muuta`, `tilausrivi_kommentti`, `kerayskommentti`, `purkukommentti`, `myyntihinta`, `myyntihinta_maara`, `kehahin`, `vihahin`, `vihapvm`, `yksikko`, `ei_saldoa`, `kommentoitava`, `tuotetyyppi`, `myynninseuranta`, `hinnastoon`, `sarjanumeroseuranta`, `suoratoimitus`, `status`, `yksin_kerailyalustalle`, `keraysvyohyke`, `panttitili`, `tilino`, `tilino_eu`, `tilino_ei_eu`, `tilino_kaanteinen`, `tilino_marginaali`, `tilino_osto_marginaali`, `tilino_triang`, `kustp`, `kohde`, `projekti`, `laatija`, `luontiaika`, `muutospvm`, `muuttaja`, `eankoodi`, `epakurantti25pvm`, `epakurantti50pvm`, `epakurantti75pvm`, `epakurantti100pvm`, `myymalahinta`, `nettohinta`, `halytysraja`, `varmuus_varasto`, `tilausmaara`, `ostoehdotus`, `tahtituote`, `tarrakerroin`, `tarrakpl`, `myynti_era`, `minimi_era`, `valmistuslinja`, `valmistusaika_sekunneissa`, `tullikohtelu`, `tullinimike1`, `tullinimike2`, `toinenpaljous_muunnoskerroin`, `vienti`, `tuotekorkeus`, `tuoteleveys`, `tuotesyvyys`, `tuotemassa`, `tuotekuva`, `nakyvyys`, `kuluprosentti`, `vakkoodi`, `vakmaara`, `leimahduspiste`, `meria_saastuttava`, `vak_imdg_koodi`, `kuljetusohje`, `pakkausmateriaali`, `alv`, `myyjanro`, `ostajanro`, `tuotepaallikko`)
				VALUES
				('{$kukarow['yhtio']}', 'KAYNTI', 'Käynti', 0, 0, '', '', '', '', '', '', '', '', '', '', '', 0.000000, 0, 0.000000, 0.000000, '0000-00-00', 'V', 'o', '', 'K', '', '', '', '', 'N', '', 0, '', '', '', '', '', '', '', '', 0, 0, 0, 'import', NOW(), NOW(), 'import', '', '0000-00-00', '0000-00-00', '0000-00-00', '0000-00-00', 0.000000, 0.000000, 0.00, 0.00, 0.00, '', '', 0.00, 0, 0.00, 0.00, '', 0, '', '', '', 0.00, '', 0.0000, 0.0000, 0.0000, 0.0000, '', '', 0.000, '', '', '', '', 0, '', '', 23.00, 0, 0, 0);",
			"	INSERT INTO `tuotteen_avainsanat` (`yhtio`, `tuoteno`, `kieli`, `laji`, `selite`, `selitetark`, `status`, `nakyvyys`, `jarjestys`, `laatija`, `luontiaika`, `muutospvm`, `muuttaja`)
				VALUES
				('{$kukarow['yhtio']}', 'MUISTUTUS', 'fi', 'sammutin_tyyppi', 'jauhesammutin', '', '', '', 0, 'import', NOW(), NOW(), 'import');",
			"	INSERT INTO `tuotteen_avainsanat` (`yhtio`, `tuoteno`, `kieli`, `laji`, `selite`, `selitetark`, `status`, `nakyvyys`, `jarjestys`, `laatija`, `luontiaika`, `muutospvm`, `muuttaja`)
				VALUES
				('{$kukarow['yhtio']}', 'MUISTUTUS', 'fi', 'sammutin_koko', '1', '', '', '', 0, 'import', NOW(), NOW(), 'import');",
			"	INSERT INTO `tuotteen_avainsanat` (`yhtio`, `tuoteno`, `kieli`, `laji`, `selite`, `selitetark`, `status`, `nakyvyys`, `jarjestys`, `laatija`, `luontiaika`, `muutospvm`, `muuttaja`)
				VALUES
				('{$kukarow['yhtio']}', 'KAYNTI', 'fi', 'tyomaarayksen_ryhmittely', 'tarkastus', '3', '', '', 3, 'import', NOW(), NOW(), 'import');",
	);
	foreach ($query_array as $query) {
		pupe_query($query);
	}
}