<?php
	
require("inc/parametrit.inc");

echo "<font class='head'>".t("Tullinimikkeet")."</font><hr>";

/*
	// nämä pitää ajaa jos päivittää uudet tullinimikkeet:

	update tullinimike set su=trim(su);
	update tullinimike set su='' where su='-';
	update tullinimike set su_vientiilmo='NAR' where su='p/st';
	update tullinimike set su_vientiilmo='MIL' where su='1 000 p/st';
	update tullinimike set su_vientiilmo='MIL' where su='1000 p/st';
	update tullinimike set su_vientiilmo='LPA' where su='l alc. 100';
	update tullinimike set su_vientiilmo='LTR' where su='l';
	update tullinimike set su_vientiilmo='KLT' where su='1 000 l';
	update tullinimike set su_vientiilmo='KLT' where su='1000 l';
	update tullinimike set su_vientiilmo='TJO' where su='TJ';
	update tullinimike set su_vientiilmo='MWH' where su='1 000 kWh';
	update tullinimike set su_vientiilmo='MWH' where su='1000 kWh';
	update tullinimike set su_vientiilmo='MTQ' where su='m³';
	update tullinimike set su_vientiilmo='MTQ' where su='m3';
	update tullinimike set su_vientiilmo='GRM' where su='g';
	update tullinimike set su_vientiilmo='MTK' where su='m²';
	update tullinimike set su_vientiilmo='MTK' where su='m2';
	update tullinimike set su_vientiilmo='MTR' where su='m';
	update tullinimike set su_vientiilmo='NPR' where su='pa';
	update tullinimike set su_vientiilmo='CEN' where su='100 p/st';

*/

if ($tee == "muuta") {

	$ok = 0;
	$uusitullinimike1 = trim($uusitullinimike1);
	$uusitullinimike2 = trim($uusitullinimike2);
	
	// katotaan, että tullinimike1 löytyy
	$query = "SELECT cn FROM tullinimike WHERE cn = '$uusitullinimike1' and kieli = '$yhtiorow[kieli]'";
	$result = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($result) != 1 or $uusitullinimike1 == "") {
		$ok = 1;
		echo "<font class='error'>Tullinimike 1 on virheellinen!</font><br>";
	}

	// kaks pitkä tai ei mitään
	if (strlen($uusitullinimike2) != 2) {
		$ok = 1;
		echo "<font class='error'>Tullinimike 2 tulee olla 2 merkkiä pitkä!</font><br>";
	}

	// tää on aika fiinisliippausta 
	if ($ok == 1) echo "<br>";
	
	// jos kaikki meni ok, nii päivitetään
	if ($ok == 0) {

		if ($tullinimike2 != "") $lisa = " and tullinimike2='$tullinimike2'";
		else $lisa = "";
		
		$query = "update tuote set tullinimike1='$uusitullinimike1', tullinimike2='$uusitullinimike2' where yhtio='$kukarow[yhtio]' and tullinimike1='$tullinimike1' $lisa";
		$result = mysql_query($query) or pupe_error($query);
		
		echo sprintf("<font class='message'>Päivitettiin %s tuotetta.</font><br><br>", mysql_affected_rows());

		$tullinimike1 = $uusitullinimike1;
		$tullinimike2 = $uusitullinimike2;
		$uusitullinimike1 = "";
		$uusitullinimike2 = "";
	}
}

