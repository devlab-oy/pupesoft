<?php

	require("inc/parametrit.inc");

	if ($tee == "update") {
	
		foreach ($vari as $regvari => $uusivari) {		
			$yhtiorow["css"] = preg_replace("/(.*?):(.*?);(.*?\/\*$regvari\*\/)/i", "\\1: $uusivari; \\3", $yhtiorow["css"]);
			$yhtiorow["css"] = preg_replace("/ {2,}/", " ", $yhtiorow["css"]);
		}
			
		$query = "UPDATE yhtion_parametrit SET css='$yhtiorow[css]' WHERE yhtio='$kukarow[yhtio]'";
		$result = mysql_query($query) or pupe_error($query);
	
		echo "<META HTTP-EQUIV='Refresh'CONTENT='1;URL=$PHP_SELF'>";
		exit;
	}


	echo "
	<font class='head'>CSS-testing:</font><hr><br>";

	preg_match_all("/.*?\/\*((FONT|TAUSTA)VÄRI.*?)\*\//", $yhtiorow['css'], $varitmatch);

	$varit = array();

	for($i=0; $i<count($varitmatch[0]); $i++) {
		if (!isset($varit[$varitmatch[1][$i]])) {
			$varit[$varitmatch[1][$i]] = $varitmatch[0][$i];
		}
	}

	ksort($varit);

	echo "	<script language='javascript'>
				function variupdate (vari_index) {
					document.getElementById(\"2_\"+vari_index).style.backgroundColor = document.getElementById(\"1_\"+vari_index).value.substring(1);
				}
			</script> ";

	echo "Muuta CSS:n värejä:";
	echo "<table><form method='post'>";
	echo "<input type='hidden' name='tee' value='update'>";

	foreach($varit as $vari_index => $vari) {
	
		preg_match("/(#[a-f0-9]{3,6});/i", $vari, $varirgb);

		echo "<tr>
				<td>$vari_index</td><td><input type='text' name = 'vari[$vari_index]' value='$varirgb[1]' id='1_$vari_index' onkeyup='variupdate(\"$vari_index\");' onblur='variupdate(\"$vari_index\");'></td>
				<td id='2_$vari_index' style='background-color:$varirgb[0];'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
				<td>$varirgb[0]</td></tr>";
	}

	echo "</table><input type='submit' value='Päivitä'></form><br><br><br><br>";


	echo "
	Tässä näkee miten logon linkki käyttäytyy (Linkki A.puhdas):<hr><br>
	<a class='puhdas' href='#'><img border='0' src='http://www.pupesoft.com/pupesoft.gif' alt='logo' height='50'></a>

	<br>
	<br>
	<br>

	Tässä näkee miten formit käyttäytyy:<hr>
	<form action='#'>
	<select>
	<option>1 - ensimmäinen</option>
	<option>2 - toinen</option>
	<option>3 - kolmas</option>
	</select>
	<input type='text'>
	<input type='checkbox'	name='1'>
	<input type='radio' 	name='2'>
	<input type='radio' 	name='2'>
	<input type='submit' value='Normaali submit-nappula'>
	</form> pitäisi pysyä nipussa ilman suurempia aukkoja ja rivinvaihtoja.

	<br>
	<br>
	<br>

	Muutama nappula:<hr>
	<input type='button' value='input type=button'>
	<button class='valinta'>valinta:class button-nappula</button>
	<button class='valinta'>normaali button-nappula</button>

	<br>
	<br>
	<br>

	Taulukko:<hr>
	<table>
	<tr><th>TH</th><th>TH</th><th>TH</th></tr>
	<tr><th><a href='#'>TH-linkki, fontinväri sama kuin TH:ssa</a></th><th><a href='#'>TH-linkki, fontinväri sama kuin TH:ssa</th><th><a href='#'>TH-linkki, fontinväri sama kuin TH:ssa</th></tr>
	<tr><td>TD</td><td>TD</td><td>TD</td></tr>
	<tr><td><a class='td' href='#'>td:class linkki</a></td><td><a class='td' href='#'>td:class linkki</a></td><td><a class='td' href='#'>td:class linkki</a></td></tr>
	<tr class='aktiivi'><td>TD (TR.aktiivi) (Tässä on hover toiminto)</td><td>TD (tr aktiivi)</td><td>TD (tr aktiivi)</td></tr>
	<tr><td class='back'>TD.back: Fontinväri sama kuin TD:ssä mutta tausta sama kuin BODY:ssä</td><td class='back'>TD.back</td><td class='back'>TD.back</td></tr>
	<tr><td class='green'>TD.green: Fontti vihreä. Tausta sama kuin TD:ssä</td><td class='green'>TD.green</td><td class='green'>TD.green</td></tr>
	<tr><td class='liveSearch'>TD.liveSearch</td><td class='liveSearch'></td><td class='liveSearch'></td></tr>
	<tr><td class='red'>TD.red: Fontti punainen. Tausta sama kuin TD:ssä</td><td class='red'>TD.red</td><td class='red'>TD.red</td></tr>
	<tr><td class='spec'>TD.spec: Tausta sama kuin TD:ssä mutta fontinväri sama ku TH:ssa</td><td class='spec'>TD.spec</td><td class='spec'>TD.spec</td></tr>
	<tr><td class='tumma'>TD.tumma: Fontti ja tausta samanväriset kuin TH:ssa</td><td class='tumma'>TD.tumma</td><td class='tumma'>TD.tumma</td></tr>
	</table>

	<br>
	<br>
	<br>
	Linkit:<hr>
	<a href='#'>oletus-linkki</a><br><br>
	<a class='kale' href='#'>kale:class linkki</a><br><br>
	<a class='menu' href='#'>menu:class linkki</a><br>
	<a class='puhdas' href='#'>puhdas:class linkki</a><br><br>
	<a class='td' href='#'>td:class linkki</a><br>

	<br>
	<br>
	<br>
	Fontit:<hr>
	Default tekstiä: bla bla bla bla!!!<br>
	<font class='error'>ERROR tekstiä: bla bla bla bla!!! (Esim. Punainen)</font><br>
	<font class='head'>HEAD tekstiä: bla bla bla bla!!!</font><br>
	<font class='info'>INFO tekstiä: bla bla bla bla!!!</font><br>
	<font class='kaleinfo'>KALEINFO tekstiä: bla bla bla bla!!!</font><br>
	<font class='menu'>MENU tekstiä: bla bla bla bla!!!</font><br>
	<font class='message'>MESSAGE tekstiä: bla bla bla bla!!!</font><br>
	<font class='ok'>OK tekstiä: bla bla bla bla!!! (Esim. Kirkkaanvihreä)</font><br>



	<br>
	<br>

	<pre>PRE-tekstiä: bla bla bla bla!!! Tämä on monospace fontti.</pre>

	<br>
	<br>

	<div class='popup' style='visibility:visible'>DIV:POPUP kannataa vaan kattoa, että on hyvän näköinen suhteessa muihin väreihin</div>

	<br>
	<br>";

	require("inc/footer.inc");

?>