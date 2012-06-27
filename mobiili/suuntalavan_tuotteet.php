<?php

$_GET['ohje'] = 'off';

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
	$res = suuntalavan_tuotteet(array($alusta_tunnus), $laskurow['liitostunnus'], "tuotepaikka");

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
		.tumma 			{color: #f2f2f2; background-color: #1f4458;}
	-->
	</style>

  	<script type='text/javascript'>
  		$(function() {
			document.getElementById('viivakoodi').focus();

			$('td.selectable').on('click', function() {
				$('td.tumma').removeClass('tumma');

				$(this).parent().children().toggleClass('tumma');
			});
  		});
	</script>

	<body>
		<form method='post' action=''>
			<table class='alusta' border='0'>
				<tr>
					<td colspan='4' class='head'><font class='head'>",t("Suuntalavan tuotteet", $browkieli),"</font><br /><br />
						<table class='inner'>
							<tr>
								<td colspan='4'>
									<font class='menu'>",t("Viivakoodi", $browkieli),"</font>&nbsp;<input type='text' id='viivakoodi' name='viivakoodi' value='' />
								</td>
							</tr>
							<tr>
								<th>
									",t("Tuotenro", $browkieli),"
								</th>
								<th>
									",t("M‰‰r‰", $browkieli),"
								</th>
								<th>
									",t("Yks", $browkieli),"
								</th>
								<th>
									",t("Osoite", $browkieli),"
								</th>
							</tr>";

							foreach ($tuotteet as $tuote) {
								echo "<tr>";
								echo "<td class='selectable' nowrap>{$tuote['tuoteno']}</td>";
								echo "<td class='selectable' nowrap>{$tuote['maara']}</td>";
								echo "<td class='selectable' nowrap>{$tuote['yks']}</td>";
								echo "<td class='selectable' nowrap>{$tuote['osoite']}</td>";
								echo "</tr>";
							}

echo "						<tr>
								<td colspan='4' class='menu'>
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