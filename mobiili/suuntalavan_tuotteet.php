<?php

if (@include_once("../inc/parametrit.inc"));
elseif (@include_once("inc/parametrit.inc"));

if (isset($submit) and trim($submit) != '') {

	if ($submit == 'cancel') {
		echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=alusta.php'>";
		exit;
	}
}

$tuotteet = array();

if (isset($alusta_tunnus)) {
	$res = suuntalavan_tuotteet(array($alusta_tunnus), $laskurow['liitostunnus']);

	$i = 0;

	while ($row = mysql_fetch_assoc($res)) {
		$tuotteet[$i]['tilriv_tunnus'] = $row['tunnus'];
		$tuotteet[$i]['tuoteno'] = $row['tuoteno'];
		$tuotteet[$i]['maara'] = $row['varattu'];
		$tuotteet[$i]['yks'] = $row['yksikko'];
		$tuotteet[$i]['osoite'] = "{$row['hyllyalue']} {$row['hyllynro']} {$row['hyllyvali']} {$row['hyllytaso']}";

		$i++;
	}
}

echo "
<html>
	<head>
	<title>",t("Suuntalavan tuotteet", $browkieli),"</title>

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
		SELECT			{width:100%; font-size:10pt;}
	-->
	</style>

	<meta http-equiv='Pragma' content='no-cache'>
	<meta http-equiv='Content-Type' content='text/html; charset=iso-8859-1'>
	</head>

	<body onload='setFocus();'>
		<form method='post' action=''>
			<table class='alusta' border='0'>
				<tr>
					<td colspan='4' class='head'><font class='head'>",t("Suuntalavan tuotteet", $browkieli),"</font><br /><br />
						<table class='inner'>
							<tr>
								<td class='menu'>
									<font class='menu'>",t("Tuotenro", $browkieli),"</font>&nbsp;
									<font class='menu'>",t("M‰‰r‰", $browkieli),"</font>&nbsp;
									<font class='menu'>",t("Yks", $browkieli),"</font>&nbsp;
									<font class='menu'>",t("Osoite", $browkieli),"</font>
								</td>
							</tr>
							<tr>
								<td>
									<select name='foo' size='4'>";

									foreach ($tuotteet as $tuote) {
										echo "<option value='{$tuote['tilriv_tunnus']}'>";
										echo "{$tuote['tuoteno']}&nbsp;&raquo;&nbsp;";
										echo "{$tuote['maara']}&nbsp;&raquo;&nbsp;";
										echo "{$tuote['yks']}&nbsp;&raquo;&nbsp;";
										echo "<span style='padding-left:10px;'>{$tuote['osoite']}</span>";
										echo "</option>";
									}

echo "								</select>
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


echo "		</table>
		</form>
	</body>
</html>";