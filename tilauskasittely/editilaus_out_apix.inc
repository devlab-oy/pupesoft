<?php

$edi_order  = "*IS from:721111720-1 to:IKH,ORDERS*id:{$laskurow['tunnus']} version:AFP-1.0 *MS\n";
$edi_order .= "*MS {$laskurow['tunnus']}\n";
$edi_order .= "*RS OSTOTIL\n";
$edi_order .= "OSTOTIL.OT_NRO:{$laskurow['tunnus']}\n";
$edi_order .= "OSTOTIL.OT_TOIMITTAJANRO:0\n";
$edi_order .= "OSTOTIL.OT_TILAUSTYYPPI:{$laskurow['tilaustyyppi']}\n";
$edi_order .= "OSTOTIL.OT_TILAUSAIKA:\n";
$edi_order .= "OSTOTIL.OT_KASITTELIJA:\n";
$edi_order .= "OSTOTIL.OT_TOIMITUSAIKA:\n";
$edi_order .= "OSTOTIL.OT_TOIMITUSTAPA:{$laskurow['toimitustapa']}\n";
$edi_order .= "OSTOTIL.OT_TOIMITUSEHTO:{$laskurow['toimitusehto']}\n";
$edi_order .= "OSTOTIL.OT_MAKSUEHTO:{$laskurow['maksuehto']}\n";
$edi_order .= "OSTOTIL.OT_VIITTEEMME:{$laskurow['viesti']}\n";
