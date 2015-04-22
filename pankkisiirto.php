<?php
	require_once 'inc/parametrit.inc';
	
	// käyttäjä on painanut Jatka -nappia
	if ($_POST[ 'jatka' ]) {
		
		// muuttuja, joka lisätään tekstitiedostoon
		$luonti = getdate();
		$maksupvm = $_POST[ 'maksupvm' ];
		$maksajanNimi = $_POST[ 'maksajanNimi' ];
		$saajanNimi = $_POST[ 'saajanNimi' ];
		$maksaja = $_POST[ 'maksajanTili' ];
		$saaja = $_POST[ 'saajanTili' ];
		$errorText = '';
		
		// tarkitetaan ja formatoidaan maksajan pankkitili
		$pankkitili = $maksaja;
		// require 'inc/pankkitilinoikeellisuus.php';
		if (empty($pankkitili)) {
			$errorText = 'Maksajan tilinumero on virheellinen.';
		} else {
			$maksaja = $pankkitili;
		}
		
		// tarkitetaan ja formatoidaan saajan pankkitili
		$pankkitili = $saaja;
		// require 'inc/pankkitilinoikeellisuus.php';
		if (empty($pankkitili)) {
			$errorText = 'Saajan tilinumero on virheellinen.';
		} else {
			$saaja = $pankkitili;
		}
		
		// saaja ja maksaja ei voi olla sama yritys
		if ($saaja == $maksaja) {
			$errorText = 'Et voi maksaa omalle tilillesi.';
		}
		
		// tarkistetaan alkaako maksajan pankkitili kirjaimilla FI
		if (substr($maksaja, 0, 2) == 'FI') {
		
			$query = "SELECT	omistaja
						FROM	TAMK_pankkitili 
						WHERE	yhtio = 'pankk'
						AND		tilinro = '$maksaja' 
						";
			
			$result = mysql_query($query);
			
			if (mysql_num_rows($result) == 0) {
				// tilinumeroa ei ole olemassa
				$errorText = 'Maksajan tilinumero on virheellinen.';
			} else {
				if (empty($maksajanNimi)) {
					$row = mysql_fetch_array($result);
					$maksajanNimi = $row[ 'omistaja' ];
				}
			}
		}
		else {
			$errorText = 'Maksajan tilinumero on virheellinen.';
		}
		
		// tarkistetaan alkaako saajan pankkitili kirjaimilla FI
		if (substr($saaja, 0, 2) == 'FI') {
		
			$query = "SELECT	omistaja
						FROM	TAMK_pankkitili 
						WHERE	yhtio = 'pankk'
						AND		tilinro = '$saaja' 
						";
			
			$result = mysql_query($query);
			
			if (mysql_num_rows($result) == 0) {
				// tilinumeroa ei ole olemassa
				$errorText = 'Saajan tilinumero on virheellinen.';
			} else {
				if (empty($saajanNimi)) {
					$row = mysql_fetch_array($result);
					$saajanNimi = $row[ 'omistaja' ];
				}
			}
		}
		else {
			$errorText = 'Saajan tilinumero on virheellinen.';
		}
		$viite = $_POST[ 'viite' ];
		$viesti = $_POST[ 'viesti' ];
		$summa = str_replace(',','.',$_POST[ 'summa' ]);
		
		// summan pitää olla yli 0
		if ($summa <= 0) {
			$errorText = 'Syötä maksun summa.';
		}
		// tarkistetaan, että on rahaa maksaa maksu (jos tapahtumapäivä on nyt)
		if ($maksupvm == date('Y-m-d')) {
			if ($summa > getSaldo($maksaja, $maksupvm)) {
				// rahaa ei ole tarpeeksi
				$errorText = 'Tilinsaldo ei riitä maksuun.';
			} else {
				// rahaa on tarpeeksi, ei toimintoja
			}
		}
		
		// Tarkastetaan, että päivämäärä on oikeassa muodossa
		if(strlen($maksupvm) != 10) {
			$errorText = 'Anna päivämäärä muodossa pp.kk.vvvv.';
		}
		else {
			$maksupvm = mysql_real_escape_string(substr($maksupvm, 6, 4) . '-' . substr($maksupvm, 3, 2) . '-' . substr($maksupvm, 0, 2));
		}
		
		$tapvm = $maksupvm;
		
		$query = 
		"insert into TAMK_pankkitapahtuma set 
		yhtio = 'pankk',
		saaja='$saaja',
		saajanNimi = '$saajanNimi',
		maksaja='$maksaja',
		maksajanNimi = '$maksajanNimi',
		summa='$summa',
		tapvm=if('$tapvm' < now(), now(), '$tapvm'),
		kurssi=1,
		valkoodi = 'EUR',
		viite = '$viite',
		selite = '$viesti',
		arkistotunnus = md5(now()), 
		laatija='$kukarow[kuka]',
		luontiaika=now()
		";
		
		//echo $query;
		if (empty($errorText)) {
			mysql_query($query);
			print '<p>maksu suoritettu</p>';
		}
	} 
	
	if (!isset($errorText) or !empty($errorText)) {
	// uuden maksun syöttö -lomake
		print ' <div id="uusiMaksuLomake">
					<p class="head">Uusi maksu:<hr/></p>
						<p class="error">' . $errorText . '</p>
						<form action="" method="post" >
							<table id="uusiMaksuKentat">
								<tr>
								<td>Maksetaan tililtä</td>
								<td>
								<input type="text" name="maksajanTili" value="' . $maksaja . '" />
								</td>
								</tr>
								<tr>
								<td>Maksajan nimi</td>
								<td>
								<input type="text" name="maksajanNimi" value="' . $maksajanNimi . '" />
								</td>
								</tr>
								<tr>
								<td>&nbsp;</td><td>&nbsp;</td>
								</tr>
								
								<tr>
								<td>Saajan tilinumero</td>
								<td>
								<input type="text" name="saajanTili" value="' . $saaja .  '" size="20" maxlength="20" class="kentta" />
								</td>
								</tr>
								<tr>
								<td>Saajan nimi</td>
								<td><input type="text" name="saajanNimi" value="' . $saajanNimi . '" size="20" maxlength="30" class="kentta" />
								</td>
								</tr>
								<tr>
								<td>Eräpäivä</td>
								<td> <input type="text" name="maksupvm" size="10" maxlength="10" class="kentta" id="date" value="' . $maksunErapaiva . '"/>  (pp.kk.vvvv)
								</td>
								</tr>
								<tr>
								<td>Maksun määrä</td>
								<td><input type="text" name="summa" value="' . $summa . '" size="10" maxlength="15" class="kentta" id="maksunMaaraKentta" />EUR</td>
								</tr>
								<tr>
								<td>Viite</td>
								<td><input type="text" name="viite" value="' . $viite . '" size="20" maxlength="15" class="kentta" /></td>
								</tr>
								<tr>
								<td>Viesti</td>
								<td><textarea class="kentta" name="viesti" >' . $viesti . '</textarea></td>
								</tr>
								<tr>
								<td>Tilioteteksti</td>
								<td>tilisiirto</td>
								</tr>
							</table>
							
							<p id="painikkeet">
							<input type="submit" name="jatka" value="JATKA" class="painike"/>
							<input type="reset" value="TYHJENNÄ" class="painike"/>
							</p>
						</form>
				</div><!-- uusiMaksuLomake -->';
	}

	function getSaldo($tilinro, $pvm) {
		//exec("echo $tilinro > KAKKAHOUSU.txt");
		$query = "	SELECT	sum(if(saaja = '$tilinro', summa, summa * -1))
					FROM	TAMK_pankkitapahtuma
					WHERE	(saaja = '$tilinro' OR maksaja = '$tilinro')
					AND		tapvm <= '$pvm' 
					";
		$result = mysql_query($query) or pupe_error($query);
		
		$row = mysql_fetch_array($result);
		
		$tilinSaldo = $row[0];
				
		return $tilinSaldo;
	} // end of function getSaldo()

	
?>
