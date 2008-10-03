<?php

/*
	Tiedostohallintalinkki
*/

//	Tsekataan että pakolliset muuttujat on, muuten skipataan automatic

//	Tsekataan onko meillä tämä käytössä vai ei..
if(!function_exists("svnInit")) {
	function svnInit () {
		
		if(defined("svnStatus")) {
			return svnStatus;
		}
		
		//	Oko näitä ei ole joten skipataan aina..
		if(!defined("svnMethod") or !defined("svnUrl") or !defined("svnPath") or !defined("svnLanguage")) {
			$status = false;
		}
		else {			
			$status = true;
		}

		define("svnStatus", $status);
		
		return $status;
	}
}

if(!function_exists("svnAction")) {
	function svnAction ($action, $src, $dst="", $comments="") {
		global $kukarow;
		
		if(svnStatus) {
			
			//	Tarkastetaan, että tiedot on muodollisesti oikein..
			$check = svnCheckCommit($action, $src, $dst, $comments);
			if($check === true) {
								
				if($comments != "") {
					$comments = "-m \"".t($comments, svnLanguage)."\n\n- $kukarow[nimi]\"";
				}
				
				//	Koska joissain asennuksissa PHP:llä ei ole mitään järjellistä localea pakotetaan se aina mukaan constanilla console_LC_CTYPE
				
				$cmd = "LC_CTYPE=".console_LC_CTYPE." svn $action $comments $src $dst";
				
				if(strpos(console_LC_CTYPE, "UTF-8") !== false) {
					$cmd = utf8_encode($cmd);
				}

				exec($cmd, $output, $retval);
				
				if(svnDebug>0) {
					echo "cmd: $cmd<br>retval: $retval<br>output: ".implode("<br>", $output)."<br>";
				}

				if($retval == 0) {
					
					if(strpos(console_LC_CTYPE, "UTF-8") !== false) {
						foreach($output as &$o) {
							$o = utf8_decode($o);
						}
					}
					
					return $output;
				}
				else {
					return false;
				}			
			}
			else {
				return $check;
			}
		} 
	}		
}

if(!function_exists("makeSvnTarget")) {
	function makeSvnTarget (&$target) {
		
		if(svnStatus===false) return false;
		
		if($target == "") {
			return false;			
		}
		else {
			$target = str_replace(" ", "\ ", $target);
			
			//	jos on paikallinen file tarkistetaan että sellanen oikeesti on
			if(substr($target, 0, 6) == "local:") {
				$target = substr($target, 6);
				if(!file_exists($target)) {
					var_dump(file_exists($target));
					return false;
				}
			}
			else {
				
				if($target == "root") {
					$target = "";
				}
				
				$target = svnMethod."://".svnUrl."/".svnPath."/".$target;
			}
		}
	}
}

if(!function_exists("svnCheckCommit")) {
	function svnCheckCommit ($action, &$src, &$dst="", &$comments="") {
		
		if(svnStatus===false) return false;
		
		$action = trim($action);
		$noDstAction = array("delete", "list", "list -R");
		$nocommentsAction = array("list", "list -R");
		$allowedActions = array("move", "copy", "import", "delete", "list" , "list -R");
		
		if($action == "delete" and $src == "") {
			return "juurikansiota et voi poistaa";
		}
		
		if(in_array($action, $allowedActions)) {
			
			if(in_array($action, $nocommentsAction)) {
				$comments="";
			}
			
			if(in_array($action, $noDstAction)) {
				$dst="";
			}
			
			if($comments != "" or in_array($action, $nocommentsAction)) {
				
				if(makeSvnTarget($src) !== false) {
					if(makeSvnTarget($dst) !== false or in_array($action, $noDstAction)) {

						return true;
					}

					return "dst virheellinen $dst";
				}
				return "src virheellinen $src";
			}
		}
		else {
			return "svn toiminto $action ei kelpaa";
		}
	}		

}

if(!function_exists("svnListDirectory")) {
	function svnListDirectory ($dir, $params="") {
		
		if(svnStatus===false) return false;
		
		$list = svnAction("list $params", $dir);
		if(count($list) > 0) {

			foreach($list as &$p) {
				$p = "$dir$p";				
			}
			
			return $list;
		}
		
		return false;
	}
}

if(!function_exists("svnFileLink")) {
	function svnFileLink ($file) {
		
		if(svnStatus===false) return false;
		
		return svnPreviewMethod."://".svnPreviewUrl."/$file";
	}		
}

if(!function_exists("svnFindActiveProject")) {
	function svnFindActiveProject ($tunnus) {
		
		if(svnStatus===false) return false;
		
		$list = svnAction("list", svnProjectsActive);
		
		foreach($list as $p) {			
			if(substr($p, 0, strpos($p, " ")) == $tunnus) {
				return svnProjectsActive."/$p";
			}
		}
	}		
}
if(!function_exists("svnFindActiveQuotation")) {
	function svnFindActiveQuotation ($tunnus) {

		if(svnStatus===false) return false;

		$list = svnAction("list", svnQuotationsActive);
		foreach($list as $p) {			
			if(substr($p, 0, strpos($p, " ")) == $tunnus) {
				return svnQuotationsActive."/$p";
			}
		}
	}		
}

