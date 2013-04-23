<?php

	// TODO: yes, functions should go in a seperate file... then again, this would make it more 'portable'
	function getWhois($server, $query) {
		$fp = @fsockopen($server, 43);
		if (!$fp) {
			return false;
		} else {
	        fputs($fp, $query . "\r\n");
			$data = "";
	        while (!feof($fp)) 
	        {
	            $data .= fread($fp, 128);
	        }
			$data = str_replace("\r\n", "\n", $data);
			
			$ret = array();
			if (preg_match_all('/([a-z]+):\s+(.+)/i', $data, $matches, PREG_SET_ORDER)) {
				foreach ($matches as $match) {
					$ret[strtolower($match[1])] = $match[2];					
				}	
			}
			return $ret;
		}
	}

	function ipInNetwork($ip, $networks) {
		foreach ($networks as $network) {
			if ($network[0] <= $ip && $network[1] >= $ip) {
				return true;
			}
		}
		return false;
	}

	function explodeIp($ip, $max = false) {
		// explode an IP address so that it can be represented as a sortable string
		// this function can also calculate subnet first and last host
		if (preg_match('/^[0-9\.\/]{3,}$/', $ip, $matches)) {
			// good enough for ipv4
			$netmaskhelper = array(0,1,3,7,15,31,63,127,255);
			$netmaskhelper2 = array(0, 128, 192, 224, 240, 248, 252, 254, 255);
			
			$arr = explode('/', $ip);
			$arr2 = explode('.', $arr[0]);

			$check = array(0,0,0,0);
			for ($i=0;$i<count($arr2);$i++) {
				$check[$i] = $arr2[$i];
			}
			
			for ($i=0;$i<count($check);$i++) {
				if (isset($arr[1]) && $arr[1] != "") {
					// check netmask
					$diff = $arr[1] -(($i + 1) * 8);
					if ($diff < 0) {
						if ($max) {
							if ($diff < -7) {
								$check[$i] = $check[$i] | $netmaskhelper[8];
							} else {
								$check[$i] = $check[$i] | $netmaskhelper[abs($diff)];
							}
						} else {
							if ($diff < -7) {
								$check[$i] = $check[$i] & $netmaskhelper2[0];
							} else {
								$check[$i] = $check[$i] & $netmaskhelper2[8 + $diff];
							}
						}
					}
				}
			}
			
			$ret = "";
			foreach ($check as $part) {
				$ret .= str_pad($part, 3, "0", STR_PAD_LEFT);
			}
			return $ret;

		} else if (preg_match('/^[0-9a-f\:\.]{2,}$/', $ip, $matches)) {
			// could be ipv6, do a bit more validation though
			$arr = explode(":", $ip);
			if (count($arr) > 8) {
				// IPvWhat?
				return $ip;
			} else if (count($arr) == 8) {
				$check = $arr;
			} else {
				$check = array("","","","","","","","");
				if ($arr[0] == "" && $arr[1] != "") {
					return $ip;
				} else if ($arr[0] == "" && $arr[1] == "") {
					// shorthand starting at begin
					$pos = 7;
					for ($i=count($arr) -1;$i>0;$i--) {
						$check[$pos--] = $arr[$i];
					}
				} else if ($arr[count($arr) - 1] == "" && $arr[count($arr) - 2] != "") {
					return $ip;
				} else if ($arr[count($arr) - 1] == "" && $arr[count($arr) - 2] == "") {
					// shorthand starting at end
					for ($i=0;$i<count($arr);$i++) {
						$check[$i] = $arr[$i];
					}
				} else {
					// shorthand in the middle
					for ($i=0;$i<count($arr);$i++) {
						if ($arr[$i] == "") break;
						$check[$i] = $arr[$i];
					}
					$pos = 7;
					for ($i=count($arr) -1;$i>0;$i--) {
						if ($arr[$i] == "") break;
						$check[$pos--] = $arr[$i];
					}
				}
				
			}

			$ret = "";
			foreach ($check as $part) {
				$ret .= str_pad($part, 4, "0", STR_PAD_LEFT);
			}
			return $ret;

		}
	}


	// TODO: make parameterized
	$in = "example.htm";
	
	$data = file_get_contents($in);	

	$clients = array();
	
	if(preg_match('/<table border="0">(.*?)<\/table>/is', $data, $matches)) {

		echo "Found table\r\n";
		
		$table = $matches[1];
		unset($matches);
		
		if (preg_match_all('/<tr>(.*?)<\/tr>/is', $table, $matches)) {
			
			echo "Found rows\r\n";
			
			if (isset($matches[1][0])) {
				// this should contain th elements
				if (preg_match_all('/<th>(.*?)<\/th>/is', $matches[1][0], $headers)) {

					foreach ($matches[1] as $row) {
					
						if (preg_match_all('/<td>(.*?)<\/td>/is', $row, $match)) {
							
							$mode = $match[1][3]; 	// mode
							$time = $match[1][6]; 	// milliseconds to process the request
							$client = $match[1][10]; // ip of the client
							
							switch (strtolower(trim(strip_tags($mode)))) {
								case "w": // sending reply								
								case "r": // reading request								
								case "k": // keep-alive								
								case "g": // waiting for gracefull restart								
									// for now, only interested in these ones
									if (!isset($clients[$client])) $clients[$client]["count"] = 0;
									$clients[$client]["count"]++;
							}
							
						}
					}					
				}
				
			}
			
		}
		
	}

	echo "Gathering IP information... this can take a while\r\n";
	$networks = array();
	foreach ($clients as $client => $brol) {
		echo ".";
		
		$ip = explodeIp($client);
		if (!ipInNetwork($ip, $networks)) {
			
			$whois = getWhois("whois.iana.org", $client);
			if (isset($whois["whois"])) {
				$whois2 = getWhois($whois["whois"], $client);			
				if (isset($whois2["cidr"])) $clients[$client]["cidr"] = $whois2["cidr"];
				if (isset($whois2["inetnum"])) $clients[$client]["inetnum"] = $whois2["inetnum"];
				if (isset($whois2["netname"])) $clients[$client]["netname"] = $whois2["netname"];
				if (isset($whois2["orgname"])) $clients[$client]["orgname"] = $whois2["orgname"];
				if (isset($whois2["name"])) $clients[$client]["name"] = $whois2["name"];

				if (isset($clients[$client]["cidr"])) {
					$f = explodeIp($clients[$client]["cidr"]);
					$t = explodeIp($clients[$client]["cidr"], true);
				} else if (isset($clients[$client]["inetnum"])) {
					
					if (strpos($clients[$client]["inetnum"], '-') !== false) {
						$i = explode('-', $clients[$client]["inetnum"]);
						$f = explodeIp(trim($i[0]));
						$t = explodeIp(trim($i[1]));
					} else {
						$f = explodeIp($clients[$client]["inetnum"]);
						$t = explodeIp($clients[$client]["inetnum"], true);
					}
					
				} else {
					// assume /24
					$f = explodeIp($client . "/24");
					$t = explodeIp($client . "/24", true);
				}	
				
				$clients[$client]["f"] = $f;
				$clients[$client]["t"] = $t;

				// add network to network array				
				$networks[] = array($f, $t);
								
			}
			
		}
		
	}

	var_dump($clients);

?>