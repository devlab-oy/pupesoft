	<?php

	require("inc/parametrit.inc");

	enable_ajax();

	if (isset($livesearch_tee) and $livesearch_tee == "TILIHAKU") {
		livesearch_tilihaku();
		exit;
	}

	$MONTH_ARRAY = array(1=>t('Tammikuu'),t('Helmikuu'),t('Maaliskuu'),t('Huhtikuu'),t('Toukokuu'),t('Kesäkuu'),t('Heinäkuu'),t('Elokuu'),t('Syyskuu'),t('Lokakuu'),t('Marraskuu'),t('Joulukuu'));

	if (!isset($tilinimi)) $tilinimi = "";
	if (!isset($vyorytyksen_tili)) $vyorytyksen_tili = "";
	if (!isset($tilinalku)) $tilinalku = "";
	if (!isset($tilinloppu)) $tilinloppu = "";
	if (!isset($tee)) $tee = "";
	if (!isset($valkoodi)) $valkoodi = "";

	if ($tee == 'I') {

		$isumma_tmp = $itili_tmp = $iselite_tmp = $ikustp_tmp = array();

		$i = 1;

		foreach ($isumma as $key => $val) {
			if ($val != '') {
				$isumma_tmp[$i] = $val;
				$itili_tmp[$i] = $itili[$key];
				$iselite_tmp[$i] = $iselite[$key];
				$ikustp_tmp[$i] = $ikustp[$key];
				$i++;
			}
		}

		if (count($isumma_tmp) > 0) {
			$isumma = $isumma_tmp;
			$itili = $itili_tmp;
			$iselite = $iselite_tmp;
			$ikustp = $ikustp_tmp;

			$maara = count($isumma) + 1;

			require('tosite.php');
			exit;
		}
	}

	echo "<font class='head'>".t("Tilisaldon vyörytys")."</font><hr>\n";

	$formi = 'tosite';
	$kentta = 'tpp';

	echo "<br>\n";

	echo "<form name='tosite' method='post' autocomplete='off'>\n";
	echo "<input type='hidden' name='tee' value='TARKISTA'>\n";
	echo "<table>";

	echo "<tr><th>".t("Valitse tilikausi")."</th>";

 	$query = "	SELECT *
				FROM tilikaudet
				WHERE yhtio = '$kukarow[yhtio]'
				ORDER BY tilikausi_alku desc";
	$vresult = mysql_query($query) or pupe_error($query);

	echo "<td><select name='tkausi' onchange='submit();'>";

	while ($vrow = mysql_fetch_assoc($vresult)) {
		$sel="";

		if (!isset($savrow)) {
			$savrow	= $vrow;
		}

		if ($tkausi == $vrow["tunnus"]) {
			$sel 	= "selected";
			$savrow	= $vrow;
		}

		echo "<option value = '$vrow[tunnus]' $sel>".tv1dateconv($vrow["tilikausi_alku"])." - ".tv1dateconv($vrow["tilikausi_loppu"])."</option>";
	}
	echo "</select></td></tr>";

	$alku = str_replace("-", "", substr($savrow["tilikausi_alku"], 0, 7));
	$lopp = str_replace("-", "", substr($savrow["tilikausi_loppu"], 0, 7));

	echo "<tr><th>".t("Valitse kuukausi")."</th>
			<td><select name='alvk'>
			<option value=''>".t("Koko tilikausi")."</option>";

	for ($a = $alku; $a <= $lopp; $a++) {
		if (substr($a, -2) == "13") {
			$a += 88;
		}

		if ($alvk == $a) $sel = "SELECTED";
		else $sel = "";

		echo "<option value = '$a' $sel>".substr($a, 0, 4)." - ".$MONTH_ARRAY[(int) substr($a, -2)]."</option>";
	}

	echo "</td></tr>";

	echo "<tr><th>",t("Valuutta"),"</th><td>";

	$query = "	SELECT nimi, tunnus
				FROM valuu
				WHERE yhtio = '{$kukarow['yhtio']}'
				ORDER BY jarjestys";
	$vresult = pupe_query($query);

	echo " <select name='valkoodi'>\n";

	while ($vrow = mysql_fetch_assoc($vresult)) {
		$sel = "";
		if (($vrow['nimi'] == $yhtiorow["valkoodi"] and $valkoodi == "") or ($vrow["nimi"] == $valkoodi)) {
			$sel = "selected";
		}
		echo "<option value='{$vrow['nimi']}' {$sel}>{$vrow['nimi']}</option>\n";
	}

	echo "</select></td></tr>";

	echo "<tr><th>".t("Vyörytyksen tili")."</th><td width='200' valign='top'>".livesearch_kentta("tosite", "TILIHAKU", "vyorytyksen_tili", 170, $vyorytyksen_tili, "EISUBMIT")." $tilinimi</td></tr>";
	echo "<tr><th>".t("Tilin alku")."</th><td width='200' valign='top'>".livesearch_kentta("tosite", "TILIHAKU", "tilinalku", 170, $tilinalku, "EISUBMIT")." $tilinimi</td></tr>";
	echo "<tr><th>".t("Tilin loppu")."</th><td width='200' valign='top'>".livesearch_kentta("tosite", "TILIHAKU", "tilinloppu", 170, $tilinloppu, "EISUBMIT")." $tilinimi</td></tr>";

	echo "<tr><th>".t("Tarkenne")."</th><td>";

	$monivalintalaatikot = array("KUSTP");
	$noautosubmit = TRUE;

	require ("tilauskasittely/monivalintalaatikot.inc");

	echo "</td></tr></table>\n";
	echo "<br><input type = 'submit' value = '".t("Näytä")."'></form><br><br>";

	if ($tee == "TARKISTA") {

		$tkausi = (int) $tkausi;

		$query = "	SELECT tilikausi_alku, tilikausi_loppu
					FROM tilikaudet
					WHERE yhtio = '$kukarow[yhtio]'
					and tunnus = $tkausi";
		$vresult = mysql_query($query) or pupe_error($query);

		if (mysql_num_rows($vresult) == 1) {
			$tilikausirow = mysql_fetch_assoc($vresult);
		}
		else {
			echo "<font class='error'>".t("Tuntematon tilikausi")."</font>";
			exit;
		}

		$lisa1 = "";
		$where = "";
		$kustp = "";
		$groupby = "";

		if ($alvk != "") {
			$alvy = (int) substr($alvk, 0, 4);
			$alvm = (int) substr($alvk, 4, 2);
			$alvd = date("d", mktime(0, 0, 0, $alvm+1, 0, $alvy));

			echo "<font class='message'>".t("Vain kuukausi").": $alvy - ".$MONTH_ARRAY[$alvm]."</font><br>";

			$lisa1 = " sum(if(tiliointi.tapvm >= '$alvy-$alvm-01' and tiliointi.tapvm <= '$alvy-$alvm-$alvd', tiliointi.summa, 0)) Saldo";
			$where = " and tiliointi.tapvm <= '$alvy-$alvm-$alvd'";

			$tilikausirow["tilikausi_alku"] =  $alvy. "-". sprintf("%02s",$alvm) . "-01";
			$tilikausirow["tilikausi_loppu"] =  $alvy. "-". sprintf("%02s",$alvm) . "-" . $alvd;
		}
		else {
			$lisa1 = " sum(tiliointi.summa) Saldo";
		}

		echo "<font class='head'>".t("Kumulatiivinen saldo")." ".tv1dateconv($tilikausirow["tilikausi_alku"])." - ".tv1dateconv($tilikausirow["tilikausi_loppu"])."</font><hr>";

		if ($tilinalku == '' and $tilinloppu == '') {
			echo "<font class='error'>".t("Pitää syöttää ainakin yksi tili")."</font>";
		}
		elseif ($vyorytyksen_tili == "") {
			echo "<font class='error'>",t("Syötä vyörytyksen tili"),"</font>";
		}
		else {

			if (count($mul_kustp) > 0 and $mul_kustp[0] !='') {
				$where .= " and tiliointi.kustp in (";
				foreach ($mul_kustp as $kustp) {
					$where .= "'$kustp',";
				}
				$where = substr($where, 0, -1); // vika pilkku pois
				$where .= ")";
				$groupby = ", kustp";
			}

			if (trim($tilinloppu) == '') {
				$tilinloppu = $tilinalku;
			}

			if (trim($tilinalku) == '') {
				$tilinalku = $tilinloppu;
			}

			if (trim($tilinalku) > trim($tilinloppu)) {
				$swap = $tilinalku;
				$tilinalku = $tilinloppu;
				$tilinloppu = $swap;
			}

			$query = "	SELECT tiliointi.tilino, group_concat(distinct tiliointi.kustp) kustp, {$lisa1}, sum(tiliointi.summa) tilisaldo
						FROM tiliointi
						WHERE tiliointi.yhtio  = '{$kukarow['yhtio']}'
						AND tiliointi.korjattu = ''
						AND tiliointi.tapvm   >= '{$tilikausirow['tilikausi_alku']}'
						AND tiliointi.tapvm   <= '{$tilikausirow['tilikausi_loppu']}'
						AND tiliointi.tilino between '{$tilinalku}' AND '{$tilinloppu}'
						{$where}
						GROUP BY tilino {$groupby}";
			$result = mysql_query($query) or pupe_error($query);

			echo "	<script type='text/javascript'>
						$(function() {

							$('#submit_button').attr('disabled', true);

							$('.vyorytys_pros').on('keyup', function(e) {

								e.preventDefault();

								if (e.keyCode == 13) return;

								var val = $(this).val();
								val = parseFloat(val.replace(',', '.'));

								var summa = '';

								if (!isNaN(val)) {
									var kaytettava_saldo = parseFloat($('#vyorytyksen_kaytettava_saldo').html());
									summa = (val / 100) * kaytettava_saldo;
									summa = summa.toFixed(2);
									$('#summa_'+$(this).attr('id')).html(summa);
								}
								else {
									$('#summa_'+$(this).attr('id')).html('');
								}

								var tmp_i = $('#summa_'+$(this).attr('id')).attr('name');
								$('#isumma\\\\['+tmp_i+'\\\\]').val(summa);

								var val_sum = 0;

								$('.vyorytys_pros').each(function() {
									var val = $(this).val();
									val = parseFloat(val.replace(',', '.'));

									if (!isNaN(val)) val_sum += val;
								});

								$('#vyorytys_pros_total').html(val_sum);

								if (val_sum > 100.00 || val_sum < 100.00) {
									$('#vyorytys_pros_total').removeClass('ok').addClass('error');
									$('#submit_button').attr('disabled', true);
								}
								else {
									$('#vyorytys_pros_total').removeClass('error').addClass('ok');
									$('#submit_button').attr('disabled', false);
								}
							});
						});
					</script>";

			echo "<form name='tosite' action='' method='post' autocomplete='off'>\n";
			echo "<input type='hidden' name='tee' value='I'>\n";

			$i = 1;

			// Tehdään päätaulu
			echo "<table>";
			echo "<tr>";
			echo "<td class='back'>";

			echo "<table>";
			echo "<tr>";
			echo "<th>".t("Tili")."</th>";
			echo "<th>".t("Saldo")."</th>";
			if ($kustp != '') {
				echo "<th>".t("Kustannuspaikka")."</th>";
			}
			echo "</tr>";

			$saldo_per_kp = array();
			$kumulus = 0;

			while ($trow = mysql_fetch_array($result)) {

				echo "<tr>";
				echo "<td>$trow[tilino]</td>";
				echo "<td>$trow[Saldo]</td>";

				if ($kustp != '') {

					$query2 = "	SELECT nimi, koodi, tunnus
								FROM kustannuspaikka
								WHERE yhtio = '$kukarow[yhtio]'
								AND tunnus = '$trow[kustp]'";
					$result2 = mysql_query($query2) or pupe_error($query2);
					$tarkenne = mysql_fetch_assoc($result2);

					if ($tarkenne["nimi"] == '') {
						$tarkenne["nimi"] = t("Ei kustannuspaikkaa");
					}

					echo "<td>$tarkenne[koodi] $tarkenne[nimi]</td>";
				}

				echo "</tr>";

				$kp_tunn = (isset($tarkenne) and isset($tarkenne['tunnus'])) ? $tarkenne['tunnus'] : 0;

				if (!isset($saldo_per_kp[$kp_tunn])) $saldo_per_kp[$kp_tunn] = 0;
				$saldo_per_kp[$kp_tunn] += $trow['Saldo'];

				$kumulus = $kumulus + $trow["Saldo"];

				$i++;
			}

			echo "<tr><td class='back' colspan='3'></td></tr>";

			foreach ($saldo_per_kp as $kp_tunn => $saldo_kum) {
				echo "<tr>";
				echo "<th>",t("Yhteensä"),"</th>";
				echo "<td>{$saldo_kum}</td>";

				$query2 = "	SELECT nimi, koodi
							FROM kustannuspaikka
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND tunnus = '{$kp_tunn}'";
				$result2 = mysql_query($query2) or pupe_error($query2);
				$tarkenne = mysql_fetch_assoc($result2);

				if ($tarkenne["nimi"] != '') {
					echo "<td>$tarkenne[koodi] $tarkenne[nimi]</td>";
				}

				echo "</tr>";
			}

			echo "<input type='hidden' name='summa' value='$kumulus'>\n";
			echo "</table>";

			echo "</td>";

			echo "<td class='back'>";

			echo "<table>";
			echo "<tr>";
			echo "<th>",t("Kustannuspaikka"),"</th>";
			echo "<th>",t("Saldo"),"</th>";
			echo "<th>",t("Tili"),"</th>";
			echo "<th>%</th>";
			echo "</tr>";

			$query = "	SELECT DISTINCT koodi, nimi, tunnus
						FROM kustannuspaikka
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND tyyppi = 'K'
						AND kaytossa != 'E'
						ORDER BY koodi+0, koodi, nimi";
			$res = pupe_query($query);

			$i = 1;

			while ($row = mysql_fetch_assoc($res)) {
				echo "<tr>";
				echo "<td>{$row['koodi']} {$row['nimi']}</td>";
				echo "<td id='summa_vyorytys_pros_{$i}' name='{$i}'></td>";
				echo "<td>{$vyorytyksen_tili}</td>";
				echo "<td>";
				echo "<input type='text' class='vyorytys_pros' id='vyorytys_pros_{$i}' name='vyorytys_pros[{$i}]' value='' size='6' maxlength='5' />";
				echo "<input type='hidden' name='itili[{$i}]' value='{$vyorytyksen_tili}'>\n";
				echo "<input type='hidden' name='isumma[{$i}]' id='isumma[{$i}]' value=''>\n";
				echo "<input type='hidden' name='ikustp[{$i}]' value='{$row['tunnus']}'>\n";
				echo "<input type='hidden' name='iselite[{$i}]' value='",t("Vyörytys"),"'>\n";
				echo "</td>";
				echo "</tr>";

				$i++;
			}

			echo "<input type='hidden' name='tpp' value='",date("d"),"'>\n";
			echo "<input type='hidden' name='tpk' value='",date("m"),"'>\n";
			echo "<input type='hidden' name='tpv' value='",date("Y"),"'>\n";

			echo "<tr>";
			echo "<th>",t("Yhteensä"),"</th>";
			echo "<td id='vyorytyksen_kaytettava_saldo'>{$kumulus}</td>";
			echo "<td>{$vyorytyksen_tili}</td>";
			echo "<td id='vyorytys_pros_total'></td>";
			echo "</tr>";

			echo "<tr><td class='back' colspan='4'></td></tr>";

			$i++;

			foreach ($saldo_per_kp as $kp_tunn => $saldo_kum) {

				echo "<tr>";

				$query2 = "	SELECT nimi, koodi
							FROM kustannuspaikka
							WHERE yhtio = '{$kukarow['yhtio']}'
							AND tunnus = '{$kp_tunn}'";
				$result2 = mysql_query($query2) or pupe_error($query2);
				$tarkenne = mysql_fetch_assoc($result2);

				if ($tarkenne["nimi"] != '') {
					echo "<td>$tarkenne[koodi] $tarkenne[nimi]</td>";
				}
				else {
					echo "<td></td>";
				}

				$saldo_kum = -1 * $saldo_kum;

				echo "<td>{$saldo_kum}</td>";
				echo "<td>{$vyorytyksen_tili}</td>";
				echo "<td></td>";
				echo "</tr>";

				echo "<input type='hidden' name='itili[{$i}]' value='{$vyorytyksen_tili}'>\n";
				echo "<input type='hidden' name='isumma[{$i}]' id='isumma[{$i}]' value='{$saldo_kum}'>\n";
				echo "<input type='hidden' name='ikustp[{$i}]' value='{$kp_tunn}'>\n";
				echo "<input type='hidden' name='iselite[{$i}]' value='",t("Vyörytys"),"'>\n";

				$i++;
			}

			echo "<input type='hidden' name='maara' value='{$i}'>\n";
			echo "<input type='hidden' name='valkoodi' value='{$valkoodi}'>\n";

			echo "</table>";

			echo "</td>";
			echo "</tr>";
			echo "</table>";

			echo "<br><input id='submit_button' type='submit' value='".t("Tee tosite")."'></form><br><br>";
		}
	}

	require ("inc/footer.inc");
