<?php

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
	$error['alusta'] = "<font class='error'>".t("Sy�t� alustan SSCC").".</font>";
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

		echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=suuntalavan_tuotteet.php{$url}&oletuspaikat=true'>";
		exit;
	}

	if ($submit == 'submit') {
		$return = etsi_suuntalava_sscc(trim($alusta));

		$valinta = "Valitse";

		if (count($return) == 0) {
			$error['alusta'] = "<font class='error'>".t("Alustaa ei voida viel� ottaa k�sittelyyn. Hae uudestaan.").".</font>";
		}
	}
}

echo "<div class='header'><h1>",t("ALUSTA", $browkieli),"</h1></div>";

echo "<div class='main'>
	<form method='post' action=''>
	<table>
		<tr>
			<th>ALUSTA</th>
		</tr>
		<tr>
			<td>
			<input type='text' id='alusta' name='alusta' value='{$alusta}' />
			</td>
		</tr>
		<tr>
			<td colspan='4' class='back'>{$error['alusta']}</td>
		</tr>
	</table>";

if (isset($return) and count($return) > 0) {

	echo "<table id='saapumiset'>";

	foreach ($return as $row) {
		echo "<tr>";
		echo "<th nowrap>",t("Saapuminen", $browkieli),"</th>";
		echo "<td nowrap>{$row['saapuminen_nro']}";
		echo "</tr><tr>";
		echo "<th nowrap>",t("Nimi", $browkieli),"<br>",t("Toim.nro", $browkieli),"</th>";
		echo "<td nowrap>{$row['nimi']}<br>";
		echo "{$row['toimittajanro']}</td>";
		echo "</tr><tr>";
		echo "<th nowrap>",t("Var / Koh", $browkieli),"</th>";
		echo "<td nowrap>{$row['varastossa']} / {$row['kohdistettu']}</td>";
		echo "</tr>";
		echo "<tr><td colspan=2><hr></td></tr>";
	}
	echo "<input type='hidden' name='alusta_chk' value='{$alusta}' />";
	echo "<input type='hidden' name='alusta_tunnus' value='{$return[0]['suuntalava']}' />";
	echo "<input type='hidden' name='liitostunnus' value='{$return[0]['liitostunnus']}' />";
}

echo "</table>";
echo "</div>";

echo "<div class='controls'>
	<button name='submit' value='submit' onclick='submit();'>",t($valinta, $browkieli),"</button>
	<button class='right' name='submit' id='takaisin' value='cancel' onclick='submit();'>",t("Takaisin", $browkieli),"</button>
	</form>
</div>";

# Autofocus opera mobileen
echo "<input type='button' id='myHiddenButton' visible='false' onclick='javascript:doFocus();' width='1px' style='display:none'>";
echo "<script type='text/javascript'>

	function doFocus() {
	        var focusElementId = 'alusta';
	        var textBox = document.getElementById(focusElementId);
	        textBox.focus();
	    }

	function clickButton() {
	   document.getElementById('myHiddenButton').click();
	}

	if(!document.getElementById('saapumiset')) {
   		setTimeout('clickButton()', 500);
	}

</script>
";

require('inc/footer.inc');