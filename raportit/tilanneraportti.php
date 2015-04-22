<?php
/// tilanneraportti.php
/// TAMK Opettajat-valikko, tilanneraportti yritysten perustiedoista sek‰ yksitt‰isten henkilˆiden tekemist‰ toiminnoista
///
/// Annika Granlund
/// annika.j.granlund@gmail.com
/// 2010-07-07
/// modified: 2010-08-19

///
/// Sarakkeen lis‰‰minen tilanneraporttiin
///
/// PERUSTIETOJEN MƒƒRƒ
/// - lis‰‰ $hakutaulut tauluun uuden tiedon ja taulun nimi 
/// - muokkaa mysql-kysely‰ uuden haun mukaiseksi (tai lis‰‰ uusi hakuehto), mik‰li tarpeen
///
/// TAPAHTUMIEN LAATIJAT
/// - lis‰‰ $query2 kysely (UNION ALL), jonka haluat tulostuvan raporttiin, tiedot pit‰‰ groupata laatijan mukaan (ks. mallia nykyisist‰ kyselyist‰)
///

require_once "../inc/parametrit.inc";

print "	<font class='head'>Tilanneraportti</font><hr>
		<font>Mik‰li haluat vaihtaa yrityst‰, valitse valikosta Opettajat -> Valitse yritys</font><br/><br/>";

