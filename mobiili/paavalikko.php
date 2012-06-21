<?php

if (@include_once("../inc/parametrit.inc"));
elseif (@include_once("inc/parametrit.inc"));

echo "
<html>
	<head>
	<title>Login</title>

	<style type='text/css'>
	<!--
		A				{color: #c0c0c0; text-decoration:none;}
		A:hover			{color: #ff0000; text-decoration:none;}
		IMG				{padding:10pt;}
		BODY			{background:#fff;}
		FONT.info		{font-size:8pt;  font-family:Lucida,Verdana,Helvetica,Arial; color: #c0c0c0;}
		FONT.head		{font-size:15pt; font-family:Lucida,Verdana,Helvetica,Arial; color: #666699; font-weight:bold; letter-spacing: .05em;}
		FONT.menu		{font-size:10pt; font-family:Lucida,Verdana,Helvetica,Arial; color: #666;}
		FONT.error		{font-size:9pt;  font-family:Lucida,Verdana,Helvetica,Arial; color: #ff6666;}
		TD				{padding:3pt; width:50%; height: 100%; -moz-border-radius: 5pt; -webkit-border-radius: 5pt; text-align: center; background: #eee}
		TD.head			{text-align: center; background: #FFF;}
		TABLE.main		{background: #FFF}
		TABLE.paavalikko {padding:7pt; border-width: 1px 1px 1px 1px; /* top right bottom left */ border-style: solid; border-color: #a0a0a0; vertical-align: top; background: #eee; -moz-border-radius: 10pt; -webkit-border-radius: 10pt;}
		TABLE.paavalikko {width: 100%;}
		INPUT, BUTTON	{font-size:10pt; width:100%}
	-->
	</style>

	<meta http-equiv='Pragma' content='no-cache'>
	<meta http-equiv='Content-Type' content='text/html; charset=iso-8859-1'>
	</head>

	<body>

		<table class='main' border='0'>
			<tr>
				<td class='head'><font class='head'>",t("P‰‰valikko", $browkieli),"</font><br><br>

					<table class='paavalikko'>
						<tr>
							<td>
								<button value=''>Siirto</button>
							</td>
							<td>
								<form name='tulouta' target='_top' action='tulouta.php' method='post'>
									<button value='' onclick='submit();'>Tulouta</button>
								</form>
							</td>
						</tr>
						<tr>
							<td>
								<button value=''>Inventointi</button>
							</td>
							<td>
								<button value=''>Tuki</button>
							</td>
						</tr>
						<tr>
							<td colspan='2'>
								<button value=''>Lopeta</button>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
	</body>
</html>";