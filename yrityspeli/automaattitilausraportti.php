<?php
/// automaattitilausraportti.php
/// TAMK Yrityspeli-valikko, sutinan automaattitilausten raportti
///
/// Annika Granlund, Jarmo Kortetj‰rvi
/// 2010-06-30

require ("/var/www/html/pupesoft/inc/parametrit.inc");

	echo "<font class='head'>Automaattitilaukset</font><hr/><br/>";

	if ($_POST[ 'hae' ]) {
		$aloituspvm = $_POST[ 'startdate' ];
		$lopetuspvm = $_POST[ 'enddate' ];
		$errorText = '';
		
		// tarkistetaan, ett‰ p‰iv‰m‰‰r‰t on annettu
		if ( !$aloituspvm ) {
			$errorText = 'Tarkista alkupvm.';
			$aloituspvm = false;
		}
		if ( !$lopetuspvm ) {
			$errorText = 'Tarkista loppupvm.';
			$lopetuspvm = false;
		}
		
		// jos errorTextiss‰ on jotain, se tulostetaan
		if ( $errorText ) {
			print "<font class='error'>$errorText</font><br/>";
			getSearchForm();
		}
		
		// jos kaikki ok (aloitus ja lopetuspvm:t annettu), tehd‰‰n kyselyt valintojen mukaan
		if ( $aloituspvm != false and $lopetuspvm != false and empty($errorText) ) {
			// aloitusp‰iv‰m‰‰r‰ sql-kysely‰ varten (datetime muodossa)
			$aloitusPvmMySql = date( 'Y-m-d', strtotime($aloituspvm) ) . ' 00:00:00';
			// lopetusp‰iv‰m‰‰r‰ sql-kysely‰ varten (datetime muodossa)
			$lopetusPvmMySql = date( 'Y-m-d', strtotime($lopetuspvm) ) . ' 23:59:59';

			$query = " 	SELECT 	*
								, DATE_FORMAT(tilausaika, '%d.%m.%Y') AS pvm
								, DATE_FORMAT(tilausaika, '%H:%i') AS aika
						FROM	TAMK_automaattitilaus
						WHERE	tilausaika >= '$aloitusPvmMySql' AND tilausaika <= '$lopetusPvmMySql'
						ORDER BY tilausaika ASC
					";
					
			$result = mysql_query($query);	
			$numrows = mysql_num_rows($result);
			
			if($numrows > 0){
				echo "	<script type='text/javascript' src='/lib/standardista_table_sorting/common.js'></script>
						<script type='text/javascript' src='/lib/standardista_table_sorting/css.js'></script>
						<script type='text/javascript' src='/lib/standardista_table_sorting/standardista-table-sorting.js'></script>
					";
					
				echo "	<table class='sortable' id='automaattitilaukset'>
							<thead>
							<tr>
								<th>Viikko</th>
								<th>Tilauspvm</th>
								<th>Tilausaika</th>
								<th>Tilaustyyppi</th>
								<th>Summaosuus</th>
								<th>Tilausrivit</th>
								<th>Suoritettu</th>
							</tr>
							</thead>
							<tbody>
					";
				
				while($row = mysql_fetch_assoc($result)){
				
					// tilaustyyppi
						switch ($row['tilaustyyppi']){
							case 1:
								$tyyppi = "sutina";
								break;
							case 2:
								$tyyppi = "random";
								break;
							default:
								$tyyppi = "tuntematon";
								break;
						}
					// suoritettu
						if($row[suoritettu] == 1) $suoritettu = "kyll‰";
						else $suoritettu = "ei";
					
						$viikko = substr($row[viikko],5,2);
				
					echo "	<tr>
								<td class='numeric'>$viikko</td>
								<td>$row[pvm]</td>
								<td>$row[aika]</td>
								<td>$tyyppi</td>
								<td class='numeric'>$row[summa]%</td>
								<td class='numeric'>$row[tilausrivit]</td>
								<td>$suoritettu</td>
							</tr>
						";
				}
				echo	"</tbody></table>";
			}
			else{
				echo "<p>Ei automaattitilauksia haetulla aikav‰lill‰.<br/>
					<a href='automaattitilausraportti.php'>Uusi haku</a></p>";
			}
		}
	}
	else{
		// lomake, jolla k‰ytt‰j‰ valitsee aikav‰lin sek‰ raporttityypin
		getSearchForm();
	}

/**
 * Piirt‰‰ automaattitilausten lomakkeen, joka sis‰lt‰‰ aikav‰lihakukent‰t (aloitus ja lopetuspvm) ja hae-napin.
 *
 * @access   public
 * @param    mixed   $startDate   oletuksena false, haun aloitusp‰iv‰m‰‰r‰
 * @param    mixed   $endDate     oletuksena false, haun lopetusp‰iv‰m‰‰r‰
 */
function getSearchForm($startDate = false, $endDate = false) {
	print	"<font class='message'>Valitse aikav‰li ja paina hae.</font><br/>
		
		<style>";
			// kalenterin tyylitiedosto
			require_once '../../lib/calendar/calendar.css';
			
print 	"</style>
		<form action='' method='post'>
			<p>Syˆt‰ tiedot muodossa pp.kk.vvvv</p>
			<table>
			<tr>
				<td>Alkupvm</td>
				<td>
					<input type='text' name='startdate' id='startdate' value='$startDate' />
					<script type='text/javascript' src='../../lib/calendar/calendar.js' ></script>
					<script type='text/javascript' >
						calendar.set('startdate');
					</script>
				</td>
			</tr>
			<tr>
				<td>Loppupvm</td>
				<td>
					<input type='text' name='enddate' id='enddate' value='$endDate' />
					<script type='text/javascript' scr='../../lib/calendar/calendar.js' ></script>
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

require("/var/www/html/pupesoft/inc/footer.inc");
?>