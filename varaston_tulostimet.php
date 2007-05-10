<?php

require "inc/parametrit.inc";

echo "<font class='head'>".t("Varaston tulostusalueet")."</font><hr>";

// tehd‰‰n varastopaikat..

if ($tee=='update')
{
	$query  = "	update varaston_tulostimet set
				varasto			= '$varasto',
				prioriteetti	= '$prioriteetti',
				alkuhyllyalue	= '$alkuhyllyalue',
				alkuhyllynro	= '$alkuhyllynro',
				loppuhyllyalue	= '$loppuhyllyalue',
				loppuhyllynro	= '$loppuhyllynro',
				printteri		= '$printteri'
				where yhtio	='$kukarow[yhtio]' and tunnus='$tunnus'";
	$result = mysql_query($query) or pupe_error($query);

	echo "<h2>".t("Varaston tulostusalueet p‰ivitetty")."</h2>";

	$tee=''; // n‰ytet‰‰n printerit
}


// poistetaan varastopaikat

if ($tee=='poista')
{
	$query = "delete from varaston_tulostimet where yhtio='$kukarow[yhtio]' and tunnus='$tunnus'";
	$result = mysql_query($query) or pupe_error($query);

	$tee = ''; // n‰ytet‰‰n printterit
}

// tehd‰‰n uusi varastopaikat

if ($tee=='uusi')
{
	$query = "insert into varaston_tulostimet (yhtio) values ('$kukarow[yhtio]')";
	$result = mysql_query($query) or pupe_error($query);
	$tunnus = mysql_insert_id($link);

	$tee='edit'; // menn‰‰n muokkausruutuun
}


// n‰ytet‰‰n muokkausruutu

if ($tee=='edit')
{
	$query  = "select * from varaston_tulostimet where yhtio='$kukarow[yhtio]' and tunnus='$tunnus'";
	$result = mysql_query($query) or pupe_error($query);
	$row    = mysql_fetch_array($result);

	echo "<form method='post' action='$PHP_SELF'>
	<input type='hidden' name='tee' value='update'>
	<input type='hidden' name='tunnus' value='$row[tunnus]'>";

	echo "<table>\n";

	for ($i=1; $i<mysql_num_fields($result)-1; $i++)
	{
		if (substr(mysql_field_name($result,$i),0,9)=='printteri')
		{

			echo "<tr><th>";
			echo t(mysql_field_name($result,$i));
			echo "</th><td>";

			$query = "select * from kirjoittimet where yhtio='$kukarow[yhtio]'";
			$kires = mysql_query($query) or pupe_error($query);

			echo "<select name='".mysql_field_name($result,$i)."'>";
			echo "<option value=''>".t("Ei kirjoitinta")."</option>";

			while ($kirow=mysql_fetch_array($kires))
			{
				if ($kirow["tunnus"]==$row[$i]) $select='SELECTED';
				else $select = '';

				echo "<option value='$kirow[tunnus]' $select>$kirow[kirjoitin]</option>";
			}

			echo "</select>";

			echo "</td></tr>\n";
		}
		
		elseif (substr(mysql_field_name($result,$i),0,9)=='varasto')
		{

			echo "<tr><th>";
			echo t(mysql_field_name($result,$i));
			echo "</th><td>";

			$query = "select * from varastopaikat where yhtio='$kukarow[yhtio]'";
			$kires = mysql_query($query) or pupe_error($query);

			echo "<select name='".mysql_field_name($result,$i)."'>";

			while ($kirow=mysql_fetch_array($kires))
			{
				if ($kirow["tunnus"]==$row[$i]) $select='SELECTED';
				else $select = '';

				echo "<option value='$kirow[tunnus]' $select>$kirow[nimitys]</option>";
			}

			echo "</select>";

			echo "</td></tr>\n";
		}
		
		
		else
		{
			echo "<tr><th>".t(mysql_field_name($result,$i))."</th><td><input type='text' name='".mysql_field_name($result,$i)."' value='$row[$i]'></td></tr>\n";
		}
	}

	echo "</table>";

	echo "<input type='submit' value='".t("P‰ivit‰")."'></form>";
}



// n‰ytet‰‰n kaikki yhtion varastopaikat...

if ($tee=='')
{
	$query  = "select * from varaston_tulostimet where yhtio='$kukarow[yhtio]' order by varasto, prioriteetti, alkuhyllyalue";
	$result = mysql_query($query) or pupe_error($query);


	echo "<table border='0'><tr>";

	for ($i=1; $i<mysql_num_fields($result)-1; $i++)
	{
		echo "<th>";
		echo mysql_field_name($result,$i);
		echo "</th>";
	}

	echo "<th colspan='2'></th></tr>";

	while ($row=mysql_fetch_array($result))
	{
		echo "<tr>";

		for ($i=1; $i<mysql_num_fields($result)-1; $i++)
		{
			if (substr(mysql_field_name($result,$i),0,9)=='printteri')
			{
				$query = "select * from kirjoittimet where yhtio='$kukarow[yhtio]' and tunnus='$row[$i]'";
				$kires = mysql_query($query) or pupe_error($query);
				$kirow = mysql_fetch_array($kires);
				echo "<td>$kirow[kirjoitin]</td>";
			}
			elseif (mysql_field_name($result,$i)=='varasto')
			{
				$query = "select * from varastopaikat where yhtio='$kukarow[yhtio]' and tunnus='$row[$i]'";
				$kires = mysql_query($query) or pupe_error($query);
				$kirow = mysql_fetch_array($kires);
				echo "<td>$kirow[nimitys]</td>";
			}
			else
			{
				echo "<td>$row[$i]</td>";
			}
		}

		echo "<form method='post' action='$PHP_SELF'>
		<input type='hidden' name='tee' value='edit'>
		<input type='hidden' name='tunnus' value='$row[tunnus]'>
		<td><input type='submit' value='".t("Muokkaa")."'></td></form>";

		echo "<SCRIPT LANGUAGE=JAVASCRIPT>
					function verify(){
							msg = '".t("Haluatko todella poistaa t‰m‰n tietueen?")."';
							return confirm(msg);
					}
			</SCRIPT>";


		echo "<form method='post' action='$PHP_SELF' onSubmit = 'return verify()'>
		<input type='hidden' name='tee' value='poista'>
		<input type='hidden' name='tunnus' value='$row[tunnus]'>
		<td><input type='submit' value='".t("Poista")."'></td></form>";

		echo "</tr>";
	}

	echo "</table><br><br>";

	echo "<form method='post' action='$PHP_SELF'>
	<input type='hidden' name='tee' value='uusi'>
	<input type='submit' value='".t("Tee uusi tulostusalue")."'>
	</form>";
}

require ("inc/footer.inc");

?>