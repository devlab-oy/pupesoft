<?php 

/// tˆmˆ funkkari mahdollistaa lis‰‰m‰‰n tason mihin tahansa v‰liin, toivottavasti :) 
function LisaaTaso($isinimi, $tasonimi ,$lft, $laji,$kukarow, $tkoodi) {
	
	$q1 = "LOCK TABLE dynaaminen_puu WRITE";
	$r = mysql_query($q1) or pupe_error($q1);
	
	$q2 = 	"SELECT @myRight := rgt, @myLeft := lft  FROM dynaaminen_puu WHERE nimi = '{$isinimi}' AND lft='{$lft}' AND laji='".$laji."' AND yhtio='{$kukarow[yhtio]}'";
	$r = mysql_query($q2) or pupe_error($q2);

	$q3 =	"UPDATE dynaaminen_puu set rgt = rgt + 2, muutospvm = now(), muuttaja = '$kukarow[kuka]' WHERE rgt = @myRight  AND laji='$laji' AND yhtio='{$kukarow[yhtio]}'";
	$r = mysql_query($q3) or pupe_error($q3);
	
	$q4 =	"UPDATE dynaaminen_puu set lft = lft + 1, rgt = rgt +1, muutospvm = now(), muuttaja = '$kukarow[kuka]' WHERE lft > @myLeft  AND laji='$laji' AND yhtio='{$kukarow[yhtio]}'";
	$r = mysql_query($q4) or pupe_error($q4);
	
	$q4 =	"UPDATE dynaaminen_puu set lft = lft + 1, rgt = rgt + 1, muutospvm = now(), muuttaja = '$kukarow[kuka]' WHERE lft > @myRight  AND laji='$laji' AND yhtio='{$kukarow[yhtio]}'";
	$r = mysql_query($q4) or pupe_error($q4);
	
	$qroot = "UPDATE dynaaminen_puu set rgt = rgt + 2, muutospvm = now(), muuttaja = '$kukarow[kuka]' WHERE lft = 1  AND laji='$laji' AND yhtio='{$kukarow[yhtio]}'";
	$rroot = mysql_query($qroot) or pupe_error($qroot);

	$q5 =	"INSERT INTO dynaaminen_puu(yhtio,koodi,nimi, lft, rgt, laji,laatija,luontiaika) values ('$kukarow[yhtio]','{$tkoodi}','{$tasonimi}',@myLeft + 1 , @myRight + 1,'$laji','$kukarow[kuka]',now())";
	$r = mysql_query($q5) or pupe_error($q5);

/*
luontiaika		= now(),
laatija			= '$kukarow[kuka]',
muutospvm		= now(),
muuttaja		= '$kukarow[kuka]'";
*/


	$q6 = "UNLOCK TABLES";
	$r = mysql_query($q6) or pupe_error($q6);
	
	//// p‰iitys ongelma. Ratkaistaan maanantaina. T: Jampppa
}


function LisaaPaaKat($kukarow,$KatNimi, $laji)
{
	$q = "INSERT INTO dynaaminen_puu(yhtio,nimi,lft,rgt, laji, laatija, luontiaika) VALUES('$kukarow[yhtio]','$KatNimi',1,2,'$laji','$kukarow[kuka]',now())";
//	echo $q;
	$r = mysql_query($q) or pupe_error($q);
}

function LisaaLapsi($isi, $laji, $kukarow, $Lnimi, $pleft, $subd,$koodi) { //laita vittu kuntoon !!!
	$apu = $apu2 = $apu3  = $apu4 ="";
	
	if($pleft ==1) 
	{
		$apu 	= " where rgt >= @myRight";
		$apu2 	= "@myRight , @myRight + 1";
		$apu3 	= "@myRight := rgt ";
		$apu4 	= " WHERE lft > @myRight";
	} // mik‰li is‰ on root, niin lis‰t‰‰n sille alakat
	
	elseif($subd >= 1)
	{
		$apu 	= " where rgt > @myLeft";
		$apu2 	= "@myLeft +1 , @myLeft + 2";
		$apu3 	= "@myLeft := lft ";
		$apu4 	= "WHERE lft > @myLeft";
	} // lis‰t‰‰n alakategorialle lapsi
	
	else
	{
		$apu 	= "WHERE rgt > @myRight"; 
		$apu2 	= "@myRight + 1, @myRight + 2";
		$apu3 	= "@myRight := rgt ";
		$apu4 	= " WHERE lft > @myRight";
	} //alakat lapsen lis‰ys
	
	$q1 = "LOCK TABLE dynaaminen_puu WRITE";
	$r = mysql_query($q1) or pupe_error($q1);
	$q2 = "SELECT ".$apu3." FROM dynaaminen_puu WHERE nimi = '".$isi."' AND laji='".$laji."' AND yhtio='{$kukarow[yhtio]}'";
	$r = mysql_query($q2) or pupe_error($q2);
	$q3 = "UPDATE dynaaminen_puu SET rgt = rgt + 2, muutospvm = now(), muuttaja = '$kukarow[kuka]' ".$apu." AND laji='$laji' AND yhtio='{$kukarow[yhtio]}'";
//	echo $q3;
	$r = mysql_query($q3) or pupe_error($q3);
	$q4 = "UPDATE dynaaminen_puu SET lft = lft + 2,muutospvm = now(), muuttaja = '$kukarow[kuka]' ".$apu4. " AND laji='$laji' AND yhtio='{$kukarow[yhtio]}'";
	$r = mysql_query($q4) or pupe_error($q4);
//	echo "<br />".$q4;
	$q5 = "INSERT INTO dynaaminen_puu(yhtio, nimi, koodi, lft, rgt, laji, laatija, luontiaika) VALUES('$kukarow[yhtio]','$Lnimi','$koodi', ".$apu2 .",'$laji','$kukarow[kuka]',now())";
//	echo "<br />".$q5;
	$r = mysql_query($q5) or pupe_error($q5);
	$q6 = "UNLOCK TABLES";
	$r = mysql_query($q6) or pupe_error($q6);

}	


