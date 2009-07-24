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

	preg_match_all("/.*?\/\*(.*?(_COLOR|_BACKGROUND))\*\//", $yhtiorow['css'], $varitmatch);

	$varit = array();

	for($i=0; $i<count($varitmatch[0]); $i++) {
		if (!isset($varit[$varitmatch[1][$i]])) {
			$varit[$varitmatch[1][$i]] = $varitmatch[0][$i];
		}
	}

	ksort($varit);

	echo "
	T‰ss‰ n‰kee miten formit k‰ytt‰ytyy:<hr>
	<form action='#'>
	<select>
	<option>1 - ensimm‰inen</option>
	<option>2 - toinen</option>
	<option>3 - kolmas</option>
	</select>
	<input type='text'>
	<input type='checkbox'	name='1'>
	<input type='radio' 	name='2'>
	<input type='radio' 	name='2'>
	<input type='submit' value='Normaali submit-nappula'>
	</form> pit‰isi pysy‰ nipussa ilman suurempia aukkoja ja rivinvaihtoja.

	<br>
	<br>

	Muutama nappula:<hr>
	<input type='button' value='input type=button'>
	<button class='valinta'>valinta:class button-nappula</button>
	<button class='valinta'>normaali button-nappula</button>

	<br>
	<br>

	Taulukko:<hr>
	<table>
	<tr><th>TH</th><th>Normi headeri</th></tr>
	<tr><th><a href='#'>TH linkki</a></th><th><a href='#'>normi linkki th solussa</th></tr>
	<tr><td>TD</td><td>Normi solu</td></tr>
	<tr><td><a href='#'>TD linkki</a></td><td><a href='#'>normi linkki td solusssa</a></td></tr>
	<tr><td><a class='td' href='#'>TD linkki class='td'</a></td><td><a class='td' href='#'>td-luokan linkki td solusssa</a></td></tr>
	<tr class='aktiivi'><td>TD (TR class='aktiivi')</td><td>normi TD mutta TR:ss‰ on hover toiminto</td></tr>
	<tr><td class='back'>TD class='back'</td><td class='back'>tausta sama kuin BODY:ss‰</td></tr>
	<tr><td class='ok'>TD class='ok'</td><td class='ok'>ok teksti‰ normi td solussa</td></tr>
	<tr><td class='error'>TD class='error'</td><td class='error'>error teksti‰ normi td solussa</td></tr>
	<tr><td class='spec'>TD class='spec'</td><td class='spec'>speciaali TD, esim. tausta sama kuin TD:ss‰ mutta fontinv‰ri sama ku TH:ssa</td></tr>
	<tr><td class='tumma'>TD class='tumma'</td><td class='tumma'>speciaali TD esim. fontti ja tausta samanv‰riset kuin TH:ssa</td></tr>
	<tr><td class='liveSearch'>TD class='liveSearch'</td><td class='liveSearch'>k‰ytet‰‰n jossain searchissa</td></tr>
	</table>

	<br>
	Linkit:<hr>
	<div style='width:300px'>
	<p><a href='#'>oletus-linkki</a></p>
	<p><a class='kale' href='#'>class kale linkki</a></p>
	<p><a class='menu' href='#'>class menu linkki</a></p>
	<p><a class='td' href='#'>class td linkki</a></p>
	</div>

	<br>
	Fontit:<hr>
	Default teksti‰: Lorem ipsum dolor sit amet, consectetur adipisicing elit.<br>
	<font class='head'>HEAD teksti‰: Lorem ipsum dolor sit amet, consectetur adipisicing elit.</font><br>
	<font class='message'>MESSAGE teksti‰: Lorem ipsum dolor sit amet, consectetur adipisicing elit.</font><br>
	<font class='error'>ERROR teksti‰: Lorem ipsum dolor sit amet, consectetur adipisicing elit. (Esim. Punainen)</font><br>
	<font class='ok'>OK teksti‰: Lorem ipsum dolor sit amet, consectetur adipisicing elit. (Esim. Kirkkaanvihre‰)</font><br>
	<font class='info'>INFO teksti‰: Lorem ipsum dolor sit amet, consectetur adipisicing elit. </font><br>
	<font class='kaleinfo'>KALEINFO teksti‰: Lorem ipsum dolor sit amet, consectetur adipisicing elit.</font><br>
	<pre>PRE-teksti‰: Lorem ipsum dolor sit amet, consectetur adipisicing elit. T‰m‰ on monospace fontti.</pre>
	<div class='popup' style='visibility:visible'>div class='popup' tulee yleens‰ taulukon p‰‰lle, pit‰‰ olla hyv‰n n‰kˆinen suhteessa muihin v‰reihin</div>

	<br><br><br>";
	
	echo "	<script language='javascript'>
				function variupdate (vari_index) {
					document.getElementById(\"2_\"+vari_index).style.backgroundColor = document.getElementById(\"1_\"+vari_index).value.substring(1);
				}
			</script> ";

	echo "Muuta CSS:n v‰rej‰:";
	echo "<table><form method='post'>";
	echo "<input type='hidden' name='tee' value='update'>";

	foreach($varit as $vari_index => $vari) {
	
		preg_match("/(#[a-f0-9]{3,6});/i", $vari, $varirgb);

		echo "<tr>
				<td>$vari_index</td><td><input type='text' name = 'vari[$vari_index]' value='$varirgb[1]' id='1_$vari_index' onkeyup='variupdate(\"$vari_index\");' onblur='variupdate(\"$vari_index\");'></td>
				<td id='2_$vari_index' style='background-color:$varirgb[0];'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
				<td>$varirgb[0]</td></tr>";
	}

	echo "</table><input type='submit' value='P‰ivit‰'></form><br><br><br><br>";
	
	require("inc/footer.inc");

?>