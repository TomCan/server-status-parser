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
			$ret = array();
			if (preg_match_all('/([a-z]+):\s+(.+)/i', $data, $matches, PREG_SET_ORDER)) {
				foreach ($matches as $match) {
					$ret[strtolower($match[1])] = $match[2];					
				}	
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
								case "w":								
								case "r":								
								case "k":								
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
	foreach ($clients as $client => $brol) {
		echo ".";
		$whois = getWhois("whois.iana.org", $client);
		if (isset($whois["whois"])) {
			$whois2 = getWhois($whois["whois"], $client);			
			if (isset($whois2["cidr"])) $clients[$client]["cidr"] = $whois2["cidr"];
			if (isset($whois2["netname"])) $clients[$client]["netname"] = $whois2["netname"];
			if (isset($whois2["orgname"])) $clients[$client]["orgname"] = $whois2["orgname"];
		}
	}

	var_dump($clients);

?>