function PoistaLapset($lapsi, $plft, $laji, $kukarow) {
	if ($plft == 1) {
		echo "<p><font class='error'>Et Voi poistaa p‰‰kategoriaa !!!</font></p>";
	}
	else {
 		$q1 = "LOCK TABLE dynaaminen_puu WRITE";
 		$r = mysql_query($q1) or pupe_error($q1);
 		$q2 = "SELECT @myLeft := lft, @myRight := rgt, @myWidth := rgt - lft + 1 FROM dynaaminen_puu WHERE nimi = '".$lapsi."' AND laji='$laji' AND yhtio='{$kukarow[yhtio]}'";
 		$r = mysql_query($q2) or pupe_error($q2);
 		$q3 = "DELETE FROM dynaaminen_puu WHERE lft = @myLeft  AND laji='$laji' and yhtio='{$kukarow[yhtio]}'";
 		$r = mysql_query($q3) or pupe_error($q3);	
 		$q4 = "UPDATE dynaaminen_puu SET rgt = rgt -1, lft = lft -1,muutospvm = now(), muuttaja = '$kukarow[kuka]' WHERE lft between @myLeft and @myRight  AND laji='$laji'";
 		$r = mysql_query($q4) or pupe_error($q4);
 		$q5 = "UPDATE dynaaminen_puu SET rgt = rgt - 2, muutospvm = now(), muuttaja = '$kukarow[kuka]' WHERE rgt > @myRight  AND laji='$laji'";
 		$r = mysql_query($q5) or pupe_error($q5);
 		$q7 = "UPDATE dynaaminen_puu SET lft = lft - 2, muutospvm = now(), muuttaja = '$kukarow[kuka]'  WHERE lft > @myRight  AND laji='$laji'";
 		$r = mysql_query($q7) or pupe_error($q7);
		
		$q6 = "UNLOCK TABLES";
		$r = mysql_query($q6) or pupe_error($q6);
	}

/* 	Mik‰li koetetaan poistaa p‰‰kategoriaa niin ohjelma herjaa, muussa tapauksessa
	ohjelma poistaa valitun kategorian ja nostaa sen alakategoriat yhden isommalle tasolle.
*/ 
	
}	
	

function lapset($isi, $plft, $laji,$kukarow) {
	$dump = 	"SELECT (COUNT(node.nimi)-1) as Lapsi
				FROM dynaaminen_puu AS node , dynaaminen_puu AS parent 
				WHERE node.lft BETWEEN parent.lft AND parent.rgt 
				AND parent.nimi='$isi' AND parent.lft = '$plft' AND parent.laji='{$laji}' AND node.laji='{$laji}' AND node.yhtio='{$kukarow[yhtio]}' GROUP BY parent.nimi ORDER BY node.lft";

	$dumm = mysql_query($dump) or pupe_error($dump);
	$rivi =  mysql_fetch_assoc($dumm);

	return $rivi['Lapsi'];
} // funktio joka palauttaa valitun kategorian alakategorioiden lukum‰‰r‰n, k‰ytet‰‰n syvyyden m‰‰rittelyss‰
	
