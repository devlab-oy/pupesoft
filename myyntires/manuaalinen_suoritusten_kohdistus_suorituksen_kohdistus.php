<?php

// estet‰‰n sivun lataus suoraan
if (!empty($HTTP_GET_VARS["oikeus"]) ||
    !empty($HTTP_POST_VARS["oikeus"]) ||
    !empty($HTTP_COOKIE_VARS["oikeus"]) ||
    !isset($oikeus)) {

  echo "<p>".t("Kielletty toiminto")."!</p>";
  exit;
}

echo "<font class='head'>".t("Manuaalinen suoritusten kohdistaminen (laskujen valinta)")."</font><hr>";
$query = "	SELECT suoritus.summa, suoritus.valkoodi valkoodi, concat(viite, viesti) tieto, suoritus.tilino, maksupvm, kirjpvm, nimi_maksaja, asiakas_tunnus, suoritus.tunnus, tiliointi.tilino ttilino
			FROM suoritus, tiliointi
			 WHERE suoritus.yhtio ='$kukarow[yhtio]'
			 and suoritus.tunnus='$suoritus_tunnus'
			 and suoritus.yhtio=tiliointi.yhtio
			 and suoritus.ltunnus=tiliointi.tunnus";
$result = mysql_query($query) or pupe_error($query);

//N‰ytet‰‰n suorituksen tiedot!
echo "<table><tr>";
for ($i = 0; $i < mysql_num_fields($result)-3; $i++) {
	echo "<th>" . t(mysql_field_name($result,$i)) . "</th>";
}
echo "</tr>";

$suoritus = mysql_fetch_array ($result);

echo "<tr>";

for ($i=0; $i<mysql_num_fields($result)-3; $i++) {
	echo "<td>$suoritus[$i]</td>";
}

echo "</tr>";
echo "</table>";
//echo "<br>";
$asiakas_tunnus = $suoritus['asiakas_tunnus'];
$suoritus_summa= $suoritus['summa'];
$suoritus_tunnus = $suoritus['tunnus'];
$suoritus_ttilino = $suoritus['ttilino'];
$valkoodi = $suoritus['valkoodi'];

/* haetaan kaatotilin summa ja n‰ytet‰‰n osak‰yttˆliittym‰‰

$query = "
SELECT SUM(summa) summa
FROM suoritus
WHERE yhtio='$kukarow[yhtio]' AND ltunnus<>0 AND asiakas_tunnus=$asiakas_tunnus and kohdpvm = '0000-00-00'";

$result = mysql_query($query) or pupe_error($query);

$kaato = mysql_fetch_object($result);
$kaatosumma=$kaato->summa;
*/

$pyocheck='';
$osacheck='';
if ($osasuoritus == '1') $osacheck = 'checked';
if ($pyoristys_virhe_ok == '1') $pyocheck = 'checked';

echo "<form action = '$PHP_SELF?tila=$tila&asiakas_tunnus=$asiakas_tunnus' method = 'post'>";
echo "<table>";
echo "<tr><th>".t("Summa")."</th><td><input type='text' name='summa' value='0.0' readonly></td>";
echo "<th>".t("Erotus")."</th><td><input type='text' name='jaljella' value='$suoritus_summa' readonly></td></tr>";

//echo "<tr><th>Suorituksia kohdistamatta</th><td>$kaatosumma</td></tr>";
echo "</table>";
echo "</form>";



//
//N‰ytet‰‰n laskut!
//

//XXX korjasin t‰st‰ ...viite,tunnus -> kapvm, viite //lieska 2004-03-17
$kentat = 'summa, kasumma, laskunro, erpcm, kapvm, viite';
$kentankoko = array(10,10,15,10,10,15);
$array = split(",", $kentat);
$count = count($array);
$lisa='';
$ulisa='';
for ($i=0; $i<=$count; $i++) {
  // tarkastetaan onko hakukent‰ss‰ jotakin
  if (strlen($haku[$i]) > 0) {
    $lisa .= " and " . $array[$i] . " like '%" . $haku[$i] . "%'";
    $ulisa .= "&haku[" . $i . "]=" . $haku[$i];
  }
}
if (strlen($ojarj) > 0) {
  $jarjestys = $array[$ojarj];
}
else{
  $jarjestys = 'erpcm';
}


// Myyntilaskuissa tila=U

