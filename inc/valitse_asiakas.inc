<?php

require ('/parametrit.inc');

echo "<font class='head'>".t("Ext-Tarjoukset")."</font><hr>";
?>

<style>

</style>
<script>

</script>
<?php

$request = array();

if ($tee == 'nayta_asiakas_valinta') {
	echo_asiakas_valinta($request);
}
else {
	echo_poimitut_asiakkaat($request);
}

function echo_asiakas_valinta($request) {
	echo_asiakas_rajaus($request);
	echo_asiakas_table($request);
}

function echo_asiakas_rajaus($request) {

}

function echo_poimitut_asiakkaat($request) {
	echo_asiakas_table($request);
}

function echo_asiakas_table($request) {
	
}