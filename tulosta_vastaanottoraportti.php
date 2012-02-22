<?php

require ("inc/parametrit.inc");

echo "<font class='head'>".t("Tulosta Vastaanottoraportti")."</font><hr>";

if ($tee == "etsi") {

	$query = "	SELECT *
				FROM lasku
				WHERE yhtio = '{$kukarow["yhtio"]}' and
				tila = 'K' and
				laskunro = '{$keikka}' and
				vanhatunnus = 0 and
				alatila = 'X' and
				kohdistettu = 'X'";
	$res = pupe_query($query);

	if (mysql_num_rows($res) == 1) {
		$laskurow = mysql_fetch_assoc($res);
		$otunnus = $laskurow["tunnus"];
		$kukakutsuu = "KOPIO";
		$tee = "vastaanottoraportinkopio";
		
		require('tilauskasittely/tulosta_vastaanottoraportti.inc');
		$tee="";
	}
	else {
		echo "<font class='error'>Keikkaa $keikka ei löytynyt!</font>";
		$tee="";
	}

}
if ($tee == "") {
	echo "<form action='$PHP_SELF' method='post'>";
	echo "<input type='hidden' name='tee' value='etsi'>";

	echo "<table>";
	echo "<tr>";
	echo "<th>Syötä keikkanumero: </th>";
	echo "<td><input type='text' name='keikka'></td>";
	echo "</tr>";
	echo "</table>";

	echo "<br><input type='submit' value='Etsi keikka'>";
	echo "</form>";
}

require ("inc/footer.inc");
?>