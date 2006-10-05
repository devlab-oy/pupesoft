<?php
	require "inc/parametrit.inc";
	echo "<font class='head'>Kopioi tilikartta:</font><hr>";
	if ($tila == 'x')
	{
		$query = "SELECT tunnus FROM tili where yhtio='$kukarow[yhtio]' limit 1";
		$kukar = mysql_query($query) or pupe_error($query);
		if (mysql_num_rows($kukar) > 0) echo "<font class='error'>Yhtiöllä '$kukarow[yhtio]' on jo tilikartta</font><br>";
		else {

			echo "<font class='message'>Kopioitiin tilikartta $fromyhtio --> $kukarow[yhtio]</font><br>";

			$query = "SELECT * FROM tili where yhtio='$fromyhtio'";
			$kukar = mysql_query($query) or pupe_error($query);

			while ($row = mysql_fetch_array($kukar))
			{
				$query = "insert into tili (nimi, sisainen_taso, tilino, ulkoinen_taso, yhtio, oletusalv) values ('$row[nimi]','$row[sisainen_taso]','$row[tilino]','$row[ulkoinen_taso]','$kukarow[yhtio]', '$row[oletusalv]')";
				$upres = mysql_query($query) or pupe_error($query);
			}
		}
		
		$query = "SELECT tunnus FROM taso where yhtio='$kukarow[yhtio]' limit 1";
		$kukar = mysql_query($query) or pupe_error($query);
		if (mysql_num_rows($kukar) > 0) echo "<font class='error'>Yhtiöllä '$kukarow[yhtio]' on jo tilikartantasot</font><br>";
		else {

			echo "<font class='message'>Kopioitiin tilikartantasot $fromyhtio --> $kukarow[yhtio]</font><br>";

			$query = "SELECT * FROM taso where yhtio='$fromyhtio'";
			$kukar = mysql_query($query) or pupe_error($query);

			while ($row = mysql_fetch_array($kukar))
			{
				$query = "insert into taso (tyyppi, laji, taso, nimi, yhtio) values ('$row[tyyppi]','$row[laji]','$row[taso]','$row[nimi]','$kukarow[yhtio]')";
				$upres = mysql_query($query) or pupe_error($query);
			}
		}
		$fromyhtio='';
	}
	else {
		echo "<form action='$PHP_SELF' method='post'>";
		echo "<input type='hidden' name='tila' value='x'>";
		echo "<table>";
		$query = "select yhtio, nimi from yhtio";
		$yhres = mysql_query($query) or pupe_error($query);

		echo "<tr><th align='left'>Yhtiöltä:</th><td><select name='fromyhtio'>";

		while ($yhrow = mysql_fetch_array ($yhres))
		{
			echo "<option $select value='$yhrow[yhtio]'>$yhrow[yhtio]</option>";
		}
		echo "</select></td>";
		echo "<td><input type='submit' value='Kopioi'></td></tr>";
	}
?>
