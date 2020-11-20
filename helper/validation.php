<?php 

if (! function_exists('validate')) {
    function validate($type, $str_input) {
        if ($type=="required"){
        	if (empty($str_input)){
        		return false;
        	}
        	return true;
        }

        if ($type=="email"){
        	if (!filter_var($str_input, FILTER_VALIDATE_EMAIL)) {
			  return false;
			}
			return true;
        }

        if ($type=="date"){
        	return (DateTime::createFromFormat('Y-m-d H:i:s', $date) !== false);
        }

    }
}