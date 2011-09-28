<?php
	global $urldata;

	function order_link($url, $parameters, $order) {
	  if (parameters != "") $temp = "&";
	  return "$url?$temp$parameters&order=$order";
	}

	function customer_link($url, $parameter, $value) {
	  return "$url?tila=valittu&$parameter=$value";
	}

	function urlput($url) {
		global $urldata;

		return "$url&urlpdata=".base64_encode(serialize($urldata));
	}

	function furlput() {
		global $urldata;

		return "<input type=\"hidden\" name=\"urlpdata\" value=\"".base64_encode(serialize($urldata)).
			   "\">\n";
	}

	function urlget() {
		global $urldata;

		$urldata = unserialize(base64_decode($_GET['urlpdata']));
		if (!is_array($urldata))
			$urldata = array();
	}

	urlget();
?>