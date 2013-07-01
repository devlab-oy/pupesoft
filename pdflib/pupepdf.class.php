<?php

require_once('pdflib/phppdflib.class.php');

class PDF extends pdffile {

	function pt_mm($pointseja) {
		return round($pointseja * 0.3527777778,2);
	}

	function scaleImage($value, $limit) {

		list($height, $width) = explode("x", $value);
		list($maxHeight, $maxWidth) = explode("x", $limit);

		$scale = array();
		$scale["h"] = 1;
		$scale["w"] = 1;

		if($height > $maxHeight) {
			$scale["h"] = $maxHeight/$height;
		}
		if($value > $limit) {
			$scale["w"] = $maxWidth/$width;
		}

		$s = min($scale);

		return array(($height * $s), ($width * $s), $s);
	}


	function addPage($size) {
		$oid = $this->new_page($size);
		$this->currentPage=current($this->objects);
		$this->currentPage["oid"] = $oid;
	}

	function countParagraphHeight($txt, $font, $sW=0) {
		global $fonts;

		if($sW>0) {
			$sW=mm_pt($sW);
		}
		elseif($sW<0) {
			$sW=$this->currentPage["width"]-$this->currentPage["margin-left"]-$this->currentPage["margin-right"]+mm_pt($w);
		}
		else {
			$sW=$this->currentPage["width"]-$this->currentPage["margin-left"]-$this->currentPage["margin-right"];
		}

		// Poistetaan kaikki roskat
		$txt = str_replace(array("\r","\t"), array("","    "), $txt);
		$riveja=1;

		//	Ja lasketaan..
		$i=0;
		$stringw=0;
		foreach(preg_split("/([\s\n])+/U",$txt, -1, PREG_SPLIT_DELIM_CAPTURE) as $osa) {
			if($i==0) {
				$stringw+=$this->strlen($osa,$fonts[$font]);
				if($stringw>$sW) {
					$riveja++;
					$stringw=0;
				}
				$i=1;
			}
			else {
				if(preg_match("/\n/",$osa)) {
					$riveja++;
					$stringw=0;
				}
				else {
					$stringw+=$this->strlen($osa,$fonts[$font]);
					if($stringw>$sW) {
						$riveja++;
						$stringw=0;
					}
				}
			}
		}

		return $riveja;
	}

