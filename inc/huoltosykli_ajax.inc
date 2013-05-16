<?php

if ($action == 'hae_huoltosykli') {
	$huoltosykli = hae_huoltosykli($huoltosykli_tunnus);

	array_walk_recursive($huoltosykli, 'array_utf8_encode');

	echo json_encode($huoltosykli);
}

exit;