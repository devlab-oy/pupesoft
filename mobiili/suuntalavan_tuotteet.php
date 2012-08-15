<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;

if (@include_once("../inc/parametrit.inc"));
elseif (@include_once("inc/parametrit.inc"));

if (isset($submit) and trim($submit) != '') {

	$data = array(
		'tilausrivi' => (int) $tilausrivi,
		'alusta_tunnus' => (int) $alusta_tunnus,
		'liitostunnus' => (int) $liitostunnus
	);

	$url = http_build_query($data);

	# edit ja submit tarvitsee valitun rivin.
	if (!isset($_POST['tilausrivi']) and $viivakoodi == '') {
		$error['tuotteet'] = t("Riviä ei ole valittu", $browkieli).'.';
	}
	else {
		if ($submit == 'edit') {
			echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=muokkaa_suuntalavan_rivia.php?{$url}'>";
			exit;
		}
		elseif ($submit == 'submit') {
			echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=vahvista_kerayspaikka.php?{$url}'>";
			exit;
		}
	}
	if ($submit == 'cancel') {
		echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=alusta.php'>";
		exit;
	}
	elseif ($submit == 'varalle') {
		echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=suuntalava_varalle.php?{$url}'>";
		exit;
	}
}

$sort_by_direction_tuoteno 		= (!isset($sort_by_direction_tuoteno) or $sort_by_direction_tuoteno == 'asc') ? 'desc' : 'asc';
$sort_by_direction_maara 		= (!isset($sort_by_direction_maara) or $sort_by_direction_maara == 'asc') ? 'desc' : 'asc';
$sort_by_direction_yksikko 		= (!isset($sort_by_direction_yksikko) or $sort_by_direction_yksikko == 'asc') ? 'desc' : 'asc';
$sort_by_direction_tuotepaikka 	= (!isset($sort_by_direction_tuotepaikka) or $sort_by_direction_tuotepaikka == 'asc') ? 'desc' : 'asc';

$tuotteet = array();

if (isset($alusta_tunnus)) {

	if (isset($sort_by) and $sort_by == "tuoteno") {
		$orderby = "tuoteno";
		$ascdesc = $sort_by_direction_tuoteno;
	}
	elseif (isset($sort_by) and $sort_by == "maara") {
		$orderby = "maara";
		$ascdesc = $sort_by_direction_maara;
	}
	elseif (isset($sort_by) and $sort_by == "yksikko") {
		$orderby = "yksikko";
		$ascdesc = $sort_by_direction_yksikko;
	}
	else {
		$orderby = "tuotepaikka";
		$ascdesc = $sort_by_direction_tuotepaikka;
	}

	# Haetaan eankoodilla
	$eankoodi = (isset($viivakoodi) and trim($viivakoodi))  ? trim($viivakoodi) : "";
	$res = suuntalavan_tuotteet(array($alusta_tunnus), $liitostunnus, $orderby, $ascdesc, "", "", $eankoodi);

	# Jos tuotetta ei löydy tältä lavalta
	if (mysql_num_rows($res) == 0 && $eankoodi != '') {
		$error['tuotteet'] = "Suuntalavalta ei löytynyt kyseistä tuotetta";
		# Haetaan tuotteet uudelleen ilman eankoodia
		$res = suuntalavan_tuotteet(array($alusta_tunnus), $liitostunnus, $orderby, $ascdesc);
	}
	# Muuten tyhjä lava
	elseif(mysql_num_rows($res) == 0) {
		echo "Suuntalava on tyhjä!<br>";

		# Merkataan puretuksi
		$query = "UPDATE suuntalavat SET suuntalavat.tila = 'P' WHERE tunnus = '{$alusta_tunnus}' AND yhtio = '{$kukarow['yhtio']}'";
		$purettu_result = pupe_query($query);

		if (mysql_affected_rows() == 0) {
			echo "VIRHE: Suuntalavaa ei purettu<br>";
			echo mysql_error();
		}
		echo "<META HTTP-EQUIV='Refresh'CONTENT='2;URL=alusta.php'>";
		exit;
	}

	$i = 0;
	while ($row = mysql_fetch_assoc($res)) {
		$tuotteet[$i]['tilriv_tunnus'] = $row['tunnus'];
		$tuotteet[$i]['tuoteno'] = $row['tuoteno'];
		$tuotteet[$i]['maara'] = $row['varattu'];
		$tuotteet[$i]['yks'] = $row['yksikko'];
		$tuotteet[$i]['osoite'] = "{$row['hyllyalue']} {$row['hyllynro']} {$row['hyllyvali']} {$row['hyllytaso']}";
		$tuotteet[$i]['tuotekerroin'] = $row['tuotekerroin'] != '' ? (float) $row['tuotekerroin'] : 1;

		$i++;
	}
}

