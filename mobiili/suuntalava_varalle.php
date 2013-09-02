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

if (!isset($hyllyalue)) $hyllyalue = "";
if (!isset($hyllynro)) $hyllynro = "";
if (!isset($hyllyvali)) $hyllyvali = "";
if (!isset($hyllytaso)) $hyllytaso = "";

$error = array(
	'varalle' => ''
);

if (isset($submit) and trim($submit) != '') {

	if ($submit == 'submit') {
		# Koodi ei saa olla tyhj�!
		if ($koodi != '') {

			// Parsitaan uusi tuotepaikka
			// Jos tuotepaikka on luettu viivakoodina, muotoa (C21 045) tai (21C 03V)
			if (preg_match('/^([a-z���#0-9]{2,4} [a-z���#0-9]{2,4})/i', $tuotepaikka) and substr_count($tuotepaikka, " ") == 1) {

				// Pilkotaan viivakoodilla luettu tuotepaikka v�lily�nnist�
				list($alku, $loppu) = explode(' ', $tuotepaikka);

				// M�ts�t��n numerot ja kirjaimet erilleen
				preg_match_all('/([0-9]+)|([a-z]+)/i', $alku, $alku);
				preg_match_all('/([0-9]+)|([a-z]+)/i', $loppu, $loppu);

				// Hyllyn tiedot oikeisiin muuttujiin
				$hyllyalue = $alku[0][0];
				$hyllynro  = $alku[0][1];
				$hyllyvali = $loppu[0][0];
				$hyllytaso = $loppu[0][1];

				// Kaikkia tuotepaikkoja ei pystyt� parsimaan
				if (empty($hyllyalue) or empty($hyllynro) or empty($hyllyvali) or empty($hyllytaso)) {
					$error['varalle'] .= t("Tuotepaikan haussa virhe, yrit� sy�tt�� tuotepaikka k�sin") . " ($tuotepaikka)<br>";
				}
			}
			// Tuotepaikka sy�tetty manuaalisesti (C-21-04-5) tai (C 21 04 5)
			elseif (strstr($tuotepaikka, '-') or strstr($tuotepaikka, ' ')) {
				// Parsitaan tuotepaikka omiin muuttujiin (erotelto v�lily�nnill�)
				if (preg_match('/\w+\s\w+\s\w+\s\w+/i', $tuotepaikka)) {
					list($hyllyalue, $hyllynro, $hyllyvali, $hyllytaso) = explode(' ', $tuotepaikka);
				}
				// (erotelto v�liviivalla)
				elseif (preg_match('/\w+-\w+-\w+-\w+/i', $tuotepaikka)) {
					list($hyllyalue, $hyllynro, $hyllyvali, $hyllytaso) = explode('-', $tuotepaikka);
				}

				// Ei saa olla tyhji� kentti�
				if ($hyllyalue == '' or $hyllynro == '' or $hyllyvali == '' or $hyllytaso == '') {
					$error['varalle'] .= t("Virheellinen tuotepaikka") . ". ($tuotepaikka)<br>";
				}
			}
			else {
				$error['varalle'] .= t("Virheellinen tuotepaikka, yrit� sy�tt�� tuotepaikka k�sin") . " ($tuotepaikka)<br>";
			}

			# Tarkistetaan hyllypaikka ja varmistuskoodi
			# hyllypaikan on oltava reservipaikka ja siell� ei saa olla tuotteita
			$options = array('varmistuskoodi' => $koodi, 'reservipaikka' => 'K');
			$kaikki_ok = tarkista_varaston_hyllypaikka($hyllyalue, $hyllynro, $hyllyvali, $hyllytaso, $options);

			# Jos hyllypaikka ok, laitetaan koko suuntalava varastoon
			if ($kaikki_ok and $error['varalle'] == '') {

				# Poistetaan k�ytt�j�n kesken, ett� osataan vied� varastoon
				$query = "UPDATE kuka SET kesken = 0 where yhtio = '$kukarow[yhtio]' and kuka = '$kukarow[kuka]'";
				$res = pupe_query($query);

				# Haetaan saapumiset?
				$saapumiset = hae_saapumiset($alusta_tunnus);

				# P�ivitet��n hyllypaikat
				$paivitetyt_rivit = paivita_hyllypaikat($alusta_tunnus, $hyllyalue, $hyllynro, $hyllyvali, $hyllytaso);

				if ($paivitetyt_rivit > 0) {
					# Hylly arrayksi...
					$hylly = array(
						"hyllyalue" => $hyllyalue,
						"hyllynro" => $hyllynro,
						"hyllyvali" => $hyllyvali,
						"hyllytaso" => $hyllytaso);

					# Vied��n varastoon keikka kerrallaan.
					foreach($saapumiset as $saapuminen) {
						# Saako keikan vied� varastoon
						if (saako_vieda_varastoon($saapuminen, 'kalkyyli', 1) == 1) {
							# Ei saa vied� varastoon, skipataan?
							$varastovirhe = true;
							continue;
						} else {
							vie_varastoon($saapuminen, $alusta_tunnus, $hylly);

							if (isset($komento) and $komento['Tavaraetiketti'] != "") {

								$suuntalavat = array($alusta_tunnus);
								$otunnus = $saapuminen;

								require('tilauskasittely/tulosta_tavaraetiketti.inc');
							}
						}
					}
					# Jos kaikki meni ok
					if (isset($varastovirhe)) {
						$error['varalle'] .= t("Virhe varastoonvienniss�")."<br>";
					} else {
						echo "<META HTTP-EQUIV='Refresh'CONTENT='2;URL=alusta.php'>";
						exit;
					}
				}
				else {
					$error['varalle'] .= t("Yht��n tuotetta ei l�ytynyt suuntalavalta")."<br>";
				}
			}
			else {
				$error['varalle'] .= t("Virheellinen varmistukoodi tai hyllypaikka ei ole reservipaikka")."<br>";
			}
		}
		else {
			$error['varalle'] .= t("Varmistukoodi ei voi olla tyhj�")."<br>";
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
			<th>",t("Ker�yspaikka", $browkieli),"</th>
			<td><input type='text' name='tuotepaikka' value='{$hyllyalue} {$hyllynro} {$hyllyvali} {$hyllytaso}' /></td>
		</tr>
		<tr>
			<th>",t("Koodi", $browkieli),"</th>
			<td colspan='2'><input type='text' name='koodi' value='{$koodi}' size='7' />
		</tr>
		<tr>
			<th>",t("Tulosta tavaraetiketit"),"</th>
			<td colspan='2'><select name='komento[Tavaraetiketti]'>";

			$query = "	SELECT *
						FROM kirjoittimet
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND komento NOT IN ('edi','email')
						ORDER BY kirjoitin";
			$kires = pupe_query($query);

			echo "<option value=''>",t("Ei kirjoitinta"),"</option>";

			while ($kirow = mysql_fetch_assoc($kires)) {
				$sel = $kirow['tunnus'] == $kukarow['kirjoitin'] ? " selected" : "";
				echo "<option value='{$kirow['komento']}'{$sel}>{$kirow['kirjoitin']}</option>";
			}

			echo "</select></td>
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
