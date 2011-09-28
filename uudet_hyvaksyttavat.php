<?php

	// Kutsutaanko CLI:st�
	if (php_sapi_name() != 'cli') {
		die ("T�t� scripti� voi ajaa vain komentorivilt�!");
	}

	if (!isset($argv[1]) or $argv[1] == '') {
		echo "Anna kuka!!!\n";
		die;
	}

	// otetaan tietokanta connect
	require ("inc/connect.inc");
	require ("inc/functions.inc");

	if (isset($argv[2]) and $argv[2] != "") {
		$ajalta = $argv[2];
	}

	//	Oletus
	if ((int) $ajalta == 0) $ajalta = 1;

	$query    = "SELECT * from kuka where kuka='$argv[1]' limit 1";
	$kukares = mysql_query($query) or pupe_error($query);
	if (mysql_num_rows($kukares) == 0) die("Karhuajaa ei l�yry!\n$query\n");
	$kukarow = mysql_fetch_array($kukares);

	$query    = "SELECT * from yhtio where yhtio='$kukarow[yhtio]'";
	$yhtiores = mysql_query($query) or pupe_error($query);
	if (mysql_num_rows($yhtiores) == 0) die("Firmaa ei l�yry!\n");
	$yhtiorow = mysql_fetch_array($yhtiores);

	$query = "	SELECT *
				FROM yhtion_parametrit
				WHERE yhtio='$kukarow[yhtio]'";
	$result = mysql_query($query) or die ("Kysely ei onnistu yhtio $query");

	if (mysql_num_rows($result) == 1) {
		$yhtion_parametritrow = mysql_fetch_array($result);

		// lis�t��n kaikki yhtiorow arrayseen
		foreach ($yhtion_parametritrow as $parametrit_nimi => $parametrit_arvo) {
			$yhtiorow[$parametrit_nimi] = $parametrit_arvo;
		}
	}

	$query = "	SELECT concat_ws(' ',lasku.nimi, nimitark) nimi, tapvm, erpcm, round(summa * valuu.kurssi,2) summa, kuka.eposti, lasku.hyvaksyja_nyt,
					UNIX_TIMESTAMP(lasku.luontiaika) luontiaika,
					UNIX_TIMESTAMP(h1time) h1time,
					UNIX_TIMESTAMP(h2time) h2time,
					UNIX_TIMESTAMP(h3time) h3time,
					UNIX_TIMESTAMP(h4time) h4time,
					UNIX_TIMESTAMP(h5time) httime,
					lasku.hyvak1 hyvak1,
					lasku.hyvak2 hyvak2,
					lasku.hyvak3 hyvak3,
					lasku.hyvak4 hyvak4,
					lasku.hyvak5 hyvak5
				FROM lasku
				LEFT JOIN valuu ON valuu.yhtio=lasku.yhtio and lasku.valkoodi=valuu.nimi
				JOIN kuka ON kuka.yhtio=lasku.yhtio and lasku.hyvaksyja_nyt=kuka.kuka and kuka.eposti <> ''
				WHERE lasku.yhtio='$kukarow[yhtio]' and lasku.tila = 'H' and (tilaustyyppi != 'M' or h1time != '0000-00-00 00:00:00')
				ORDER BY kuka.eposti, tapvm";
	$result = mysql_query($query) or pupe_error($query);

	while ($trow=mysql_fetch_array($result)) {
		$muistuta = 0;
		//	Kandeeko t�st� muistuttaa?
		for($i=1;$i<=5;$i++) {
			if ($trow["hyvak$i"] == $trow["hyvaksyja_nyt"]) {

				//	Verrataan luontiaikaan..
				if ($i == 1) {
					if ($trow["luontiaika"]>=strtotime("00:00:00 -$ajalta days")) {
						$muistuta = 1;
					}
				}
				//	Verrataan edelliseen hyv�ksynt��n
				else {
					$e = $i-1;
					if ($trow["h{$e}time"]>=strtotime("00:00:00 -$ajalta days")) {
						$muistuta = 1;
					}
				}
			}
		}

		if ($muistuta == 1) {

			if ($trow['eposti'] != $veposti) {
				if ($veposti != '') {
					$meili = t("Sinulla on seuraavat uudet laskut hyv�ksytt�v�n�").":\n\n" . $meili;
					$tulos = mail($veposti, mb_encode_mimeheader(t("Uudet hyv�ksytt�v�t laskusi"), "ISO-8859-1", "Q"), $meili, "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n", "-f $yhtiorow[postittaja_email]");
				}
				$meili = '';
				$veposti = $trow['eposti'];
			}

			$meili .= "Laskuttaja: " . $trow['nimi'] . "\n";
			$meili .= "Laskutusp�iv�: " . $trow['tapvm'] . "\n";
			$meili .= "Er�p�iv�: " . $trow['erpcm'] . "\n";
			$meili .= "Summa: " .$yhtiorow["valkoodi"]." ".$trow['summa'] . "\n\n";
		}
	}

	if ($meili != '') {
		$meili = t("Sinulla on seuraavat uudet laskut hyv�ksytt�v�n�").":\n\n" . $meili;
		$tulos = mail($veposti, mb_encode_mimeheader(t("Uudet hyv�ksytt�v�t laskusi"), "ISO-8859-1", "Q"), $meili, "From: ".mb_encode_mimeheader($yhtiorow["nimi"], "ISO-8859-1", "Q")." <$yhtiorow[postittaja_email]>\n", "-f $yhtiorow[postittaja_email]");
	}

?>