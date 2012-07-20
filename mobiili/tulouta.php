<?php
echo "<meta name='viewport' content='width=device-width,height=device-height, user-scalable=no'/>";
$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;

if (@include_once("../inc/parametrit.inc"));
elseif (@include_once("inc/parametrit.inc"));

if (isset($submit) and trim($submit) == 'cancel') {
	echo "<META HTTP-EQUIV='Refresh'CONTENT='0;URL={$palvelin2}mobiili'>";
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

include("kasipaate.css");
echo "
	<body>
		<form method='post' action=''>
			<table border='0'>
				<tr>
					<td><h1>",t("Tulouta", $browkieli),"</h1>
						<table>
							<tr>
								<td>",t("Tulotyyppi", $browkieli),"</td>
							</tr>
							<tr>
								<td>
									<select name='tulotyyppi' size='4'>
										<option value='suuntalava'>",t("ASN / Suuntalava", $browkieli),"</option>
									</select>
								</td>
							</tr>
							<!--
							<tr>
								<td>
									<button value='wat'>",t("Suuntalavat", $browkieli),"</button>
								</td>
							</tr>
							-->
							<tr>
								<td>
									<button name='submit' value='submit' onclick='submit();'>",t("Valitse", $browkieli),"</button>
									<button name='submit' value='cancel' onclick='submit();'>",t("Takaisin", $browkieli),"</button>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr><td>&nbsp;</td></tr>
				<tr>
					<td>{$error['tulotyyppi']}</td>
				</tr>
			</table>
		</form>
	</body>";

#require('inc/footer.inc');