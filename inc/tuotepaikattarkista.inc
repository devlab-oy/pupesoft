<?php

	if (mysql_field_name($result, $i) == "tuoteno")
	{
		$query = "select * from tuote where yhtio='$kukarow[yhtio]' and tuoteno='$t[$i]'";
		$chktuote = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($chktuote)!=1)
		{
			$virhe[$i] = "".t("Tuotenumero ei löydy!")."";
			$errori = 1;
		}
	}
?>
