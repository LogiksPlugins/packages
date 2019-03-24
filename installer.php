<?php
if(!defined('ROOT')) exit('No direct script access allowed');

if(!function_exists("configure_package")) {
  
  //install feature config file
  //move .install/files into respective folders
  //install sql folder
  //install sql schema
  //generate permissions
  function configure_package($packagePath, $overwrite=true) {
    //$tempDir = _dirTemp("packages");
    $packageID = basename($packagePath);
    $pluginInfo = [];
    $basePath = APPROOT;
    $appID = SITENAME;
    if(defined("CMS_APPROOT")) {
      $basePath = CMS_APPROOT;
    }
    if(defined("CMS_SITENAME")) {
      $appID = CMS_SITENAME;
    }
    if(file_exists($packagePath."logiks.json")) {
      $pluginInfo = json_decode(file_get_contents($packagePath."logiks.json"),true);
    }
    
    //feature config
    if(is_file($packagePath."feature.cfg")) {
      copy($packagePath."feature.cfg",$basePath."config/features/{$packageID}.cfg");
    }
    if(is_file($packagePath."feature.json")) {
      copy($packagePath."feature.json",$basePath."config/features/{$packageID}.json");
    }
    
    //.install folder
    if(is_dir($packagePath.".install/")) {
      installDotFolder($packagePath.".install/");
    }
    
    //SQL Folder
    if(is_dir($packagePath."sql/")) {
      installSQLFolder($packagePath."sql/");
    }
    
    //SQL Schema
    if(is_file($packagePath."schema.json")) {
      installSchema($packagePath."schema.json");
    }
    
    //Generate Permissions
    if(isset($pluginInfo['permissions'])) {
      installPermissions($pluginInfo['permissions']);
    }
    
    return "Package installed successfully";
  }
  
  function installPermissions($permissionArr) {
    foreach($permissionArr as $activity=>$b) {
      if(!is_array($b)) $b = [$b];

      foreach($b as $actionType) {
        RoleModel::getInstance()->registerRole($packageID, $activity, $actionType, $appID, $_SESSION['SESS_USER_ID'], $_SESSION['SESS_GUID']);
      }
    } 
  }
  
  function installSchema($schemaFile) {
  }
  
  function installSQLFolder($packagePath) {
    if(is_file($packagePath."schema.sql")) {
      $sqlQuery = file_get_contents($packagePath."schema.sql");
      $sqlQuery = explode(";\n",$sqlQuery);

      foreach($sqlQuery as $sql) {
        $sql = trim($sql);
        if(strlen($sql)<=1) continue;
        $a = _db()->_RAW($sql)->_RUN();
      }
    }
    
    if(is_file($packagePath."data.sql")) {
      $sqlQuery = file_get_contents($packagePath."data.sql");
      $sqlQuery = explode(";\n",$sqlQuery);

      foreach($sqlQuery as $sql) {
        $sql = trim($sql);
        if(strlen($sql)<=1) continue;
        $a = _db()->_RAW($sql)->_RUN();
      }
    }
    
    if(is_file($packagePath."schema_core.sql")) {
      $sqlQuery = file_get_contents($packagePath."schema_core.sql");
      $sqlQuery = explode(";\n",$sqlQuery);

      foreach($sqlQuery as $sql) {
        $sql = trim($sql);
        if(strlen($sql)<=1) continue;
        $a = _db(true)->_RAW($sql)->_RUN();
      }
    }
    
    if(is_file($packagePath."data_core.sql")) {
      $sqlQuery = file_get_contents($packagePath."data_core.sql");
      $sqlQuery = explode(";\n",$sqlQuery);

      foreach($sqlQuery as $sql) {
        $sql = trim($sql);
        if(strlen($sql)<=1) continue;
        $a = _db(true)->_RAW($sql)->_RUN();
      }
    }
  }
  
  function installDotFolder($dotDir) {
    $tempFS = scandir($dotDir);
    $tempFS = array_splice($tempFS,2);
    foreach($tempFS as $f) {
      copyFolder($dotDir.$f,$basePath.$f,$overwrite);
    }
  }
}
?>