<?php
	require ("inc/parametrit.inc");

	echo "<font class='head'>".t("Kopioi käyttöoikeuksia").":</font><hr>";

		if ($copyready!='') // ollaan painettu kopio submittia
		{
			echo "<font class='message'>".t("Kopioitiin oikeudet")." $fromkuka ($fromyhtio) --> $tokuka ($toyhtio)</font><br><br>";

			$query = "SELECT * FROM oikeu where kuka='$fromkuka' and yhtio='$fromyhtio'";
			$kukar = mysql_query($query) or pupe_error($query);

			$query = "delete from oikeu where kuka='$tokuka' and yhtio='$toyhtio'";
			$delre = mysql_query($query) or pupe_error($query);

			while ($row = mysql_fetch_array($kukar))
			{
				$query = "insert into oikeu values ('$tokuka','$row[sovellus]','$row[nimi]','$row[alanimi]','$row[paivitys]','$row[lukittu]','$row[nimitys]','$row[jarjestys]','$row[jarjestys2]','','$toyhtio',0)";
				$upres = mysql_query($query) or pupe_error($query);
			}

			$fromkuka='';
			$tokuka='';
			$fromyhtio='';
			$toyhtio='';
		}

		echo "<br><form action='$PHP_SELF' method='post'>";
		echo "<input type='hidden' name='tila' value='copy'>";

		echo "<font class='message'>".t("Keneltä kopioidaan").":</font>";

		// tehdään käyttäjälistaukset

		$query = "SELECT distinct(nimi), kuka FROM kuka WHERE extranet='' ORDER BY nimi";
		$kukar = mysql_query($query) or pupe_error($query);

		echo "<table><tr><th align='left'>".t("Käyttäjä").":</th><td>
		<select name='fromkuka' onchange='submit()'>
		<option value=''>".t("Valitse käyttäjä")."</option>";

		while ($kurow=mysql_fetch_array($kukar))
		{
			if ($fromkuka==$kurow[1]) $select='selected';
			else $select='';

			echo "<option $select value='$kurow[1]'>$kurow[0] ($kurow[1])</option>";
		}

		echo "</select></td></tr>";

		if ($fromkuka!='')
		{
			// tehdään yhtiolistaukset

			$query = "select distinct kuka.yhtio, yhtio.nimi from kuka, yhtio where kuka.kuka='$fromkuka' and kuka.extranet='' and yhtio.yhtio=kuka.yhtio ";
			$yhres = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($yhres) > 1)
			{
				echo "<tr><th align='left'>".t("Yhtio").":</th><td><select name='fromyhtio'>";

				while ($yhrow = mysql_fetch_array ($yhres))
				{
					echo "from $fromyhtio ja $kurow[0]<br> ";
					if ($fromyhtio==$yhrow[0]) $select='selected';
					else $select='';

					echo "<option $select value='$yhrow[yhtio]'>$yhrow[nimi]</option>";
				}

				echo "</select></td></tr>";
			}
			else
			{
				if (mysql_num_rows($yhres) == 1) {
					$yhrow = mysql_fetch_array ($yhres);
					echo "<input type='hidden' name='fromyhtio' value='$yhrow[yhtio]'>";
				}
				else {
					echo "Pahaa tapahtui!";
					exit;
				}
			}
		}

		echo "</table>";

		echo "<br><br><font class='message'>".t("Kenelle kopioidaan").":</font>";

		// tehdään käyttäjälistaukset

		$query = "SELECT distinct(nimi), kuka FROM kuka WHERE extranet='' ORDER BY nimi";
		$kukar = mysql_query($query) or pupe_error($query);

		echo "<table><tr><th align='left'>".t("Käyttäjä").":</th><td>
		<select name='tokuka' onchange='submit()'>
		<option value=''>".t("Valitse käyttäjä")."</option>";

		while ($kurow=mysql_fetch_array($kukar)) {
			if ($tokuka==$kurow[1]) $select='selected';
			else $select='';

			echo "<option $select value='$kurow[1]'>$kurow[0] ($kurow[1])</option>";
		}

		echo "</select></td></tr>";

		if ($tokuka!='') {
			// tehdään yhtiolistaukset

			$query = "select distinct kuka.yhtio, yhtio.nimi from kuka, yhtio where kuka.kuka='$tokuka' and kuka.extranet='' and yhtio.yhtio=kuka.yhtio ";
			$yhres = mysql_query($query) or pupe_error($query);

			if (mysql_num_rows($yhres) > 1) {
				echo "<tr><th align='left'>".t("Yhtio").":</th><td><select name='toyhtio'>";

				while ($yhrow = mysql_fetch_array ($yhres)) {
					if ($toyhtio==$yhrow[0]) $select='selected';
					else $select='';

					echo "<option $select value='$yhrow[yhtio]'>$yhrow[nimi]</option>";
				}

				echo "</select></td></tr>";
			}
			else {
				if (mysql_num_rows($yhres) == 1) {
					$yhrow = mysql_fetch_array ($yhres);
					echo "<input type='hidden' name='toyhtio' value='$yhrow[yhtio]'>";
				}
				else {
					echo "Pahaa tapahtui!";
					exit;
				}
			}
		}

		echo "</table>";

		if (($tokuka!='') and ($fromkuka!='')) {
			echo "<br><br>";
			echo "<input type='submit' name='copyready' value='".t("Kopioi käyttöoikeudet")." $fromkuka --> $tokuka'>";
		}

		echo "</form>";

	require("inc/footer.inc");
?>
