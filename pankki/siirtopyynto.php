<?php

function siirtopyynto ($tyyppi, $palvelu, $sala ,$paivays);

$apu= sprintf ('%-17.17s', "SIIRTOPYYNTO");
$apu.= sprintf ('%-10.10s', $tyyppi);
$apu.= sprintf ('%-18.18s', $palvelu);
$apu.= sprintf ('%-10.10s', $sala);
$apu.= sprintf ('%-6.6s', $paivays);
$apu .= " 9979 " . "999";

return $apu;

?>
