<?php
	/**
	 *
	 * Varastoryhmien saldojenlukuscripti
	 *
	 */

	// Kutsutaanko CLI:st�
	if (php_sapi_name() != 'cli') {
		die ("T�t� scripti� voi ajaa vain komentorivilt�!");
	}

	$kukarow = array();

	$kukarow['yhtio'] = isset($argv[1]) ? $argv[1] : die("Et antanut yhtiota!\n");
	$kukarow['kuka'] = 'crond';

	require 'inc/connect.inc';
	require 'inc/functions.inc';

	echo date("d.m.Y @ G:i:s")." - Varastoryhmien p�ivitys\n";

	// poistetaan kaikki varastoryhma-tuotteen_avainsanat
	$query = "	DELETE FROM tuotteen_avainsanat
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND laji like 'VARASTORYHMA%'";
	$tuotteen_avainsana_res = mysql_query($query) or die("Virhe poistettaessa tuotteen avainsanoja!\n".mysql_error($query)."\n\n");

	$query = "	SELECT *
				FROM avainsana
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND laji = 'VARASTORYHMA'
				AND selitetark != ''";
	$avainsana_res = mysql_query($query) or die("Virhe haettaessa varastoryhma avainsanoja!\n".mysql_error($query)."\n\n");

	if (mysql_num_rows($avainsana_res) == 0) {
		echo date("d.m.Y @ G:i:s")." - Varastoryhmi� ei ole perustettu.\n";
	}
	else {
		$query = "	SELECT tuote.tuoteno, ifnull((SELECT isatuoteno FROM tuoteperhe WHERE tuoteperhe.yhtio = tuote.yhtio AND tuoteperhe.isatuoteno = tuote.tuoteno AND tuoteperhe.tyyppi = 'P' LIMIT 1), '') isa
					FROM tuote
					WHERE tuote.yhtio = '$kukarow[yhtio]'";
		$res = mysql_query($query) or die("Virhe haettaessa tuotteita!\n".mysql_error($query)."\n\n");

		echo date("d.m.Y @ G:i:s")." - Aloitetaan ".mysql_num_rows($res)." tuotteen p�ivitys. ($kukarow[yhtio])\n";

		while ($row = mysql_fetch_assoc($res)) {

			mysql_data_seek($avainsana_res, 0);

			while ($avainsana_row = mysql_fetch_assoc($avainsana_res)) {
				$varastot = explode(',', $avainsana_row['selitetark']);

				$myytavissa = 0;

				if ($row['isa'] != '') {
					$saldot = tuoteperhe_myytavissa($row["tuoteno"], '', '', $varastot);			

					foreach ($saldot as $varasto => $myytavissa_apu) {
						$myytavissa += $myytavissa_apu;
					}
				}
				else {
					list($saldo, $hyllyssa, $myytavissa) = saldo_myytavissa($row["tuoteno"], '', $varastot);			
				}

				if ($myytavissa > 0) {
					$query = "	INSERT INTO tuotteen_avainsanat SET
								yhtio = '{$kukarow['yhtio']}',
								tuoteno = '{$row['tuoteno']}',
								kieli = '{$avainsana_row['kieli']}',
								laji = 'VARASTORYHMA_$avainsana_row[selite]',
								selite = '$myytavissa',
								laatija = '{$kukarow['kuka']}',
								luontiaika = now(),
								muutospvm = now(),
								muuttaja = '{$kukarow['kuka']}'";
					$tuotteen_avainsana_res = mysql_query($query) or die("Virhe lisattaessa tuotteen avainsanoja!\n".mysql_error($query)."\n\n");
				}
			}
		}
	}
	echo date("d.m.Y @ G:i:s")." - Varastoryhmien p�ivitys. Done!\n\n";
?>