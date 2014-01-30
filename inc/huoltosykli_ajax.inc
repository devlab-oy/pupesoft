<?php

if ($action == 'hae_huoltovali_options') {
	$huoltovali_options = huoltovali_options($huoltosykli_tunnus);

	$options_string = "";

	foreach( $huoltovali_options as $key => $val ){
		$options_string .= "<option value='" . $val . "'>" . $key ."</option>";
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
