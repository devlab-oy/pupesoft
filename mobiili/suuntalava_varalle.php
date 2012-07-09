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
	'varalle' => ''
);

if (isset($submit) and trim($submit) != '') {

	if ($submit == 'submit') {
		if ($koodi != '') {

			# Tarkistetaan hyllypaikka ja varmistuskoodi
			$kaikki_ok = tarkista_varaston_hyllypaikka($hyllyalue, $hyllynro, $hyllyvali, $hyllytaso, $koodi);

			# Jos hyllypaikka ok
			if ($kaikki_ok) {
				echo "hyllypaikka ok";
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

echo "
	<style type='text/css'>
	<!--
		A, A:visited	{color: #c0c0c0; text-decoration:none;}
		.error		{color: #ff6666;}
	-->
	</style>

	<table border='0'>
		<tr>
			<td><h1>",t("Suuntalavavaralle", $browkieli),"</h1>
				<form name='varalleformi' method='post' action=''>
				<table>
					<tr>
						<td>",t("Suuntalava", $browkieli),"</td>
						<td colspan='3'>{$alusta_tunnus}</td>
					</tr>
					<tr>
						<td>",t("Alue", $browkieli),"</td>
						<td>",t("Nro", $browkieli),"</td>
						<td>",t("Väli", $browkieli),"</td>
						<td>",t("Taso", $browkieli),"</td>
					</tr>
					<tr>
						<td><input type='text' name='hyllyalue' value='{$hyllyalue}' /></td>
						<td><input type='text' name='hyllynro' value='{$hyllynro}' /></td>
						<td><input type='text' name='hyllyvali' value='{$hyllyvali}' /></td>
						<td><input type='text' name='hyllytaso' value='{$hyllytaso}' /></td>
					</tr>
					<tr>
						<td>",t("Koodi", $browkieli),"</td>
						<td colspan='2'><input type='text' name='koodi' value='{$koodi}' size='7' />
					</tr>
					<tr>
						<td nowrap>
							<button name='submit' value='submit' onclick='submit();'>",t("OK", $browkieli),"</button>
						</td>
						<td nowrap>
							<button name='submit' value='cancel' onclick='submit();'>",t("Takaisin", $browkieli),"</button>
						</td>
					</tr>
					<tr><td>&nbsp;</td></tr>
				</table>
				<span class='error'>{$error['varalle']}</span>
				<input type='hidden' name='alusta_tunnus' value='{$alusta_tunnus}' />
				<input type='hidden' name='liitostunnus' value='{$liitostunnus}' />
				<input type='hidden' name='selected_row' value='{$selected_row}' />
				</form>
			</td>
		</tr>
	</table>";

require('inc/footer.inc');