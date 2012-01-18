<?php

	$no_head = "yes";

	require('inc/parametrit.inc');
	
	// tarkistetaan oikeuksia ja katsotaan etta muokataanko puuta vai puuliitoksia
	$saamuokata = $saamuokataliitosta = false;
	if($tee == 'valitsesegmentti' || $tee == 'addtotree' || $tee == 'removefromtree' && tarkista_oikeus('yllapito.php', 'puun_alkio', 1)) {
		$saamuokataliitosta = true;
	}
	elseif(tarkista_oikeus('dynaaminen_puu.php', $toim, 1)) {
		$saamuokata = true;
	}
	
	// tarvii romplata tekstimuuttujia kun tehdaan jQuery.ajaxin kanssa 
	$uusi_nimi	= utf8_decode($uusi_nimi);
	$uusi_koodi	= utf8_decode($uusi_koodi);
	
	function getnoderow ($toim, $nodeid) {
		global $yhtiorow, $kukarow; 
		$qu = "SELECT *
		FROM dynaaminen_puu
		WHERE dynaaminen_puu.yhtio = '{$yhtiorow['yhtio']}' AND dynaaminen_puu.laji = '{$toim}' AND dynaaminen_puu.tunnus = '{$nodeid}'";
		$re = pupe_query($qu);
		$numres = mysql_num_rows($re);
		
		if($numres > 0) {
			$row = mysql_fetch_assoc($re);
			return $row;
		}
		else {
			return false;
		}
	}
	
	// nodeid tarvitaan aina
	if(isset($nodeid) && isset($toim) && trim($toim) != "") {
		
		$noderow = getnoderow($toim, $nodeid);
		
		// muokkaustoiminnot
		if(isset($tee)) {
			if($saamuokata) {
					// Siirretään haaraa järjestyksessä ylös tai alas
				if ($tee == 'ylos' or $tee == 'alas') {
					$src['lft'] = $noderow['lft'];
					$src['rgt'] = $noderow['rgt'];
					
					// $tee:ssa on suunta mihin siirretään
					$kohde = SiirraTaso($toim, $src, $tee);
				}
				elseif($tee == 'lisaa' && isset($uusi_nimi) && trim($uusi_nimi) != "" && isset($uusi_koodi) && trim($uusi_koodi) != "") {
					// lisataan lapsitaso
					$uusirivi = LisaaLapsi($toim, $noderow['lft'], $noderow['syvyys'], $uusi_koodi, $uusi_nimi);
					
					echo "<input type='hidden' id='newid' value='{$uusirivi['tunnus']}' />
						  <input type='hidden' id='newcode' value='{$uusirivi['koodi']}' />";
				}
				elseif($tee == 'poista') {
					// poistaa ja upgradettaa alemmat lapset isommaksi.
					PoistaLapset($toim, $noderow['lft']);
				}
				elseif($tee == 'muokkaa' && isset($uusi_nimi) && trim($uusi_nimi) != "" && isset($uusi_koodi) && trim($uusi_koodi) != "") {
					paivitakat($toim, $uusi_koodi, $uusi_nimi, $nodeid);
				}
				// haetaan uudelleen paivittyneet
				$noderow = getnoderow($toim, $nodeid);
			}
			elseif($saamuokataliitosta) {
				if($tee == 'addtotree') {
					TuotteenAlkiot($toim, $liitos, $nodeid, $kieli); 
				}
				elseif($tee == 'removefromtree') {
					$qu = "DELETE FROM puun_alkio
							WHERE yhtio = '{$yhtiorow["yhtio"]}' AND laji = '{$toim}' AND liitos ='{$liitos}' AND puun_tunnus = {$nodeid}";
					$re = pupe_query($qu);
				}
			}
			
			$tee = '';
		}
		
		if ($noderow == false) {
			echo "<p>".t("Valitse uusi taso")."...</p>";
			exit;
		}
		
		echo "<h2 style='font-size: 20px'>".$noderow['nimi']."</h2><hr />
				<p><font class='message'>".t("Koodi").":</font> ".$noderow['koodi']."<br />".
				" <font class='message'>lft/rgt:</font> ".$noderow['lft']." / ".$noderow['rgt']."<br />".
				"<font class='message'>".t("Tunnus").":</font> ".$noderow['tunnus']."<br />".
				" <font class='message'>".t("Syvyys").":</font> ".$noderow['syvyys']."<br />".
				" <font class='message'>".t("Toimittajan koodi").":</font> ".$noderow['toimittajan_koodi'].
				"</p>";
		
		// tuotteet
		$qu = "SELECT count(*) lkm
				FROM puun_alkio
				WHERE yhtio = '{$yhtiorow['yhtio']}' AND laji = '{$toim}' AND puun_tunnus = '{$nodeid}'";
		$re = pupe_query($qu);
		$row = mysql_fetch_assoc($re);
		$own_items = $row['lkm'];
		
		// lapsitasojen tuotteet
		$qu = "SELECT count(*) lkm
				FROM dynaaminen_puu puu
				JOIN puun_alkio alkio ON (puu.yhtio = alkio.yhtio AND puu.tunnus = alkio.puun_tunnus)
				WHERE puu.yhtio = '{$yhtiorow['yhtio']}' AND puu.laji = '{$toim}' AND puu.lft > {$noderow['lft']} AND puu.rgt < {$noderow['rgt']}";
		$re = pupe_query($qu);
		$row = mysql_fetch_assoc($re);
		
		$child_items = $row['lkm'];
	
		echo "<p>";
		if($own_items>0) echo "<font class='message'>".t("Liitoksia").":</font> ".$own_items."<br />";
		if($child_items>0) echo "<font class='message'>".t("Liitoksia lapsitasoilla").":</font>".$child_items;
		echo "</p>";
		
		echo "<hr /><div id='editbuttons'>";
		if ($saamuokata) {
			echo "	<a href='#' id='showeditbox' id='muokkaa'><img src='{$palvelin2}pics/lullacons/document-properties.png' alt='",t('Muokkaa lapsikategoriaa'),"'/> ".t('Muokkaa tason tietoja')."</a><br /><br />
					<a href='#' class='editbtn' id='ylos'><img src='{$palvelin2}pics/lullacons/arrow-single-up-green.png' alt='",t('Siirrä ylöspäin'),"'/> ".t('Siirrä tasoa ylöspäin')."</a><br />
					<a href='#' class='editbtn' id='alas'><img src='{$palvelin2}pics/lullacons/arrow-single-down-green.png' alt='",t('Siirrä alaspäin'),"'/> ".t('Siirrä tasoa alaspäin')."</a><br /><br />
					<a href='#' id='showaddbox'><img src='{$palvelin2}pics/lullacons/add.png' alt='",t('Lisää'),"'/>".t('Lisää uusi lapsitaso')."</a><br /><br />";
			
			// poistonappi aktiivinen vain jos ei ole liitoksia
			if($own_items > 0 || $child_items > 0) {
				echo "<font style='info'>".t("Poistaminen ei ole mahdollista kun tasolla on liitoksia.")."</font>";
			}
			else {
				echo "<a href='#' class='editbtn' id='poista'><img src='{$palvelin2}pics/lullacons/stop.png' alt='",t('Poista'),"'/> ".t('Poista taso')."</a>";
			}
		}
		elseif($saamuokataliitosta) {
			// tarkistetaan onko jo liitetty
			$qu = "SELECT *
					FROM puun_alkio
					WHERE yhtio = '{$yhtiorow["yhtio"]}' AND laji = '{$toim}' AND liitos = '{$liitos}' AND puun_tunnus = {$noderow["tunnus"]}";
			$re = pupe_query($qu);
			
			if(mysql_num_rows($re) > 0) {
				$row = mysql_fetch_assoc($re);
				echo "<a class='editnode' id='removefromtree'>".t("Poista liitos")." ({$liitos} - {$noderow["tunnus"]})</a>";
			}
			else {
				echo "<a class='editnode' id='addtotree'>".t("Tee liitos")." ({$liitos} - {$noderow["tunnus"]})</a>";
			}
		}
		echo "</div>";
		
		// tason muokkauslaatikko
		echo "<div id='nodebox' style='display: none'>
			<form id='tasoform'>
			<fieldset>
				<legend style='font-weight: bold' id='nodeboxtitle'></legend>
				<ul style='list-style:none; padding: 5px'>
					<li style='padding: 3px'>
						<label style='display: inline-block; width: 50px'>Nimi <font style='color: red'>*</font></label>
						<input size='35' id='uusi_nimi' autocomplete='off' />
					</li>
					<li style='padding: 3px'>
						<label style='display: inline-block; width: 50px'>Koodi <font style='color: red'>*</font></label>
						<input size='35' id='uusi_koodi' autocomplete='off' />
					</li>	
				</ul>
				<input type='hidden' id='tee' />
				<p style='display: none; color: red' id='nodeboxerr'>Nimi tai koodi ei saa olla tyhjä.</p>
				<input type='submit' id='submitbtn' value='Tallenna' />
			</fieldset>
			</form>
		</div>";
		
		?>
		<script language="javascript">
		var params = new Object();
		<?php 
		echo "params['toim'] = '{$toim}';
			  params['kieli'] = '{$kieli}';
			 ";
		
		if($saamuokata) {
			echo "params['nodeid'] = {$nodeid};
				var nimi = '{$noderow["nimi"]}';
				var koodi = '{$noderow["koodi"]}';";
			?>
			
			jQuery(".editbtn").click(function(){
				params["tee"] = this.id;
				editNode(params);
				return false;
			});
			
			var nodebox			= jQuery("#nodebox");
			var addboxbutton	= jQuery("#showaddbox");
			var editboxbutton	= jQuery("#showeditbox");
			var nodeboxtitle	= jQuery("#nodeboxtitle");
			var nodeboxname		= jQuery("#uusi_nimi");
			var nodeboxcode		= jQuery("#uusi_koodi");
			var tee				= jQuery("#tee");
			
			addboxbutton.click(function() {
				tee.val("lisaa");
				nodeboxtitle.html("Lisää taso");
				addboxbutton.replaceWith(nodebox);
				nodeboxname.val("").focus();
				nodebox.show();
				nodeboxcode.val("");
				return false;
			});
			
			editboxbutton.click(function() {
				tee.val("muokkaa");
				nodeboxtitle.html("Muokkaa tasoa");
				editboxbutton.replaceWith(nodebox);
				nodebox.show();
				nodeboxname.val(nimi).focus();
				nodeboxcode.val(koodi);
				return false;
			});
			
			jQuery("#tasoform").submit(function() {
				params["uusi_nimi"]		= jQuery("#uusi_nimi").val();
				params["uusi_koodi"]	= jQuery("#uusi_koodi").val();
				params["tee"]			= jQuery("#tee").val();
				
				if(params["uusi_nimi"] == "" || params["uusi_koodi"] == "") {
					jQuery("#nodeboxerr").show();
					return false;
				}
				
				editNode(params);
				return false;
			});
			<?php
		}
		elseif($saamuokataliitosta) {
			echo "params['liitos']		= '{$liitos}';
				  params['nodeid']	= '{$noderow["tunnus"]}';";
			?>
			jQuery(".editnode").click(function() {
				params["tee"] = this.id;
				editNode(params);
			});
			<?php
		}
		?>
		</script>
		<?php
		// suljetaan nodelaatikko
		echo "</div>";
	}
	else {
		echo "virhe: nodeid tai toim puuttuu";
	}

?>
