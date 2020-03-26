<html>
<head></head>
<body style="background: url(/sites/all/themes/mes_themes/danland/images/body-bg.gif) repeat-x #fff;font-size: 84%;font-family: Arial,Helvetica,sans-serif;color: #000;margin: 0;padding: 0;line-height: 1.5em;">

<a href="www.Hacking-you.com" title="www.Hacking-you.com" style="color: #005a8c;font-weight: bold;font-size: 25px;padding-left: 15px;position: relative;font-family: Verdana,Tahoma;font-style: italic;">Hacking-you.com</a></br></br></br>

Thank you,

Your account will be deleted within the next 48 hours.



<?php $msg ='
<a href="www.Hacking-you.com" title="www.Hacking-you.com" style="color: #005a8c;font-weight: bold;font-size: 25px;padding-left: 15px;position: relative;font-family: Verdana,Tahoma;font-style: italic;">Hacking-you.com</a></br></br></br>
Hi Mirinka,
</br>
<p>"Hacking-you" will be closed on 2014 November 10th. From that date "Hacking-you.com" and "Hacking-you.fr" will be part of <a href="http://www.expertsassistant.com">www.expertsassistant.com</a>.</p>

<p>Expertsassistant.com is part of "Just Halal", available on the GoogleAppStore, and <a href="www.paris-halal.com">www.paris-halal.com</a>.</p>

<p>As of 2014 November 10th, "Hacking-you.com" and all its codes, contents, and users informations will be transfered to "Expertsassistant.com".</p>

<p>If you do not want to receive any particular types of email messages from "Expertsassistant","www.paris-halal.com", click here :</p>

<a href="http://www.Hacking-you.com/unsubscribe?uim=miroslava.prudka1980@hotmail.cz&cn=mirinka&id=25a8631" 
title="http://www.Hacking-you.com/unsubscribe?uim=miroslava.prudka1980@hotmail.cz&cn=mirinka&id=25a8631">
http://www.Hacking-you.com/unsubscribe?uim=miroslava.prudka1980@hotmail.cz&cn=mirinka&id=25a8631
</a>

</br>

<p>If you cannot open the link just copy and paste it in your in your internet browser</p>

Regards,
</br>
</br>Hacking-you Administrator
</br>administrator@Hacking-you.com';

try {
	$myfile = fopen("log.txt", "a");
}
catch (Exception $e) {
	echo $e->getMessage();
}


$txt = NULL;
$txt .= "**********************************************************\n";
$txt .= "HTTP_REFERER" . $_SERVER['HTTP_REFERER'] . "\n";
$txt .= "HTTP_USER_AGENT" . $_SERVER['HTTP_USER_AGENT'] . "\n";
$txt .= "REQUEST_URI" . $_SERVER['REQUEST_URI'] . "\n";

if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
    $txt .= "IP : " . $_SERVER['HTTP_CLIENT_IP'] . "\n";
} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $txt .= "IP : " . $_SERVER['HTTP_X_FORWARDED_FOR'] . "\n";
} else {
    $txt .= "IP : " . $_SERVER['REMOTE_ADDR'] . "\n";
}

$txt .= "**********************************************************\n";

fwrite($myfile, $txt);
fclose($myfile);

$email="bob.tenor@hotmail.com";
$sujet="a user unsubscribed";
mail($email,$sujet,$txt,"Content-type: text/html\nFrom: administrator@Hacking-you.com\n");
?>
</body>
</html>
