<?php

$_GET['ohje'] = 'off';
$_GET["no_css"] = 'yes';

$mobile = true;

if (@include_once("../inc/parametrit.inc"));
elseif (@include_once("inc/parametrit.inc"));

echo "
	<style type='text/css'>
	<!--
		A, A:visited	{color: #c0c0c0; text-decoration:none;}
		.error		{color: #ff6666;}
	-->
	</style>

	<body>
		<table border='0'>
			<tr>
				<td><h1>",t("P‰‰valikko", $browkieli),"</h1>
					<table>
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
	</body>";