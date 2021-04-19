<?php
	require "inc/parametrit.inc";

	echo "<table cellpadding='5'><tr><td valign='top' class='back'>";

	$query  = "show tables from $dbkanta";
    $result =  mysql_query($query);

	while ($row=mysql_fetch_array($result))
	{
		echo "<a href='$PHP_SELF?table=$row[0]'>$row[0]</a><br>";
	}

	echo "</td><td class='back' valign='top'>";

	if ($table!='')
	{
		$query  = "show columns from $table";
		$fields =  mysql_query($query);

		echo "<b>$table</b> (<a href='db.php?table=$table'>table</a> - <a href='db-index.php?table=$table'>index</a>)<br><br>";
		echo "<table>";
		echo "<tr><th>field</th><th>type</th><th>null</th><th>key</th><th>default</th><th>extra</th></tr>";

		$kala = array();

		while ($row=mysql_fetch_array($fields))
		{
			//tehd‰‰n array, ett‰ saadaan sortattua nimen mukaan..
			array_push($kala,"<tr><td>$row[0]</td><td>$row[1]</td><td>$row[2]</td><td>$row[3]</td><td>$row[4]</td><td>$row[5]</td></tr>");
		}

		sort($kala);

		foreach ($kala as $rivi)
		{
			echo "$rivi";
		}

		echo "</table>";
	}

	echo "</td></tr></table>";

	require "inc/footer.inc";
?>
