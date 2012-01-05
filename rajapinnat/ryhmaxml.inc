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

	foreach ($dnsryhma as $osasto) {

		$topgroup = $information->addChild('topgroup');
			$topgroup->addAttribute('id','');
			$topgroup->addChild('topgroupname','root');
				$group = $topgroup->addChild('group');
				foreach ($osasto as $huijaus) {
					// en tee mitään, otan vain tässä sisemmältä tuoten osastojen nimitykset.
				}
				$group->addAttribute('id',$huijaus["osasto"]);
					$group->addChild('groupname',utf8_encode(xmlentities($huijaus["osasto_fi"])));
					$group->addChild('groupnameswe',utf8_encode(xmlentities($huijaus["osasto_se"])));
					$group->addChild('groupnameeng',utf8_encode(xmlentities($huijaus["osasto_en"])));

					// Osasto pysyy samana, mutta try vaihtuu
					foreach ($osasto as $try) {
						$subgroup = $group->addChild('subgroup');
						$subgroup->addAttribute('id',$try["try"]);
							$subgroup->addChild('subgroupcode',$try["try"]);
							$subgroup->addChild('subgroupname',		utf8_encode(xmlentities($try["try_fi"])));
							$subgroup->addChild('subgroupnameswe',	utf8_encode(xmlentities($try["try_se"])));
							$subgroup->addChild('subgroupnameeng',	utf8_encode(xmlentities($try["try_en"])));
					}
	}

	$xml->asXML("../dataout/dnsgroups.xml");