if(!function_exists("svnFindCompleteProject")) {
	function svnFindCompleteProject ($tunnus) {
		
		if(svnStatus===false) return false;
		
		$list = svnAction("list", svnProjectsComplete);
		
		foreach($list as $p) {			
			if(substr($p, 0, strpos($p, " ")) == $tunnus) {
				return svnProjectsComplete."/$p";
			}
		}
	}		
}
if(!function_exists("svnFindRejectedQuotation")) {
	function svnFindRejectedQuotation ($tunnus) {
		
		if(svnStatus===false) return false;
		
		$list = svnAction("list", svnQuotationsRejected);
		
		foreach($list as $p) {			
			if(substr($p, 0, strpos($p, " ")) == $tunnus) {
				return svnQuotationsRejected."/$p";
			}
		}
	}		
}

if(!function_exists("svnListProjectDirectory")) {

	if(svnStatus===false) return false;

	function svnListProjectDirectory ($tunnus, $params="") {
		return svnListDirectory(svnProjectDirectory($tunnus), $params);
	}
}
if(!function_exists("svnListQuotationDirectory")) {
	function svnListQuotationDirectory ($tunnus, $params="") {
		
		if(svnStatus===false) return false;
		
		return svnListDirectory(svnQuotationDirectory($tunnus), $params);
	}
}

if(!function_exists("svnProjectDirectory")) {
	function svnProjectDirectory ($tunnus) {
		
		if(svnStatus===false) return false;
		
		//	Koitetaan eka aktiivisista
		$path = svnFindActiveProject($tunnus);
		
		if($path == "") {
			$path = svnFindCompleteProject($tunnus);			
		}

		if($path != "") {
			return $path;
		}

		return false;
	}
}
if(!function_exists("svnQuotationDirectory")) {
	function svnQuotationDirectory ($tunnus) {
		
		if(svnStatus===false) return false;
		
		//	Koitetaan eka aktiivisista
		$path = svnFindActiveQuotation($tunnus);
		
		if($path == "") {
			$path = svnFindRejectedQuotation($tunnus);			
		}

		if($path != "") {
			return $path;
		}

		return false;
	}
}

if(!function_exists("svnOpenNew")) {
	function svnOpenNew ($tunnus, $type) {
		global $kukarow;
		
		if(svnStatus===false) return false;
		
		if($type == "PROJEKTI") {
			$path 			= svnProjectsActive;
			$templatePath 	= svnDocumentSamplesDirProjects;
		}
		elseif($type == "TARJOUS") {
			$path 			= svnQuotationsActive;
			$templatePath 	= svnDocumentSamplesDirQuotations;			
		}
		else {
			return "Tyyppi puuttuu";
		}
		
		//	Haetaan vaadittava tieto
		$query = "	SELECT lasku.nimi nimi, asiakkaan_kohde.kohde kohde, laskun_lisatiedot.seuranta seuranta
					FROM lasku
					JOIN laskun_lisatiedot ON laskun_lisatiedot.yhtio=lasku.yhtio and laskun_lisatiedot.otunnus=lasku.tunnus
					LEFT JOIN asiakkaan_kohde ON asiakkaan_kohde.yhtio=laskun_lisatiedot.yhtio and asiakkaan_kohde.tunnus= laskun_lisatiedot.asiakkaan_kohde
					WHERE lasku.yhtio='$kukarow[yhtio]' and lasku.tunnus='$tunnus' and lasku.tunnusnippu = lasku.tunnus";
		$kohderes = mysql_query($query) or pupe_error($query);
		if(mysql_num_rows($kohderes) > 0) {
			$kohderow = mysql_fetch_array($kohderes);
			$pDir = trim($path."/{$tunnus} {$kohderow["seuranta"]} {$kohderow["nimi"]} {$kohderow["kohde"]}");
			$sDir = $templatePath."/{$kohderow["seuranta"]}";
			
			//	Tsekataan onko seuranta specifi pohja
			if(svnListDirectory($sDir) === false) {
				$sDir = $templatePath."/default";
			}
			
			if(svnAction("copy", $sDir, $pDir, t(ucfirst(strtolower($type))." avattu")) !== false) {
				//	Uudelleennimetäänt tiedostot projektille
				$list = svnAction("list -R", $pDir);
				foreach($list as $file) {
					if(strpos(basename($file), "XXXXXX") !== false) {
						$file = $pDir."/".$file;
						svnAction("move", $file, str_replace("XXXXXX", $tunnus, $file), "Nimetään tiedosto uudestaan");
					}
				}
				
				return true;
			}
			else {
				return t("Projektin avauksessa tapahtui virhe");
			}
		}
		else {
			return t("Projekti ei kelpaa");
		}
	}
}


//	Tarkastetaan/avataan lopuksi se yhteys
svnInit();

?>
