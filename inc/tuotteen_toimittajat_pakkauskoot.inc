<?php

$otsikko = t('Toimittajan vaihtoehtoiset pakkauskoot');
$otsikko_nappi  = t("pakkauskoko");

if ($from == "") {
  $kentat = "tunnus, toim_tuoteno_tunnus, pakkauskoko, yksikko";
}
else {
  $kentat = "tunnus, pakkauskoko, yksikko";
}

$jarjestys = 'pakkauskoko';
