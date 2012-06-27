<?php

if (@include_once("../inc/parametrit.inc"));
elseif (@include_once("inc/parametrit.inc"));

if (isset($submit) and trim($submit) == 'cancel') {
	echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=paavalikko.php'>";
	exit;
}

$error = array(
	'tulotyyppi' => '',
);

if (isset($submit) and trim($submit) == 'submit' and isset($tulotyyppi) and trim($tulotyyppi) != '') {

	if ($tulotyyppi == 'suuntalava') {
		echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL=alusta.php'>";
		exit;
	}
}

if (isset($submit) and trim($submit) == 'submit') {
	if ($tulotyyppi == '') $error['tulotyyppi'] = "<font class='error'>".t("Valitse tulotyyppi")."!</font>";
}



echo "
<html>
	<head>
	<title>",t("Tulouta", $browkieli),"</title>

	<style type='text/css'>
	<!--
		A				{color: #c0c0c0; text-decoration:none;}
		A:hover			{color: #ff0000; text-decoration:none;}
		IMG				{padding:10pt;}
		FONT.info		{font-size:8pt;  font-family:Lucida,Verdana,Helvetica,Arial; color: #c0c0c0;}
		FONT.head		{font-size:15pt; font-family:Lucida,Verdana,Helvetica,Arial; color: #666699; font-weight:bold; letter-spacing: .05em;}
		FONT.menu		{font-size:10pt; font-family:Lucida,Verdana,Helvetica,Arial; color: #666;}
		FONT.error		{font-size:8pt;  font-family:Lucida,Verdana,Helvetica,Arial; color: #ff6666;}
		TD				{padding:3pt; width:50%; height: 100%; -moz-border-radius: 5pt; -webkit-border-radius: 5pt; text-align: center; background: #eee}
		TD.menu 		{background: #eee}
		TABLE.inner		{padding:7pt; border-width: 1px 1px 1px 1px; /* top right bottom left */ border-style: solid; border-color: #a0a0a0; vertical-align: top; background: #eee; -moz-border-radius: 10pt; -webkit-border-radius: 10pt;}
		TABLE.inner		{width: 100%;}
		INPUT, BUTTON	{font-size:10pt; width:100%}
		SELECT			{width:100%}
	-->
	</style>

	<meta http-equiv='Pragma' content='no-cache'>
	<meta http-equiv='Content-Type' content='text/html; charset=iso-8859-1'>
	</head>

	<body>
		<form method='post' action=''>
			<table class='tulouta' border='0'>
				<tr>
					<td class='head'><font class='head'>",t("Tulouta", $browkieli),"</font><br /><br />
						<table class='inner'>
							<tr>
								<td class='menu'>
									<font class='menu'>",t("Tulotyyppi", $browkieli),"</font>
								</td>
							</tr>
							<tr>
								<td class='menu'>
									<select name='tulotyyppi' size='4'>
										<option value='suuntalava'>ASN / Suuntalava</option>
									</select>
								</td>
							</tr>
							<tr>
								<td class='menu'>
									<button value='wat'>Suuntalavat</button>
								</td>
							</tr>
							<tr>
								<td class='menu'>
									<button name='submit' value='submit' onclick='submit();'>OK</button>
									<button name='submit' value='cancel' onclick='submit();'>Takaisin</button>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td class='back'>{$error['tulotyyppi']}</td>
				</tr>
			</table>
		</form>
	</body>
</html>";