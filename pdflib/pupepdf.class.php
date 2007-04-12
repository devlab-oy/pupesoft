
<?php

require_once('../pdflib/phppdflib.class.php');

class PDF extends pdffile {

	function pt_mm($pointseja) {
		return round($pointseja * 0.3527777778,2);
	}

	function addPage($size) {
		$oid = $this->new_page($size);
		$this->currentPage=current($this->objects);
		$this->currentPage["oid"] = $oid;
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
		elseif($valuutta == "EUR" and $maa == "FR") {
			setlocale(LC_MONETARY, 'fr_FR');	
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

	function write($bottom, $ln, $left, $right, $txt, $font, $align="", $style="", $page="") {
		global $fonts;
 		
		$p = $fonts[$font];

		$right = mm_pt($right);
		$left = mm_pt($left);
		$bottom = mm_pt($bottom);
										
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
		
		$top = $bottom + ($ln * ($p["height"]+2));
		
		if($page == "") {
			$page = $this->currentPage["oid"];
		}

		$this->draw_paragraph($top,	$left, $bottom, $right,	$txt, $page, $p);
		
		$this->lasth = $p["height"];
	}

	function writeStrlen($txt, $font) {
		global $fonts;
 		
		$p = $fonts[$font];
		return $this->strlen($txt,$p);
	}

	function placeImage($top, $width, $imageFile) {
						
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
				
				$this->image_place($image,$top,$width,$this->currentPage["oid"]);
			}
			else {
				echo "Kuvavirhe!";
				return false;
			}			
		}
		else {
			echo "Kuvavirhe!";
			return false;
		}
	}
}

?>