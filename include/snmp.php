<?php

	function snmpTable($host, $community, $oID) {
		snmp_set_oid_numeric_print(TRUE);
		snmp_set_quick_print(TRUE);
		snmp_set_enum_print(TRUE); 

		$retval = array();
		$raw = snmprealwalk($host, $community, $oID);
		if (count($raw) == 0) return ($retval); // no data
		
		$prefix_length = 0; 
		$largest = 0;
		foreach ($raw as $key => $value) {
			if ($prefix_length == 0) {
				// don't just use $oID's length since it may be non-numeric
				$prefix_elements = count(explode('.',$oID));
				$tmp = '.' . strtok($key, '.');
				while ($prefix_elements > 1) {
					$tmp .= '.' . strtok('.');
					$prefix_elements--;
				}
				$tmp .= '.';
				$prefix_length = strlen($tmp);
			}
			$key = substr($key, $prefix_length);
			$index = explode('.', $key, 2);
			isset($retval[$index[1]]) or $retval[$index[1]] = array();
			if ($largest < $index[0]) $largest = $index[0];
			$retval[$index[1]][$index[0]] = $value;
		}

		if (count($retval) == 0) return ($retval); // no data

		// fill in holes and blanks the agent may "give" you
		foreach($retval as $k => $x) {
			for ($i = 1; $i <= $largest; $i++) {
			if (! isset($retval[$k][$i])) {
					$retval[$k][$i] = '';
				}
			}
			ksort($retval[$k]);
		}
		return($retval);
	}
	
	function getBlackBerryUserID() {
	    $arr = snmpTable(BB_SERVER, BB_SNMP_COMMUNITY_STRING, "1.3.6.1.4.1.3530.5.30.1.3");
	    
        foreach($arr AS $key=>$value) {
            if (in_array(BB_TEST_USER_ID, $value)) {
                return $key;
            }
        }
	}

?>
