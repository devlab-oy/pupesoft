<?php
	require ("inc/parametrit.inc");
	require ("inc/tilinumero.inc");

	echo "<font class='head'>".t("Anna lainaa")."</font><hr>";
	
	$errorText = '';
	if ($tee != '') {
		// Etsit‰‰n tili
		$query = "	SELECT *
					FROM TAMK_pankkitili
					WHERE yhtio = '$kukarow[yhtio]' and tilinro = '$tilinro'";
		$result = mysql_query($query) or pupe_error($query);
		if (mysql_num_rows($result) == 0) {
			$errorText = 'Lainatili‰ ei lˆydy';
		}
		else {
			$tilrow = mysql_fetch_array($result);
			
			// tehd‰‰n viite
			$viite = $yhtiorow[ytunnus].time().rand(0,9);
			require ("inc/generoiviite.inc");
			
			// summa ja korko% mysql:lle sopivaksi
			//$summa = number_format($summa, 2, '.', '');
			$summa = str_replace(',', '.', $summa);  //  Lis‰tty 31.10.2013
			
			if($summa <= 0) {
				$errorText = 'Voit syˆtt‰‰ vain positiivisia lukuja';
			}
			
			//$korkoprosentti = number_format($korkoprosentti, 2, '.', '');
			$korkoprosentti = str_replace(',', '.', $korkoprosentti);  //  Lis‰tty 31.10.2013
			
			if($korkoprosentti <= 0) {
				$errorText = 'Et voi syˆtt‰‰ negatiivisia lukuja';
			}
			
			if($maksuera <= 0) {
				$errorText = 'Lainalle t‰ytyy antaa maksuer‰n suuruudeksi suurempi kuin nolla';
			}
			
			// jos tarvittavat tiedot annettu
			if($errorText == '' && !empty($summa) && !empty($tyyppi) && !empty($maksuera) && !empty($korkoprosentti) && (!empty($korkotyyppi) || ($korkotyyppi==1 && !empty($korkoprosentti)))){
				
				function getArchiveReferenceNumber() {
				$number = uniqid('41n0P');
				return $number;
				}				
				$arkistotunnus = getArchiveReferenceNumber();
				
				// tehd‰‰n header-tieto TAMK_pankkitapahtuma-tauluun
				$query = "	INSERT INTO TAMK_pankkitapahtuma
						(
						saaja
						, maksaja
						, tapvm
						, summa
						, arkistotunnus
						, selite
						, saajanNimi
						, maksajanNimi
						, yhtio
						, viite
						, eiVaikutaSaldoon
						)
						VALUES
						(
						'$tilinro'
						,'$yhtiorow[pankkitili1]'
						, now()
						, '$summa'
						, '$arkistotunnus'
						, 'Myˆnnetty laina'
						, '$tilrow[omistaja]'
						, '$yhtiorow[nimi]'
						, '$yhtiorow[yhtio]'
						, '$viite'
						, 'l'
						); ";
			
				$result = mysql_query($query) or pupe_error($query);
				
				// tehd‰‰n lainan tiedot TAMK_lainantiedot-tauluun
				
				// euribor-korkoisessa lainassa korkoprosentti merkit‰‰n nollaksi
				if($korkotyyppi>1){
					$korkomarginaali = $korkoprosentti;
					$korkoprosentti = 0;
				}
				else $korkomarginaali = 0;
				
				// er‰p‰iv‰ on toistaiseksi aina kuukauden ensimm‰inen p‰iv‰
				$erapaiva = 1;
				
				$maksuera = str_replace(',', '.', $maksuera);  //  Lis‰tty 31.10.2013
				
				$query = "	INSERT INTO TAMK_lainantiedot
							(
							arkistotunnus
							, korko
							, korkotyyppi
							, tyyppi
							, maksuera
							, erapaiva
							, korkomarginaali
							)
							VALUES
							(
							'$arkistotunnus'
							, '$korkoprosentti'
							, '$korkotyyppi'
							, '$tyyppi'
							, '$maksuera'
							, '$erapaiva'
							, '$korkomarginaali'
							)
						";
				$result = mysql_query($query) or pupe_error($query);
				
				echo "<font class='message'>".t("Annettiin lainaa ")."$tilrow[omistaja] $summa &euro;!</font><br><br>";
				// Tyhjennet‰‰n muuttujat ettei k‰ytt‰j‰lle tule houkutus antaa toista samanlaista lainaa
				unset($tilinro); unset($summa); unset($tyyppi); unset($maksuera); unset($korkotyyppi); unset($korkoprosentti);
				
				// Ajetaan euriborien tarkastus
				exec('php /var/www/html/ainopankki/tarkistaLyhennys.php');
			}
			else {
				$errorText = 'Virhe: Tarkista syˆtetyt tiedot';
			}
		}
	}
	
	echo "<table><form action = '' method='post'>";
	echo "<p class='error'>$errorText</p>";
	echo "	<tr>
				<th>".t("Anna tilinumero")."</th>
				<td><input type='text' name = 'tilinro' value='$tilinro'></td>
			</tr>";
	echo "	<tr>
				<th>".t("Summa")."</th>
				<td><input type='text' name = 'summa' value='$summa'></td>
			</tr>";		
	echo "	<tr><th>".t("Tyyppi")."</th>
				<td><select name = 'tyyppi'>
						<option value='0'>- valitse -</option>
						<option value='1'>Tasalyhennys </option>
						<option value='2'>Kiinte‰ tasaer‰ </option>
				</td>
			</tr>";	
	echo "	<tr><th>".t("Koron tyyppi")."</th>
				<td><select name = 'korkotyyppi'>
						<option value='0'>- valitse -</option>
						<option value='1'>Kiinte‰</option>
						<option value='2'>3kk Euribor</option>
						<option value='3'>6kk Euribor</option>
						<option value='4'>12kk Euribor</option>
				</td>
			</tr>";
	echo "	<tr><th>".t("Korko% / korkomarginaali")."</th>
				<td><input type='text' name = 'korkoprosentti' value='$korkoprosentti'></td>
			</tr>";	
	echo "	<tr><th>".t("Maksuer‰n suuruus")."</th>
				<td><input type='text' name = 'maksuera' value='$maksuera'></td>
			</tr>";
	echo "	<tr>
				<td></td>
				<td>
					<input type='hidden' name = 'tee' value='D'>
					<input type='Submit' value='".t('Anna laina')."'>
				</td>
			</tr>
			</form>
		</table>";
		
	echo "<p>Huom!</p>";
	echo "<p>Jos koron tyyppi euribor, korko% -kentt‰‰n voi m‰‰ritt‰‰ lainamarginaalin k‰ytt‰en pistett‰ desimaalimerkkin‰.</p>";
	echo "<p>Koron desimaalimerkkin‰ PISTE, eli korko muotoa &quot;4.50&quot;. Pilkkua k‰ytett‰ess‰ desimaalit hyl‰t‰‰n.</p>";
	echo "<p>Syˆt‰ lainasumma ilman tuhaterotinta.</p>";
	
	require ("inc/footer.inc");

?>