function paivitakat($isi, $uusinimi, $laji, $kukarow,$koodi) {
	
	if (trim($koodi) == '') {
	
		$q1 = "LOCK TABLE dynaaminen_puu WRITE";
		$r = mysql_query($q1) or pupe_error($q1);
		$q2 = "UPDATE dynaaminen_puu set nimi='{$uusinimi}', muutospvm = now(), muuttaja = '$kukarow[kuka]' where nimi='{$isi}' AND laji='{$laji}' AND yhtio='{$kukarow[yhtio]}'";
		//echo $q2;
		$r = mysql_query($q2) or pupe_error($q2);
		$q6 = "UNLOCK TABLES";
		$r = mysql_query($q6) or pupe_error($q6);
	}
	else
	{
		$q1 = "LOCK TABLE dynaaminen_puu WRITE";
		$r = mysql_query($q1) or pupe_error($q1);
		$q2 = "UPDATE dynaaminen_puu set nimi='{$uusinimi}', muutospvm = now(), muuttaja = '$kukarow[kuka]', koodi='{$koodi}' where nimi='{$isi}' AND laji='{$laji}' AND yhtio='{$kukarow[yhtio]}'";
		//echo $q2;
		$r = mysql_query($q2) or pupe_error($q2);
		$q6 = "UNLOCK TABLES";
		$r = mysql_query($q6) or pupe_error($q6);	
	}
	// Yksinkertainen funktio joka p‰ivitt‰‰ valitun kategorian valitulle nimelle
}	
	
function kukaisi($lapsi,$valinta) {
	$query =   "SELECT T_SN.C_PARENT_NODENO as isi, T_SN_DESIG.C_TEXT as selite
				FROM T_SN
				JOIN T_SN_DESIG ON (T_SN_DESIG.C_FK=T_SN.C_PK)
				WHERE T_SN.C_TREETYPE_VALKEY='PC'
				AND C_LNG='FI' and T_SN.C_NODENO=". $lapsi;			
	
	$result = mysql_query($query) or pupe_error($query);
	$row = mysql_fetch_assoc($result);

	if ($valinta == 1) {
		$selite = $row['selite'];
	}
	else {
		$selite = $row['isi'];
	}
return $selite;
} // palauttaa Isin


// n‰m‰ 2 alla olevaa on tecdoccia...
function Root() {
		$query 		=   "SELECT nimi FROM dynaaminen_puu where lft=1";			
		$result		= 	mysql_query($query) or pupe_error($query);
		$row		= 	mysql_fetch_assoc($result);
		$palauta	= 	$row['nimi'];
		return $palauta;
}

// LisaaLapset k‰ytet‰‰n transcriptin‰ ku haetaan isosta datasta paskat dynaaminen_puu-tauluun,,,
function LisaaLapset($isinimi, $isinum, $Lnimi,$node,$pnode) {
  // Taulun rakenne johon lis‰t‰‰n transriptist‰ dataa ja paljon...

	$apu = $apu2 = $apu3  = $apu4 ="";
	
	if($isinum == 1) 
	{
		$apu 	= " where rgt >= @myRight";
		$apu2 	= "@myRight , @myRight + 1";
		$apu3 	= "@myRight := rgt ";
		$apu4 	= " WHERE lft > @myRight";
	} // mik‰li is‰ on root, niin lis‰t‰‰n sille alakat
	
	elseif ($isinum >1)
	{
		$apu 	= " where rgt > @myLeft";
		$apu2 	= "@myLeft +1 , @myLeft + 2";
		$apu3 	= "@myLeft := lft ";
		$apu4 	= "WHERE lft > @myLeft";
	} // lis‰t‰‰n alakategorialle 
	
	else
	{
		$apu 	= "WHERE rgt > @myRight"; 
		$apu2 	= "@myRight + 1, @myRight + 2";
		$apu3 	= "@myRight := rgt ";
		$apu4 	= " WHERE lft > @myRight";
	} //alakat lapsen lis‰ys
	
	$q1 = "LOCK TABLE dynaaminen_puu WRITE";
	//$r = mysql_query($q1) or pupe_error($q1);
	if ($isinum == 1) {
		$q2 = "SELECT ".$apu3." FROM dynaaminen_puu WHERE nimi = '".Root()."'";
	}
	else {
		$q2 = "SELECT ".$apu3." FROM dynaaminen_puu WHERE NODENO=".$isinum;
	}
//	echo $q2."<br />";
		
	//$q22 = "select @myLeft := lft from dynaaminen_puu where nimi = '".."'";
	
	$r = mysql_query($q2) or pupe_error($q2);
	$q3 = "UPDATE dynaaminen_puu SET rgt = rgt + 2 ".$apu;
	$r = mysql_query($q3) or pupe_error($q3);
	$q4 = "UPDATE dynaaminen_puu SET lft = lft + 2 ".$apu4;
	$r = mysql_query($q4) or pupe_error($q4);
	$q5 = "INSERT INTO dynaaminen_puu (nimi, NODENO, PNODENO, lft, rgt) VALUES ('".$Lnimi."',".$node.",".$pnode.",". $apu2 .")";
//	echo $q5."<br />";
	
	$r = mysql_query($q5) or pupe_error($q5);
	$q6 = "UNLOCK TABLES";
	$r = mysql_query($q6) or pupe_error($q6);


}	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
?>