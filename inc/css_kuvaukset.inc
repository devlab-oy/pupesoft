<?php
/// csstest.php-tiedoston värien muokkauksessa käytettävien nimien kuvaukset
/// css_kuvaukset.php
/// Annika Granlund, Jarmo Kortetjärvi
/// created: 2010-08-17

$css_kuvaus = array(
		'BODY_BACKGROUND'		=>	'Sivun tausta'
	,	'BODY_COLOR'			=>	'Sivun perusteksti'
	,	'ERROR_COLOR'			=>	'Virheteksti'
	,	'HEAD_COLOR'			=>	'Otsikkoteksti'
	,	'HR_BACKGROUND'			=>	'Osioiden erotinviiva (esim. otsikon alla)'
	,	'INFO_COLOR'			=>	'Infoteksti (fonttikooltaan normaalia pienempää)'
	,	'INPUT_COLOR'			=>	'Painikkeiden teksti ja syötekenttien teksti'
	,	'LINK_COLOR'			=>	'Linkit'
	,	'LIVESEARCH_BACKGROUND'	=>	'Live-hakukenttien tausta'
	,	'MENUHOVER_BACKGROUND'	=>	'Aktiivisen navigaatioelementin tausta'
	,	'MENUHOVER_COLOR'		=>	'Aktiivisen navigaatioelementin teksti'
	,	'MENULINK_COLOR'		=>	'Navigaatioelementin'
	,	'MESSAGE_COLOR'			=>	'Lihavoitu infoteksti'
	,	'OK_COLOR'				=>	'Onnistuneen tai hyväksytyn toiminnan palaute'
	,	'POPUP_BACKGROUND'		=>	'Ponnahdusikkunoiden tausta'
	,	'POPUP_COLOR'			=>	'Ponnahdusikkunoiden teksti'
	,	'SPEC_BACKGROUND'		=>	'Erikoiselementin tausta'
	,	'SPEC_COLOR'			=>	'Erikoiselementin teksti'
	,	'TDLINKKI_COLOR'		=>	'Taulukon solussa oleva linkki'
	,	'TH_BACKGROUND'			=>	'Taulukon otsikkosolu'
	,	'TH_COLOR'				=>	'Taulukon otsikkosolun tausta'
	,	'TRAKTIIVI_BACKGROUND'	=>	'Aktiivisen solun tausta'
	,	'TRAKTIIVI_COLOR'		=>	'Aktiivisen solun teksti'
	,	'TR_BACKGROUND'			=>	'Taulukon solun tausta'
	,	'TR_COLOR'				=>	'Taulukon solun teksti'
	,	'TUMMA_BACKGROUND'		=>	'Tumman elementin tausta'
	,	'TUMMA_COLOR'			=>	'Tumman elementin teksti'
	);
	
function getCSSDescription($css_name){
	global $css_kuvaus;
	
	if(array_key_exists($css_name, $css_kuvaus)){
		return $css_kuvaus[$css_name];
	}
	else return false;
}
?>