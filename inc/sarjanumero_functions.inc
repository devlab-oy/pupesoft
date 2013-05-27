<?php

if (!function_exists("livesearch_sarjanumerohaku")) {

	function livesearch_sarjanumerohaku($toim = '') {
		global $yhtiorow, $kukarow, $palvelin2;

		$tee = $_REQUEST["livesearch_tee"];
		$nimi = $_REQUEST["livesearch_nimi"];
		$formi = $_REQUEST["livesearch_form"];
		$hakuid = $_REQUEST["livesearch_hakuid"];
		$submit = $_REQUEST["livesearch_submit"];
		$haku = trim($_REQUEST["livesearch_haku"]);

		if (strlen($haku) > 2 and $haku{0} != "*" and substr($haku, -1) != '*' and $haku{0} != "?" and $haku{0} != "#") {

			echo "
			<script TYPE='text/javascript' LANGUAGE='JavaScript'>
				document.getElementById('livesearch_$hakuid').style.visibility = 'visible';
			</script>";

			$query = "	SELECT *
						FROM sarjanumeroseuranta
						WHERE yhtio = '{$kukarow['yhtio']}'
						AND sarjanumero LIKE '%{$haku}%'";
			$result = pupe_query($query);

			$limit = 0;
			if (mysql_num_rows($result) > 0) {
				while ($row = mysql_fetch_assoc($result)) {
					echo "	<div id='sarjanumero_valinta_$row[tunnus]'
								name='selectOptions$hakuid'
								onmouseover=\"this.className='liveSearchSelectedItem';
									keyStrokeIndex = -1;
									var aEls = document.getElementsByName('selectOptions$hakuid');
									for (var iEl = 0; iEl < aEls.length; iEl++) {
										if (aEls[iEl].id.substring(0,14) == 'kayttaja_valinta_' && aEls[iEl].id != 'sarjanumero_valinta_$row[tunnus]') {
											document.getElementById(aEls[iEl].id).className='';
										}
									} \"
								onmouseout=\"this.className='';\"
								onclick=\"document.getElementById('$hakuid').value='$row[tunnus]';";
					if ($submit == "") {
						echo "document.$formi.submit();";
					}
					else {
						echo "document.getElementById('livesearch_$hakuid').style.visibility = 'hidden';";
					}
					echo "\"
								style='padding:2px;'>
								<font style='font-weight:bold'>{$row['tuoteno']} - {$row['sarjanumero']}</font>
							</div><div name='selectValues$hakuid' id='$row[tunnus]'></div>";
					$limit++;

					if ($limit > 49) {
						break;
					}
				}

				echo "<div style='padding-left:2px;padding-bottom:5px;padding-top:5px;'><font class='info'>", t("Haulla löytyi"), " ".mysql_num_rows($result)." ", t("sarjanumeroa"), ", ".t("näytetään")." 50 ".t("tarkinta osumaa").".</font></div>";
			}
			else {
				echo "<div style='padding:2px'><font class='message'>".t("Ei sarjanumeroita").".</font></div>";
			}
		}
		else {
			echo "
			<script TYPE='text/javascript' LANGUAGE='JavaScript'>
				document.getElementById('livesearch_$hakuid').style.visibility = 'hidden';
			</script>";
		}
	}
}


if (!function_exists('hae_sarjanumero')) {

	function hae_sarjanumero($tunnus) {
		global $kukarow, $yhtiorow;

		$query = "	SELECT *
					FROM sarjanumeroseuranta
					WHERE yhtio = '{$kukarow['yhtio']}'
					AND tunnus = '{$tunnus}'";
		$result = pupe_query($query);

		return mysql_fetch_assoc($result);
	}
}