if ($asiakas_nimi=='') { // Etsit‰‰n ytunnuksella
	$query = "SELECT ytunnus FROM asiakas WHERE tunnus=$asiakas_tunnus AND ytunnus!='' AND yhtio ='$kukarow[yhtio]'";
	$result = mysql_query($query) or pupe_error($query);

	if ($ytunnusrow=mysql_fetch_array ($result)) {
  		$ytunnus = $ytunnusrow[0];
	} else {
  	echo "<font class='head'>".t("Asiakkaalta ei lˆydy y-tunnusta")."</font>";
  	exit;
	}
	$query = "SELECT summa-saldo_maksettu summa, kasumma, laskunro, erpcm, kapvm, viite, lasku.tunnus
				FROM lasku
            	WHERE lasku.yhtio ='$kukarow[yhtio]' and tila = 'U' AND ytunnus='$ytunnus' and mapvm='0000-00-00' and valkoodi='$valkoodi' $lisa
				ORDER BY $jarjestys";
} else {
	$query = "SELECT summa-saldo_maksettu summa, kasumma, laskunro, erpcm, kapvm, viite, lasku.tunnus
					FROM lasku
            		WHERE yhtio ='$kukarow[yhtio]' and tila = 'U' AND nimi='$asiakas_nimi' and mapvm='0000-00-00' and valkoodi='$valkoodi' $lisa
					ORDER BY $jarjestys";
}
$result = mysql_query($query) or pupe_error($query);

echo "<form action = '$PHP_SELF?tila=$tila&suoritus_tunnus=$suoritus_tunnus&asiakas_tunnus=$asiakas_tunnus&asiakas_nimi=$asiakas_nimi' method = 'post'>";
echo "<table><tr><th colspan='2'></th>";

for ($i = 0; $i < mysql_num_fields($result)-1; $i++) {
  echo "<th><a href='$PHP_SELF?suoritus_tunnus=$suoritus_tunnus&asiakas_tunnus=$asiakas_tunnus&asiakas_nimi=$asiakas_nimi&tila=$tila&ojarj=".$i.$ulisa."'>" . t(mysql_field_name($result,$i))."</a></th>";
}

echo "<th></th></tr>";
echo "<tr><th>L</th><th>K</th>";

for ($i = 0; $i < mysql_num_fields($result)-1; $i++) {
  echo "<td><input type='text' size='$kentankoko[$i]' name='haku[$i]' value='$haku[$i]'></td>";
}
echo "<td><input type='submit' value='".t("Etsi")."'></td></tr>";

echo"</form>";
echo "<form action = '$PHP_SELF?tila=tee_kohdistus' method = 'post' onSubmit='return validate(this)'>";
$laskucount=0;

if ($asiakas_nimi != '') echo "<input type='hidden' name='asiakas_nimi' value='$asiakas_nimi'>";

while ($maksurow=mysql_fetch_array ($result)) {
  $query="select count(*) maara from tiliointi where tiliointi.yhtio='$kukarow[yhtio]' and tiliointi.ltunnus = '$maksurow[tunnus]' and tilino='$suoritus_ttilino'";
  $cresult = mysql_query($query) or pupe_error($query);
  $maararow=mysql_fetch_array ($cresult);
  if ($maararow['maara'] > 0) {
	  $laskucount++;
	  $lasku_tunnus = $maksurow['tunnus'];
	  $bruttokale = $maksurow['summa']-$maksurow['kasumma'];
	  echo "<tr><th>";
	  echo "<input type='checkbox' name='lasku_tunnukset[]' value='$lasku_tunnus' onclick='javascript:paivita1(this)'>";
	  echo "<input type='hidden' name='lasku_summa' value='$maksurow[0]'>";
	  echo "</th><th>";
	  echo "<input type='checkbox' name='lasku_tunnukset_kale[]' value='$lasku_tunnus' onclick='javascript:paivita2(this)'>";
	  echo "<input type='hidden' name='lasku_kasumma' value='$bruttokale'>";
	  echo "</th>";
  }
  else {
  	echo "<tr><th colspan = '2'>".t('V‰‰r‰ saamisettili')." ($suoritus_ttilino)</th>";
  }
  for ($i=0; $i<mysql_num_fields($result)-1; $i++) {
    echo "<td>$maksurow[$i]</td>";
  }
  echo "<th></th></tr>\n";
}
echo "<input type='hidden' name='suoritus_tunnus' value='$suoritus_tunnus'>";
echo "</th></tr>";
echo "<tr><th colspan='9'> ".t("L = lasku ilman kassa-alennusta K = lasku kassa-alennuksella")."</th></tr>";
echo "</table>";
echo "<table>";
echo "<tr><th>".t("Kirjaa erotus kassa-aleen")."</th><td><input type='checkbox' name='pyoristys_virhe_ok' value='1' $pyocheck></td>";
echo "<th>".t("Osasuorita lasku")."</th><td><input type='checkbox' name='osasuoritus' value='1' $osacheck onclick='javascript:osasuo(this)'></td></tr>";
echo "</table>";
echo "<br><input type='submit' value='".t("Kohdista")."'>";
echo "</form>";

echo "
<script language='JavaScript'><!--

function paivita1(checkboxi) {
";

if($laskucount==1)
     echo " if(checkboxi==document.forms[2].elements['lasku_tunnukset[]']) {
       document.forms[2].elements['lasku_tunnukset_kale[]'].checked=false;
    }";
