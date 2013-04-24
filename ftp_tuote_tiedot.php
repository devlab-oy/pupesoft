<?php

//* Tämä skripti käyttää slave-tietokantapalvelinta *//
$useslave = 1;

// Kutsutaanko CLI:stä
$php_cli = FALSE;

if (php_sapi_name() == 'cli') {
	$php_cli = TRUE;
}

if (!$php_cli) {
	require ("inc/parametrit.inc");
	$kyhtio = $kukarow['yhtio'];
}
else {
	if ($argv[1] != "") $kyhtio = trim($argv[1]);
	else die ("Yhtiö on annettava!");

	require ("/var/www/html/pupesoft/inc/connect.inc");
	require ("/var/www/html/pupesoft/inc/functions.inc");
	$tee = "aja";
}

// palautetaan $syy
function tee_file($result, $dirri, $tiedostonnimi, $ftpkuvahost, $ftpkuvauser, $ftpkuvapass) {

	//lähetetään tiedosto
	$conn_id = ftp_connect($ftpkuvahost);

	// jos connectio ok, kokeillaan loginata
	if ($conn_id) {
		$login_result = ftp_login($conn_id, $ftpkuvauser, $ftpkuvapass);

		if ($login_result === FALSE) {
			$syy .= "Could not login to remote host ($conn_id, $ftpkuvauser, $ftpkuvapass)\n";
		}
	}
	else {
		$syy .= "Could not connect to remote host. ($ftpkuvahost)\n";
	}

	// jos login ok kokeillaan uploadata
	if ($login_result) {

		ftp_pasv($conn_id, true);

		$kokonimi = $dirri."/".$tiedostonnimi;

		if (!file_exists($kokonimi)) {
			$handle = fopen("$kokonimi", "x");

			if ($handle === FALSE) {
				$syy .= "Tiedoston $kokonimi luonti epäonnistui!\n";
			}
			else {

				$fields = mysql_num_fields($result);

				$ulos = "";

				for ($i=0; $i < $fields; $i++) {
					$ulos .= mysql_field_name($result, $i);

					if ($i == $fields-1) {
						$ulos .= "\n";
					}
					else {
						$ulos .= "\t";
					}
				}

				$order   = array("\r\n", "\n", "\r");
				while ($row = mysql_fetch_array($result)) {
					for ($i=0; $i < $fields; $i++) {

						if (strpos($row[$i],'"') !== FALSE) {
							$row[$i] = str_replace('"',"",$row[$i]);
						}

						if (strpos(($temp = mysql_field_name($result, $i)),"lyhytkuvaus") !== FALSE) {
							$row[$i] = cut_text($row[$i],100);
						}

						/*
						if (strpos(($temp = mysql_field_name($result, $i)),"mainosteksti") !== FALSE or strpos(($temp = mysql_field_name($result, $i)),"kuvaus") !== FALSE) {


								$from = array("[lihavoitu]", "[/lihavoitu]", "[kursivoitui]", "[/kursivoitu]", "[alleviivaus]", "[/alleviivaus]", "[lista]", "[/lista]");
								$to   = array("<b>", "</b>", "<i>", "</i>", "<u>", "</u>", "<ul>", "</li></ul>");

								$row[$i] = str_replace($from,$to,$row[$i]);

								$ulcount = substr_count($row[$i], "<ul>");

								$ulppos = 0;
								for ($s=0; $s < $ulcount; $s++) {
									$ulpos = strpos($row[$i], "<ul>",$ulpos);

									$ilpos = strpos($row[$i], "[*]", $ulpos);

									$row[$i] = substr_replace($row[$i],"<li>",$ilpos,3);

									$ulpos = $ilpos;
								}

								$row[$i] = str_replace("[*]","</li><li>",$row[$i]);

						}*/


						$ulos .= str_replace($order,"<br>",$row[$i]);

						if ($i == $fields-1) {
							$ulos .= "\n";
						}
						else {
							$ulos .= "\t";
						}
					}
				}


				fputs ($handle,$ulos);
				fclose ($handle);

				$upload = ftp_put($conn_id, $tiedostonnimi, realpath($kokonimi), FTP_BINARY);

				//check upload
				if ($upload === FALSE) {
					$syy .= "Transfer failed ($conn_id, $tiedostonnimi, ".realpath($kokonimi).")\n";

				}
			}

			//poistetaan filu
			system("rm -f '$kokonimi'");
		}
	}

	if ($conn_id) {
		ftp_close($conn_id);
	}

	return $syy;

}

