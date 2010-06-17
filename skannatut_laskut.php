<?php

	if ($silent == '') {
		require "inc/parametrit.inc";
	}

	function listdir($start_dir = '.') {

		$files = array();

		if (is_dir($start_dir)) {

			$fh = opendir($start_dir);

			while (($file = readdir($fh)) !== false) {
				if (strcmp($file, '.') == 0 or strcmp($file, '..') == 0 or substr($file, 0, 1) == ".") {
					continue;
				}
				$filepath = $start_dir . '/' . $file;

				if (is_dir($filepath)) {
					$files = array_merge($files, listdir($filepath));
				}
				else {
					array_push($files, $filepath);
				}
			}
			closedir($fh);
			sort($files);
		}
		else {
			$files = false;
		}

		return $files;
	}

	if ($silent == '') {
		echo "<font class='head'>".t("Skannatut laskut")."</font><hr>";	

		echo "<table>";
	}

	if (trim($yhtiorow['skannatut_laskut_polku']) == '') {
		echo "<font class='error'>",t("Skannattujen laskujen hakemistopolku on tyhjä yhtiön parametreissa"),"!</font><br/>";
		require "inc/footer.inc";
		exit;
	}

	$dir = $yhtiorow['skannatut_laskut_polku'];

	// käydään läpi ensin käsiteltävät kuvat
	$files = listdir($dir);

	foreach ($files as $file) {
		$path_parts = pathinfo($file);

		$query = "	SELECT kesken
					FROM kuka
					WHERE yhtio = '$kukarow[yhtio]'";
		$kesken_chk_res = mysql_query($query) or pupe_error($query);

		while ($kesken_chk_row = mysql_fetch_assoc($kesken_chk_res)) {
			if ($path_parts['filename'] == $kesken_chk_row['kesken']) continue 2;
		}

		if ($silent == '') {
			echo "<form name='laskut' method='post' action='ulask.php?iframe=yes&tultiin=skannatut_laskut'>";
			echo "<tr><td>$path_parts[basename]<input type='hidden' name='skannattu_lasku' value='$path_parts[basename]'></td><td class='back'><input type='submit' value='",t("Valitse"),"'></td></tr>";
			echo "</form>";
		}
		else {
			$skannattu_lasku = $path_parts['basename'];
			$iframe = 'yes';
			$tultiin = 'skannatut_laskut';
			$skippi = 'joo';
			break;
		}
	}

	if ($silent == '') {
		echo "</table>";
	}

	if ($silent == '') {
		require "inc/footer.inc";
	}

?>