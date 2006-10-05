<?php

///* Tämä skripti käyttää slave-tietokantapalvelinta *///
	if ((($toim != 'hyvaksynta') or ($tee != 'T')) and ($toim !='maksuvalmius')) $useslave = 1;
	require ("inc/parametrit.inc");

	echo "<font class='head'>".t("Virtuaaliraportointi")."</font><hr>";

	require ("inc/".$toim.".inc");
	require ("inc/footer.inc");
?>
