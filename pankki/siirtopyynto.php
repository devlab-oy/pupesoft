<?php

function siirtopyynto ($aineisto, $salasana ,$paivays);

$apu= sprintf ('%-17.17s', "SIIRTOPYYNTO");
$apu.= sprintf ('%-10.10s', "");
$apu.= sprintf ('%-18.18s', $aineisto);
$apu.= sprintf ('%-10.10s', $salasana);
$apu.= sprintf ('%-6.6s', $paivays);
$apu .= " 9979 " . "999";

return $apu;

?>
