<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;

if (@include_once("../inc/parametrit.inc"));
elseif (@include_once("inc/parametrit.inc"));

if (!isset($alusta_tunnus, $liitostunnus, $tilausrivi)) exit;

$alusta_tunnus = (int) $alusta_tunnus;
$liitostunnus = (int) $liitostunnus;
$tilausrivi = 0;

$error = array(
	'varalle' => ''
);

if (isset($submit) and trim($submit) != '') {

	if ($submit == 'submit') {
		# Koodi ei saa olla tyhjä!
		if ($koodi != '') {

			# Tarkistetaan hyllypaikka ja varmistuskoodi
			# hyllypaikan on oltava reservipaikka ja siellä ei saa olla tuotteita
			$options = array('varmistuskoodi' => $koodi, 'reservipaikka' => 'K');
			$kaikki_ok = tarkista_varaston_hyllypaikka($hyllyalue, $hyllynro, $hyllyvali, $hyllytaso, $options);

			# tarkistetaan että paikalla ei ole tuotteita
			$query = "SELECT *
						FROM tuotepaikat
						WHERE yhtio		= '{$kukarow['yhtio']}'
						AND hyllyalue	= '$hyllyalue'
						AND hyllynro	= '$hyllynro'
						AND hyllyvali	= '$hyllyvali'
						AND hyllytaso	= '$hyllytaso'";
			$result = pupe_query($query);
			$tuotteita_tuotepaikalla = mysql_num_rows($result);

			if ($tuotteita_tuotepaikalla > 0) {
				$error['varalle'] = t("Tuotepaikalla on tuotteita!");
				$kaikki_ok = false;
			}

			# Jos hyllypaikka ok, laitetaan koko suuntalava varastoon
			if ($kaikki_ok) {

				# Haetaan saapumiset?
				$saapumiset = hae_saapumiset($alusta_tunnus);

				# Päivitetään hyllypaikat
				$paivitetyt_rivit = paivita_hyllypaikat($alusta_tunnus, $hyllyalue, $hyllynro, $hyllyvali, $hyllytaso);

				if ($paivitetyt_rivit > 0) {
					# Hylly arrayksi...
					$hylly = array(
						"hyllyalue" => $hyllyalue,
						"hyllynro" => $hyllynro,
						"hyllyvali" => $hyllyvali,
						"hyllytaso" => $hyllytaso);

					# Viedään varastoon keikka kerrallaan.
					foreach($saapumiset as $saapuminen) {
						# Saako keikan viedä varastoon
						if (saako_vieda_varastoon($saapuminen, 'kalkyyli', 1) == 1) {
							# Ei saa viedä varastoon, skipataan?
							$varastovirhe = true;
							continue;
						} else {
							vie_varastoon($saapuminen, $alusta_tunnus, $hylly);
						}
					}
					# Jos kaikki meni ok
					if (isset($varastovirhe)) {
						$error['varalle'] .= t("Virhe varastoonviennissä");
					} else {
						echo "<META HTTP-EQUIV='Refresh'CONTENT='2;URL=alusta.php'>";
						exit;
					}
				}
				else {
					$error['varalle'] = t("Yhtään tuotetta ei löytynyt suuntalavalta");
				}
			}
			else {
				$error['varalle']  = t("Virheellinen varmistukoodi tai hyllypaikka ei ole reservipaikka");
			}
		}
		else {
			$error['varalle'] = t("Varmistukoodi ei voi olla tyhjä");
		}
	}
}

# Haetaan SSCC
$sscc_query = mysql_query("	SELECT sscc
							FROM suuntalavat
							WHERE tunnus='{$alusta_tunnus}'
							AND yhtio='{$kukarow['yhtio']}'");
$sscc = mysql_fetch_assoc($sscc_query);

$url = "alusta_tunnus={$alusta_tunnus}&liitostunnus={$liitostunnus}";

echo "<div class='header'>";
echo "<button onclick='window.location.href=\"suuntalavan_tuotteet.php?$url\"' class='button left'><img src='back2.png'></button>";
echo "<h1>",t("SUUNTALAVAVARALLE"),"</h1></div>";

echo "<div class='main'>

	<form name='varalleformi' method='post' action=''>
	<table>
		<tr>
			<th>",t("Suuntalava", $browkieli),"</th>
			<td colspan='3'>{$sscc['sscc']}</td>
		</tr>
		<tr>
			<th>",t("Alue", $browkieli),"</th>
			<td><input type='text' name='hyllyalue' value='{$hyllyalue}' /></td>
		</tr>
		<tr>
			<th>",t("Nro", $browkieli),"</th>
			<td><input type='text' name='hyllynro' value='{$hyllynro}' /></td>
		<tr>
			<th>",t("Väli", $browkieli),"</th>
			<td><input type='text' name='hyllyvali' value='{$hyllyvali}' /></td>
		<tr>
			<th>",t("Taso", $browkieli),"</th>
			<td><input type='text' name='hyllytaso' value='{$hyllytaso}' /></td>
		</tr>
		<tr>
			<th>",t("Koodi", $browkieli),"</th>
			<td colspan='2'><input type='text' name='koodi' value='{$koodi}' size='7' />
		</tr>
	</table>
	</div>

	<div class='controls'>
		<button name='submit' value='submit' class='button' onclick='submit();'>",t("OK", $browkieli),"</button>
	</div>

	<span class='error'>{$error['varalle']}</span>
	<input type='hidden' name='alusta_tunnus' value='{$alusta_tunnus}' />
	<input type='hidden' name='liitostunnus' value='{$liitostunnus}' />
	<input type='hidden' name='tilausrivi' value='{$tilausrivi}' />
	</form>
</div>";

require('inc/footer.inc');