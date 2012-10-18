<?php

if ($_POST['ajax_toiminto'] == 'tilin_oletusalv') {
	$tili = mysql_real_escape_string($_POST['tilino']);

	$query = "	SELECT oletus_alv
				FROM tili
				WHERE tilino = '{$tili}'
				AND yhtio = '{$kukarow['yhtio']}'
				AND oletus_alv IS NOT NULL;";

	$result = pupe_query($query);

	if (mysql_num_rows($result) == 1) {
		$row = mysql_fetch_assoc($result);

		echo json_encode(
			array('vero' => preg_replace ('/\.?0*$/', '', $row['oletus_alv']))
		);	
		exit;
	}

	echo json_encode(
		array('empty' => ' ')
	);

}

exit;
?>