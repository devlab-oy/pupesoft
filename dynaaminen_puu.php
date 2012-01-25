<?php

	require('inc/parametrit.inc');

	enable_jquery();
	
	$otsikko = strtolower($toim) == "tuote" ? "Tuotepuu" : "Asiakaspuu";
	echo "<font class='head'>{$otsikko}</font><hr /><br />";
	
	
	if (tarkista_oikeus('dynaaminen_puu.php', $toim, 1)) {
		$saamuokata = true;
	} else {
		$saamuokata = false;
	}
	
	
	if(tarkista_oikeus('yllapito.php', 'puun_alkio', 1)) {
		$saamuokataliitoksia = true;
	} else {
		$saamuokataliitoksia = false;
	}

	// luodaan uusi root node
	if (isset($tee) && isset($toim)) {
	
		if($tee == 'valitsesegmentti') {
			// haetaan valitut segmentit ja enabloidaan valintaominaisuudet yms
			$qu = "SELECT puun_tunnus
					FROM puun_alkio 
					WHERE yhtio = '{$yhtiorow['yhtio']}' AND laji = '{$toim}' AND liitos = '{$liitos}'";
			$re = pupe_query($qu);
			// haetaan tiedot arrayhin myohempaa kayttoa varten
			while($row = mysql_fetch_assoc($re)) {
				$valitutnodet[] = $row['puun_tunnus'];
			}
		}
		elseif($tee == 'paakat' && isset($uusi_nimi) && $uusi_nimi != "") {
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
	if(mysql_num_rows($re) == 0) {
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
		echo "<div style='border: 1px solid black; width: 500px; background: #ffffff'>";
		echo "<ul id='eka'>";
		
		$prevdepth = 0;
		
		while ($row = mysql_fetch_assoc($re)) {
		
			// tarkistetaan onko dynaamisen puun syvyys oikein
			/*
			if ($row["node_syvyys"] != $row["syvyys"]) {
				$qu = "	UPDATE dynaaminen_puu
						SET syvyys = {$row["syvyys"]}
						WHERE yhtio	= '{$kukarow["yhtio"]}'
						AND laji	= '{$toim}'
						AND tunnus 	= {$row["node_tunnus"]}";
				$re = pupe_query($qu);
			}
			*/
			
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
			if($diffi == 0) echo "</li>";
			
			echo "\n<li class='nodes' id='{$row['node_tunnus']}'>{$row['node_nimi']} ({$row['node_tunnus']} / {$row['node_koodi']})";
			
			$prevdepth = $row['syvyys'];
		}
		
		echo "</ul></div>
				<div id='infobox' style='padding: 20px; border: 1px solid black; background: #ffffff; position: fixed; left: 520px; top: 68px;'></div>";
		
		?>
		<script language="javascript">
					
		var dynpuuparams = new Object();
		
		<?php
		echo	'dynpuuparams["toim"] = "'.$toim.'";
				 dynpuuparams["tee"] = "'.$tee.'";
				 dynpuuparams["kieli"] = "'.$kieli.'";';
				 
		if(isset($liitos) && $liitos != "") {
			echo 'dynpuuparams["liitos"] = "'.$liitos.'";';
		}
		?>
		var loadimg = "<img src='pics/loading_orange.gif' id='loading' />";
		var activenode;
		
		jQuery.ajaxSetup({
			url: "dynaaminen_puu_ajax.php",
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
					
					if(params["tee"] == "ylos") {
						var current = jQuery("#"+params["nodeid"]);
						current.prev().before(current);
					}
					else if(params["tee"] == "alas") {
						var current = jQuery("#"+params["nodeid"]);
						current.next().after(current);
					}
					else if(params["tee"] == "lisaa") {
						var nodeulli = jQuery("#"+params["nodeid"]+" > ul > li:first");
						var newli = "<li class='nodes' id='"+jQuery("#newid").val()+"'>"+params["uusi_nimi"]+" ("+jQuery("#newid").val()+" / "+jQuery("#newcode").val()+")</li>";
						if(nodeulli.size()) {
							nodeulli.before(newli);
						}
						else {
							jQuery("#"+params["nodeid"]).append("<ul>"+newli+"</ul>");
						}
						enableNodes();
					}
					else if(params["tee"] == "muokkaa") {
						var updli = jQuery("#"+params["nodeid"]);
						var childul = jQuery("#"+params["nodeid"]+" > ul");
						updli.html(params["uusi_nimi"]+" ("+params["nodeid"]+" / "+params["uusi_koodi"]+")");
						if(childul.size() > 0) {
							updli.append("<ul>"+childul.html()+"</ul>");
						}
					}
					else if(params["tee"] == "poista") {
						var remli = jQuery("#"+params["nodeid"]);
						var parentul = remli.parent();
						remli.remove();
						if(!(parentul.children("li")[0])) {
							parentul.remove();
						}
					}
					else if(params["tee"] == "addtotree") {
						jQuery("#"+params["nodeid"]).removeClass("ok");
						jQuery("#"+params["nodeid"]).addClass("error");
					}
					else if(params["tee"] == "removefromtree") {
						jQuery("#"+params["nodeid"]).removeClass("error");
					}
				}
			});
		}
		<?php
		// tarvittavat javascriptit kun muokataan liitoksia
		if($tee == 'valitsesegmentti') {
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