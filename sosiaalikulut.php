<?php

	require("inc/parametrit.inc");

	enable_ajax();

	if (isset($livesearch_tee) and $livesearch_tee == "TILIHAKU") {
		livesearch_tilihaku();
		exit;
	}

	echo "<font class='head'>",t("Sosiaalikulujen laskenta"),"</font><hr>\n";

	require('inc/footer.inc');
