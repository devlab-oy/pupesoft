<?php

require 'inc/parametrit.inc';
require 'inc/pankkiyhteys_functions.inc';

echo "<h1 class='head'>" . t('Aineistojen lähetys pankkiin') . "</h1>";
echo "<hr>";

sepa_pankkiyhteys_kunnossa();

require 'inc/footer.inc';
