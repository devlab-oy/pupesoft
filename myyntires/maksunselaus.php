<?php
	include "inc/parametrit.inc";

	echo "<br><font class='head'>".t("Maksujen selaus")."</font><hr>";

	$query = "SELECT tapvm, summa, selite, tunnus
                         FROM maksu
                         WHERE yhtio ='$kukarow[yhtio]'
			       and tyyppi = 'MU'
			       and maksettu <> '1'
			 ORDER BY tapvm";

	$result = mysql_query($query) or pupe_error($query);

        echo "<table><tr>";

        for ($i = 0; $i < mysql_num_fields($result)-1; $i++) {
                	echo "<th>" . t(mysql_field_name($result,$i))."</th>";
        }
	echo "<th></th></tr>";

        while ($maksurow=mysql_fetch_array ($result)) {

		for ($i=0; $i<mysql_num_fields($result)-1; $i++) {
			echo "<td>$maksurow[$i]</td>";
                }

		echo "</tr></form>";
	}

	echo "</tr></table>";
	echo "</body></html>";
?>
