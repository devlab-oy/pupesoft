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
			$kaikki_ok = tarkista_varaston_hyllypaikka($hyllyalue, $hyllynro, $hyllyvali, $hyllytaso, $koodi);

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
						$error['varalle'] .= "Virhe varastoonviennissä";
					} else {
						echo "<META HTTP-EQUIV='Refresh'CONTENT='2;URL=alusta.php'>";
						exit;
					}
				}
				else {
					$error['varalle'] = "Yhtään tuotetta ei löytynyt suuntalavalta";
				}
			}
			else {
				$error['varalle']  = "Virheellinen varmistukoodi tai tuotepaikka.";
			}
		}
		else {
			$error['varalle'] = "Varmistukoodi ei voi olla tyhjä";
		}
	}
	# Takaisin
	elseif ($submit == 'cancel') {
		$url = "?alusta_tunnus={$alusta_tunnus}&liitostunnus={$liitostunnus}";

		echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=suuntalavan_tuotteet.php{$url}'>";
		exit;
	}
}

# Haetaan SSCC
$sscc_query = mysql_query("	SELECT sscc
							FROM suuntalavat
							WHERE tunnus='{$alusta_tunnus}'
							AND yhtio='{$kukarow['yhtio']}'");
$sscc = mysql_fetch_assoc($sscc_query);

include("kasipaate.css");

echo "<div class='header'><h1>",t("SUUNTALAVAVARALLE"),"</h1></div>";

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
		<tr>
			<td nowrap>
				<button name='submit' value='submit' onclick='submit();'>",t("OK", $browkieli),"</button>
			</td>
			<td nowrap>
				<button class='right' name='submit' value='cancel' onclick='submit();'>",t("Takaisin", $browkieli),"</button>
			</td>
		</tr>
	</div>

	<span class='error'>{$error['varalle']}</span>
	<input type='hidden' name='alusta_tunnus' value='{$alusta_tunnus}' />
	<input type='hidden' name='liitostunnus' value='{$liitostunnus}' />
	<input type='hidden' name='tilausrivi' value='{$tilausrivi}' />
	</form>
</div>";

require('inc/footer.inc');