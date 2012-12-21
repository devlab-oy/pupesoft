<?php

	if (isset($_REQUEST["ajax"]) and $_REQUEST["ajax"] == "OK") {
		$no_head = "yes";
	}

	require('inc/parametrit.inc');

	if (isset($_REQUEST["ajax"]) and $_REQUEST["ajax"] == "OK") {
		// tarkistetaan oikeuksia ja katsotaan etta muokataanko puuta vai puuliitoksia
		$saamuokata = false;
		$saamuokataliitosta = false;

		if ($tee == 'valitsesegmentti' or $tee == 'addtotree' or $tee == 'removefromtree' and tarkista_oikeus('yllapito.php', 'puun_alkio', 1)) {
			$saamuokataliitosta = true;
		}
		elseif ($oikeurow['paivitys'] == '1') {
			$saamuokata = true;
		}

		// tarvii romplata tekstimuuttujia kun tehdaan jQuery.ajaxin kanssa
		$uusi_nimi	= (isset($uusi_nimi)) ? utf8_decode($uusi_nimi): "";
		$uusi_koodi	= (isset($uusi_koodi)) ? utf8_decode($uusi_koodi): "";

		function getnoderow ($toim, $nodeid) {
			global $yhtiorow, $kukarow;

			$qu = "	SELECT *
					FROM dynaaminen_puu
					WHERE dynaaminen_puu.yhtio 	= '{$yhtiorow['yhtio']}'
					AND dynaaminen_puu.laji 	= '{$toim}'
					AND dynaaminen_puu.tunnus 	= '{$nodeid}'";
			$re = pupe_query($qu);
			$numres = mysql_num_rows($re);

			if ($numres > 0) {
				$row = mysql_fetch_assoc($re);
				return $row;
			}
			else {
				return false;
			}
		}

		// nodeid tarvitaan aina
		if (isset($nodeid) and isset($toim) and trim($toim) != "") {

			$noderow = getnoderow($toim, $nodeid);

			// muokkaustoiminnot
			if (isset($tee) and $tee != '') {
				if ($saamuokata) {
					// Siirretään haaraa järjestyksessä ylös tai alas
					if ($tee == 'ylos' or $tee == 'alas') {
						$src['lft'] = $noderow['lft'];
						$src['rgt'] = $noderow['rgt'];

						// $tee:ssa on suunta mihin siirretään
						$kohde = SiirraTaso($toim, $src, $tee);
					}
					elseif ($tee == 'lisaa' and isset($uusi_nimi) and trim($uusi_nimi) != "") {
						// lisataan lapsitaso

						$uusi_koodi = $uusi_koodi == '' ? '0' : $uusi_koodi;

						$uusirivi = LisaaLapsi($toim, $noderow['lft'], $noderow['syvyys'], $uusi_koodi, $uusi_nimi);
						paivitapuunsyvyys($toim);

						echo "<input type='hidden' id='newid' value='{$uusirivi['tunnus']}' />
							  <input type='hidden' id='newcode' value='{$uusirivi['koodi']}' />";
					}
					elseif ($tee == 'poista') {
						// poistaa ja upgradettaa alemmat lapset isommaksi.
						PoistaLapset($toim, $noderow['lft']);
						paivitapuunsyvyys($toim);
					}
					elseif ($tee == 'muokkaa' and isset($uusi_nimi) and trim($uusi_nimi) != "") {
						$uusi_koodi = $uusi_koodi == '' ? '0' : $uusi_koodi;
						paivitakat($toim, $uusi_koodi, $uusi_nimi, $nodeid);

						echo "<input type='hidden' id='newcode' value='{$uusi_koodi}' />";
					}
					elseif ($tee == 'siirrataso' and isset($kohdetaso) and $kohdetaso != "") {
						// haetaan kohdenode
						$targetnoderow = getnoderow($toim, $kohdetaso);

						if ($targetnoderow != FALSE) {
							$src['lft'] = $noderow['lft'];
							$src['rgt'] = $noderow['rgt'];
							siirraOksa($toim,$src,$targetnoderow['rgt']);
							paivitapuunsyvyys($toim);
						}
					}
					// haetaan uudelleen paivittyneet
					$noderow = getnoderow($toim, $nodeid);

				}
				elseif ($saamuokataliitosta) {
					if ($tee == 'addtotree') {
						TuotteenAlkiot($toim, $liitos, $nodeid, $kieli);
					}
					elseif ($tee == 'removefromtree') {
						$qu = "	DELETE FROM puun_alkio
								WHERE yhtio = '{$yhtiorow["yhtio"]}'
								AND laji = '{$toim}'
								AND liitos ='{$liitos}'
								AND puun_tunnus = {$nodeid}";
						$re = pupe_query($qu);
					}
				}
				$tee = '';
			}

			if ($noderow == FALSE) {
				echo "<p>".t("Valitse uusi taso")."...</p>";
				exit;
			}

			echo "<h2 style='font-size: 20px'>".$noderow['nimi']."</h2><hr />
					<p><font class='message'>".t("Koodi").":</font> ".$noderow['koodi']."<br />".
					"<font class='message'>".t("Tunnus").":</font> ".$noderow['tunnus']."<br />".
					" <font class='message'>".t("Syvyys").":</font> ".$noderow['syvyys']."<br />".
					" <font class='message'>lft / rgt:</font> ".$noderow['lft']." / ".$noderow['rgt'].
					"</p>";

			// " <font class='message'>".t("Toimittajan koodi").":</font> ".$noderow['toimittajan_koodi'].

			// tuotteet
			$qu = "	SELECT count(*) lkm
					FROM puun_alkio
					WHERE yhtio = '{$yhtiorow['yhtio']}'
					AND laji = '{$toim}'
					AND puun_tunnus = '{$nodeid}'";
			$re = pupe_query($qu);
			$row = mysql_fetch_assoc($re);
			$own_items = $row['lkm'];

			// lapsitasojen tuotteet
			$qu = "	SELECT count(*) lkm
					FROM dynaaminen_puu puu
					JOIN puun_alkio alkio ON (puu.yhtio = alkio.yhtio AND puu.tunnus = alkio.puun_tunnus)
					WHERE puu.yhtio = '{$yhtiorow['yhtio']}'
					AND puu.laji = '{$toim}'
					AND puu.lft > {$noderow['lft']}
					AND puu.rgt < {$noderow['rgt']}";
			$re = pupe_query($qu);
			$row = mysql_fetch_assoc($re);

			$child_items = $row['lkm'];

			echo "<p>";
			if ($own_items > 0) {
				echo "<font class='message'>".t("Liitoksia").":</font> <a href='yllapito.php?toim=puun_alkio&laji={$toim}&haku[4]={$nodeid}'>".$own_items."</a><br />";
			}
			if ($child_items > 0) echo "<font class='message'>".t("Liitoksia lapsitasoilla").":</font>".$child_items;
			echo "</p>";

			echo "<hr /><div id='editbuttons'>";
			if ($saamuokata) {
				echo "	<a href='#' id='showeditbox' id='muokkaa'><img src='{$palvelin2}pics/lullacons/document-properties.png' alt='",t('Muokkaa lapsikategoriaa'),"'/> ".t('Muokkaa tason tietoja')."</a><br /><br />
						<a href='#' class='editbtn' id='ylos'><img src='{$palvelin2}pics/lullacons/arrow-single-up-green.png' alt='",t('Siirrä ylöspäin'),"'/> ".t('Siirrä tasoa ylöspäin')."</a><br />
						<a href='#' class='editbtn' id='alas'><img src='{$palvelin2}pics/lullacons/arrow-single-down-green.png' alt='",t('Siirrä alaspäin'),"'/> ".t('Siirrä tasoa alaspäin')."</a><br /><br />
						<a href='#' id='showmovebox'> <img src='{$palvelin2}pics/lullacons/arrow-single-right-green.png' alt='",t('Siirrä alatasoksi'),"'/> ".t('Siirrä oksa alatasoksi')."</a><br /><br />
						<a href='#' id='showaddbox'><img src='{$palvelin2}pics/lullacons/add.png' alt='",t('Lisää'),"'/>".t('Lisää uusi lapsitaso')."</a><br /><br />";

				// poistonappi aktiivinen vain jos ei ole liitoksia
				if ($own_items > 0 or $child_items > 0) {
					echo "<font style='info'>".t("Poistaminen ei ole mahdollista kun tasolla on liitoksia.")."</font>";
				}
				else {
					echo "<a href='#' class='editbtn' id='poista'><img src='{$palvelin2}pics/lullacons/stop.png' alt='",t('Poista'),"'/> ".t('Poista taso')."</a>";
				}
			}
			elseif ($saamuokataliitosta) {
				// tarkistetaan onko jo liitetty
				$qu = "SELECT *
						FROM puun_alkio
						WHERE yhtio = '{$yhtiorow["yhtio"]}'
						AND laji = '{$toim}'
						AND liitos = '{$liitos}'
						AND puun_tunnus = {$noderow["tunnus"]}";
				$re = pupe_query($qu);

				if (mysql_num_rows($re) > 0) {
					$row = mysql_fetch_assoc($re);
					echo "<a class='editnode' id='removefromtree'>".t("Poista liitos")." ({$liitos} - {$noderow["tunnus"]})</a>";
				}
				else {
					echo "<a class='editnode' id='addtotree'>".t("Tee liitos")." ({$liitos} - {$noderow["tunnus"]})</a>";
				}
			}
			echo "</div>";

			// tason siirtolaatikko
			echo "<div id='movebox' style='display: none'>
					<form id='moveform'>
					<fieldset>
						<legend style='font-weight: bold'>".t("Siirrä valitun tason alatasoksi")."</legend>
						<ul style='list-style:none; padding: 5px'>
							<li style='padding: 3px'>
								<label style='display: inline-block; width: 125px'>".t("Kohdetason tunnus")." <font class='error'>*</font></label>
								<input size='5' id='kohdetaso' autocomplete='off' />
							</li>
						</ul>
						<input type='submit' id='movesubmitbtn' value='".t("Siirrä")."' />
						</form>
					</div>
					";

			// tason muokkauslaatikko
			echo "<div id='nodebox' style='display: none'>
				<form id='tasoform'>
				<fieldset>
					<legend style='font-weight: bold' id='nodeboxtitle'></legend>
					<ul style='list-style:none; padding: 5px'>
						<li style='padding: 3px'>
							<label style='display: inline-block; width: 50px'>".t("Nimi")." <font class='error'>*</font></label>
							<input size='35' id='uusi_nimi' autocomplete='off' />
						</li>
						<li style='padding: 3px'>
							<label style='display: inline-block; width: 50px'>".t("Koodi")."</label>
							<input size='35' id='uusi_koodi' autocomplete='off' />
						</li>
					</ul>
					<input type='hidden' id='tee' />
					<p style='display: none; color: red' id='nodeboxerr'>".t("Nimi tai koodi ei saa olla tyhjä").".</p>
					<input type='submit' id='editsubmitbtn' value='".t("Tallenna")."' />
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

			if ($saamuokata) {
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
				var movebox			= jQuery("#movebox");
				var addboxbutton	= jQuery("#showaddbox");
				var moveboxbutton	= jQuery("#showmovebox");
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

				moveboxbutton.click(function () {
					moveboxbutton.replaceWith(movebox);
					movebox.show();
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

					if (params["uusi_nimi"] == "") {
						jQuery("#nodeboxerr").show();
						return false;
					}

					editNode(params);

					return false;
				});

				jQuery("#moveform").submit(function() {
					params["kohdetaso"]	= jQuery("#kohdetaso").val();
					params["tee"]		= "siirrataso";

					editNode(params);
					return false;
				});
				<?php
			}
			elseif ($saamuokataliitosta) {
				echo "params['liitos']	= '{$liitos}';
					  params['nodeid']	= '{$noderow["tunnus"]}';";
				?>
				jQuery(".editnode").click(function() {
					params["tee"] = this.id;
					editNode(params);
				});
			<?php } ?>
			</script>
			<?php
			// suljetaan nodelaatikko
			echo "</div>";
		}
		else {
			echo "<p>".t("virhe: nodeid tai toim puuttuu")."</p>";
		}

		exit;
	}


	if (strtoupper($toim) == "TUOTE") {
		$otsikko = t("Tuotepuu");
	}
	elseif (strtoupper($toim) == "ASIAKAS") {
		$otsikko = t("Asiakaspuu");
	}
	else {
		$otsikko = t("Organisaatiopuu");
	}

	echo "<font class='head'>{$otsikko}</font><hr /><br />";

	$saamuokata = false;
	$saamuokataliitoksia = false;

	if ($oikeurow['paivitys'] == '1') {
		$saamuokata = true;
	}

	if (tarkista_oikeus('yllapito.php', 'puun_alkio', 1)) {
		$saamuokataliitoksia = true;
	}

	// luodaan uusi root node
	if (isset($tee) and isset($toim)) {

		if ($tee == 'valitsesegmentti') {
			// haetaan valitut segmentit ja enabloidaan valintaominaisuudet yms
			$qu = "	SELECT puun_tunnus
					FROM puun_alkio
					WHERE yhtio = '{$yhtiorow['yhtio']}'
					AND laji = '{$toim}'
					AND liitos = '{$liitos}'";
			$re = pupe_query($qu);
			// haetaan tiedot arrayhin myohempaa kayttoa varten
			while($row = mysql_fetch_assoc($re)) {
				$valitutnodet[] = $row['puun_tunnus'];
			}
		}
		elseif ($tee == 'paakat' and isset($uusi_nimi) and $uusi_nimi != "") {
			// luodaan uusi paakategoria
			LisaaPaaKat($toim, $uusi_nimi);
			$tee = '';
		}
		paivitapuunsyvyys($toim);
	}

	/* html list */
	$qu = "	SELECT
			node.lft AS lft,
			node.rgt AS rgt,
			node.nimi AS node_nimi,
			node.koodi AS node_koodi,
			node.tunnus AS node_tunnus,
			node.syvyys as node_syvyys,
			(COUNT(node.tunnus) - 1) AS syvyys
			FROM dynaaminen_puu AS node
			JOIN dynaaminen_puu AS parent ON node.yhtio=parent.yhtio and node.laji=parent.laji AND node.lft BETWEEN parent.lft AND parent.rgt
			WHERE node.yhtio = '{$kukarow["yhtio"]}'
			AND node.laji = '{$toim}'
			GROUP BY node.lft
			ORDER BY node.lft";
	$re = pupe_query($qu);

	// handlataan tilanne kun ei ole viela puun root nodea
	if (mysql_num_rows($re) == 0) {
		echo "<form method='POST'>
				<fieldset>
					<legend>".t("Luo uusi puu")."</legend>
					<label>".t("Nimi").": </label><input type='text' name='uusi_nimi' />
					<input type='hidden' name='toim' value='".$toim."' />
					<input type='hidden' name='tee' value='paakat' />
					<input type='submit' value='".t("Tallenna")."' />
				</fieldset>
			</form>";
	}
	// muutoin jatketaan normaalisti
	else {
		echo "<div class='spec' style='border: 1px solid black; width: 500px;'>";
		echo "<ul id='eka'>";

		$prevdepth = 0;

		while ($row = mysql_fetch_assoc($re)) {

			// vahan kikkailua jotta saadaan list elementit suljettua standardin mukaisesti
			$diff = $row['syvyys'] - $prevdepth;
			$diffi = $diff;

			while($diff > 0) {
				echo "\n<ul>";
				$diff--;
			}
			while($diff < 0) {
				echo "</li>\n</ul>\n</li>";
				$diff++;
			}
			if ($diffi == 0) echo "</li>";

			echo "<li class='nodes' id='{$row['node_tunnus']}'>{$row['node_nimi']} ({$row['node_tunnus']} / {$row['node_koodi']})";

			$prevdepth = $row['syvyys'];
		}

		echo "</ul></div>
				<div id='infobox' class='spec' style='padding: 20px; border: 1px solid black; position: fixed; left: 520px; top: 68px;'></div>";

		?>
		<script language="javascript">

		var dynpuuparams = new Object();

		<?php
		echo	'dynpuuparams["toim"] = "'.$toim.'";
				 dynpuuparams["tee"] = "'.$tee.'";
				 dynpuuparams["kieli"] = "'.$kieli.'";';

		if (isset($liitos) and $liitos != "") {
			echo 'dynpuuparams["liitos"] = "'.$liitos.'";';
		}
		?>

		var loadimg = "<img src='pics/loading_orange.gif' id='loading' />";
		var activenode;

		jQuery.ajaxSetup({
			url: "dynaaminen_puu.php?ajax=OK",
			type: "POST",
			cache: false
		});

		function enableNodes() {
			jQuery(".nodes").click(function() {
				$("#"+activenode).removeClass("ok");
				activenode = this.id;
				$(this).addClass("ok");
				jQuery("#infobox").html(loadimg);

				dynpuuparams["nodeid"] = this.id;

				jQuery.ajax({
					data: dynpuuparams,
					success: function(retval) {
						jQuery("#infobox").html(retval);
					}
				});
				return(false);
			});
		}

		enableNodes();

		function editNode(params) {
			var editbox = jQuery("#editbuttons");
			jQuery(editbox).hide().after(loadimg);

			jQuery.ajax({
				data: params,
				success: function(retval) {
					jQuery("#infobox").html(retval);

					if (params["tee"] == "ylos") {
						var current = jQuery("#"+params["nodeid"]);
						current.prev().before(current);
					}
					else if (params["tee"] == "alas") {
						var current = jQuery("#"+params["nodeid"]);
						current.next().after(current);
					}
					else if (params["tee"] == "lisaa") {
						var nodeulli = jQuery("#"+params["nodeid"]+" > ul > li:first");
						var newli = "<li class='nodes' id='"+jQuery("#newid").val()+"'>"+params["uusi_nimi"]+" ("+jQuery("#newid").val()+" / "+jQuery("#newcode").val()+")</li>";
						if (nodeulli.size()) {
							nodeulli.before(newli);
						}
						else {
							jQuery("#"+params["nodeid"]).append("<ul>"+newli+"</ul>");
						}
						enableNodes();
					}
					else if (params["tee"] == "muokkaa") {
						var updli = jQuery("#"+params["nodeid"]);
						var childul = jQuery("#"+params["nodeid"]+" > ul");
						updli.html(params["uusi_nimi"]+" ("+params["nodeid"]+" / "+jQuery("#newcode").val()+")");
						if (childul.size() > 0) {
							updli.append("<ul>"+childul.html()+"</ul>");
						}
					}
					else if (params["tee"] == "poista") {
						var remli = jQuery("#"+params["nodeid"]);
						var parentul = remli.parent();
						remli.remove();
						if (!(parentul.children("li")[0])) {
							parentul.remove();
						}
					}
					else if (params["tee"] == "addtotree") {
						jQuery("#"+params["nodeid"]).removeClass("ok");
						jQuery("#"+params["nodeid"]).addClass("error");
					}
					else if (params["tee"] == "removefromtree") {
						jQuery("#"+params["nodeid"]).removeClass("error");
					}
					else if (params["tee"] == "siirrataso") {
						window.location.reload();
					}
				}
			});
		}
		<?php
		// tarvittavat javascriptit kun muokataan liitoksia
		if ($tee == 'valitsesegmentti') {
			$nodet = implode("','", $valitutnodet);
			echo "var valitutnodet = ['".$nodet."'];";
		?>
			jQuery.each(valitutnodet, function() {
				jQuery("#"+this).addClass("error");
			});

		<?php
		}
		?>
		</script>
		<?php
	}

	require('inc/footer.inc');
