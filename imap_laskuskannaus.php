<?php

	// Kutsutaanko CLI:stä
	$php_cli = FALSE;

	if (php_sapi_name() == 'cli' or isset($editil_cli)) {
		$php_cli = TRUE;
	}

	if ($php_cli) {

		require_once("inc/functions.inc");
		require_once("inc/connect.inc");

		ini_set("include_path", ini_get("include_path").PATH_SEPARATOR.dirname(__FILE__).PATH_SEPARATOR."/usr/share/pear");
		#error_reporting(E_ALL ^E_WARNING ^E_NOTICE);
		#ini_set("display_errors", 0);

		if ($argv[1] == '') {
			die ("Yhtiö on pakollinen tieto!\n");
		}

		$yhtiorow = hae_yhtion_parametrit($argv[1]);

		$pupe_root_polku = dirname(__FILE__);

		$imaplaskut_skannauskansio 	= substr($yhtiorow['skannatut_laskut_polku'], -1) != '/' ? $pupe_root_polku."/".$yhtiorow['skannatut_laskut_polku'].'/' : $pupe_root_polku."/".$yhtiorow['skannatut_laskut_polku'];
		$imaplaskut_username		= $imaplaskut_param[$yhtiorow['yhtio']]["username"];
		$imaplaskut_password		= $imaplaskut_param[$yhtiorow['yhtio']]["password"];
		$imaplaskut_imap_server		= $imaplaskut_param[$yhtiorow['yhtio']]["imap_server"];
		$imaplaskut_imap_port		= $imaplaskut_param[$yhtiorow['yhtio']]["imap_port"];
		$imaplaskut_in_box 			= $imaplaskut_param[$yhtiorow['yhtio']]["in_box"];
		$imaplaskut_ok_box 			= $imaplaskut_param[$yhtiorow['yhtio']]["ok_box"];
		$imaplaskut_er_box 			= $imaplaskut_param[$yhtiorow['yhtio']]["er_box"];
		$imaplaskut_domain 			= $imaplaskut_param[$yhtiorow['yhtio']]["domain"];
	}
	else {
		echo "Voidaan ajaa vain komentoriviltä!!!\n";
		die;
	}

	function sqimap_session_id($unique_id = FALSE) {
	    static $sqimap_session_id = 1;

	    if (!$unique_id) {
	        return( sprintf("A%03d", $sqimap_session_id++) );
	    }
		else {
	        return( sprintf("A%03d", $sqimap_session_id++) . ' UID' );
	    }
	}

	function sqimap_run_command ($imap_stream, $query, $handle_errors, &$response, &$message, $unique_id = false, $filter=false, $outputstream=false, $no_return=false) {
	    if ($imap_stream) {
	        $sid = sqimap_session_id($unique_id);
	        fputs ($imap_stream, $sid . ' ' . $query . "\r\n");
	        $read = sqimap_read_data ($imap_stream, $sid, $handle_errors, $response, $message, $query,$filter,$outputstream,$no_return);
	        return $read;
	    }
		else {
	        echo date("d.m.Y @ G:i:s").": ERROR: No available IMAP stream.\n";
	        return false;
	    }
	}

	function sqimap_run_command_list ($imap_stream, $query, $handle_errors, &$response, &$message, $unique_id = false) {
	    if ($imap_stream) {
	        $sid = sqimap_session_id($unique_id);
	        fputs ($imap_stream, $sid . ' ' . $query . "\r\n");
	        $read = sqimap_read_data_list ($imap_stream, $sid, $handle_errors, $response, $message, $query );
	        return $read;
	    }
		else {
	        echo date("d.m.Y @ G:i:s").": ERROR: No available IMAP stream.\n";
	        return false;
	    }
	}

	function sqimap_read_data ($imap_stream, $tag_uid, $handle_errors,&$response, &$message, $query = '', $filter=false,$outputstream=false,$no_return=false) {

	    $res = sqimap_read_data_list($imap_stream, $tag_uid, $handle_errors, $response, $message, $query, $filter, $outputstream, $no_return);

	    return $res[0];
	}

	function sqimap_fread($imap_stream,$iSize,$filter=false, $outputstream=false, $no_return=false) {
	    if (!$filter || !$outputstream) {
	        $iBufferSize = $iSize;
	    } else {
	        // see php bug 24033. They changed fread behaviour %$^&$%
	        $iBufferSize = 7800; // multiple of 78 in case of base64 decoding.
	    }
	    if ($iSize < $iBufferSize) {
	        $iBufferSize = $iSize;
	    }

	    $iRetrieved = 0;
	    $results = '';
	    $sRead = $sReadRem = '';
	    // NB: fread can also stop at end of a packet on sockets.
	    while ($iRetrieved < $iSize) {
	        $sRead = fread($imap_stream,$iBufferSize);
	        $iLength = strlen($sRead);
	        $iRetrieved += $iLength ;
	        $iRemaining = $iSize - $iRetrieved;
	        if ($iRemaining < $iBufferSize) {
	            $iBufferSize = $iRemaining;
	        }
	        if ($sRead == '') {
	            $results = false;
	            break;
	        }
	        if ($sReadRem != '') {
	            $sRead = $sReadRem . $sRead;
	            $sReadRem = '';
	        }

	        if ($filter && $sRead != '') {
	           // in case the filter is base64 decoding we return a remainder
	           $sReadRem = $filter($sRead);
	        }
	        if ($outputstream && $sRead != '') {
	           if (is_resource($outputstream)) {
	               fwrite($outputstream,$sRead);
	           } else if ($outputstream == 'php://stdout') {
	               echo $sRead;
	           }
	        }
	        if ($no_return) {
	            $sRead = '';
	        } else {
	            $results .= $sRead;
	        }
	    }
	    return $results;
	}

	function sqimap_read_data_list ($imap_stream, $tag_uid, $handle_errors, &$response, &$message, $query = '', $filter = false, $outputstream = false, $no_return = false) {
	    $read = '';
	    $tag_uid_a = explode(' ',trim($tag_uid));
	    $tag = $tag_uid_a[0];
	    $resultlist = array();
	    $data = array();
	    $read = sqimap_fgets($imap_stream);
	    $i = 0;

	    while ($read) {
	        $char = $read{0};
	        switch ($char)
	        {
	          case '+':
	          default:
	            $read = sqimap_fgets($imap_stream);
	            break;

	          case $tag{0}:
	          {
	            /* get the command */
	            $arg = '';
	            $i = strlen($tag)+1;
	            $s = substr($read,$i);
	            if (($j = strpos($s,' ')) || ($j = strpos($s,"\n"))) {
	                $arg = substr($s,0,$j);
	            }
	            $found_tag = substr($read,0,$i-1);
	            if ($arg && $found_tag==$tag) {
	                switch ($arg)
	                {
	                  case 'OK':
	                  case 'BAD':
	                  case 'NO':
	                  case 'BYE':
	                  case 'PREAUTH':
	                    $response = $arg;
	                    $message = trim(substr($read,$i+strlen($arg)));
	                    break 3; /* switch switch while */
	                  default:
	                    /* this shouldn't happen */
	                    $response = $arg;
	                    $message = trim(substr($read,$i+strlen($arg)));
	                    break 3; /* switch switch while */
	                }
	            } elseif($found_tag !== $tag) {
	                /* reset data array because we do not need this reponse */
	                $data = array();
	                $read = sqimap_fgets($imap_stream);
	                break;
	            }
	          } // end case $tag{0}

	          case '*':
	          {
	            if (preg_match('/^\*\s\d+\sFETCH/',$read)) {
	                /* check for literal */
	                $s = substr($read,-3);
	                $fetch_data = array();
	                do { /* outer loop, continue until next untagged fetch
	                        or tagged reponse */
	                    do { /* innerloop for fetching literals. with this loop
	                            we prohibid that literal responses appear in the
	                            outer loop so we can trust the untagged and
	                            tagged info provided by $read */
	                        if ($s === "}\r\n") {
	                            $j = strrpos($read,'{');
	                            $iLit = substr($read,$j+1,-3);
	                            $fetch_data[] = $read;
	                            $sLiteral = sqimap_fread($imap_stream,$iLit,$filter,$outputstream,$no_return);
	                            if ($sLiteral === false) { /* error */
	                                break 4; /* while while switch while */
	                            }
	                            /* backwards compattibility */
	                            $aLiteral = explode("\n", $sLiteral);
	                            /* release not neaded data */
	                            unset($sLiteral);
	                            foreach ($aLiteral as $line) {
	                                $fetch_data[] = $line ."\n";
	                            }
	                            /* release not neaded data */
	                            unset($aLiteral);
	                            /* next fgets belongs to this fetch because
	                               we just got the exact literalsize and there
	                               must follow data to complete the response */
	                            $read = sqimap_fgets($imap_stream);
	                            if ($read === false) { /* error */
	                                break 4; /* while while switch while */
	                            }
	                            $fetch_data[] = $read;
	                        } else {
	                            $fetch_data[] = $read;
	                        }
	                        /* retrieve next line and check in the while
	                           statements if it belongs to this fetch response */
	                        $read = sqimap_fgets($imap_stream);
	                        if ($read === false) { /* error */
	                            break 4; /* while while switch while */
	                        }
	                        /* check for next untagged reponse and break */
	                        if ($read{0} == '*') break 2;
	                        $s = substr($read,-3);
	                    } while ($s === "}\r\n");
	                    $s = substr($read,-3);
	                } while ($read{0} !== '*' &&
	                         substr($read,0,strlen($tag)) !== $tag);
	                $resultlist[] = $fetch_data;
	                /* release not neaded data */
	                unset ($fetch_data);
	            } else {
	                $s = substr($read,-3);
	                do {
	                    if ($s === "}\r\n") {
	                        $j = strrpos($read,'{');
	                        $iLit = substr($read,$j+1,-3);
	                        $data[] = $read;
	                        $sLiteral = fread($imap_stream,$iLit);
	                        if ($sLiteral === false) { /* error */
	                            $read = false;
	                            break 3; /* while switch while */
	                        }
	                        $data[] = $sLiteral;
	                        $data[] = sqimap_fgets($imap_stream);
	                    } else {
	                         $data[] = $read;
	                    }
	                    $read = sqimap_fgets($imap_stream);
	                    if ($read === false) {
	                        break 3; /* while switch while */
	                    } else if ($read{0} == '*') {
	                        break;
	                    }
	                    $s = substr($read,-3);
	                } while ($s === "}\r\n");
	                break 1;
	            }
	            break;
	          } // end case '*'
	        }   // end switch
	    } // end while

	    /* error processing in case $read is false */
	    if ($read === false) {
	        unset($data);
	        echo date("d.m.Y @ G:i:s").": ERROR: Connection dropped by IMAP server.\n";
	        exit;
	    }

	    /* Set $resultlist array */
	    if (!empty($data)) {
	        $resultlist[] = $data;
	    }
	    elseif (empty($resultlist)) {
	        $resultlist[] = array();
	    }

	    /* Return result or handle errors */
	    if ($handle_errors == false) {
	        return( $resultlist );
	    }
	    switch ($response) {
	    case 'OK':
	        return $resultlist;
	        break;
	    case 'NO':
	        /* ignore this error from M$ exchange, it is not fatal (aka bug) */
	        if (strstr($message, 'command resulted in') === false) {
	            echo date("d.m.Y @ G:i:s").": ERROR: Could not complete request.\n";
	            exit;
	        }
	        break;
	    case 'BAD':
	        echo date("d.m.Y @ G:i:s").": ERROR: Bad or malformed request.\n";
	        exit;
	    case 'BYE':
	        echo date("d.m.Y @ G:i:s").": ERROR: IMAP server closed the connection.\n";
	        exit;
	    default:
	        echo date("d.m.Y @ G:i:s").": ERROR: Unknown IMAP response.\n";
	       return $resultlist;
	       break;
	    }
	}

	function sq_is8bit($string,$charset='') {
	    global $default_charset;

	    if ($charset=='') $charset=$default_charset;

	    /**
	     * Don't use \240 in ranges. Sometimes RH 7.2 doesn't like it.
	     * Don't use \200-\237 for iso-8859-x charsets. This ranges
	     * stores control symbols in those charsets.
	     * Use preg_match instead of ereg in order to avoid problems
	     * with mbstring overloading
	     */
	    if (preg_match("/^iso-8859/i",$charset)) {
	        $needle='/\240|[\241-\377]/';
	    } else {
	        $needle='/[\200-\237]|\240|[\241-\377]/';
	    }
	    return preg_match("$needle",$string);
	}

	function quoteimap($str) {
	    return preg_replace("/([\"\\\\])/", "\\\\$1", $str);
	}

	function sqimap_fgets($imap_stream) {
	    $read = '';
	    $buffer = 4096;
	    $results = '';
	    $offset = 0;
	    while (strpos($results, "\r\n", $offset) === false) {
	        if (!($read = fgets($imap_stream, $buffer))) {
	        /* this happens in case of an error */
	        /* reset $results because it's useless */
	        $results = false;
	            break;
	        }
	        if ( $results != '' ) {
	            $offset = strlen($results) - 1;
	        }
	        $results .= $read;
	    }
	    return $results;
	}

	function sqimap_mailbox_select ($imap_stream, $mailbox) {

	    if ($mailbox == 'None') {
	        return;
	    }

	    // cleanup $mailbox in order to prevent IMAP injection attacks
	    $mailbox = str_replace(array("\r","\n"), array("",""),$mailbox);

	    $read = sqimap_run_command($imap_stream, "SELECT \"$mailbox\"",
	                               true, $response, $message);
	    $result = array();
	    for ($i = 0, $cnt = count($read); $i < $cnt; $i++) {
	        if (preg_match('/^\*\s+OK\s\[(\w+)\s(\w+)\]/',$read[$i], $regs)) {
	            $result[strtoupper($regs[1])] = $regs[2];
	        } else if (preg_match('/^\*\s([0-9]+)\s(\w+)/',$read[$i], $regs)) {
	            $result[strtoupper($regs[2])] = $regs[1];
	        } else {
	            if (preg_match("/PERMANENTFLAGS(.*)/i",$read[$i], $regs)) {
	                $regs[1]=trim(preg_replace (  array ("/\(/","/\)/","/\]/") ,'', $regs[1])) ;
	                $result['PERMANENTFLAGS'] = $regs[1];
	            } else if (preg_match("/FLAGS(.*)/i",$read[$i], $regs)) {
	                $regs[1]=trim(preg_replace (  array ("/\(/","/\)/") ,'', $regs[1])) ;
	                $result['FLAGS'] = $regs[1];
	            }
	        }
	    }
	    if (preg_match('/^\[(.+)\]/',$message, $regs)) {
	        $result['RIGHTS']=$regs[1];
	    }

	    return $result;
	}

	function showMessagesForMailbox($imapConnection, $in_box, $ok_box, $er_box, $domain, $skannauskansio) {

		// Valitaan inboxi
		$mbxresponse = sqimap_mailbox_select($imapConnection, $in_box);

		// haetaan inboxin kaikki viestit
		$query = "SEARCH UID 1:*";
		$read_list = sqimap_run_command_list($imapConnection, $query, true, $response, $message, '');

		// Otetaan messageid:t
		if (preg_match("/\* SEARCH ([0-9 ]*)/", $read_list[0][0], $matches)) {
			$messaget = explode(" ", trim($matches[1]));
		}
		else {
			$messaget = array();
		}

		$messu_seqid_corr = 0;

		foreach ($messaget as $messu_seqid) {
			// Oliks tää ok maili
			$is_ok = FALSE;

			$messu_seqid = $messu_seqid-$messu_seqid_corr;

			// Haetaan viestin UID
			$query = "FETCH $messu_seqid (UID)";
			$fetch_uid = sqimap_run_command_list($imapConnection, $query, true, $response, $message, '');

			if (isset($fetch_uid[0][0]) and preg_match("/\(UID ([0-9]*)\)/", $fetch_uid[0][0], $matches)) {
				$uid = $matches[1];
			}
			else {
				continue;
			}

			// Haetaan viestin From
			$query = "UID FETCH $uid (BODY[HEADER.FIELDS (From)])";
			$fetch_from = sqimap_run_command_list($imapConnection, $query, true, $response, $message, '');

			if (!preg_match("/[a-z\.]*?@$domain/i", $fetch_from[0][1], $matches)) {
				echo "Laskuja hyväksytään vain $domain domainista, ".$fetch_from[0][1]."\n";
			}
			else {
				// Haetaan viestin BODYSTRUCTURE
				$query = "UID FETCH $uid (BODYSTRUCTURE)";
				$fetch_message = sqimap_run_command_list($imapConnection, $query, true, $response, $message, '');

				preg_match("/BODYSTRUCTURE \((.*)\)/", $fetch_message[0][0], $matches);
				$bodyt = explode(")(", $matches[1]);

				for ($bodyind = 1; $bodyind <= count($bodyt); $bodyind++) {
					// Haetaan viestin BODY, tai siis osa siitä
					$query = "UID FETCH $uid (BODY[$bodyind])";
					$fetch_body_part = sqimap_run_command_list($imapConnection, $query, true, $response, $message, '');

					if (preg_match("/\(BODY\[$bodyind\] NIL\)/", $fetch_body_part[0][0])) {
						break;
					}

					// Fetchataan filename
					if (preg_match("/\(\"(FILE)?NAME\" \"(.*?)\"\)/", $bodyt[($bodyind-1)], $matches)) {
						// Ok maili
						$is_ok = TRUE;

						$path_parts = pathinfo($matches[2]);
						$name		= strtoupper($path_parts['filename']);
						$ext		= strtoupper($path_parts['extension']);

						// Hyväksytyt filet
						if (strtoupper($ext) != "JPG" and strtoupper($ext) != "JPEG" and strtoupper($ext) != "PNG" and strtoupper($ext) != "GIF" and strtoupper($ext) != "PDF") {
							echo  "Ainoastaan .jpg .gif .png .pdf tiedostot sallittuja!\n";
							continue;
						}

						// Kirjoitetaan liitetiedosto levylle
						$attachmentbody = "";

						for ($line = 1; $line < count($fetch_body_part[0])-1; $line++) {
							$attachmentbody .= trim($fetch_body_part[0][$line]);
						}

						$attachmentbody = base64_decode($attachmentbody);

						// Katotaan, ettei samalla nimellä oo jo laskua jonossa
						if (file_exists($skannauskansio."/".$matches[2])) {

							$kala = 1;
							$filename = $matches[2];

							while (file_exists($skannauskansio."/".$filename)) {
								$filename = $kala."_".$matches[2];
								$kala++;
							}

							$matches[2] = $filename;
						}

						file_put_contents($skannauskansio."/".$matches[2], $attachmentbody);
					}
				}
			}

			$movebox = $is_ok ? $ok_box : $er_box;

			// Siiretään maili sopivaan kansioon
			$query = "UID COPY $uid $movebox";
			$response = sqimap_run_command_list($imapConnection, $query, true, $response, $message, '');

			$query = "UID STORE $uid +flags (\Deleted)";
			$response = sqimap_run_command_list($imapConnection, $query, true, $response, $message, '');

			// Sekvenssinumero penenee automnaattisesti sitä mukaa kun siirretään maileja veke kansiosta
			$messu_seqid_corr++;
		}
	}

	function sqimap_logout ($imap_stream) {
	    /* Logout is not valid until the server returns 'BYE'
	     * If we don't have an imap_stream we're already logged out */
	    if(isset($imap_stream) && $imap_stream) {
			sqimap_run_command($imap_stream, 'LOGOUT', false, $response, $message);
		}
	}

	echo date("d.m.Y @ G:i:s").": Ostolaskujen sisäänluku.\n";

	if (!extension_loaded('openssl')) {
		echo date("d.m.Y @ G:i:s").": SSL ei käytössä!\n";
		exit;
	}

	$imap_stream = fsockopen('tls://' . $imaplaskut_imap_server, $imaplaskut_imap_port, $errno, $errstr, 5);

	if ($imap_stream) {

		$server_info = fgets ($imap_stream, 1024);

		echo date("d.m.Y @ G:i:s").": Stream ok: $server_info\n";

		// Original IMAP login code
        $query = 'LOGIN';

		if (sq_is8bit($imaplaskut_username)) {
            $query .= ' {' . strlen($imaplaskut_username) . "}\r\n$imaplaskut_username";
        }
		else {
            $query .= ' "' . quoteimap($imaplaskut_username) . '"';
        }
        if(sq_is8bit($imaplaskut_password)) {
            $query .= ' {' . strlen($imaplaskut_password) . "}\r\n$imaplaskut_password";
        }
		else {
            $query .= ' "' . quoteimap($imaplaskut_password) . '"';
        }

		$read = sqimap_run_command ($imap_stream, $query, false, $response, $message);

		if ($response != "OK") {
			echo date("d.m.Y @ G:i:s").": Login failed!\n";
			echo date("d.m.Y @ G:i:s").": Response: $response $message\n";
			exit;
		}

		/*
		// listataan kaikki mailboxit ruudulle
		$query = "LIST \"\" \"*\"";
		$read_list = sqimap_run_command_list ($imap_stream, $query, true, $response, $message, '');

	    foreach ($read_list as $r) {
			var_dump($r);
			echo "\n";
		}
		*/

		showMessagesForMailbox($imap_stream, $imaplaskut_in_box, $imaplaskut_ok_box, $imaplaskut_er_box, $imaplaskut_domain, $imaplaskut_skannauskansio);
 		sqimap_logout($imap_stream);
	}
	else {
		echo date("d.m.Y @ G:i:s").": Failure: ".$errno.$errstr."\n\n";
	}

	echo date("d.m.Y @ G:i:s").": Ostolaskujen sisäänluku. Done!\n\n";
?>