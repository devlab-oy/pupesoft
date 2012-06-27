<?php

$_GET['ohje'] = 'off';

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

		if (count($return) == 0) {
			$error['alusta'] = "<font class='error'>".t("Alustaa ei löytynyt").".</font>";
		}
	}
}

echo "
<html>
	<head>
	<title>",t("Alusta", $browkieli),"</title>

	<style type='text/css'>
	<!--
		A				{color: #c0c0c0; text-decoration:none;}
		A:hover			{color: #ff0000; text-decoration:none;}
		IMG				{padding:10pt;}
		FONT.info		{font-size:8pt;  font-family:Lucida,Verdana,Helvetica,Arial; color: #c0c0c0;}
		FONT.head		{font-size:15pt; font-family:Lucida,Verdana,Helvetica,Arial; color: #666699; font-weight:bold; letter-spacing: .05em;}
		FONT.menu		{font-size:10pt; font-family:Lucida,Verdana,Helvetica,Arial; color: #666;}
		FONT.error		{font-size:8pt;  font-family:Lucida,Verdana,Helvetica,Arial; color: #ff6666;}
		TD				{padding:3pt; width:50%; height: 100%; text-align: center; background: #eee}
		TD.menu 		{background: #eee}
		TABLE.alusta	{width:500px;}
		TABLE.inner		{width: 100%; padding:7pt; border-width: 1px 1px 1px 1px; /* top right bottom left */ border-style: solid; border-color: #a0a0a0; vertical-align: top; background: #eee; -moz-border-radius: 10pt; -webkit-border-radius: 10pt;}
		INPUT, BUTTON	{font-size:10pt; width:100%}
		SELECT			{width:100%}
	-->
	</style>

	<script type='text/javascript'>
		function setFocus() {
			document.getElementById('alusta').focus();
		}
	</script>

	<meta http-equiv='Pragma' content='no-cache'>
	<meta http-equiv='Content-Type' content='text/html; charset=iso-8859-1'>
	</head>

	<body onload='setFocus();'>
		<form method='post' action=''>
			<table class='alusta' border='0'>
				<tr>
					<td colspan='4' class='head'><font class='head'>",t("Alusta", $browkieli),"</font><br /><br />
						<table class='inner'>
							<tr>
								<td class='menu'>
									<input type='text' id='alusta' name='alusta' value='{$alusta}' />
								</td>
							</tr>
							<tr>
								<td class='menu'>
									<button name='submit' value='submit' onclick='submit();'>OK</button>
									<button name='submit' value='cancel' onclick='submit();'>Lopeta</button>
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
	echo "<input type='hidden' name='sallitaan_eteenpain' value='X' />";
}

echo "		</table>
		</form>
	</body>
</html>";