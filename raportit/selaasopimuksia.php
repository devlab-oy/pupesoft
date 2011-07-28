<?php

	// DataTables p‰‰lle
	$pupe_DataTables = "selaasoppareita";

	require('../inc/parametrit.inc');

	pupe_DataTables($pupe_DataTables, 11, 11, '', 'true', 'true');

	$query_ale_lisa = generoi_alekentta('M');

	echo "<font class='head'>".t("Selaa Sopimuksia")."</font><hr>";

	// Tehd‰‰n taulukko
	echo "<table class='display' id='$pupe_DataTables'>";
	echo "<thead>";
	echo "<tr>";
	echo "<th width='1'>".t("Sopimus")."</th>";
	echo "<th width='1'>".t("Asiakkaan")."<br>".t("Tilausnumero")."</th>";
	echo "<th width='1'>".t("Asiakas")."</th>";
	echo "<th width='1'>".t("Tuoteno")."</th>";
	echo "<th width='1'>".t("Nimitys")."</th>";
	echo "<th width='1'>".t("Kommentti")."</th>";
	echo "<th width='1'>".t("Alku pvm")."</th>";
	echo "<th width='1'>".t("Loppu pvm")."</th>";
	echo "<th width='1'>".t("Kpl")."</th>";
	echo "<th width='1'>".t("Hinta")."</th>";
	echo "<th width='1'>".t("Rivihinta")."</th>";
	echo "</tr>";

	// Hakukent‰t
	echo "<tr>";
	echo "<td width='1'>	<input type='text' name='search_tilausnumero'/></td>";
	echo "<td width='1'>	<input type='text' name='search_asiakkaan_tilausnumero'/></td>";
	echo "<td width='1'>	<input type='text' size='30' name='search_asiakas'/></td>";
	echo "<td>				<input type='text' size='20' name='search_tuoteno/'></td>";
	echo "<td>				<input type='text' size='20' name='search_nimitys'/></td>";
	echo "<td>				<input type='text' size='20' name='search_kommentti'/></td>";
	echo "<td width='1'>	<input type='text' size='12' name='search_rivinsopimus_alku'/></td>";
	echo "<td width='1'>	<input type='text' size='12' name='search_rivinsopimus_loppu'/></td>";
	echo "<td width='1'>	<input type='text' size='10' name='search_kpl'/></td>";
	echo "<td>				<input type='text' size='10' name='search_hinta'/></td>";
	echo "<td>				<input type='text' size='10' name='search_summa'/></td>";
	echo "</tr>";
	echo "</thead>";
	echo "<tbody>";

	$query = "	SELECT lasku.tunnus tilaus,
				concat(lasku.ytunnus, '<br>', lasku.nimi) asiakas,
				if (tilausrivi.kerayspvm = '0000-00-00', if(sopimus_loppupvm = '0000-00-00', '', sopimus_loppupvm), tilausrivi.kerayspvm) rivinsopimus_alku,
				if (tilausrivi.toimaika = '0000-00-00', if(sopimus_alkupvm = '0000-00-00', '', sopimus_loppupvm), tilausrivi.toimaika) rivinsopimus_loppu,
				lasku.asiakkaan_tilausnumero,
				sopimus_alkupvm,
				sopimus_loppupvm,
				lasku.valkoodi,
				tuote.nimitys,
				tilausrivi.tuoteno,
				round(tilausrivi.hinta * (tilausrivi.tilkpl) * {$query_ale_lisa}, {$yhtiorow["hintapyoristys"]}) rivihinta,
				tilausrivi.tilkpl,
				tilausrivi.hinta,
				tilausrivi.kommentti
				FROM lasku use index (tila_index)
				JOIN laskun_lisatiedot ON (laskun_lisatiedot.yhtio = lasku.yhtio and laskun_lisatiedot.otunnus = lasku.tunnus and (laskun_lisatiedot.sopimus_loppupvm >= now() or laskun_lisatiedot.sopimus_loppupvm = '0000-00-00'))
				JOIN tilausrivi ON (tilausrivi.yhtio = lasku.yhtio and tilausrivi.otunnus = lasku.tunnus)
				JOIN tilausrivin_lisatiedot ON (tilausrivin_lisatiedot.yhtio = lasku.yhtio and tilausrivi.tunnus = tilausrivin_lisatiedot.tilausrivitunnus)
				JOIN tuote ON (tuote.yhtio = lasku.yhtio and tuote.tuoteno = tilausrivi.tuoteno)
				WHERE lasku.yhtio = '{$kukarow["yhtio"]}'
				AND tila = '0'
				AND alatila NOT IN ('D')
				ORDER by lasku.tunnus, rivinsopimus_alku ASC, rivinsopimus_loppu ASC";
	$result = pupe_query($query);

	while ($rivit = mysql_fetch_assoc($result)) {
		echo "<tr class='aktiivi'>";
		echo "<td nowrap>{$rivit["tilaus"]}</td>";
		echo "<td>{$rivit["asiakkaan_tilausnumero"]}</td>";
		echo "<td>{$rivit["asiakas"]}</td>";
		echo "<td nowrap>{$rivit["tuoteno"]}</td>";
		echo "<td>{$rivit["nimitys"]}</td>";
		echo "<td>{$rivit["kommentti"]}</td>";
		echo "<td nowrap>{$rivit["rivinsopimus_alku"]}</td>";
		echo "<td nowrap>{$rivit["rivinsopimus_loppu"]}</td>";
		echo "<td nowrap>{$rivit["tilkpl"]}</td>";
		echo "<td nowrap align='right'>".hintapyoristys($rivit["hinta"])."</td>";
		echo "<td nowrap align='right'>{$rivit["rivihinta"]}</td>";
		echo "</tr>";
	}

	echo "</tbody>";
	echo "</table>";

	require ("inc/footer.inc");