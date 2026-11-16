<?php 
if (basename($_SERVER["PHP_SELF"]) == basename(__FILE__)) {
    http_response_code(403);
    exit("403 Forbidden - Direct access not allowed.");
}
if(substr($_SERVER["REQUEST_URI"], -10) == "config.php"){header("Location:./");}; 
$data['mikhmon'] = array ('1'=>'mikhmon<|<Admin','mikhmon>|>mZWfoZ9jamQ=');











