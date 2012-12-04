<?php

	function cleanInt($input) {
		preg_match('/\d+/', $input, $matches);
		return $matches[0];
	}

	function cleanString($input) {
		preg_match('(\".*?\")', $input, $matches);
		$string = str_replace('"', "", $matches[0]);
		print_r($string);
	}
    
    function logThat($string, $logLevel) {
        if ($logLevel <= LOG_LEVEL) {
            echo date("Y-m-d H:i:s") . ' - ' . $string . "\r\n";
        }
    }

?>