include("kasipaate.css");

echo "<div class='header'><h1>",t("SUUNTALAVAN TUOTTEET", $browkieli),"</h1></div>";

echo "<form name='viivakoodiformi' method='post' action=''>
	<table>
		<tr>
			<td>",t("Viivakoodi", $browkieli),":&nbsp;<input type='text' id='viivakoodi' name='viivakoodi' value='' />
			</td>
			<td>
				<button name='submit' value='viivakoodi' onclick='submit();'>",t("Etsi", $browkieli),"</button>
			</td>
		</tr>
	</table>
	</form>";

echo "
<form name='hakuformi' method='post' action=''>

<div class='controls'>
	<button class='left' name='submit' value='submit' onclick='submit();'>",t("Valitse", $browkieli),"</button>
	<button name='submit' value='edit' onclick='submit();'>",t("Muokkaa", $browkieli),"</button>
	<button name='submit' value='varalle' onclick='return varmista();'>",t("Varalle", $browkieli),"</button>
	<button class='right' name='submit' value='cancel' onclick='submit();'>",t("Takaisin", $browkieli),"</button>
</div>";

if (isset($error)) {
	echo "<span class='error'>{$error['tuotteet']}</span>";
}

echo "<div class='main'>
	<table>

<tr>
<th>&nbsp;</th>
<th nowrap>
<a href='suuntalavan_tuotteet.php?alusta_tunnus={$alusta_tunnus}&liitostunnus={$liitostunnus}&sort_by=tuoteno&sort_by_direction_tuoteno={$sort_by_direction_tuoteno}'>",t("Tuote", $browkieli),"</a>&nbsp;";

echo $sort_by_direction_tuoteno == 'asc' ? "<img src='{$palvelin2}pics/lullacons/arrow-double-up-green.png' />" : "<img src='{$palvelin2}pics/lullacons/arrow-double-down-green.png' />";

echo "</th>
<th nowrap>
<a href='suuntalavan_tuotteet.php?alusta_tunnus={$alusta_tunnus}&liitostunnus={$liitostunnus}&sort_by=maara&sort_by_direction_maara={$sort_by_direction_maara}'>",t("Määrä", $browkieli),"</a>&nbsp;";

echo $sort_by_direction_maara == 'asc' ? "<img src='{$palvelin2}pics/lullacons/arrow-double-up-green.png' />" : "<img src='{$palvelin2}pics/lullacons/arrow-double-down-green.png' />";

echo "</th>
<th nowrap>
<a href='suuntalavan_tuotteet.php?alusta_tunnus={$alusta_tunnus}&liitostunnus={$liitostunnus}&sort_by=yksikko&sort_by_direction_yksikko={$sort_by_direction_yksikko}'>",t("Yks", $browkieli),"</a>&nbsp;";

echo $sort_by_direction_yksikko == 'asc' ? "<img src='{$palvelin2}pics/lullacons/arrow-double-up-green.png' />" : "<img src='{$palvelin2}pics/lullacons/arrow-double-down-green.png' />";

