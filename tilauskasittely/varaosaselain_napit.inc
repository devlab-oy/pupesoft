<?php

$kaytettava_yhtio = $kukarow['yhtio'] == 'atarv' ? 'artr' : $kukarow['yhtio'];

$extranet_kayttaja = hae_kayttajaan_linkitetty_extranet_kayttaja($kukarow['kuka'], $kaytettava_yhtio);

echo "<a href='{$varaosaselain_url}?user={$extranet_kayttaja['kuka']}&pass={$extranet_kayttaja['salasana']}&seamless&s&p' target='_blank'><button type='button'>".t('Siirry varaosaselaimeen')."</button></a>";

echo "<form action='{$palvelin2}{$tilauskaslisa}tilaus_myynti.php' method='POST'>";
echo "  <input type='hidden' name='tee' value='hae_orumnetin_ostoskori' />
        <input type='hidden' name='extranet_kayttaja' value='{$extranet_kayttaja['kuka']}' />
        <input type='hidden' name='tilausnumero' value='$tilausnumero'>
        <input type='hidden' name='mista' value='$mista'>
        <input type='hidden' name='lopetus' value='$lopetus'>
        <input type='hidden' name='ruutulimit' value = '$ruutulimit'>
        <input type='hidden' name='tyojono' value='$tyojono'>
        <input type='hidden' name='projektilla' value='$projektilla'>
        <input type='hidden' name='orig_tila' value='$orig_tila'>
        <input type='hidden' name='orig_alatila' value='$orig_alatila'>
        <input type='hidden' name='toim' value='$toim'>
        <input type='submit' value='".t("Hae varaosaselain ostoskori")."'>";
echo "</form>";
