#!/usr/bin/php
<?php

set_include_path(get_include_path() . PATH_SEPARATOR . "/usr/share/yubikey-val:/etc/yubico/val");

require_once 'ykval-config.php';
require_once 'ykval-db.php';


$logname="ykval-import";
$myLog = new Log($logname);

$db = Db::GetDatabaseHandle($baseParams, $logname);

if (!$db->connect()) {
  $myLog->log(LOG_WARNING, "Could not connect to database");
  error_log("Could not connect to database");
  exit(1);
 }


while ($res=fgetcsv(STDIN, 0, ",")) {
  $params=array("active"=>$res[0],
		"created"=>$res[1],
		"modified"=>$res[2],
		"yk_publicname"=>$res[3],
		"yk_counter"=>$res[4],
		"yk_use"=>$res[5],
		"yk_low"=>$res[6],
		"yk_high"=>$res[7],
		"nonce"=>$res[8],
		"notes"=>$res[9]);


  $query="SELECT * FROM yubikeys WHERE yk_publicname='" . $params['yk_publicname'] . "'";
  $result=$db->customQuery($query);
  if($db->rowCount($result)) {
    $query="UPDATE yubikeys SET " .
      "active='" . $params["active"] . "' " .
      ",created='" . $params["created"] . "' " .
      ",modified='" . $params["modified"] . "' " .
      ",yk_counter='" . $params["yk_counter"] . "' " .
      ",yk_use='" . $params["yk_use"] . "' " .
      ",yk_low='" . $params["yk_low"] . "' " .
      ",yk_high='" . $params["yk_high"] . "' " .
      ",nonce='" . $params["nonce"] . "' " .
      ",notes='" . $params["notes"] . "' " .
      "WHERE yk_publicname='" . $params['yk_publicname'] . "' AND " .
      "(".$params['yk_counter'].">yk_counter or (".$params['yk_counter']."=yk_counter and " .
      $params['yk_use'] . ">yk_use))";

    if(!$db->customQuery($query)) {
      $myLog->log(LOG_ERR, "Failed to update yk_publicname with query " . $query);
      error_log("Failed to update yk_publicname with query " . $query);
      exit(1);
    }

  } else {
    // We didn't have the yk_publicname in database so we need to do insert instead
    $query="INSERT INTO yubikeys " .
      "(active,created,modified,yk_publicname,yk_counter,yk_use,yk_low,yk_high,nonce,notes) VALUES " .
      "('" . $params["active"] . "', " .
      "'" . $params['created'] . "'," .
      "'" . $params['modified'] . "'," .
      "'" . $params['yk_publicname'] . "'," .
      "'" . $params['yk_counter'] . "'," .
      "'" . $params['yk_use'] . "'," .
      "'" . $params['yk_low'] . "'," .
      "'" . $params['yk_high'] . "'," .
      "'" . $params['nonce'] . "'," .
      "'" . $params['notes'] . "')";

    if(!$db->customQuery($query)){
      $myLog->log(LOG_ERR, "Failed to insert new yk_publicname with query " . $query);
      error_log("Failed to insert new yk_publicname with query " . $query);
      exit(1);
    }
  }
  $db->closeCursor($result);
 }


$myLog->log(LOG_NOTICE, "Successfully imported yubikeys to database");
echo "Successfully imported yubikeys to database\n";
