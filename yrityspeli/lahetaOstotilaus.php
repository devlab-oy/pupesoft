<?php
// tehdään tällänen häkkyrä niin voidaan scriptiä kutsua vaikka perlistä..

	// otetaan includepath aina rootista
	ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(__FILE__).PATH_SEPARATOR."/usr/share/pear");
	error_reporting(E_ALL ^E_WARNING ^E_NOTICE);
	ini_set("display_errors", 0);

	require ("../inc/connect.inc");
	require ("../inc/functions.inc");
	
	$query = "SELECT * FROM kuka WHERE yhtio = 'myyra' AND kuka = 'jarmok'";
	$result  = mysql_query($query);
	$kukarow = mysql_fetch_assoc($result);

	$yhtiorow = hae_yhtion_parametrit($kukarow['yhtio']);

	$komentorivilta = "ON";

	$userfile	= trim($argv[2]);
	$filenimi	= $userfile;
	$ok 		= 1;	
	
	postData();



function postData(){
	$postdata = http_build_query(
		array(
			'toim' => 'OSTO',
			'tee' => 'TULOSTA',
			'otunnus' => '1214',
			'ppa' => '11',
			'kka' => '07',
			'vva' => '2010',
			'ppl' => '11',
			'kkl' => '07',
			'vvl' => '2010',
			'tilausnumero' => '1214',
			'lasku_yhtio' => 'myyra',
			'kieli' => 'fi',
			'komento[Ostotilaus]' => 'email',
			'tulosta' => 'Tulosta',
		)
	);

	$opts = array('http' =>
		array(
			'method'  => 'POST',
			'header'  => 'Content-type: application/x-www-form-urlencoded',
			'content' => $postdata
		)
	);

	$context  = stream_context_create($opts);
	
	echo file_get_contents('../tilauskasittely/tulostakopio.php', FILE_USE_INCLUDE_PATH, $context);
	
	// debuggi
		//echo file_get_contents('../../palvelut/mailer/echo.php', false, $context);
}
?>
