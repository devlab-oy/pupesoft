<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;

if (@include_once("../inc/parametrit.inc"));
elseif (@include_once("inc/parametrit.inc"));

$alusta_tunnus = (int) $alusta_tunnus;
$liitostunnus = (int) $liitostunnus;
$tilausrivi = (int) $tilausrivi;

$error = array(
	'rivi' => ''
);

$data = array(
	'alusta_tunnus' => $alusta_tunnus,
	'liitostunnus' => $liitostunnus
);
$url = http_build_query($data);

# Haetaan suuntalavan tuotteet
$res = suuntalavan_tuotteet(array($alusta_tunnus), $liitostunnus, "", "", "", $tilausrivi);
$row = mysql_fetch_assoc($res);

# Jos on painettu nappia
if (isset($submit) and trim($submit) != '') {

	# Pois suuntalavalta nappi
	if ($submit == 'submit') {

		if (!isset($maara)) {
			$error['rivi'] = t("Syˆt‰ m‰‰r‰", $browkieli).'.';
		}
		elseif (!is_numeric($maara)) {
			$error['rivi'] = t("M‰‰r‰n pit‰‰ olla numero", $browkieli).'.';
		}
		elseif ($maara < 1 or $maara >= $row['varattu']) {
			if ($row['varattu'] == 1) {
				$error['rivi'] = t("Virheellinen m‰‰r‰", $browkieli).'.';
			}
			else {
				$error['rivi'] = t("Sallitut m‰‰r‰t ovat", $browkieli).' 1 - '.($row['varattu'] - 1).'.';
			}
		}
		else {
			# P‰ivitet‰‰n tilausrivin m‰‰r‰ ja splitataan rivi
			$ok = paivita_tilausrivin_kpl($tilausrivi, ($row['varattu'] - $maara));
			$uuden_rivin_id = splittaa_tilausrivi($tilausrivi, $maara, false, true);

			# Redirect alustaan vai suuntalavan_tuotteet
			echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=suuntalavan_tuotteet.php?{$url}'>";
			exit;
		}
	}
}

echo "<div class='header'>";
echo "<button onclick='window.location.href=\"suuntalavan_tuotteet.php?$url\"' class='button left'><img src='back2.png'></button>";
echo "<h1>",t("MUOKKAA SUUNTALAVAN RIVIƒ", $browkieli),"</h1></div>";

echo "<div class='main'>

<form name='muokkaaformi' method='post' action=''>
<table>
	<tr>
		<th>",t("Suuntalava", $browkieli),"</th>
		<td colspan='2'>{$alusta_tunnus}</td>
	</tr>
	<tr>
		<th>",t("Tuote", $browkieli),"</th>
		<td colspan='2'>{$row['tuoteno']}</td>
	</tr>
	<tr>
		<th>",t("Toim. Tuotekoodi", $browkieli),"</th>
		<td colspan='2'>{$row['toim_tuoteno']}</td>
	</tr>
	<tr>
		<th>",t("M‰‰r‰", $browkieli),"</th>
		<td><input type='text' name='maara' value='' size='7' />
		<td>{$row['varattu']} {$row['yksikko']}</td>
	</tr>
</table>
<input type='hidden' name='alusta_tunnus' value='{$alusta_tunnus}' />
<input type='hidden' name='liitostunnus' value='{$liitostunnus}' />
<input type='hidden' name='tilausrivi' value='{$tilausrivi}' />
<span class='error'>{$error['rivi']}</span>
</div>";

echo "<div class='controls'>
	<button name='submit' value='submit' class='button' onclick='submit();'>",t("Pois suuntalavalta", $browkieli),"</button>
</div>";

require('inc/footer.inc');