<?php

	require "inc/parametrit.inc";

echo "<font class='head'>Tallenna taulu: </font><hr>";



	$date 	= date("Y-m-d_H.i.s");

	$dirri	= "/data/out/";

	$file	= $table.".".$date.".txt";

	$var1	= '';

	



if ($tee=='UV') {

	

	//tarkastellaan rajataanko hakua kentällä

	if ($kentta1 != '')  {

			$var1	= $kentta1;

			$var1	.= " LIKE '";

			$var1	.= $arvo1;

			$var1	.= "' and ";

	}



	//tsekataan order

	if ($order != '')  {

			$ord	.= " ORDER BY ";

			$ord	.= $order;

	}

	

	

	



	//sortataan noista kentistä sopiva lista, tiputettaan aina tunnus ja yhtio pois

	//pari jokeria

	$i	= 0; 		//lasketaan pilkun paikkaa (ei laiteta ekalla kierroksella)



	$query  = "show columns from $table";

	$result =  mysql_query($query);



	while ($row=mysql_fetch_array($result)) {

		$y	= 1;

	

		if ($row[0]=='yhtio') { 

			$y=0; 

			}

		if ($row[0]=='tunnus') { 

			$y=0; 

			}





		if ($y==1) {

			if ($i==0){

				$fields	= $row[0];

			}

			if ($i>=1) {

				$fields	.= ", ".$row[0];

			}

		$i++;

		}

	}

	

	

	//sitten kysellään haetun kannan tiedot ja pusketaan ne tiedostoon

	$r	= 0;	//raportoidaan montako riviä vietiin



	$fh = fopen("/data/out/".$file, "a");



	$query2  = "SELECT $fields FROM $table WHERE $var1 yhtio='$kukarow[yhtio]' $ord";

	$result2 =  mysql_query($query2);

	

	while ($row2=mysql_fetch_array($result2)) {

	$i	= 0;



		$query  = "show columns from $table";

		$result =  mysql_query($query);

		

			while ($row=mysql_fetch_array($result)) {

				$sarake = $row[0];





				$y	= 1;

	

				if ($row[0]=='yhtio') { 

					$y=0; 

				}

				if ($row[0]=='tunnus') { 

					$y=0; 

				}



		

				if ($y==1) {

					if ($i==0){

						$rivi	= $row2[$sarake].";";

					}

					if ($i>=1) {

						$rivi .= $row2[$sarake].";";

					}

				$i++;

				}

		}	



		$rivivie = $rivi;

		$rivivie .= "MUUTA";

		$rivivie .= "\r\n";

		$r++;



		if (fwrite($fh, $rivivie) === FALSE) die("Tiedoston kirjoitus epäonnistui!");	



		



	}

	// suljetaan tiedosto

	fclose($fh);





	echo "<font class='info'>Vietiin taulu $table tiedostoon $dirri$file</font><hr><br>";

	echo "<font class='info'>Vietiin yhteensä $r riviä.</font><hr><br>";

	if ($kentta1!='') echo "<font class='info'>Rajattu sarake $kentta1 arvolla $arvo1</font><hr><br>";

	if ($order!='') echo "<font class='info'>Järjestetty sarakkeen $order mukaan.</font><hr><br>";



		



}

if ($tee=='') {

	echo "<form maction='$PHP_SELF' method='post'>

	<table border='0'>

	<tr><th>Vie taulu</th><td><select name='table'>

		<option value='tuote'>Tuote</option>

		<option value='tuotepaikat'>Tuotepaikat</option>

		<option value='tuoteperhe'>Tuoteperheet</option>

		</select></td>

	</tr>

	<input type='hidden' name = 'tee' value='UV'>

	<tr><th>Sarake 1</th><td><input type='text' name = 'kentta1' value='$kentta1'></td></tr>

	<tr><th>Arvo 1</th><td><input type='text' name = 'arvo1' value='$arvo1'></td></tr>

	<tr><th>Order by</th><td><input type='text' name = 'order' value='$order'></td></tr>

	<th></th><td><input type='submit' value='Tallenna'></td>

	</table>

	</form>";



}





require("inc/footer.inc");

?>