function cut_text($text, $chars) {

	if (strlen($text) == 0 or strlen($text) < $chars) {
		return $text;
	}
	else {

		if (substr($text,$chars) == " ") {
			return substr($text,0,$chars)."...";
		}
		elseif (substr($text,$chars) == ".") {
			return substr($text,0,$chars)."..";
		}
		elseif (substr($text,$chars) == ",") {
			return substr($text,0,$chars-1)."...";
		}
		else {

			$text = substr($text,0,$chars);
			$pos[0] = strrpos($text," ");
			$pos[1] = strrpos($text,".");
			$pos[2] = strrpos($text,",");


			sort($pos);

			if (substr($text,$pos[2],1) == " ") {
				return substr($text,0,$pos[2])."...";
			}
			elseif (substr($text,$pos[2],1) == ".") {
				return substr($text,0,$pos[2])."..";
			}
			elseif (substr($text,$pos[2],1) == ",") {
				return substr($text,0,$pos[2]-1)."...";
			}

		}
	}

	return "";
}

$syy = "";

if ($tee == "aja") {

	echo date("H:i:s").": Aloitetaan tuotetietojen siirto.\n";

	// tarvitaan $ftpkuvahost $ftpkuvauser $ftpkuvapass
	if ($ftpkuvahost=='' or $ftpkuvauser=='' or $ftpkuvapass=='') {
		$syy .= "Lähetykseen tarvittavia tietoja puuttuu! (host, user, pass)";
	}
	else {

		$dirri = "/tmp";

		if (!is_writable($dirri)) {
			die("$kokonimi ei ole määritelty kirjoitusoikeutta. Ei voida jatkaa!<br>");
		}

		/*tuotetieto haku*/
		$query = "  SELECT tuote.tuoteno, tuote.try, tuote.osasto, tuote.nimitys, ta_nimitys_se.selite as nimitys_se,
					tuote.kuvaus, ta_kuvaus_se.selite as kuvaus_se,
					group_concat(liitetiedostot.filename SEPARATOR ',') as tiedostot,
					group_concat(liitetiedostot.selite SEPARATOR ',') as selitteet,
					tuote.kuvaus as lyhytkuvaus, ta_kuvaus_se.selite as lyhytkuvaus_se,
					ta_nimitys_en.selite as nimitys_en, ta_kuvaus_en.selite as kuvaus_en, ta_kuvaus_en.selite as lyhytkuvaus_en,
					ta_nimitys_ru.selite as nimitys_ru, ta_kuvaus_ru.selite as kuvaus_ru, ta_kuvaus_ru.selite as lyhytkuvaus_ru,
					ta_nimitys_ee.selite as nimitys_ee, ta_kuvaus_ee.selite as kuvaus_ee, ta_kuvaus_ee.selite as lyhytkuvaus_ee,
					ta_nimitys_de.selite as nimitys_de, ta_kuvaus_de.selite as kuvaus_de, ta_kuvaus_de.selite as lyhytkuvaus_de,
					tuote.myyntihinta
					FROM tuote
					LEFT JOIN liitetiedostot on tuote.yhtio = liitetiedostot.yhtio and tuote.tunnus = liitetiedostot.liitostunnus and liitetiedostot.liitos = 'tuote' and liitetiedostot.kayttotarkoitus in ('TK','MU')
					LEFT JOIN tuotteen_avainsanat as ta_nimitys_se on tuote.yhtio = ta_nimitys_se.yhtio and tuote.tuoteno = ta_nimitys_se.tuoteno and ta_nimitys_se.laji = 'nimitys' and ta_nimitys_se.kieli='se'
					LEFT JOIN tuotteen_avainsanat as ta_kuvaus_se on tuote.yhtio = ta_kuvaus_se.yhtio and tuote.tuoteno = ta_kuvaus_se.tuoteno and ta_kuvaus_se.laji = 'kuvaus' and ta_kuvaus_se.kieli='se'
					LEFT JOIN tuotteen_avainsanat as ta_lyhyt_se on tuote.yhtio = ta_lyhyt_se.yhtio and tuote.tuoteno = ta_lyhyt_se.tuoteno and ta_lyhyt_se.laji = 'lyhyt' and ta_lyhyt_se.kieli='se'
					LEFT JOIN tuotteen_avainsanat as ta_nimitys_en on tuote.yhtio = ta_nimitys_en.yhtio and tuote.tuoteno = ta_nimitys_en.tuoteno and ta_nimitys_en.laji = 'nimitys' and ta_nimitys_en.kieli='en'
					LEFT JOIN tuotteen_avainsanat as ta_kuvaus_en on tuote.yhtio = ta_kuvaus_en.yhtio and tuote.tuoteno = ta_kuvaus_en.tuoteno and ta_kuvaus_en.laji = 'kuvaus' and ta_kuvaus_en.kieli='en'
					LEFT JOIN tuotteen_avainsanat as ta_lyhyt_en on tuote.yhtio = ta_lyhyt_en.yhtio and tuote.tuoteno = ta_lyhyt_en.tuoteno and ta_lyhyt_en.laji = 'lyhyt' and ta_lyhyt_en.kieli='en'
					LEFT JOIN tuotteen_avainsanat as ta_nimitys_ru on tuote.yhtio = ta_nimitys_ru.yhtio and tuote.tuoteno = ta_nimitys_ru.tuoteno and ta_nimitys_ru.laji = 'nimitys' and ta_nimitys_ru.kieli='ru'
					LEFT JOIN tuotteen_avainsanat as ta_kuvaus_ru on tuote.yhtio = ta_kuvaus_ru.yhtio and tuote.tuoteno = ta_kuvaus_ru.tuoteno and ta_kuvaus_ru.laji = 'kuvaus' and ta_kuvaus_ru.kieli='ru'
					LEFT JOIN tuotteen_avainsanat as ta_lyhyt_ru on tuote.yhtio = ta_lyhyt_ru.yhtio and tuote.tuoteno = ta_lyhyt_ru.tuoteno and ta_lyhyt_ru.laji = 'lyhyt' and ta_lyhyt_ru.kieli='ru'
					LEFT JOIN tuotteen_avainsanat as ta_nimitys_ee on tuote.yhtio = ta_nimitys_ee.yhtio and tuote.tuoteno = ta_nimitys_ee.tuoteno and ta_nimitys_ee.laji = 'nimitys' and ta_nimitys_ee.kieli='ee'
					LEFT JOIN tuotteen_avainsanat as ta_kuvaus_ee on tuote.yhtio = ta_kuvaus_ee.yhtio and tuote.tuoteno = ta_kuvaus_ee.tuoteno and ta_kuvaus_ee.laji = 'kuvaus' and ta_kuvaus_ee.kieli='ee'
					LEFT JOIN tuotteen_avainsanat as ta_lyhyt_ee on tuote.yhtio = ta_lyhyt_ee.yhtio and tuote.tuoteno = ta_lyhyt_ee.tuoteno and ta_lyhyt_ee.laji = 'lyhyt' and ta_lyhyt_ee.kieli='ee'
					LEFT JOIN tuotteen_avainsanat as ta_nimitys_de on tuote.yhtio = ta_nimitys_de.yhtio and tuote.tuoteno = ta_nimitys_de.tuoteno and ta_nimitys_de.laji = 'nimitys' and ta_nimitys_de.kieli='de'
					LEFT JOIN tuotteen_avainsanat as ta_kuvaus_de on tuote.yhtio = ta_kuvaus_de.yhtio and tuote.tuoteno = ta_kuvaus_de.tuoteno and ta_kuvaus_de.laji = 'kuvaus' and ta_kuvaus_de.kieli='de'
					LEFT JOIN tuotteen_avainsanat as ta_lyhyt_de on tuote.yhtio = ta_lyhyt_de.yhtio and tuote.tuoteno = ta_lyhyt_de.tuoteno and ta_lyhyt_de.laji = 'lyhyt' and ta_lyhyt_de.kieli='de'
					join avainsana as avtry on tuote.yhtio = avtry.yhtio and tuote.try = avtry.selite and avtry.laji = 'TRY' and avtry.nakyvyys != 'E' and avtry.kieli = 'fi'
					join avainsana as avosasto on tuote.yhtio = avosasto.yhtio and tuote.osasto = avosasto.selite and avosasto.laji = 'OSASTO' and avosasto.nakyvyys != 'E' and avosasto.kieli = 'fi'
					WHERE tuote.yhtio = '$kyhtio'
					AND tuote.hinnastoon = 'W'
					AND tuote.status NOT IN ('P','X')
					AND tuote.tuotetyyppi NOT IN ('A', 'B')
					GROUP BY tuote.tuoteno, tuote.try, tuote.osasto, tuote.nimitys, substring(ta_nimitys_se.selite,1,1024), substring(tuote.kuvaus,1,1024), substring(ta_kuvaus_se.selite,1,1024),
					substring(ta_nimitys_en.selite,1,1024), substring(ta_kuvaus_en.selite,1,1024), substring(ta_nimitys_ru.selite,1,1024), substring(ta_kuvaus_ru.selite,1,1024),
					substring(ta_nimitys_ee.selite,1,1024), substring(ta_kuvaus_ee.selite,1,1024), substring(ta_nimitys_de.selite,1,1024), substring(ta_kuvaus_de.selite,1,1024)
					ORDER BY tuote.tuoteno";
		$result = pupe_query($query);

		$syy .= tee_file($result, $dirri, "tuotetiedot.csv",  $ftpkuvahost, $ftpkuvauser, $ftpkuvapass);

		/*osastot*/
		$query = "	SELECT avainsana.selite as osasto, avainsana.selitetark as nimitys,
					if(isnull(se.selitetark) or se.selitetark='', '', se.selitetark) as nimitys_se,
					avainsana.selitetark_3 as kuvaus, if(isnull(se.selitetark_3) or se.selitetark_3='', '', se.selitetark_3) as kuvaus_se,
					if(isnull(en.selitetark) or en.selitetark='', '', en.selitetark) as nimitys_en, if(isnull(en.selitetark_3) or en.selitetark_3='', '', en.selitetark_3) as kuvaus_en,
					if(isnull(ru.selitetark) or ru.selitetark='', '', ru.selitetark) as nimitys_ru, if(isnull(ru.selitetark_3) or ru.selitetark_3='', '', ru.selitetark_3) as kuvaus_ru,
					if(isnull(ee.selitetark) or ee.selitetark='', '', ee.selitetark) as nimitys_ee, if(isnull(ee.selitetark_3) or ee.selitetark_3='', '', ee.selitetark_3) as kuvaus_ee,
					if(isnull(de.selitetark) or de.selitetark='', '', de.selitetark) as nimitys_de, if(isnull(de.selitetark_3) or de.selitetark_3='', '', de.selitetark_3) as kuvaus_de
					FROM avainsana
					LEFT JOIN avainsana AS se on avainsana.yhtio = se.yhtio and avainsana.selite = se.selite and se.laji = 'OSASTO' and se.kieli = 'se'
					LEFT JOIN avainsana AS en on avainsana.yhtio = en.yhtio and avainsana.selite = en.selite and en.laji = 'OSASTO' and en.kieli = 'en'
					LEFT JOIN avainsana AS ru on avainsana.yhtio = ru.yhtio and avainsana.selite = ru.selite and ru.laji = 'OSASTO' and ru.kieli = 'ru'
					LEFT JOIN avainsana AS ee on avainsana.yhtio = ee.yhtio and avainsana.selite = ee.selite and ee.laji = 'OSASTO' and ee.kieli = 'ee'
					LEFT JOIN avainsana AS de on avainsana.yhtio = de.yhtio and avainsana.selite = de.selite and de.laji = 'OSASTO' and de.kieli = 'de'
					WHERE avainsana.yhtio = '$kyhtio'
					AND avainsana.laji = 'OSASTO'
					AND avainsana.kieli = 'fi'
					AND avainsana.nakyvyys != 'E'
					ORDER BY avainsana.jarjestys";
		$result = pupe_query($query);

		$syy .= tee_file($result, $dirri, "osastot.csv",  $ftpkuvahost, $ftpkuvauser, $ftpkuvapass);

		/*tuoteryhmät*/
		$query = "	SELECT avainsana.selite as try, avainsana.selitetark as nimitys,
					if(isnull(se.selitetark) or se.selitetark='', '', se.selitetark) as nimitys_se,
					avainsana.selitetark_3 as kuvaus, if(isnull(se.selitetark_3) or se.selitetark_3='', '', se.selitetark_3) as kuvaus_se,
					if(isnull(en.selitetark) or en.selitetark='', '', en.selitetark) as nimitys_en, if(isnull(en.selitetark_3) or en.selitetark_3='', '', en.selitetark_3) as kuvaus_en,
					if(isnull(ru.selitetark) or ru.selitetark='', '', ru.selitetark) as nimitys_ru, if(isnull(ru.selitetark_3) or ru.selitetark_3='', '', ru.selitetark_3) as kuvaus_ru,
					if(isnull(ee.selitetark) or ee.selitetark='', '', ee.selitetark) as nimitys_ee, if(isnull(ee.selitetark_3) or ee.selitetark_3='', '', ee.selitetark_3) as kuvaus_ee,
					if(isnull(de.selitetark) or de.selitetark='', '', de.selitetark) as nimitys_de, if(isnull(de.selitetark_3) or de.selitetark_3='', '', de.selitetark_3) as kuvaus_de
					FROM avainsana
					LEFT JOIN avainsana AS se on avainsana.yhtio = se.yhtio and avainsana.selite = se.selite and se.laji = 'TRY' and se.kieli = 'se'
					LEFT JOIN avainsana AS en on avainsana.yhtio = en.yhtio and avainsana.selite = en.selite and en.laji = 'TRY' and en.kieli = 'en'
					LEFT JOIN avainsana AS ru on avainsana.yhtio = ru.yhtio and avainsana.selite = ru.selite and ru.laji = 'TRY' and ru.kieli = 'ru'
					LEFT JOIN avainsana AS ee on avainsana.yhtio = ee.yhtio and avainsana.selite = ee.selite and ee.laji = 'TRY' and ee.kieli = 'ee'
					LEFT JOIN avainsana AS de on avainsana.yhtio = de.yhtio and avainsana.selite = de.selite and de.laji = 'TRY' and de.kieli = 'de'
					WHERE avainsana.yhtio = '$kyhtio'
					AND avainsana.laji = 'TRY'
					AND avainsana.kieli = 'fi'
					AND avainsana.nakyvyys != 'E'
					ORDER BY avainsana.jarjestys";
		$result = pupe_query($query);

		$syy .= tee_file($result, $dirri, "tuoteryhmat.csv",  $ftpkuvahost, $ftpkuvauser, $ftpkuvapass);


		if ($syy != "") {
			echo $syy;
		}

	}

	echo date("H:i:s").": Tuotetietojen siirto valmis.\n\n";

}

if ($tee == "") {
	echo "<font class='head'>".t("Tuotetietojen siirto verkkokauppaan")."</font><hr>";

	echo "<table><form name='uliuli' method='post'>";
	echo "<input type='hidden' name='tee' value='aja'>";
	echo "<tr><td class='back' colspan='2'><br><input type='submit' value='".t("Siirrä tiedot")."'></td></tr>";
	echo "</table>";
	echo "</form>";

}

?>