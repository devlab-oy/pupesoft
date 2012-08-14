<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;

if (@include_once("../inc/parametrit.inc"));
elseif (@include_once("inc/parametrit.inc"));

$alusta_tunnus = (int) $alusta_tunnus;
$liitostunnus = (int) $liitostunnus;
$selected_row = (int) $selected_row;

$error = array(
	'rivi' => ''
);

# Haetaan suuntalavan tuotteet
$res = suuntalavan_tuotteet(array($alusta_tunnus), $liitostunnus, "", "", "", $selected_row);
$row = mysql_fetch_assoc($res);

# Jos on painettu nappia
if (isset($submit) and trim($submit) != '') {

	$data = array(
		'alusta_tunnus' => $alusta_tunnus,
		'liitostunnus' => $liitostunnus
	);
	$url = http_build_query($data);

	# Takaisin nappi
	if ($submit == 'cancel') {
		echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=suuntalavan_tuotteet.php?{$url}'>";
		exit;
	}

	# Pois suuntalavalta nappi
	elseif ($submit == 'submit') {

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
			$ok = paivita_tilausrivin_kpl($selected_row, ($row['varattu'] - $maara));
			$uuden_rivin_id = splittaa_tilausrivi($selected_row, $maara, false, true);

			# Redirect alustaan vai suuntalavan_tuotteet
			echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=suuntalavan_tuotteet.php?{$url}'>";
			exit;
		}
	}
}

include("kasipaate.css");

echo "<div class='header'><h1>",t("MUOKKAA SUUNTALAVAN RIVIƒ", $browkieli),"</h1></div>";

echo "<div class='main'>

<form name='muokkaaformi' method='post' action=''>
<table>
	<tr>
		<th>",t("Suuntalava", $browkieli),"</th>
		<td colspan='2'>{$alusta_tunnus}</td>
	</tr>
	<tr><td colspan='3'>&nbsp;</td></tr>
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
<input type='hidden' name='selected_row' value='{$selected_row}' />
<span class='error'>{$error['rivi']}</span>
</div>";

echo "<div class='controls'>
	<button name='submit' value='submit' onclick='submit();'>",t("Pois suuntalavalta", $browkieli),"</button>
	<button class='right' name='submit' value='cancel' onclick='submit();'>",t("Takaisin", $browkieli),"</button>
</div>";

require('inc/footer.inc');