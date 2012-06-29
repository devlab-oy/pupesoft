<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;

if (@include_once("../inc/parametrit.inc"));
elseif (@include_once("inc/parametrit.inc"));

$alusta_tunnus = (int) $alusta_tunnus;
$liitostunnus = (int) $liitostunnus;
$selected_row = (int) $selected_row;

if (isset($submit) and trim($submit) != '') {

	$data = array(
		'alusta_tunnus' => $alusta_tunnus,
		'liitostunnus' => $liitostunnus,
		'selected_row' => $selected_row
	);

	$url = http_build_query($data);

	if ($submit == 'cancel') {
		echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=vahvista_kerayspaikka.php?{$url}'>";
		exit;
	}
}

$res = suuntalavan_tuotteet(array($alusta_tunnus), $liitostunnus, "", "", "", $selected_row);
$row = mysql_fetch_assoc($res);

echo "
	<style type='text/css'>
	<!--
		A, A:visited	{color: #c0c0c0; text-decoration:none;}
		.error		{color: #ff6666;}
	-->
	</style>

	<table border='0'>
		<tr>
			<td><h1>",t("Uusi keräyspaikka", $browkieli),"</h1>
				<form name='uusipaikkaformi' method='post' action=''>
				<table>
					<tr>
						<td>",t("Tuote", $browkieli),"</td>
						<td colspan='3'>{$row['tuoteno']}</td>
					</tr>
					<tr>
						<td>",t("Toim. Tuotekoodi", $browkieli),"</td>
						<td colspan='3'>{$row['toim_tuoteno']}</td>
					</tr>
					<tr>
						<td>",t("Keräyspaikka", $browkieli),"</td>
						<td colspan='3'>{$row['hyllyalue']} {$row['hyllynro']} {$row['hyllyvali']} {$row['hyllytaso']}</td>
					</tr>
					<tr>
						<td>",t("Alue", $browkieli),"</td>
						<td>",t("Nro", $browkieli),"</td>
						<td>",t("Väli", $browkieli),"</td>
						<td>",t("Taso", $browkieli),"</td>
					</tr>
					<tr>
						<td><input type='text' name='hyllyalue' value='' /></td>
						<td><input type='text' name='hyllynro' value='' /></td>
						<td><input type='text' name='hyllyvali' value='' /></td>
						<td><input type='text' name='hyllytaso' value='' /></td>
					</tr>
					<tr>
						<td nowrap>
							<button name='submit' value='submit' onclick='submit();'>",t("Perusta", $browkieli),"</button>
						</td>
						<td nowrap>
							<button name='submit' value='cancel' onclick='submit();'>",t("Takaisin", $browkieli),"</button>
						</td>
						<td colspan='2'>",t("Tee tästä oletuspaikka", $browkieli)," <input type='checkbox' name='oletuspaikka' checked='checked' /></td>
					</tr>
					<tr><td>&nbsp;</td></tr>
				</table>
				<span class='error'>{$error['rivi']}</span>
				<input type='hidden' name='alusta_tunnus' value='{$alusta_tunnus}' />
				<input type='hidden' name='liitostunnus' value='{$liitostunnus}' />
				<input type='hidden' name='selected_row' value='{$selected_row}' />
				</form>
			</td>
		</tr>
	</table>";

require('inc/footer.inc');