if ($tee == "synkronoi") {
	
	$file = fopen("http://www.devlab.fi/softa/referenssitullinimikkeet.sql","r") or die (t("Tiedoston avaus epäonnistui")."!");
	
	echo "<br><br>";
	echo t("Poistetaan vanhat tullinimikkeet")."...<br>";
	
	// Poistetaan nykyiset nimikkeet....
	$query  = "	DELETE FROM tullinimike";
	$result = mysql_query($query) or pupe_error($query);
	
	// Päivitetään tuotteet 2009 - 2010
	$muunnosavaimet = array(
	"03026590"=>"03026560",
	"03026919"=>"03026915",
	"03026985"=>"03026982",
	"03026986"=>"03026982",
	"03034111"=>"03034110",
	"03034113"=>"03034110",
	"03034119"=>"03034110",
	"03034232"=>"03034242",
	"03034238"=>"03034248",
	"03034252"=>"03034242",
	"03034258"=>"03034248",
	"03034311"=>"03034310",
	"03034313"=>"03034310",
	"03034319"=>"03034310",
	"03034411"=>"03034410",
	"03034413"=>"03034410",
	"03034419"=>"03034410",
	"03034511"=>"03034510",
	"03034513"=>"03034510",
	"03034519"=>"03034510",
	"03034611"=>"03034610",
	"03034613"=>"03034610",
	"03034619"=>"03034610",
	"03034931"=>"03034930",
	"03034933"=>"03034930",
	"03034939"=>"03034930",
	"03037590"=>"03037560",
	"03037921"=>"03037920",
	"03037923"=>"03037920",
	"03037929"=>"03037920",
	"03041919"=>"03041901",
	"03042919"=>"03042901",
	"03042969"=>"03042965",
	"03055911"=>"03055910",
	"03055919"=>"03055910",
	"09109960"=>"09109105",
	"22041019"=>"22041093",
	"22041099"=>"22041093",
	"22042110"=>"22042106",
	"22042111"=>"22042111",
	"22042112"=>"22042112",
	"22042113"=>"22042113",
	"22042117"=>"22042117",
	"22042118"=>"22042118",
	"22042119"=>"22042119",
	"22042122"=>"22042122",
	"22042123"=>"22042123",
	"22042124"=>"22042124",
	"22042126"=>"22042126",
	"22042127"=>"22042127",
	"22042128"=>"22042128",
	"22042132"=>"22042132",
	"22042134"=>"22042134",
	"22042136"=>"22042136",
	"22042137"=>"22042137",
	"22042138"=>"22042138",
	"22042142"=>"22042142",
	"22042143"=>"22042143",
	"22042144"=>"22042144",
	"22042146"=>"22042146",
	"22042147"=>"22042147",
	"22042148"=>"22042148",
	"22042162"=>"22042162",
	"22042166"=>"22042166",
	"22042167"=>"22042167",
	"22042168"=>"22042168",
	"22042169"=>"22042169",
	"22042171"=>"22042171",
	"22042174"=>"22042174",
	"22042176"=>"22042176",
	"22042177"=>"22042177",
	"22042178"=>"22042178",
	"22042179"=>"22042179",
	"22042180"=>"22042180",
	"22042181"=>"22042123",
	"22042182"=>"22042111",
	"22042183"=>"22042142",
	"22042184"=>"22042179",
	"22042185"=>"22042180",
	"22042187"=>"22042187",
	"22042188"=>"22042188",
	"22042189"=>"22042189",
	"22042191"=>"22042185",
	"22042192"=>"22042186",
	"22042194"=>"22042190",
	"22042195"=>"22042189",
	"22042196"=>"22042185",
	"22042198"=>"22042187",
	"22042199"=>"22042192",
	"22042911"=>"22042911",
	"22042912"=>"22042912",
	"22042913"=>"22042913",
	"22042917"=>"22042917",
	"22042918"=>"22042918",
	"22042942"=>"22042942",
	"22042943"=>"22042943",
	"22042944"=>"22042944",
	"22042946"=>"22042946",
	"22042947"=>"22042947",
	"22042948"=>"22042948",
	"22042958"=>"22042958",
	"22042962"=>"22042979",
	"22042964"=>"22042979",
	"22042965"=>"22042979",
	"22042971"=>"22042980",
	"22042972"=>"22042980",
	"22042975"=>"22042980",
	"22042977"=>"22042911",
	"22042978"=>"22042912",
	"22042982"=>"22042942",
	"22042983"=>"22042979",
	"22042984"=>"22042980",
	"22042987"=>"22042987",
	"22042988"=>"22042988",
	"22042989"=>"22042989",
	"22042991"=>"22042985",
	"22042992"=>"22042986",
	"22042994"=>"22042990",
	"22042995"=>"22042989",
	"22042996"=>"22042985",
	"22042998"=>"22042987",
	"22042999"=>"22042992",
	"22083032"=>"22083030",
	"22083038"=>"22083030",
	"22083052"=>"22083041",
	"22083058"=>"22083049",
	"22083072"=>"22083061",
	"22083078"=>"22083069",
	"25151220"=>"25151200",
	"25151250"=>"25151200",
	"25151290"=>"25151200",
	"25161210"=>"25161200",
	"25161290"=>"25161200",
	"25202010"=>"25202000",
	"25202090"=>"25202000",
	"25239010"=>"25239000",
	"25239080"=>"25239000",
	"25301010"=>"25301000",
	"25301090"=>"25301000",
	"25309020"=>"25309000",
	"25309098"=>"25309000",
	"26140010"=>"26140000",
	"26140090"=>"26140000",
	"26159010"=>"26159000",
	"26159090"=>"26159000",
	"26190040"=>"26190090",
	"26190080"=>"26190090",
	"29181985"=>"29181950",
	"29221980"=>"29221930",
	"29309085"=>"29309060",
	"29310095"=>"29310040",
	"30039010"=>"30039000",
	"30039090"=>"30039000",
	"30041010"=>"30041000",
	"30041090"=>"30041000",
	"30042010"=>"30042000",
	"30042090"=>"30042000",
	"30043110"=>"30043100",
	"30043190"=>"30043100",
	"30043210"=>"30043200",
	"30043290"=>"30043200",
	"30043910"=>"30043900",
	"30043990"=>"30043900",
	"30044010"=>"30044000",
	"30044090"=>"30044000",
	"30045010"=>"30045000",
	"30045090"=>"30045000",
	"30049011"=>"30049000",
	"30049019"=>"30049000",
	"30049091"=>"30049000",
	"30049099"=>"30049000",
	"30059051"=>"30059050",
	"30059055"=>"30059050",
	"30066011"=>"30066010",
	"30066019"=>"30066010",
	"32074010"=>"32074085",
	"32074020"=>"32074040",
	"32074030"=>"32074040",
	"32074080"=>"32074085",
	"32121010"=>"32121000",
	"32121090"=>"32121000",
	"32129031"=>"32129000",
	"32129038"=>"32129000",
	"32129090"=>"32129000",
	"32159010"=>"32159000",
	"32159080"=>"32159000",
	"33059010"=>"33059000",
	"33059090"=>"33059000",
	"34031991"=>"34031990",
	"34031999"=>"34031990",
	"34039910"=>"34039900",
	"34039990"=>"34039900",
	"34049010"=>"34049000",
	"34049080"=>"34049000",
	"34060011"=>"34060000",
	"34060019"=>"34060000",
	"34060090"=>"34060000",
	"35079010"=>"35079030",
	"35079020"=>"35079030",
	"37011010"=>"37011000",
	"37011090"=>"37011000",
	"37023231"=>"37023285",
	"37023250"=>"37023285",
	"37023280"=>"37023285",
	"37025410"=>"37025400",
	"37025490"=>"37025400",
	"37032010"=>"37032000",
	"37032090"=>"37032000",
	"37039010"=>"37039000",
	"37039090"=>"37039000",
	"37079011"=>"37079020",
	"37079019"=>"37079020",
	"37079030"=>"37079020",
	"38040010"=>"38040000",
	"38040090"=>"38040000",
	"38061010"=>"38061000",
	"38061090"=>"38061000",
	"38249097"=>"38249087",
	"39019010"=>"39019030",
	"39019020"=>"39019030",
	"39072021"=>"39072020",
	"39072029"=>"39072020",
	"39079911"=>"39079910",
	"39079919"=>"39079990",
	"39079991"=>"39079910",
	"39079998"=>"39079990",
	"39119091"=>"39119092",
	"39119093"=>"39119092",
	"39123910"=>"39123985",
	"39123980"=>"39123985",
	"39159018"=>"39159080",
	"39159090"=>"39159080",
	"39162010"=>"39162000",
	"39162090"=>"39162000",
	"39169011"=>"39169010",
	"39169013"=>"39169010",
	"39169015"=>"39169010",
	"39169019"=>"39169010",
	"39169051"=>"39169050",
	"39169059"=>"39169050",
	"39172912"=>"39172900",
	"39172915"=>"39172900",
	"39172919"=>"39172900",
	"39172990"=>"39172900",
	"39173210"=>"39173200",
	"39173231"=>"39173200",
	"39173235"=>"39173200",
	"39173239"=>"39173200",
	"39173251"=>"39173200",
	"39173291"=>"39173200",
	"39173299"=>"39173200",
	"39173912"=>"39173900",
	"39173915"=>"39173900",
	"39173919"=>"39173900",
	"39173990"=>"39173900",
	"39191011"=>"39191012",
	"39191013"=>"39191012",
	"39191031"=>"39191080",
	"39191038"=>"39191080",
	"39191061"=>"39191080",
	"39191069"=>"39191080",
	"39191090"=>"39191080",
	"39199010"=>"39199000",
	"39199031"=>"39199000",
	"39199038"=>"39199000",
	"39199061"=>"39199000",
	"39199069"=>"39199000",
	"39199090"=>"39199000",
	"39201026"=>"39201025",
	"39201027"=>"39201025",
	"39202071"=>"39202080",
	"39202079"=>"39202080",
	"39202090"=>"39202080",
	"39206211"=>"39206212",
	"39206213"=>"39206212",
	"39207110"=>"39207100",
	"39207190"=>"39207100",
	"39207350"=>"39207380",
	"39207390"=>"39207380",
	"39209951"=>"39209952",
	"39209955"=>"39209952",
	"39219011"=>"39219010",
	"39219019"=>"39219010",
	"39239010"=>"39239000",
	"39239090"=>"39239000",
	"39249011"=>"39249000",
	"39249019"=>"39249000",
	"39249090"=>"39249000",
	"44123200"=>"44123210",
	"44129970"=>"44129940",
	"48041911"=>"48041912",
	"48041915"=>"48041912",
	"48041931"=>"48041930",
	"48041938"=>"48041930",
	"48044110"=>"48044198",
	"48044199"=>"48044198",
	"48044210"=>"48044200",
	"48044290"=>"48044200",
	"48044910"=>"48044900",
	"48044990"=>"48044900",
	"48045110"=>"48045100",
	"48045190"=>"48045100",
	"48045210"=>"48045200",
	"48045290"=>"48045200",
	"48184090"=>"48184091",
	"48204010"=>"48204000",
	"48204090"=>"48204000",
	"59113210"=>"59113211",
	"84068110"=>"84068100",
	"84068190"=>"84068100",
	"84068211"=>"84068200",
	"84068219"=>"84068200",
	"84068290"=>"84068200",
	"84072920"=>"84072900",
	"84072980"=>"84072900",
	"84073310"=>"84073300",
	"84073390"=>"84073300",
	"84081022"=>"84081023",
	"84081024"=>"84081027",
	"84081026"=>"84081023",
	"84081028"=>"84081027",
	"84109010"=>"84109000",
	"84109090"=>"84109000",
	"84185091"=>"84185090",
	"84185099"=>"84185090",
	"84193910"=>"84193900",
	"84193990"=>"84193900",
	"84201050"=>"84201080",
	"84201090"=>"84201080",
	"84213940"=>"84213980",
	"84213990"=>"84213980",
	"84241020"=>"84241000",
	"84241080"=>"84241000",
	"84243005"=>"84243008",
	"84243009"=>"84243008",
	"84251920"=>"84251900",
	"84251980"=>"84251900",
	"84253930"=>"84253900",
	"84253990"=>"84253900",
	"84282030"=>"84282020",
	"84282091"=>"84282020",
	"84282098"=>"84282080",
	"84289030"=>"84289090",
	"84289091"=>"84289090",
	"84289095"=>"84289090",
	"84332051"=>"84332050",
	"84332059"=>"84332050",
	"84334010"=>"84334000",
	"84334090"=>"84334000",
	"84411040"=>"84411070",
	"84411080"=>"84411070",
	"84425021"=>"84425020",
	"84425023"=>"84425020",
	"84425029"=>"84425020",
	"84471110"=>"84471100",
	"84471190"=>"84471100",
	"84471210"=>"84471200",
	"84471290"=>"84471200",
	"84483310"=>"84483300",
	"84483390"=>"84483300",
	"84512110"=>"84512100",
	"84512190"=>"84512100",
	"84642020"=>"84642080",
	"84642095"=>"84642080",
	"84649020"=>"84649000",
	"84649080"=>"84649000",
	"84659910"=>"84659900",
	"84659990"=>"84659900",
	"84742010"=>"84742000",
	"84742090"=>"84742000",
	"84743910"=>"84743900",
	"84743990"=>"84743900",
	"84798991"=>"84798997",
	"84798997"=>"84798997",
	"84806010"=>"84806000",
	"84806090"=>"84806000",
	"84832010"=>"84832000",
	"84832090"=>"84832000",
	"85158091"=>"85158090",
	"85158099"=>"85158090");
	
	echo t("Päivitetään muuttuneet tullinimikkeet tuotteille")."...<br>";
	
	foreach ($muunnosavaimet as $vanha => $uusi) {
		$query  = "	UPDATE tuote set 
					tullinimike1 = '$uusi' 
					where yhtio			= '$kukarow[yhtio]'
					and tullinimike1	= '$vanha'"; 
		$result = mysql_query($query) or pupe_error($query);		
	}
	
	// Eka rivi roskikseen
	$rivi = fgets($file);
	
	echo t("Lisätään uudet tullinimikkeet tietokantaan")."...<br>";
	
	while ($rivi = fgets($file)) {
		list($cnkey, $cn, $dashes, $dm, $su, $su_vientiilmo, $kieli) = explode("\t", trim($rivi));
				
		$dm = preg_replace("/([^A-Z0-9öäåÅÄÖ \.,\-_\:\/\!\|\?\+\(\)%#]|é)/i", "", $dm);
			
		$query  = "	INSERT INTO tullinimike SET
					yhtio			= '$kukarow[yhtio]',
					cnkey			= '$cnkey',
					cn				= '$cn', 
					dashes			= '$dashes', 
					dm				= '$dm', 
					su				= '$su', 
					su_vientiilmo	= '$su_vientiilmo', 
					kieli			= '$kieli', 
					laatija			= '$kukarow[kuka]', 
					luontiaika		= now()";									
		$result = mysql_query($query) or pupe_error($query);
	}
	
	fclose($file);
	
	echo t("Päivitys valmis")."...<br><br><hr>";
}


