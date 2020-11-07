<?php
/*
 * @version $Id: HEADER 14685 2011-06-11 06:40:30Z remi $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2011 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file: Remi Collet
// Purpose of file: Test the XML-RPC plugin from Command Line
// ----------------------------------------------------------------------

if (!extension_loaded("xmlrpc")) {
   die("Extension xmlrpc not loaded\n");
}
chdir(dirname($_SERVER["SCRIPT_FILENAME"]));
chdir("../../..");


$args=array();
if ($_SERVER['argc']>1) {
   for ($i=1 ; $i<count($_SERVER['argv']) ; $i++) {
      $it = explode("=",$argv[$i],2);
      $it[0] = preg_replace('/^--/','',$it[0]);
      $args[$it[0]] = (isset($it[1]) ? $it[1] : true);
   }
}

if (isset($args['help']) && !isset($args['method'])) {
   echo "\nusage : ".$_SERVER["SCRIPT_FILENAME"]." [ options] \n\n";

   echo "\thelp     : display this screen\n";
   echo "\thost     : server name or IP, default : localhost\n";
   echo "\turl      : XML-RPC plugin URL, default : $url\n";
   echo "\tusername : User name for security check (optionnal)\n";
   echo "\tpassword : User password (optionnal)\n";
   echo "\tmethod   : XML-RPC method to call, default : glpi.test\n";
   echo "\tdeflate  : allow server to compress response (if supported)\n";

   die( "\nOther options are used for XML-RPC call.\n\n");
}

if (isset($url)) {
   $url=$url;
   unset($args['url']);
} else {
	$url = "/".basename(getcwd())."/plugins/webservices/xmlrpc.php";
}

if (isset($host)) {
   $host=$host;
   unset($args['host']);
} else {
   $host='localhost';
}

if (isset($args['method'])) {
   $method=$args['method'];
   unset($args['method']);
} else {
   $method='glpi.test';
}

if (isset($args['session'])) {
   $url.='?session='.$args['session'];
   unset($args['session']);
}

$header = "Content-Type: text/xml";

if (isset($args['deflate'])) {
   unset($args['deflate']);
   $header .= "\nAccept-Encoding: deflate";
}

if (isset($args['base64'])) {
   $content = @file_get_contents($args['base64']);
   if (!$content) {
      die ("File not found or empty (".$args['base64'].")\n");
   }
   $args['base64'] = base64_encode($content);
}

foreach($args as $key => $value) {
   if (substr($value, 0, 5)=='json:') {
      $args[$key] = json_decode(substr($value, 5), true);
   }
}
echo "+ Calling '$method' on http://$host/$url\n";

$request = xmlrpc_encode_request($method, $args);
$context = stream_context_create(array('http' => array('method'  => "POST",
                                                       'header'  => $header,
                                                       'content' => $request)));

$file = file_get_contents("http://$host/$url", false, $context);
if (!$file) {
   die("+ No response\n");
}

if (in_array('Content-Encoding: deflate', $http_response_header)) {
   $lenc=strlen($file);
   echo "+ Compressed response : $lenc\n";
   $file = gzuncompress($file);
   $lend=strlen($file);
   echo "+ Uncompressed response : $lend (".round(100.0*$lenc/$lend)."%)\n";
}
$response = xmlrpc_decode($file);
if (!is_array($response)) {
   echo $file;
   die ("+ Bad response\n");
}

if (xmlrpc_is_fault($response)) {
    echo("xmlrpc error(".$response['faultCode']."): ".$response['faultString']."\n");
} else {
   echo "+ Response: ";
   print_r($response);
}
?>
