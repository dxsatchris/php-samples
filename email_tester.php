<?php 
    ini_set( 'display_errors', 1 );
    error_reporting( E_ALL );
    $from = "welcome@takiti-dev.com";
    $to = "bob.tenor@outlook.com";
    $subject = "PHP Mail Test script";
    $message = "This is a test to check the PHP Mail functionality";
    $headers = "From:" . $from;
    echo var_dump(mail($to,$subject,$message, $headers));die();
    echo "Test email sent";
?>
