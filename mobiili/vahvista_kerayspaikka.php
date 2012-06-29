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

if (isset($submit) and trim($submit) != '') {

	$data = array(
		'alusta_tunnus' => $alusta_tunnus,
		'liitostunnus' => $liitostunnus
	);

	$url = http_build_query($data);

	if ($submit == 'cancel') {
		echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=suuntalavan_tuotteet.php?{$url}'>";
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
			<td><h1>",t("Vahvista ker‰yspaikka", $browkieli),"</h1>
				<form name='vahvistaformi' method='post' action=''>
				<table>
					<tr><td colspan='3'>&nbsp;</td></tr>
					<tr>
						<td>",t("Tuote", $browkieli),"</td>
						<td colspan='2'>{$row['tuoteno']}</td>
					</tr>
					<tr>
						<td>",t("Toim. Tuotekoodi", $browkieli),"</td>
						<td colspan='2'>{$row['toim_tuoteno']}</td>
					</tr>
					<tr>
						<td>",t("M‰‰r‰", $browkieli),"</td>
						<td><input type='text' name='maara' value='' size='7' />
						<td>{$row['varattu']} {$row['yksikko']}</td>
					</tr>
					<tr>
						<td>",t("Ker‰yspaikka", $browkieli),"</td>
						<td colspan='2'>{$row['hyllyalue']} {$row['hyllynro']} {$row['hyllyvali']} {$row['hyllytaso']}</td>
					</tr>
					<tr>
						<td>",t("Koodi", $browkieli),"</td>
						<td colspan='2'><input type='text' name='maara' value='' size='7' />
					</tr>
					<tr>
						<td nowrap>
							<button name='submit' value='submit' onclick='submit();'>",t("Vahvista", $browkieli),"</button>
						</td>
						<td nowrap>
							<button name='submit' value='cancel' onclick='submit();'>",t("Takaisin", $browkieli),"</button>
						</td>
						<td>
							<button name='submit' value='new' onclick='submit();'>",t("Uusi ker‰yspaikka", $browkieli),"</button>
						</td>
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