echo "</th>
<th nowrap>
<a href='suuntalavan_tuotteet.php?alusta_tunnus={$alusta_tunnus}&liitostunnus={$liitostunnus}&sort_by=tuotepaikka&sort_by_direction_tuotepaikka={$sort_by_direction_tuotepaikka}'>",t("Hylly", $browkieli),"</a>&nbsp;";

echo $sort_by_direction_tuotepaikka == 'asc' ? "<img src='{$palvelin2}pics/lullacons/arrow-double-up-green.png' />" : "<img src='{$palvelin2}pics/lullacons/arrow-double-down-green.png' />";

echo "</th>
</tr>";

	$chk = count($tuotteet) == 1 ? " checked" : "";

	foreach ($tuotteet as $tuote) {
		echo "<tr id='{$tuote['tilriv_tunnus']}'>";
		echo "<td><input class='radio' type='radio' name='tilausrivi' value='{$tuote['tilriv_tunnus']}'{$chk} /></td>";
		echo "<td nowrap>{$tuote['tuoteno']}</td>";
		echo "<td nowrap>{$tuote['maara']}";

		if ($tuote['tuotekerroin'] != 1) echo "&nbsp;(",$tuote['maara'] * $tuote['tuotekerroin'],")";

		$onko_suoratoimitus_res = onko_suoratoimitus($tuote['tilriv_tunnus']);

		if ($row = mysql_fetch_assoc($onko_suoratoimitus_res)) {
			if ($row["suoraan_laskutukseen"] == "") echo "&nbsp;",t("JT");
		}

		echo "</td>";
		echo "<td nowrap>{$tuote['yks']}</td>";

		# Jos oletuspaikat on setattu niin ollaan tultu alustalta ja asetetaan tilausrivien
		# tuotepaikat oletuspaikoiksi.
		if ($oletuspaikat) {

			# Päivitetään tilausriveille oletuspaikat.
			$oletus_query = "	SELECT hyllyalue, hyllynro, hyllyvali, hyllytaso
								FROM tuotepaikat
								WHERE tuoteno='{$tuote['tuoteno']}'
								AND oletus='X'
								AND yhtio='{$yhtiorow['yhtio']}'";
			$oletus_result = mysql_query($oletus_query);
			$oletus = mysql_fetch_assoc($oletus_result);

			$hylly = array(
					'hyllyalue' => $oletus['hyllyalue'],
					'hyllynro'	=> $oletus['hyllynro'],
					'hyllyvali' => $oletus['hyllyvali'],
					'hyllytaso' => $oletus['hyllytaso']
				);

			# Jos tilausrivillä oleva hyllypaikka ei ole tuotteen oletuspaikka
			# päivitetään tilausrivin hyllypaikka oletuspaikaksi.
			if ($tuote['osoite'] != implode(" ", $hylly)) {
				paivita_tilausrivin_hylly($tuote['tilriv_tunnus'], $hylly);
				$tuote['osoite'] = implode(" ", $hylly);
			}
		}
		echo "<td nowrap>{$tuote['osoite']}</td>";
		echo "</tr>";
// 					}
	}
echo "
	<input type='hidden' name='alusta_tunnus' value='{$alusta_tunnus}' />
	<input type='hidden' name='liitostunnus' value='{$liitostunnus}' />
	</form>
</table>
</div>";

# Autofocus opera mobileen
echo "<input type='button' id='myHiddenButton' visible='false' onclick='javascript:doFocus();' width='1px' style='display:none'>";
echo "<script type='text/javascript'>

	function doFocus() {
	        var focusElementId = 'viivakoodi'
	        var textBox = document.getElementById(focusElementId);
	        textBox.focus();
	    }

	function clickButton() {
	   document.getElementById('myHiddenButton').click();
	}

   setTimeout('clickButton()', 1000)
</script>
";

require('inc/footer.inc');