// jos painettu hae-nappia
if ( $_POST[ 'hae' ] ) {
	print "<font>N‰in luet raporttia<br/>
			(Tapahtumien j‰rjest‰minen tapahtuu klikkaamalla alleviivattua solun otsikkoteksti‰.)<br/><br/></font>
		<font class='message'>
			Perustietojen m‰‰r‰ (kent‰t): <br/>
		</font>
		<font>
			Tieto: mit‰ tietoa kyseinen rivi edustaa.<br/>
			Kpl: montako kappaletta kyseist‰ tietoa on antamallasi hakuajalla k‰sitelty<br/>
			Keskiarvo/OPY: montako kappaletta OPYt ovat keskim‰‰rin tehneet kyseiseen tietoon hakuajan sis‰ll‰<br/>
		</font>
		<font class='message'>
			Tapahtumien laatijat: <br/>
		</font>
		<font>
			Tekij‰: opiskelijan nimi<br/>
			Mahdolliset tiedot: perustiedot, osto ja myynti, pankkitapahtumat, tyˆajan seuranta<br/>
			HUOM! Jos jotakin mahdollisista tiedoista (ks. yll‰) ei ole listattuna, tarkoittaa se, 
			ett‰ kukaan ei ole k‰sitellyt kyseist‰ tietoa antamallasi hakuajalla.<br/>
			P‰iv‰m‰‰r‰ suluissa tarkoittaa p‰iv‰‰, joilloin tekij‰ on viimeksi k‰sitellyt kyseist‰ tietoa. <br/>
			Kpl keskim‰‰rin/tekij‰: yrityksen opiskelijoiden keskim‰‰r‰inen muutosten m‰‰r‰ antamallasi hakuajalla
			</font><br/><br/>";

	$aloitusPvm = $_POST[ 'startdate' ];
	// aloitusp‰iv‰m‰‰r‰ sql-kysely‰ varten (datetime muodossa)
	$aloitusPvmMySql = date( 'Y-m-d', strtotime($aloitusPvm) ) . ' 00:00:00';
	$lopetusPvm = $_POST[ 'enddate' ];
	// lopetusp‰iv‰m‰‰r‰ sql-kysely‰ varten (datetime muodossa)
	$lopetusPvmMySql = date( 'Y-m-d', strtotime($lopetusPvm) ) . '23:59:59';
	$valittu = $yhtiorow[ 'yhtio' ];

	// jos alkupvm ei ole annettu
	if( $aloitusPvm == false ){
		$errorText = "Tarkista alkup‰iv‰m‰‰r‰.";
	}
	// jos loppupvm ei ole annettu
	if( $lopetusPvm == false ){
		$errorText = "Tarkista loppup‰iv‰m‰‰r‰.";
	}
	
	$nyt = date('d.m.Y');
	// jos alkupvm on tulevaisuudessa
	if( strtotime($aloitusPvm) > strtotime($nyt) ) {
		$errorText = "Tarkista alkup‰iv‰m‰‰r‰. (Et voi hakea tapahtumia tulevaisuudesta.)";
		$aloitusPvm = false;
	}
	// jos loppupvm on tulevaisuudessa
	if ( strtotime($lopetusPvm) > strtotime($nyt) ) {
		$errorText = "Tarkista loppup‰iv‰m‰‰r‰. (Et voi hakea tapahtumia tulevaisuudesta.)";
		$lopetusPvm = false;
	}
	
	// jos loppupvm on aiemmin kuin aloituspvm
	if ( strtotime($lopetusPvm) < strtotime($aloitusPvm)) {
		$errorText = "Tarkista p‰iv‰m‰‰r‰t.";
		$lopetusPvm = false;
		$aloitusPvm = false;
	}
	
	// pilkotaan p‰iv‰m‰‰r‰t osiin (p‰iv‰, kk, vuosi) checkdatea varten
	$aloitusPvmArray = explode('.', $aloitusPvm);
	$lopetusPvmArray = explode('.', $lopetusPvm);
	// tarkistetaan, ett‰ aloitusp‰iv‰m‰‰r‰ on oikeasti olemassa
	if ( checkdate( $aloitusPvmArray[1], $aloitusPvmArray[0], $aloitusPvmArray[2] ) == false ) {
		$errorText = "Tarkista alkup‰iv‰m‰‰r‰.";
		$aloitusPvmArray = false;
	}
	// tarkistetaan, ett‰ lopetusp‰iv‰m‰‰r‰ on oikeasti olemassa
	if ( checkdate( $lopetusPvmArray[1], $lopetusPvmArray[0], $lopetusPvmArray[2] ) == false ) {
		$errorText = "Tarkista alkup‰iv‰m‰‰r‰.";
		$lopetusPvmArray = false;
	}
	
	// jos errorTextiss‰ on tietoa, tulostetaan se
	if ($errorText) {
		print "<font class='error'>$errorText</font><br/>";
		getSearchForm( $aloitusPvm, $lopetusPvm );
	}
	
	// jos alku- ja loppupvm:t on annettu ja errorText on tyhj‰, tehd‰‰n raportti
	if( $aloitusPvm != false and $lopetusPvm != false and empty($errorText) ) {
	
		// haetaan valitun yhtion nimi, ytunnus ja pankkitilin tilinro
		$query = "	SELECT 	yhtio.nimi AS nimi
							, yhtio.ytunnus	AS ytunnus
							, TAMK_pankkitili.tilinro AS tilinro
					FROM 	yhtio
					JOIN	TAMK_pankkitili
					ON		yhtio.ytunnus = TAMK_pankkitili.ytunnus
					WHERE 	yhtio.yhtio = '$valittu' 
				";
		
		$result = mysql_query( $query ) or pupe_error( $query );
		$row = mysql_fetch_assoc($result);
		$yhtionNimi = $row[ 'nimi' ];
		$yhtionYtunnus = $row[ 'ytunnus' ];
		$yhtionPankkitili = $row[ 'tilinro' ];
	
		// Haetaan erilaisia tapahtumia ja groupataan ne laatijan mukaan.
		// Hakuehtona t‰ytyy olla kuka.profiilit = 'opiskelija', jotta vain opiskelija-profiililla olevien henkilˆiden tiedot haetaan
		// tapahtumat: 
		// 1. Haetaan kaikki perustietojen muutokset, jossa yhtiˆna on valittu yhtiˆ ja luontiaika on annettujen p‰iv‰m‰‰rien v‰lill‰,
		// tapahtumat lasketaan ja tiedot groupataan laatijan mukaan.
		// Perustietojen muutokset haetaan tauluista asiakas, toimi (toimittaja), tuote.
		// 2. Haetaan myynti- ja ostolaskujen muutokset (lasku.tila = 'o' (osto) tai 'l' (myynti)), jossa yhtiona on valittu yhtiˆ ja luontiaika
		// on annettujen p‰iv‰m‰‰rien v‰lill‰, tapahtumat lasketaan ja tiedot groupataan laatijan mukaan.
		// 3. Haetaan pankkitapahtumien muutokset tilinumeron perusteella (maksajana valittu yhtiˆ), luontiajan tulee olla valittujen
		// p‰iv‰m‰‰rien v‰lill‰, tapahtumat lasketaan ja groupataan laatijan perusteella.
		// 4. Haetaan tyˆtuntien m‰‰r‰, yhtiˆn‰ on valittu yhtio ja pvm on annettujen p‰iv‰m‰‰rien v‰lill‰, 
		// tunnit lasketaan ja tiedot groupataan kukan perusteella
		$query2 = "	SELECT 		laatija AS laatija
								, DATE_FORMAT(MAX(luontiaika), '(%d.%m.%Y)') AS luontiaika
								, count(laatija) AS kpl
								, 'perustiedot'  AS taulu
					FROM 
					(
					SELECT 		asiakas.laatija AS laatija
								, asiakas.luontiaika AS luontiaika
								, asiakas.tunnus AS tunnus
					FROM 		asiakas 
					JOIN		kuka
					ON			asiakas.laatija = kuka.kuka
					WHERE 		asiakas.yhtio = '$valittu' 
					AND 		(asiakas.luontiaika >= '$aloitusPvmMySql' AND asiakas.luontiaika <= '$lopetusPvmMySql') 
					AND			kuka.profiilit = 'opiskelija'
					UNION ALL 
					SELECT 		toimi.laatija AS laatija
								, toimi.luontiaika AS luontiaika
								, toimi.tunnus AS tunnus
					FROM 		toimi 
					JOIN		kuka
					ON			toimi.laatija = kuka.kuka
					WHERE 		toimi.yhtio = '$valittu' 
					AND 		(toimi.luontiaika >= '$aloitusPvmMySql' AND toimi.luontiaika <= '$lopetusPvmMySql') 
					AND			kuka.profiilit = 'opiskelija'
					UNION ALL 
					SELECT 		tuote.laatija 
								, tuote.luontiaika
								, tuote.tunnus
					FROM 		tuote 
					JOIN		kuka
					ON			tuote.laatija = kuka.kuka
					WHERE 		tuote.yhtio = '$valittu' 
					AND 		(tuote.luontiaika >= '$aloitusPvmMySql' AND tuote.luontiaika <= '$lopetusPvmMySql') 
					AND			kuka.profiilit = 'opiskelija'
					) AS peruslaatija 
					GROUP BY	laatija
					UNION ALL
					SELECT		lasku.laatija AS laatija
								, DATE_FORMAT(MAX(lasku.luontiaika), '(%d.%m.%Y)') AS luontiaika
								, count(lasku.laatija) AS kpl
								, 'osto ja myynti' AS taulu
					FROM		lasku
					JOIN		kuka
					ON			lasku.laatija = kuka.kuka
					WHERE		lasku.yhtio = '$valittu'
					AND			(lasku.tila = 'o' OR lasku.tila = 'l')
					AND			(lasku.luontiaika >= '$aloitusPvmMySql' AND lasku.luontiaika <= '$lopetusPvmMySql')
					AND			kuka.profiilit = 'opiskelija'
					GROUP BY	lasku.laatija
					UNION ALL
					SELECT		TAMK_pankkitapahtuma.laatija AS laatija
								, DATE_FORMAT(MAX(TAMK_pankkitapahtuma.luontiaika), '(%d.%m.%Y)') AS luontiaika
								, count(TAMK_pankkitapahtuma.luontiaika) AS kpl
								, 'pankkitapahtumat' AS taulu
					FROM		TAMK_pankkitapahtuma
					JOIN		kuka
					ON			TAMK_pankkitapahtuma.laatija = kuka.kuka
					WHERE		(TAMK_pankkitapahtuma.maksaja = '$yhtionPankkitili')
					AND			(TAMK_pankkitapahtuma.luontiaika >= '$aloitusPvmMySql' AND TAMK_pankkitapahtuma.luontiaika <= '$lopetusPvmMySql')
					AND 		kuka.profiilit = 'opiskelija'
					GROUP BY 	TAMK_pankkitapahtuma.laatija
					UNION ALL
					SELECT 		TAMK_tyoaika.kuka AS laatija
								, DATE_FORMAT(MAX(TAMK_tyoaika.pvm), '(%d.%m.%Y)') AS luontiaika
								, SUM(TAMK_tyoaika.tunnit) AS kpl 
								, 'tyotunnit' AS taulu 
					FROM 		TAMK_tyoaika
					JOIN		kuka
					ON			TAMK_tyoaika.kuka = kuka.kuka
					WHERE		TAMK_tyoaika.yhtio = '$valittu'
					AND			(TAMK_tyoaika.pvm >= '$aloitusPvmMySql' AND TAMK_tyoaika.pvm <= '$lopetusPvmMySql')
					AND 		kuka.profiilit = 'opiskelija'
					GROUP BY 	TAMK_tyoaika.kuka;
					";

		$result2 = mysql_query( $query2 ) or pupe_error( $query2 );

		// luodaan taulu k‰ytt‰jien tapahtumia varten
		$tapahtumat = array();
		// luodaan taulu eri taulujen nimi‰ ja tietoja varten
		$tapahtumaTaulut = array();
		
		// lis‰t‰‰n tietoja tapahtumat-tauluun laatijan nimen mukaan sek‰ tapahtumaTaulut-tauluun taulun nimen mukaan
		while ( $row2 = mysql_fetch_assoc( $result2 ) ) {
			$laatija = $row2[ 'laatija' ];
			$kpl = $row2[ 'kpl' ];
			$aika = $row2[ 'luontiaika' ];
			$taulu = $row2[ 'taulu' ];
			
			// jos tapahtumat taulusta lˆytyy jo avain $laatija
			if ( array_key_exists($laatija, $tapahtumat) === true ) {
				// lis‰t‰‰n tapahtumat tauluun soluun laatija uusi taulu, jossa taulun nimen‰ tietokannasta tulevan taulun nimi
				// ja tietoina kyseisen taulun tapahtumin lukum‰‰r‰ ja viimeisin aika, jolloin henkilˆ on tietoa lis‰nnyt
				$tapahtumat[$laatija][$taulu] = array( 'kpl' => $kpl, 'aika' => $aika );
			} 
			// jos arvoa ei lˆydy
			else {
				// luodaan uusi taulu uuteen soluun nimell‰ $laatija ja lis‰t‰‰n sen ensimm‰iseen kentt‰‰n taulu $row[ 'taulu' ]
				// ja siihen tiedot kpl-m‰‰r‰sr‰ ja viimeisimm‰st‰ ajasta
				$tapahtumat[$laatija] = array();
				$tapahtumat[$laatija][$taulu] = array( 'kpl' => $kpl, 'aika' => $aika ); 
			}
			
			// jos taulun nimi on jo tapahtumaTauluissa, lasketaan arvot ja lis‰t‰‰n ne vain soluun $taulu
			if ( array_key_exists($taulu, $tapahtumaTaulut) ) {
				// kyseisen taulun tapahtumien kpl-m‰‰‰r‰ ja kasvatetaan yhdell‰
				$tempKpl = $tapahtumaTaulut[$taulu][ 'kpl' ];
				$tempKpl++;
				// kyseisen taulun tapahtumien yhteism‰‰r‰, lis‰t‰‰n m‰‰r‰n lis‰tt‰v‰ arvo (tapahtuman lukum‰‰r‰)
				$tempMaara = $tapahtumaTaulut[$taulu][ 'maara' ];
				$tempMaara = $tempMaara + $kpl;
				// lis‰t‰‰n uudet lasketut arvot tauluun
				$tapahtumaTaulut[$taulu] = array( 'kpl' => $tempKpl, 'maara' => $tempMaara ); 
			} 
			// jos taulun nime‰ ei ole tapahtumaTauluissa, lis‰t‰‰n taulu (taulun nimi) avaimeksi ja arvot tauluun
			else {
				// lis‰tt‰v‰ kpl-m‰‰r‰ on t‰ss‰ tapauksessa aina 1 (kyse ensimm‰isest‰ tapahtumasta), m‰‰r‰ on kyseisen tapahtuman kpl-m‰‰r‰
				$tapahtumaTaulut[$taulu] = array( 'kpl' => '1', 'maara' => $kpl ); 
			}
		}

		// aloitetaan raportin piirt‰minen, yll‰olevaa kysely‰ ja sen tietoja k‰ytet‰‰n vasta hetken kuluttua (ks. alla)
		// javascript tapahtumien sorttausta varten
		// <script type='text/javascript' src='/lib/sortable/sortable.js' ></script>
		print "	<script type='text/javascript' src='/lib/standardista_table_sorting/common.js'></script>
				<script type='text/javascript' src='/lib/standardista_table_sorting/css.js'></script>
				<script type='text/javascript' src='/lib/standardista_table_sorting/standardista-table-sorting.js'></script>

				<table>
					<tr>
						<th colspan='2'>$yhtionNimi, $aloitusPvm - $lopetusPvm</th>
					</tr>
					<tr>
						<td><font class='head'>Perustietojen m‰‰r‰</font></td>
						<td><font class='head'>Tapahtumien laatijat</font></td>
					</tr>
					<tr>
						<td>
							<table>
								<tr>
									<th>Tieto</th>
									<th>Kpl</th>
									<th>Keskiarvo/OPY</th>
								</tr>
							";
							
						// perustietoihin liittyv‰t taulut $hakutaulut arrayhin
						// key = kent‰n nimi, value = taulu, josta tieto haetaan
						$hakutaulut = array( 'asiakas' => 'asiakas'
											, 'toimittaja' => 'toimi'
											, 'tuote' => 'tuote'
											, 'ostolasku' => 'lasku'
											, 'myyntilasku' => 'lasku'
											, 'pankkitapahtumat' => 'TAMK_pankkitapahtuma'
											, 'crm' => 'kalenteri');
						
						// loopataan hakutaulut l‰pi ja haetaan tiedot kannasta m‰‰riteltyjen ehtojen perusteella (kt. alla)
						foreach ($hakutaulut as $sarakkeenNimi => $taulunNimi) {
							if ( $taulunNimi == 'TAMK_pankkitapahtuma' ) {
								// pankkitapahtumakysely haettava tilinumeron perusteella, kuka.profiili tulee olla 'opiskelija'
								$queryPerus = " 	SELECT 		yhtio.yhtio AS yhtio
																, count($taulunNimi.maksaja) AS peruskpl 
													FROM 		$taulunNimi
													JOIN		kuka
													ON			TAMK_pankkitapahtuma.laatija = kuka.kuka
													JOIN		TAMK_pankkitili
													ON			TAMK_pankkitapahtuma.maksaja = TAMK_pankkitili.tilinro
													JOIN		yhtio
													ON			TAMK_pankkitili.ytunnus = yhtio.ytunnus
													WHERE		yhtio.yhtiotyyppi = 'OPY'
													AND			($taulunNimi.luontiaika >= '$aloitusPvmMySql' AND $taulunNimi.luontiaika <= '$lopetusPvmMySql')
													AND			kuka.profiilit = 'opiskelija' 
													GROUP BY	TAMK_pankkitapahtuma.maksaja ";
							} else {
								// haetaan joka kyselyss‰ (paitsi pankkitapahtuma)
								$queryPerus = "	SELECT 		yhtio.yhtio AS yhtio
															, count($taulunNimi.yhtio) AS peruskpl 
												FROM 		$taulunNimi 
												";
								
								if ($taulunNimi == 'lasku') {
									// kysely joko osto- tai myyntilaskulle, 
									// yhtiˆn‰ tulee olla valittu yhtio ja laskun tilan tulee olla 'o' (osto) tai 'l' (myynti)
									// myynti- ja osto-laskut halutaan erotella, siksi 2 erillist‰ kysely‰ niille
									$queryPerus .= "JOIN 		yhtio 
													ON 			$taulunNimi.yhtio = yhtio.yhtio
													JOIN		kuka
													ON			$taulunNimi.laatija = kuka.kuka
													WHERE		yhtio.yhtiotyyppi = 'OPY' ";
									if ($sarakkeenNimi == 'ostolasku') {
										$queryPerus .= "AND		tila = 'o' ";
									} 
									else if ($sarakkeenNimi == 'myyntilasku') {
										$queryPerus .= "AND		tila = 'l' ";
									}
									$queryPerus .= "AND kuka.profiilit = 'opiskelija' ";
								} else if ($taulunNimi == 'kalenteri') {
									// crm-kysely
									// kalenteri-taulussa:
									// laatija = k‰ytt‰j‰, joka on lis‰nnyt tapahtuman
									// kuka = k‰ytt‰j‰, jonka kalenterissa tapahtuma on
									$queryPerus .= "JOIN		yhtio
													ON			$taulunNimi.yhtio = yhtio.yhtio
													JOIN		kuka
													ON			kalenteri.laatija = kuka.kuka
													WHERE		yhtio.yhtiotyyppi = 'OPY'
													AND			tyyppi = 'memo' ";
								} else {
									$queryPerus .= "JOIN		yhtio
													ON			$taulunNimi.yhtio = yhtio.yhtio
													JOIN		kuka
													ON			$taulunNimi.laatija = kuka.kuka
													WHERE		yhtio.yhtiotyyppi = 'OPY' ";
								}
								
								$queryPerus .= "AND			($taulunNimi.luontiaika >= '$aloitusPvmMySql' AND $taulunNimi.luontiaika <= '$lopetusPvmMySql')
												AND			kuka.profiilit = 'opiskelija' 
												GROUP BY 	$taulunNimi.yhtio";
							}
							
							$resultPerus = mysql_query( $queryPerus ) or pupe_error( $queryPerus );
							$numRows = mysql_num_rows( $resultPerus );
							$yhteensaKpl = 0;
							$peruskpl = '';
							
							if ($numRows > 0) {
								$rivienLkm = $numRows;
								// loopataan tulokset l‰pi, lasketaan yhteism‰‰r‰
								while ($rowPerus = mysql_fetch_assoc( $resultPerus )) {
									$yhteensaKpl +=  $rowPerus[ 'peruskpl' ];
								
									// otetaan talteen kyseisen yhtiˆn m‰‰r‰
									if ( $rowPerus[ 'yhtio' ] == $valittu ) {
										$peruskpl = $rowPerus[ 'peruskpl' ];
										break;
									} else {
										$peruskpl = 0;
									}
								}
							} else {
								$peruskpl = 0;
								$rivienLkm = 1;
							}
							
							// tulostetaan kyselyjen tulokset
							print "	<tr>
										<td>$sarakkeenNimi</td>
										<td>$peruskpl</td>
										<td>" . round($yhteensaKpl / $rivienLkm, 2) . " kpl</td>
									</tr>
									";
						}
		print "				</table>
						</td>
						<td>";
						// tapahtumien laatijat, thead, tfoot, tbody on sorttausta varten!
						// tfoot (se miss‰ keskiarvot eik‰ haluta, ett‰ se sorttautuu) pit‰‰ olla theadin ja tbodyn v‰liss‰, muuten ei toimi!!!
		print "				<table class='sortable'>
								<thead>
								<tr>
									<th>Tekij‰</th>
									<th>Profiili</th>";
							foreach ( $tapahtumaTaulut as $key3 => $value3 ) {
								print "<th>$key3</th>";
							}
		print "					</tr>
								</thead>
								<tfoot>
								<tr style='color: #433152;'>
									<td colspan='2' >Kpl keskim‰‰rin/tekij‰</td>";
							// lasketaan keskiarvot, n‰m‰ tiedot siis alimpaan riviin, ei sorttausta.
							foreach ( $tapahtumaTaulut as $key2 => $value2 ) {
								print "<td>" . round( ($tapahtumaTaulut[ $key2 ]['maara'] / $tapahtumaTaulut[ $key2 ]['kpl']), 2) . "</td>";
							}
						print "	</tr>
								</tfoot>
							<tbody>";
							
							// haetaan valitun yrityksen "tyˆntekij‰t" (nimi, nimen lyhenne, profiili)
							$query = "	SELECT		nimi
													, kuka
													, profiilit 
										FROM 		kuka 
										WHERE 		yhtio = '$valittu'
										AND			profiilit = 'opiskelija'
										ORDER BY	nimi;
										";
							$result = mysql_query( $query ) or pupe_error( $query );
							
							// loopataan kyselyn tulos
							while ( $row = mysql_fetch_assoc( $result ) ) {
								$nimi = $row[ 'nimi' ];
								$kuka = $row[ 'kuka' ];
								// tulostetaan nimi ja profiili
								print " <tr>
											<td>$nimi</td>
											<td>" . $row[ 'profiilit' ] . "</td>";
								
								// k‰yd‰‰n l‰pi aiemmin tehty‰ $tapahtumat-taulua
								foreach ($tapahtumat as $key => $value) {
									// jos tyˆntekij‰kyselyn t‰m‰n hetkinen arvo ($kuka) on sama kuin tapahtumat-taulun avain ($key)
									// tulostetaan arvot, muotoillaan samalla viimeisin ajankohta
									if ( $key === $kuka ) {
										// k‰yd‰‰n l‰pi $tapahtumaTauluja, jotta saadaan rivit tulostettua dynaamisesti
										foreach ( $tapahtumaTaulut as $key2 => $value2 ) {
											// tarkistetaan, ett‰ arvo on jotakin muuuta kuin 0
											if ($value[ $key2 ][ 'kpl' ] != 0) {
												print "	<td class='numeric'>" . $value[ $key2 ][ 'kpl' ] . " <span style='font-size: 70%; color: #222;'>" 
															. $value[ $key2 ][ 'aika' ] . "</span></td>";
											} else {
												print "<td>0</td>";
											}
										}
									} 
								}
								// jos henkilˆ‰ ei ole lainkaan $tapahtumat taulussa, tulostetaan 0
								if ( !array_key_exists( $kuka, $tapahtumat)) {
									// tulostetaan nollia niin  monta, kuin on sarakkeiden m‰‰r‰ (selvi‰‰ laskemalla $tapahtumaTaulut koko)
									for ( $i = 1; $i <= sizeof($tapahtumaTaulut); $i++) {
										print "<td>0</td>";
									}
								}
								print "</tr>";
							}
							
							
							/*
							print "	<tr class='sortbottom' style='color: #433152;'>
										<td colspan='2' >Kpl keskim‰‰rin/tekij‰</td>
										<td>" . round($tapahtumaTaulut['perus']['maara'] / $tapahtumaTaulut[ 'perus' ]['kpl'], 2) . "</td>
										<td>" . round($tapahtumaTaulut['lasku']['maara'] / $tapahtumaTaulut[ 'lasku' ]['kpl'], 2) . "</td>
										<td>" . round($tapahtumaTaulut['pankki']['maara'] / $tapahtumaTaulut[ 'pankki' ]['kpl'], 2) . "</td>
									</tr>";*/
		print "				</tbody>
							</table>
						</td>
					</tr>
				</table>";
		
	print "	<form action='' method='post'>
				<p><input type='submit' name='back' value='Takaisin hakuun'/></p>
			</form>";
	}
	
} else {
	getSearchForm();
}

