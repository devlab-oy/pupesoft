<?php
echo "<meta name='viewport' content='width=device-width,height=device-height, user-scalable=no'/>";
$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;
$valinta = "Etsi";

if (@include_once("../inc/parametrit.inc"));
elseif (@include_once("inc/parametrit.inc"));

$error = array(
	'alusta' => '',
);

if (!isset($alusta)) $alusta = '';

if (isset($submit) and trim($submit) == 'submit' and trim($alusta) == '') {
	$error['alusta'] = "<font class='error'>".t("Syötä alustan SSCC").".</font>";
}

if (isset($submit) and trim($submit) != '' and $error['alusta'] == '') {

	if ($submit == 'cancel') {
		echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=tulouta.php'>";
		exit;
	}

	if ($submit == 'submit' and $alusta == $alusta_chk and trim($alusta_tunnus) != '' and trim($liitostunnus) != '') {


		$alusta_tunnus = (int) $alusta_tunnus;
		$liitostunnus = (int) $liitostunnus;

		$url = "?alusta_tunnus={$alusta_tunnus}&liitostunnus={$liitostunnus}";

		echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=suuntalavan_tuotteet.php{$url}'>";
		exit;
	}

	if ($submit == 'submit') {
		$return = etsi_suuntalava_sscc(trim($alusta));

		$valinta = "Valitse";

		if (count($return) == 0) {
			$error['alusta'] = "<font class='error'>".t("Alustaa ei voida vielä ottaa käsittelyyn. Hae uudestaan.").".</font>";
		}
	}
}

include("kasipaate.css");
echo "
	<script type='text/javascript'>
		function setFocus() {
			if (document.getElementById('alusta')) document.getElementById('alusta').focus();
		}
	</script>

	<body onload='setFocus();'>
		<form method='post' action=''>
			<table border='0'>
				<tr>
					<td colspan='4'><h1>",t("Alusta", $browkieli),"</h1>
						<table>
							<tr>
								<td>
									<input type='text' id='alusta' name='alusta' value='{$alusta}' />
								</td>
							</tr>
							<tr>
								<td>
									<button name='submit' value='submit' onclick='submit();'>",t($valinta, $browkieli),"</button>
									<button name='submit' value='cancel' onclick='submit();'>",t("Takaisin", $browkieli),"</button>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td colspan='4' class='back'>{$error['alusta']}</td>
				</tr>";

if (isset($return) and count($return) > 0) {

	echo "<tr>";
	echo "<th nowrap>",t("Saapuminen", $browkieli),"</th>";
	echo "<th nowrap>",t("Toim.nro", $browkieli),"</th>";
	echo "<th nowrap>",t("Nimi", $browkieli),"</th>";
	echo "<th nowrap>",t("Var / Koh", $browkieli),"</th>";
	echo "</tr>";

	foreach ($return as $row) {
		echo "<tr>";
		echo "<td nowrap>{$row['saapuminen_nro']}</td>";
		echo "<td nowrap>{$row['toimittajanro']}</td>";
		echo "<td nowrap>{$row['nimi']}</td>";
		echo "<td nowrap>{$row['varastossa']} / {$row['kohdistettu']}</td>";
		echo "</tr>";
	}

	echo "<input type='hidden' name='alusta_chk' value='{$alusta}' />";
	echo "<input type='hidden' name='alusta_tunnus' value='{$return[0]['suuntalava']}' />";
	echo "<input type='hidden' name='liitostunnus' value='{$return[0]['liitostunnus']}' />";
}

echo "</table></form></body>";

#require('inc/footer.inc');