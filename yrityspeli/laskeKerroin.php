<?php
/// laskeKerroin.php
/// TAMK Yrityspeli-valikko, sutinan automaattitilausten kertointen kaavat
///
/// Jarmo Kortetjärvi
/// created: 2010-08-10
/// updated: 2010-10-25

/**
 * Hakee sutinakertoimet
 *
 * @access	public
 * @return	array	$kerroinrivi	kertoimet taulukkomuodossamuodossa kertoimen nimi => kerroin
 */
	function sutinakertoimet(){
		// sutinakertoimet
			$query  = "	SELECT 	nimi, kerroin
						FROM	TAMK_sutinakerroin";
			$kertoimet = mysql_query($query) or pupe_error($query);

		$kerroinrivi = array();

		while($rivi = mysql_fetch_assoc($kertoimet)) {
			$kerroinrivi[$rivi['nimi']] = $rivi['kerroin'];
		}

		return $kerroinrivi;
	}

/**
 * Hakee tilien saldot
 *
 * @access	public
 * @param	string	$yhtio
 * @param	mixed	$startDate			default false
 * @param	mixed	$endDate			default false
 * @return	int		$saldo
 */
	function getSaldo($yhtio, $startDate = false, $endDate = false ) {
		$query = "	SELECT	tilino, SUM(summa) AS 'summa'
					FROM	tiliointi
					WHERE	yhtio = '$yhtio'
					";
					
		if (!empty($startDate)) {
			$query .= "	AND		 tapvm > '$startDate'
						";
		}
		if (!empty($endDate)) {
			$query .= "	AND		 tapvm < '$endDate' 
						";
		}
		$query .= "GROUP BY tilino";
		$result = mysql_query($query);
		
		// if query was not successful
		if ($result === false) {
			echo $query;
		}
		else {
			$saldo = array();
			while ( $row = mysql_fetch_assoc($result) ) {
				$saldo[$row['tilino']] = $row['summa'];
			}
		}
		
		return $saldo;
	}

/**
 * Hakee Diilerin mainosten klikkausten määrän
 *
 * @access	public
 * @param	string	$yhtio
 * @return	int		$clicks
 */
	function getClicks($yhtio){
		
		// Haetaan korkeintaan kuukauden vanhoja tapahtumia
		$startDate = date("Y-m-d", strtotime("-1 month"));
	
		$query = "	SELECT count(TAMK_clicked.id) 
					FROM TAMK_clicked 
					JOIN TAMK_ads ON TAMK_clicked.linkId = TAMK_ads.id 
					WHERE TAMK_clicked.yhtio = 'Media' 
					AND TAMK_ads.yhtio = '$yhtio'
					AND timestamp > '$startDate'";
		
		$result = mysql_query($query);
		$clicks = mysql_result($result, 0);
		
		// Simuloidaan realistisempaa klikkausten määrää
		$clicks = $clicks * 100;
		
		return $clicks;
	}

	
/**
 * Määrittää vaikutuksen ylärajan
 *
 * @access	public
 * @param	int		$value
 * @return	int		$maxValue
 */
	 function verifyValue($value){
		$max = 1.50;
		if($value > $max){
			$maxValue = $max;
		}
		else{
			$maxValue = $value;
		}
		
		return $maxValue;
	 }

// Kertoimien laskemiseen käytettävät funktiot

/**
 * Laskee OPYjen välisten kaupan vaikutuksen
 *
 * @access	public
 * @param	int		$saldo
 * @return	int		$opykauppa
 */
	function laskeOpykauppa($saldo){
		$kerroinrivi = sutinakertoimet();
		// TODO
		$opykauppa = 1;
		
		$opykauppa = verifyValue($opykauppa);
		return $opykauppa;
	}

/**
 * Laskee markkinoinnin vaikutuksen
 *
 * @access	public
 * @param	int		$saldo
 * @return	int		$markkinointi
 */
	function laskeMarkkinointi($saldo){
		$kerroinrivi = sutinakertoimet();
	
		$base = 1.02;
		$mod = 1/890;
		$pow = $saldo * $mod;
		
		$markkinointi = pow($base, $pow);
		$markkinointi = $kerroinrivi['markkinointipanos'] * $markkinointi;
		
		$markkinointi = verifyValue($markkinointi);
		return $markkinointi;
	}

/**
 * Laskee sijainnin vaikutuksen
 *
 * @access	public
 * @param	int		$saldo
 * @return	int		$sijainti
 */
	function laskeSijainti($saldo){
	$kerroinrivi = sutinakertoimet();
	
		$base = 1.03;
		$mod = 1/890;
		$pow = $saldo * $mod;
	
		$sijainti = pow($base, $pow);
		$sijainti = $kerroinrivi['toimipisteenSijainti'] * $sijainti;
		
		$sijainti = verifyValue($sijainti);
		return $sijainti;
	}

/**
 * Laskee asiakassuhteiden vaikutuksen
 *
 * @access	public
 * @param	int		$saldo
 * @return	int		$suhteet
 */
	function laskeAsiakassuhteet($saldo){
		$kerroinrivi = sutinakertoimet();
	
		$base = 1.14;
		$mod = 1/980;
		$pow = $saldo * $mod;
	
		$suhteet = pow($base, $pow);
		$suhteet = $kerroinrivi['asiakassuhteet'] * $suhteet;
		
		$suhteet = verifyValue($suhteet);
		return $suhteet;
	}

/**
 * Laskee henkilöstöpanoksen vaikutuksen
 *
 * @access	public
 * @param	int		$saldo
 * @return	int		$henkilostopanos
 */
	function laskeHenkilostopanos($saldo){
		$kerroinrivi = sutinakertoimet();
	
		$base = 1.14;
		$mod = 1/980;
		$pow = $saldo * $mod;
	
		$henkilostopanos = pow($base, $pow);
		$henkilostopanos = $kerroinrivi['henkilostopanos'] * $henkilostopanos;
		
		$henkilostopanos = verifyValue($henkilostopanos);
		return $henkilostopanos;
	}
	
/**
 * Laskee tehtyjen työtuntien vaikutuksen
 *
 * @access	public
 * @param	int		$saldo
 * @return	int		$tyotunnit
 */
	function laskeTyotunnit($saldo){
		$saldo = $saldo-80;
		$kerroinrivi = sutinakertoimet();
		
		$base = 3.05;
		$mod = 1/1000;
		$pow = $saldo * $mod;
		
		$tyotunnit = pow($base, $pow);
		$tyotunnit = $kerroinrivi['tyotunnit'] * $tyotunnit;
		
		$tyotunnit = verifyValue($tyotunnit);
		return $tyotunnit;
	}

/**
 * Laskee CRM:n vaikutuksen
 *
 * @access	public
 * @param	int		$saldo
 * @return	int		$CRM
 */
	function laskeCRM($saldo){
		$kerroinrivi = sutinakertoimet();
		
		// TODO
		$CRM = 1;
		
		$CRM = verifyValue($CRM);
		return $CRM;
	}

?>