else echo "
    for(i=0;i<document.forms[2].elements['lasku_tunnukset[]'].length;i++) {
      if(checkboxi==document.forms[2].elements['lasku_tunnukset[]'][i]) {
         document.forms[2].elements['lasku_tunnukset_kale[]'][i].checked=false;
      }
    }";
echo "
  paivitaSumma();
}
function paivita2(checkboxi) {
";
if($laskucount==1)
     echo "
    if(checkboxi==document.forms[2].elements['lasku_tunnukset_kale[]']) {
       document.forms[2].elements['lasku_tunnukset[]'].checked=false;
    }
";
else echo "
for(i=0;i<document.forms[2].elements['lasku_tunnukset_kale[]'].length;i++) {
      if(checkboxi==document.forms[2].elements['lasku_tunnukset_kale[]'][i]) {
         document.forms[2].elements['lasku_tunnukset[]'][i].checked=false;
      }
    }
";
echo "
  paivitaSumma();
}
function paivitaSumma() {
  var i;
  var summa=0.0;
";

if($laskucount==1)
     echo "
      if(document.forms[2].elements['lasku_tunnukset[]'].checked) {
         summa+=1.0*document.forms[2].lasku_summa.value;
      }
      if(document.forms[2].elements['lasku_tunnukset_kale[]'].checked) {
         summa+=1.0*document.forms[2].lasku_kasumma.value;
      }
   ";
else echo "
    for(i=0;i<document.forms[2].elements['lasku_tunnukset[]'].length;i++) {
      if(document.forms[2].elements['lasku_tunnukset[]'][i].checked) {
         summa+=1.0*document.forms[2].lasku_summa[i].value;
      }
    }
    for(i=0;i<document.forms[2].elements['lasku_tunnukset_kale[]'].length;i++) {
      if(document.forms[2].elements['lasku_tunnukset_kale[]'][i].checked) {
         summa+=1.0*document.forms[2].lasku_kasumma[i].value;
      }
    }
";

echo "
  document.forms[0].summa.value=Math.round(summa*100)/100;
  document.forms[0].jaljella.value=Math.round(($suoritus_summa-summa)*100)/100;
}

function round(number) {
    return number;
}

function osasuo(form) {
  if(document.forms[2].osasuoritus.checked) {
     if(document.forms[0].jaljella.value > 0) {
    	alert('".t("Et voi osasuorittaa, jos j‰jell‰ on positiivinen summa")."');
	document.forms[2].osasuoritus.checked = false;
       return false;
    }
  }
}

function validate(form) {
	var maara=0;
	var kmaara=0;
";

if($laskucount>1) echo "

	for(i=0;i<document.forms[2].elements['lasku_tunnukset[]'].length;i++) {
		if(document.forms[2].elements['lasku_tunnukset[]'][i].checked) {
        			maara+=1.0;
			}
    	}
    	for(i=0;i<document.forms[2].elements['lasku_tunnukset_kale[]'].length;i++) {
      		if(document.forms[2].elements['lasku_tunnukset_kale[]'][i].checked) {
         		kmaara+=1.0;
      		}
    	}

	maara = maara + kmaara;

	if(document.forms[2].osasuoritus.checked) {
		if ((kmaara==0) == false) {
			alert ('".t("Jos osasuoritus, ei voi valita kassa-alennusta")."');
			return false;
		}
		if ((maara==1) == false) {
			alert ('".t("Jos osasuoritus, pit‰‰ valita vain ja ainoastaan yksi lasku")."! ' + maara + ' valittu');
			return false;
		}
	}";

if($laskucount==1) echo "

      if(document.forms[2].elements['lasku_tunnukset[]'].checked) {
         maara=1;
      }
      if(document.forms[2].elements['lasku_tunnukset_kale[]'].checked) {
         maara=1;
      }
";

echo "
	if ((maara==0) == true) {
		alert('".t("Jotta voit kohdistaa, on ainakin yksi lasku valittava. Jos mit‰‰n kohdistettavaa ei lˆydy, klikkaa menusta Manuaalikohdistus p‰‰st‰ksesi takaisin alkuun")."');
		return false;
	}

	var jaljella=document.forms[0].jaljella.value;
	var kokolasku=document.forms[0].summa.value
	var suoritus_summa=$suoritus_summa;

	if(suoritus_summa==0)
		return true;

	if (document.forms[2].osasuoritus.checked == false) {
		var alennusprosentti = Math.round(100*(1-(suoritus_summa/kokolasku)));

		if(jaljella<0) {
			if(confirm('Haluatko varmasti antaa '+alennusprosentti+'% alennuksen? ('+(-1.0*jaljella)+' $yhtiorow[valkoodi])')==1) {
			        return true;
			} else return false;
		}
	}
	return true;
}

-->
</script>";




?>
