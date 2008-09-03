<?php

/*
	Tiedostohallintalinkki
*/

//	Tsekataan että pakolliset muuttujat on, muuten skipataan automatic

//	Tsekataan onko meillä tämä käytössä vai ei..
if(!function_exists("svnInit")) {
	function svnInit () {
		
		//	Oko näitä ei ole joten skipataan aina..
		if(svnMethod == "" or svnUrl == "" or svnPath == "") {
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
					$comments = "-m \"$comments\n\n- $kukarow[nimi]\"";
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
		
		$noDstAction = array("delete", "list");
		$nocommentsAction = array("list");
		if(makeSvnTarget($src) !== false) {
			if(makeSvnTarget($dst) !== false or in_array($action, $noDstAction)) {

				if(in_array($action, $noDstAction)) {
					$dst="";
				}
				
				if($comments != "" or in_array($action, $nocommentsAction)) {
					
					if(in_array($action, $nocommentsAction)) {
						$comments="";
					}
					
					if(in_array($action, array("move", "copy", "import", "delete", "list"))) {
						
						if($action == "delete" and $src == "") {
							return "juurikansiota et voi poistaa";
						}
						
						return true;
					}
					
					return "svn toiminto $action ei kelpaa";
				}

				return "comments puuttuu";
			}

			return "dst virheellinen $dst";
		}

		return "src virheellinen $src";

	}		

}

if(!function_exists("svnListDirectory")) {
	function svnListDirectory ($dir) {
		
		$list = svnAction("list", $dir);
		if(count($list) > 0) {

			foreach($list as &$p) {
				$p = "$dir/$p";				
			}
			
			return $list;
		}
		
		return false;
	}
}

if(!function_exists("svnFileLink")) {
	function svnFileLink ($file) {
	
		return svnPreviewMethod."://".svnPreviewUrl."/$file";
	}		
}

if(!function_exists("svnFindActiveProject")) {
	function svnFindActiveProject ($tunnus) {
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
		$list = svnAction("list", svnQuotationsRejected);
		
		foreach($list as $p) {			
			if(substr($p, 0, strpos($p, " ")) == $tunnus) {
				return svnQuotationsRejected."/$p";
			}
		}
	}		
}

if(!function_exists("svnListProjectDirectory")) {
	function svnListProjectDirectory ($tunnus) {
		return svnListDirectory(svnProjectDirectory($tunnus));
	}
}
if(!function_exists("svnListQuotationDirectory")) {
	function svnListQuotationDirectory ($tunnus) {
		return svnListDirectory(svnQuotationDirectory($tunnus));
	}
}

if(!function_exists("svnProjectDirectory")) {
	function svnProjectDirectory ($tunnus) {
		
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

//	Tarkastetaan/avataan lopuksi se yhteys
svnInit();

?>
