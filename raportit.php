<?php

///* Tämä skripti käyttää slave-tietokantapalvelinta *///
	if ((($toim != 'hyvaksynta') or ($tee != 'T')) and ($toim !='maksuvalmius')) $useslave = 1;
	require ("inc/parametrit.inc");

	require ("inc/".$toim.".inc");
	require ("inc/footer.inc");
?>