	function setLocales($maa, $valuutta) {
		$maa = strtoupper($maa);
		$valuutta = strtoupper($valuutta);

		//	Asetetaan valuutta..
		if($valuutta == "USD") {
			setlocale(LC_MONETARY, 'en_US');
		}
		elseif($valuutta == "GBP") {
			setlocale(LC_MONETARY, 'en_GB');
		}
		elseif($valuutta == "SEK") {
			setlocale(LC_MONETARY, 'sv_SE');
		}
		elseif($valuutta == "JPY") {
			setlocale(LC_MONETARY, 'ja_JP');
		}
		elseif($valuutta == "DKK") {
			setlocale(LC_MONETARY, 'da_DK');
		}
		elseif($valuutta == "TRY") {
			setlocale(LC_MONETARY, 'tr_TR');
		}
		elseif($valuutta == "AUD") {
			setlocale(LC_MONETARY, 'en_AU');
		}
		elseif($valuutta == "BRL") {
			setlocale(LC_MONETARY, 'pt_BR');
		}
		elseif($valuutta == "CAD") {
			setlocale(LC_MONETARY, 'en_CA');
		}
		elseif($valuutta == "CNY" or $valuutta == "RMB") {
			setlocale(LC_MONETARY, 'zh_CN');
		}
		elseif($valuutta == "HKD") {
			setlocale(LC_MONETARY, 'zh_HK');
		}
		elseif($valuutta == "IDR") {
			// ???
			setlocale(LC_MONETARY, 'id_ID');
		}
		elseif($valuutta == "KRW") {
			setlocale(LC_MONETARY, 'ko_KR');
		}
		elseif($valuutta == "MXN") {
			// ???
			setlocale(LC_MONETARY, 'mx_MX');
		}
		elseif($valuutta == "MYR") {
			// ???
			setlocale(LC_MONETARY, 'my_MY');
		}
		elseif($valuutta == "NZD") {
			setlocale(LC_MONETARY, 'en_NZ');
		}
		elseif($valuutta == "PHP") {
			// ???
			setlocale(LC_MONETARY, 'py_PY');
		}
		elseif($valuutta == "SGD") {
			// ???
			setlocale(LC_MONETARY, 'sg_SG');
		}
		elseif($valuutta == "THB") {
			// ???
			setlocale(LC_MONETARY, 'th_TH');
		}
		elseif($valuutta == "RUB") {
			setlocale(LC_MONETARY, 'ru_RU');
		}
		elseif($valuutta == "HRK") {
			setlocale(LC_MONETARY, 'hr_HR');
		}
		elseif($valuutta == "NOK") {
			setlocale(LC_MONETARY, 'no_NO');
		}
		elseif($valuutta == "BGN") {
			setlocale(LC_MONETARY, 'bg_BG');
		}
		elseif($valuutta == "NOK") {
			setlocale(LC_MONETARY, 'no_NO');
		}
		elseif($valuutta == "CZK") {
			setlocale(LC_MONETARY, 'cs_CZ');
		}
		elseif($valuutta == "NOK") {
			setlocale(LC_MONETARY, 'no_NO');
		}
		elseif($valuutta == "NOK") {
			setlocale(LC_MONETARY, 'no_NO');
		}
		elseif($valuutta == "EET") {
			setlocale(LC_MONETARY, 'et_EE');
		}
		elseif($valuutta == "HUF") {
			setlocale(LC_MONETARY, 'hu_HU');
		}
		elseif($valuutta == "LTL") {
			setlocale(LC_MONETARY, 'lt_LT');
		}
		elseif($valuutta == "LVL") {
			setlocale(LC_MONETARY, 'lv_LV');
		}
		elseif($valuutta == "NOK") {
			// ???
			setlocale(LC_MONETARY, 'no_NO');
		}
		elseif($valuutta == "PLN") {
			setlocale(LC_MONETARY, 'pl_PL');
		}
		elseif($valuutta == "RON") {
			setlocale(LC_MONETARY, 'ro_RO');
		}
		elseif($valuutta == "SKK") {
			setlocale(LC_MONETARY, 'sk_SK');
		}
		elseif($valuutta == "CHF") {
			setlocale(LC_MONETARY, 'fr_CH');
		}
		elseif($valuutta == "ISK") {
			setlocale(LC_MONETARY, 'is_IS');
		}
		elseif($valuutta == "ZAR") {
			setlocale(LC_MONETARY, 'af_ZA');
		}
		elseif($valuutta == "EUR") {
			if($maa == "FR") {
				setlocale(LC_MONETARY, 'fr_FR');
			}
			elseif($maa == "DE") {
				setlocale(LC_MONETARY, 'de_DE');
			}
			elseif($maa == "IT") {
				setlocale(LC_MONETARY, 'it_IT');
			}
			elseif($maa == "ES") {
				setlocale(LC_MONETARY, 'es_ES');
			}
			else {
				setlocale(LC_MONETARY, 'fi_FI');
			}
		}
		else {
			setlocale(LC_MONETARY, 'fi_FI');
		}

		//	Asetetaan päiväys..
		if($maa == "US") {
			setlocale(LC_TIME, 'en_US');
		}
		elseif($maa == "GB") {
			setlocale(LC_TIME, 'en_GB');
		}
		else {
			setlocale(LC_TIME, 'fi_FI');
		}
	}

	function ln($lnSpace=1.1) {
		return $this->pt_mm($this->lasth)*$lnSpace;
	}

	function teeSarakkeet($sarakkeet) {

		$wmax = $this->pt_mm($this->currentPage["width"]-$this->currentPage["margin-left"]-$this->currentPage["margin-right"]);
		//echo $wmax;

		foreach($sarakkeet as $w) {
			$tw += $w;

			//	mennään liian leveeksi!
			if($tw > $wmax) {
				$s[] = $wmax;
				return $s;
			}
			else {
				$s[] = $tw;
			}
		}

		return $s;
	}

	function write($top, $ln, $left, $right, $txt, $font, $align="", $style="", $page="") {
		global $fonts;

		$p = $fonts[$font];

		$right = mm_pt($right);
		$left = mm_pt($left);
		$top = mm_pt($top);

		if(strtoupper($style) == "B") {
			$p["font"] = $p["font"]."-Bold";
		}
		if(strtoupper($style) == "I") {
			$p["font"] = $p["font"]."-Oblique";
		}
		if(strtoupper($style) == "BI" or strtoupper($style) == "IB") {
			$p["font"] = $p["font"]."-BoldOblique";
		}

		if(strtoupper($align) == "C") {
			$p["align"] = "center";
		}
		if(strtoupper($align) == "R") {
			$p["align"] = "right";
		}
		if(strtoupper($align) == "L") {
			$p["align"] = "left";
		}

		if($right == 0) {
			$right = $this->currentPage["width"]-$this->currentPage["margin-left"]-$this->currentPage["margin-right"];
		}

		$bottom = $top - ($ln * ($p["height"]+2));

		if(!is_int($page)) {
			$page = $this->currentPage["oid"];
		}

		$this->draw_paragraph($top,	$left, $bottom, $right,	$txt, $page, $p);

		$this->lasth = $p["height"];
	}

