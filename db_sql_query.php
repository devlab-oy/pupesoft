<?php

	require "inc/parametrit.inc";
	
	if ($tee == "AJA") {
		$sqlhaku = "SELECT ".implode(",", $kentat)." FROM $table WHERE yhtio='$kukarow[yhtio]' and osasto='10'";
		$result = mysql_query($sqlhaku) or die ("<font class='error'>".mysql_error()."</font>");
		
		echo "<font class='message'>".t("Haun tulos")." ".mysql_num_rows($result)." ".t("rivi‰").".</font><br>";

		echo "<pre>";

		for ($i=0; $i<mysql_num_fields($result); $i++) {
			echo mysql_field_name($result,$i)."\t";
		}
		echo "\n";

		while ($row = mysql_fetch_array($result)) {

			for ($i=0; $i<mysql_num_fields($result); $i++) {

				// desimaaliluvuissa muutetaan pisteet pilkuiks...
				if (mysql_field_type($result, $i) == 'real') {
					echo str_replace(".",",", $row[$i])."\t";
				}
				else {
					echo "$row[$i]\t";
				}
			}
			echo "\n";
		}
		echo "</pre>";
	}

	echo "<table cellpadding='5'><tr><td valign='top' class='back'>";

	$query  = "show tables from $dbkanta";
    $result =  mysql_query($query);

	while ($row=mysql_fetch_array($result))
	{
		echo "<a href='$PHP_SELF?table=$row[0]'>$row[0]</a><br>";
	}

	echo "</td><td class='back' valign='top'>";

	if ($table!='') {
		$query  = "show columns from $table";
		$fields =  mysql_query($query);

		echo "<b>$table</b> (<a href='db.php?table=$table'>table</a> - <a href='db-index.php?table=$table'>index</a>)<br><br>";
		echo "<table>";
		echo "<tr><th>Kentt‰</th><th>Ruksi</th></tr>";
		echo "<form name='sql' action='$PHP_SELF' method='post' autocomplete='off'>";
		echo "<input type='hidden' name='table' value='$table'>";
		echo "<input type='hidden' name='tee' value='AJA'>";
		
		$kala = array();

		while ($row = mysql_fetch_array($fields)) {
			//tehd‰‰n array, ett‰ saadaan sortattua nimen mukaan..
			if ($kentat[$row[0]] == $row[0]) {
				$chk = "CHECKED";
			}
			else {
				$chk = "";
			}
			
			array_push($kala,"<tr><td>$row[0]</td><td><input type='checkbox' name='kentat[$row[0]]' value='$row[0]' $chk></td></tr>");
		}

		sort($kala);
		
		foreach ($kala as $rivi) {
			echo "$rivi";
		}
		
		echo "</table>";
		echo "<input type='submit' value='".t("Suorita")."'>";
		echo "</form>";
	}

	echo "</td></tr></table>";

	require "inc/footer.inc";
	
?>