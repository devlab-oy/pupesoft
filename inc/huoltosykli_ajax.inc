<?php

require_once('inc/laite_huolto_functions.inc');

if ($action == 'hae_huoltovali_options') {
	$huoltovali_options = huoltovali_options($huoltosykli_tunnus);

	$oletus = reset($huoltovali_options);

	$options_string = "<option value='0'>Ei huoltoväliä</option>";

	foreach( $huoltovali_options as $key => $val ){

		if ($val == $oletus) {
			$selected = 'selected';
		}

		$options_string .= "<option value='" . $val . "' ".$selected.">" . $key ."</option>";

		$selected = null;
	}

	echo $options_string;
}

if ($action == 'hae_huoltosykli_options') {

	$query = "	SELECT tunnus
				FROM laite
				WHERE yhtio = '{$kukarow['yhtio']}'
				AND tuoteno = '{$tuoteno}'";
	$result = pupe_query($query);
	$tunnus = mysql_result($result, 0);

	$huoltosykli_options = hae_huoltosyklit($tunnus, $tyyppi);

	$options_string = "<option value='0'>Ei huoltosykliä</option>";

	foreach( $huoltosykli_options as  $val ){
		$options_string .= "<option value='" . $val['tunnus'] . "'>" . $val['dropdown_text'] ."</option>";
	}

	echo $options_string;
}

exit;