	function drawRectangle($top, $left, $bottom, $right, $font, $page="") {
		global $fonts;

		if(!is_array($font)) {
			$p = $fonts[$font];
		}
		else {
			$p = $font;
		}

		$right = mm_pt($right);
		$left = mm_pt($left);
		$top = mm_pt($top);
		$bottom = mm_pt($bottom);

		if($right == 0) {
			$right = $this->currentPage["width"]-$this->currentPage["margin-left"]-$this->currentPage["margin-right"];
		}

		if(!is_int($page)) {
			$page = $this->currentPage["oid"];
		}

		$this->draw_rectangle($top, $left, $bottom, $right, $page, $p);
	}

	function writeToTemplate($top, $ln, $left, $right, $txt, $font, $align="", $style="", $template="") {
		global $fonts;

		$p = $fonts[$font];

		$right = mm_pt($right);
		$left = mm_pt($left);
		$top = mm_pt($top);

		if(strtoupper($style) == "B") {
			$p["font"] = $p["font"]."-Bold";
		}
		if(strtoupper($style) == "I") {
			$p["font"] = $p["font"]."-Oblique";
		}
		if(strtoupper($style) == "BI" or strtoupper($style) == "IB") {
			$p["font"] = $p["font"]."-BoldOblique";
		}

		if(strtoupper($align) == "C") {
			$p["align"] = "center";
		}
		if(strtoupper($align) == "R") {
			$p["align"] = "right";
		}
		if(strtoupper($align) == "L") {
			$p["align"] = "left";
		}

		if($right == 0) {
			$right = $this->currentPage["width"]-$this->currentPage["margin-left"]-$this->currentPage["margin-right"];
		}

		$bottom = $top - ($ln * ($p["height"]+10));
		$this->template->paragraph($template,  $bottom,	$left, $top, $right,	$txt, $p);
		$this->lasth = $p["height"];

	}

	function writeStrlen($txt, $font) {
		global $fonts;

		$p = $fonts[$font];
		return $this->strlen($txt,$p);
	}

	function placeImage($top, $width, $imageFile, $page="") {

		$filedata = file_get_contents($imageFile);
		if($filedata != "") {
			if(strpos($imageFile, ".png")) {
				$image = $this->png_embed($filedata);
			}
			elseif(strpos($imageFile, ".jpg")) {
				$image = $this->jfif_embed($filedata);
			}

			if($image!="") {

				$top = mm_pt($top);
				$width = mm_pt($width);

				if(!is_int($page)) {
					$page = $this->currentPage["oid"];
				}

				$this->image_place($image,$top,$width,$this->currentPage["oid"]);
			}
			else {
				echo "Kuvavirhe 1!";
				return false;
			}
		}
		else {
			echo "Kuvavirhe 2!";
			return false;
		}
	}

	function placeImageToTemplate($top, $width, $imageFile, $template, $maxSize="") {

		if(!is_array($imageFile)) {
			$filedata = file_get_contents($imageFile);
		}
		else {
			$filedata = &$imageFile["data"];
		}

		if($filedata != "") {
			if(strpos($imageFile, ".png") or $imageFile["filetype"] == "image/png") {
				$image = $this->png_embed($filedata);
			}
			elseif(strpos($imageFile, ".jpg") or $imageFile["filetype"] == "image/jpeg") {
				$image = $this->jfif_embed($filedata);
			}

			if($image!="") {
				$top = mm_pt($top);
				$left = mm_pt($width);

				if(!is_array($imageFile)) {
					$size  	= getimagesize($imageFile);
					$height	= $size[1];
					$width	= $size[0];
				}
				else {
					$height	= $imageFile["image_height"];
					$width	= $imageFile["image_width"];
				}

				if($maxSize>0) {
					list($height, $width) = $this->scaleImage("{$height}x{$width}", $maxSize);
				}

				$bottom = $top - $height;

				$this->template->image($template, $left, $bottom, $width, $height, $image);
			}
			else {
				echo "Kuvavirhe 3!";
				return false;
			}
		}
		else {
			echo "Kuvavirhe 4!";
			return false;
		}
	}

	function placeTemplate($template, $page="", $left=0, $height=0) {

		$left = mm_pt($left);
		$height = mm_pt($height);

		if(!is_int($page)) {
			$page = $this->currentPage["oid"];
		}

		$this->template->place($template, $page, $left, $height);
	}
}

?>
