<?php

	$xmlstr  = '<?xml version="1.0" encoding="iso-8859-1"?>';
	$xmlstr .= '<groups>';
	$xmlstr .= '</groups>';

	$xml = new SimpleXMLElement($xmlstr);
	
	$customer = $xml->addChild('customer');
	$customer->addChild('case','pupesoft');
	$customer->addChild('name', utf8_encode(xmlentities($yhtiorow["nimi"])));
	$customer->addChild('address', utf8_encode(xmlentities($yhtiorow["osoite"])));
	$customer->addChild('timestamp', date("Y-m-d H:i"));
	
	$information = $xml->addChild('information');
	$topgroup = $information->addChild('topgroup');
	$topgroup->addAttribute('id','');
	$topgroup->addChild('topgroupname','root');

	foreach ($dnsryhma as $key => $osasto) {

		// Osasto on joka rivillä sama, otetaan ekalta riviltä tiedot
		$group = $topgroup->addChild('group');
		$group->addAttribute('extSystemID',$key);
			$group->addChild('groupname',utf8_encode(xmlentities($dnsryhma[$key]["osasto_fi"])));
			$group->addChild('groupnameswe',utf8_encode(xmlentities($dnsryhma[$key]["osasto_se"])));
			$group->addChild('groupnameeng',utf8_encode(xmlentities($dnsryhma[$key]["osasto_en"])));

		// Loopataan osaston tuoteryhmät
		foreach ($osasto as $try) {
			$subgroup = $group->addChild('subgroup');
			$subgroup->addAttribute('extSystemID',$try["try"]);
				$subgroup->addChild('subgroupcode',$try["try"]);
				$subgroup->addChild('subgroupname',		utf8_encode(xmlentities($try["try_fi"])));
				$subgroup->addChild('subgroupnameswe',	utf8_encode(xmlentities($try["try_se"])));
				$subgroup->addChild('subgroupnameeng',	utf8_encode(xmlentities($try["try_en"])));
		}
	}

	$xml->asXML("{$pupe_root_polku}/dataout/dnsgroups.xml");

