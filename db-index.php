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
		$query  = "show index from $table";
		$fields =  mysql_query($query);

		echo "<b>$table</b> (<a href='db.php?table=$table'>table</a> - <a href='db-index.php?table=$table'>index</a>)<br><br>";
		
		echo "<table>";
		echo "<tr><th>Key_name</th><th>Seq_in_index</th><th>Column_name</th><th>Cardinality</th><th>Index_type</th></tr>";

		$boob = 1;

		while ($row=mysql_fetch_array($fields))
		{
			if ($row['Seq_in_index']==1 and $boob==0) {
				echo "<tr><td class='back' colspan='5'><hr noshade></td></tr>";
			}
			
			echo "<tr><td>$row[Key_name]</td><td>$row[Seq_in_index]</td><td>$row[Column_name]</td><td>$row[Cardinality]</td><td>$row[Index_type]</td></tr>";
			
			$boob = 0;
		}

		echo "</table>";
	}

	echo "</td></tr></table>";

	require "inc/footer.inc";
?>
