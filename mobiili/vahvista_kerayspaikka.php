<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;

if (@include_once("../inc/parametrit.inc"));
elseif (@include_once("inc/parametrit.inc"));

# N�m� on pakollisia
if (!isset($alusta_tunnus, $liitostunnus, $selected_row)) exit;

$alusta_tunnus = (int) $alusta_tunnus;
$liitostunnus = (int) $liitostunnus;
$selected_row = (int) $selected_row;

# Urlin rakennus
$data = array(
	'alusta_tunnus' => $alusta_tunnus,
	'liitostunnus' => $liitostunnus,
	'selected_row' => $selected_row
);
$url = http_build_query($data);

# Virheet
$error = array();

# Haetaan suuntalavan tuotteet
$res = suuntalavan_tuotteet(array($alusta_tunnus), $liitostunnus, "", "", "", $selected_row);
$row = mysql_fetch_assoc($res);

# Haetaan saapumiset6881
$saapumiset = hae_saapumiset($alusta_tunnus);

# Jos parametrina hylly, eli ollaan muutettu tuotteen ker�yspaikkaa
if(isset($hylly)) {
	$hylly = explode(",", $hylly);
	$row['hyllyalue'] = $hylly[0];
	$row['hyllynro'] = $hylly[1];
	$row['hyllyvali'] = $hylly[2];
	$row['hyllytaso'] = $hylly[3];
}

# Tullaan nappulasta
if (isset($submit) and trim($submit) != '') {

	if ($submit == 'cancel') {
		echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=suuntalavan_tuotteet.php?{$url}'>";
		exit;
	}
	elseif ($submit == 'new') {
		echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=uusi_kerayspaikka.php?{$url}'>";
		exit;
	}
	elseif ($submit == 'submit') {
		# Tarkistetaan ett� m��r� on sy�tetty ja numero
		if (!is_numeric($maara)) {
			$error['maara'] = "M��r�n t�ytyy olla numero";
		}
		if (empty($koodi) || !tarkista_varaston_hyllypaikka($row['hyllyalue'], $row['hyllynro'], $row['hyllyvali'], $row['hyllytaso'], $koodi)) {
			$error['koodi'] = "Varmistuskoodi on v��rin";
		}
		# Tarkistetaan varmistuskoodi
		if(is_numeric($maara) && is_numeric($koodi) && tarkista_varaston_hyllypaikka($row['hyllyalue'], $row['hyllynro'], $row['hyllyvali'], $row['hyllytaso'], $koodi)) {

			# Hylly array
			$hylly = array(
				"hyllyalue" => $row['hyllyalue'],
				"hyllynro" 	=> $row['hyllynro'],
				"hyllyvali" => $row['hyllyvali'],
				"hyllytaso" => $row['hyllytaso']);

			# Jos m��r�� pienennet��n, niin splitataan ( $maara < $row['varattu'])
			if($maara < $row['varattu']) {
				# P�ivitet��n alkuper�isen rivin kpl
				$ok = paivita_tilausrivin_kpl($selected_row, ($row['varattu'] - $maara));

				# Splitataan rivi, $pois_suuntalavalta = false
				$uuden_rivin_id = splittaa_tilausrivi($selected_row, $maara, false, false);

				# Haetaan saapumiset
				$saapuminen = hae_saapumiset($alusta_tunnus);

				# Vied��n splitattu rivi varastoon
				vie_varastoon($saapuminen[0], $alusta_tunnus, $hylly, $uuden_rivin_id);

				# Palataan suuntalavan_tuotteet sivulle
				echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=suuntalavan_tuotteet.php?{$url}'>";
				exit;
			}
			# Jos nostetaan niin tehd��n insertti erotukselle..
			elseif($maara > $row['varattu']) {
				# Tehd��n insertti erotukselle
				$kopioitu_tilausrivi = kopioi_tilausrivi($selected_row);

				# P�ivit� kopioidun kpl (maara - varattu)
				paivita_tilausrivin_kpl($kopioitu_tilausrivi, ($maara - $row['varattu']));

				# Haetaan saapumiset
				$saapuminen = hae_saapumiset($alusta_tunnus);

				# Vied��n rivit hyllyyn
				vie_varastoon($saapuminen[0], $alusta_tunnus, $hylly, $selected_row);
				vie_varastoon($saapuminen[0], $alusta_tunnus, $hylly, $kopioitu_tilausrivi);

				# Palataan suuntalavan_tuotteet sivulle
				echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=suuntalavan_tuotteet.php?{$url}'>";
				exit;
			}
			# M��r�t samat
			else {
				# Haetaan saapumiset
				$saapuminen = hae_saapumiset($alusta_tunnus);

				# Vied��n varastoon
				vie_varastoon($saapuminen[0], $alusta_tunnus, $hylly, $selected_row);

				# Jos tuotteita j�lell�, menn��n takaisin suuntalavan tuotteet sivulle
				echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=suuntalavan_tuotteet.php?{$url}'>";
				exit;
			}

		}
	}
}
include("kasipaate.css");

echo "
	<script type='text/javascript'>
		function vahvista() {
			var maara = document.getElementById('maara').value;
			var row_varattu = parseInt(document.getElementById('row_varattu').innerHTML);
			if(maara > row_varattu) {
				return confirm('Olet tulouttamassa enemm�n kuin rivill� alunperin oli. Oletko varma?');
			}
			else return true;
		}
	</script>
";

echo "<div class='header'><h1>",t("VAHVISTA KER�YSPAIKKA", $browkieli),"</h1></div>";

if (isset($error)) {
	echo "<span class='error'>";
	foreach($error as $key => $virhe) {
		echo "{$virhe}<br>";
	}
	echo "</span>";
}

echo "<div class='main'>
<form name='vahvistaformi' method='post' action=''>
<table>
	<tr>
		<th>",t("Tuote", $browkieli),"</th>
		<td colspan='2'>{$row['tuoteno']}</td>
	</tr>
	<tr>
		<th>",t("Toim. Tuotekoodi", $browkieli),"</th>
		<td colspan='2'>{$row['toim_tuoteno']}</td>
	</tr>
	<tr>
		<th>",t("M��r�", $browkieli),"</th>
		<td><input type='text' id='maara' name='maara' value='{$maara}' size='7' />
		<td><span id='row_varattu'>{$row['varattu']}</span> {$row['yksikko']}</td>
	</tr>
	<tr>
		<th>",t("Ker�yspaikka", $browkieli),"</th>
		<td colspan='2'>{$row['hyllyalue']} {$row['hyllynro']} {$row['hyllyvali']} {$row['hyllytaso']}</td>
	</tr>
	<tr>
		<th>",t("Koodi", $browkieli),"</th>
		<td colspan='2'><input type='text' name='koodi' value='' size='7' />
	</tr>
</table>

</div>";

echo "<div class='controls'>
	<button name='submit' value='submit' onclick='return vahvista();'>",t("Vahvista", $browkieli),"</button>
	<button class='right' name='submit' value='new'>",t("Uusi ker�yspaikka", $browkieli),"</button>
	<button class='right' name='submit' value='cancel' onclick='submit();'>",t("Takaisin", $browkieli),"</button>

	<input type='hidden' name='alusta_tunnus' value='{$alusta_tunnus}' />
	<input type='hidden' name='liitostunnus' value='{$liitostunnus}' />
	<input type='hidden' name='selected_row' value='{$selected_row}' />
</form>
";

require('inc/footer.inc');