echo "<br><form action = '$PHP_SELF' method='post' autocomplete='off'>";
echo t("Listaa ja muokkaa tuotteiden tullinimikkeitä").":<br><br>";
echo "<table>";
echo "<tr>";
echo "<th>".t("Syötä tullinimike").":</th>";
echo "<td><input type='text' name='tullinimike1' value='$tullinimike1'></td>";
echo "</tr><tr>";
echo "<th>".t("Syötä tullinimikkeen lisäosa").":</th>";
echo "<td><input type='text' name='tullinimike2' value='$tullinimike2'> (ei pakollinen) </td>";
echo "<td class='back'><input type='submit' value='".t("Hae")."'></td>";
echo "</tr></table>";
echo "</form><br><br>";

echo "<form action = '$PHP_SELF' method='post' autocomplete='off'>";
echo "<input type='hidden' name='tee' value='synkronoi'>";
echo t("Päivitä järjestelmän tullinimiketietokanta").":<br><br>";
echo "<table>";
echo "<th>".t("Nouda uusimmat tullinimikkeet").":</th>";
echo "<td><input type='submit' value='".t("Nouda")."'></td>";
echo "</tr></table>";
echo "</form>";


if ($tullinimike1 != "") {
	
	if ($tullinimike2 != "") $lisa = " and tullinimike2='$tullinimike2'";
	else $lisa = "";
	
	$query = "	SELECT * 
				from tuote use index (yhtio_tullinimike) 
				where yhtio = '$kukarow[yhtio]' 
				and tullinimike1 = '$tullinimike1' $lisa 
				order by tuoteno";
	$resul = mysql_query($query) or pupe_error($query);

	if (mysql_num_rows($resul) == 0) {
		echo "<font class='error'>Yhtään tuotetta ei löytynyt!</font><br>";
	}
	else {

		echo sprintf("<font class='message'>Haulla löytyi %s tuotetta.</font><br><br>", mysql_num_rows($resul));

		echo "<form action = '$PHP_SELF' method='post' autocomplete='off'>";
		echo "<input type='hidden' name='tullinimike1' value='$tullinimike1'>";
		echo "<input type='hidden' name='tullinimike2' value='$tullinimike2'>";
		echo "<input type='hidden' name='tee' value='muuta'>";

		echo "<table>";
		echo "<tr>";
		echo "<th>".t("Syötä uusi tullinimike").":</th>";
		echo "<td><input type='text' name='uusitullinimike1' value='$uusitullinimike1'></td>";
		echo "</tr><tr>";
		echo "<th>".t("Syötä tullinimikkeen lisäosa").":</th>";
		echo "<td><input type='text' name='uusitullinimike2' value='$uusitullinimike2'></td>";
		echo "<td class='back'><input type='submit' value='".t("Päivitä")."'></td>";
		echo "</tr></table>";
		echo "</form><br>";		

		echo "<table>";
		echo "<tr>";
		echo "<th>".t("Tuoteno")."</th>";
		echo "<th>".t("Osasto")."</th>";
		echo "<th>".t("Try")."</th>";
		echo "<th>".t("Merkki")."</th>";
		echo "<th>".t("Nimitys")."</th>";
		echo "<th>".t("Tullinimike")."</th>";
		echo "<th>".t("Tullinimikkeen lisäosa")."</th>";
		echo "</tr>";

		while ($rivi = mysql_fetch_array($resul)) {
			
			// tehdään avainsana query
			$oresult = t_avainsana("OSASTO", "", "and avainsana.selite ='$rivi[osasto]'");
			$os = mysql_fetch_array($oresult);
			
			// tehdään avainsana query
			$tresult = t_avainsana("TRY", "", "and avainsana.selite ='$rivi[try]'");
			$try = mysql_fetch_array($tresult);
			
			echo "<tr>";
			echo "<td><a href='yllapito.php?toim=tuote&tunnus=$rivi[tunnus]&lopetus=tullinimikkeet.php'>$rivi[tuoteno]</a></td>";
			echo "<td>$rivi[osasto] $os[selitetark]</td>";
			echo "<td>$rivi[try] $try[selitetark]</td>";
			echo "<td>$rivi[tuotemerkki]</td>";			
			echo "<td>".t_tuotteen_avainsanat($rivi, 'nimitys')."</td>";
			echo "<td>$rivi[tullinimike1]</td>";
			echo "<td>$rivi[tullinimike2]</td>";
			echo "</tr>";
		}
		echo "</table>";
	}
}

require ("inc/footer.inc");

?>