/**
 * Piirt‰‰ tilanneraportin aikav‰lihakukent‰t (aloitus ja lopetuspvm)
 *
 * @access   public
 * @param    mixed   $startDate   oletuksena false, haun aloitusp‰iv‰m‰‰r‰
 * @param    mixed   $endDate     oletuksena false, haun lopetusp‰iv‰m‰‰r‰
 */
function getSearchForm( $startDate = false, $endDate = false ) {
	print	"<font class='message'>Valitse aikav‰lit ja paina hae.</font><br/>
		
		<style>";
			// kalenterin tyylitiedosto
			require_once '../../lib/calendar/calendar.css';
			
print 	"</style>
		<form action='' method='post'>
			<p>Syˆt‰ tiedot muodossa pp.kk.vvvv</p>
			<table>
			<tr>
			<td>alkupvm</td>
			<td>
			<input type='text' name='startdate' id='startdate' value='$startDate' />
			<script type='text/javascript' src='../../lib/calendar/calendar.js' ></script>
			<script type='text/javascript' >
				calendar.set('startdate');
			</script>
			</td>
			</tr>
			<tr>
			<td>loppupvm</td>
			<td>
			<input type='text' name='enddate' id='enddate' value='$endDate' />
			<!--<script type='text/javascript' src='../../lib/calendar/calendar.js' ></script>-->
			<script type='text/javascript' >
				calendar.set('enddate');
			</script>
			</td>
			</tr>
			</table>
			<p>
			<input type='submit' name='hae' value='Hae' />
			</p>
		
		</form>";
}

require ("../inc/footer.inc");

?>