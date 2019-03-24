<?php
if(!defined('ROOT')) exit('No direct script access allowed');

include_once __DIR__."/estore.php";
include_once __DIR__."/packages.php";
include_once __DIR__."/installer.php";

//App Configs
if(!function_exists("insertAppConfig")) {
  function insertAppConfig($configName, $appName, $newParams = []) {
    $cfgFile = ROOT.CFG_FOLDER."jsonConfig/{$configName}.json";
    if(!file_exists($cfgFile)) return;

    $jsonConfig = file_get_contents($cfgFile);
    $jsonConfig = json_decode($jsonConfig,true);

    if($jsonConfig) {//!isset($jsonConfig[$appName])
      $jsonConfig[$appName] = $newParams;

      file_put_contents($cfgFile,json_encode($jsonConfig,JSON_PRETTY_PRINT));
    }
  }

  function cloneAppConfig($configName, $appOld, $appNew) {
    $cfgFile = ROOT.CFG_FOLDER."jsonConfig/{$configName}.json";
    if(!file_exists($cfgFile)) return;

    $jsonConfig = file_get_contents($cfgFile);
    $jsonConfig = json_decode($jsonConfig,true);

    if(isset($jsonConfig[$appOld])) {
      $jsonConfig[$appNew] = $jsonConfig[$appOld];

      file_put_contents($cfgFile,json_encode($jsonConfig,JSON_PRETTY_PRINT));
    }
  }
  function renameAppConfig($configName, $appOld, $appNew) {
    $cfgFile = ROOT.CFG_FOLDER."jsonConfig/{$configName}.json";
    if(!file_exists($cfgFile)) return;

    $jsonConfig = file_get_contents($cfgFile);
    $jsonConfig = json_decode($jsonConfig,true);

    if(isset($jsonConfig[$appOld])) {
      $jsonConfig[$appNew] = $jsonConfig[$appOld];
      unset($jsonConfig[$appOld]);

      file_put_contents($cfgFile,json_encode($jsonConfig,JSON_PRETTY_PRINT));
    }
  }
  function deleteAppConfig($configName, $appOld) {
    $cfgFile = ROOT.CFG_FOLDER."jsonConfig/{$configName}.json";
    if(!file_exists($cfgFile)) return;

    $jsonConfig = file_get_contents($cfgFile);
    $jsonConfig = json_decode($jsonConfig,true);

    if(isset($jsonConfig[$appOld])) {
      unset($jsonConfig[$appOld]);

      file_put_contents($cfgFile,json_encode($jsonConfig,JSON_PRETTY_PRINT));
    }
  }
  
  function processTableName($t) {
    $tArr = explode(":",$t);
    if(count($tArr)>1) {
      $t = _dbTable($tArr[1],$tArr[0]);
    }
    return $t;
  }
}

//File and Folder functions
if(!function_exists("deleteFolder")) {
  /* 
   * php delete function that deals with directories recursively
   */
  function deleteFolder($target) {
      if(is_dir($target)){
  //         $files = glob( $target . '*', GLOB_MARK); //GLOB_MARK adds a slash to directories returned
  //         foreach( $files as $file ) {
  //             deleteFolder( $file );      
  //         }
          $files = scandir($target);
          $files = array_slice($files,2);

          foreach( $files as $file ) {
            if(is_dir($target.$file)) 
              deleteFolder($target.$file."/");
            else
              deleteFolder($target.$file);
          };

          rmdir( $target );
      } elseif(is_file($target)) {
          unlink( $target );  
      }
      return file_exists($target);
  }

  /* 
   * php copying function that deals with directories recursively
   */
  function copyFolder($source, $dest, $overwrite = false,$basePath = ""){
      if(!is_dir($basePath . $dest)) //Lets just make sure our new folder is already created. Alright so its not efficient to check each time... bite me
        mkdir($basePath . $dest);
      if($handle = opendir($basePath . $source)){        // if the folder exploration is sucsessful, continue
          while(false !== ($file = readdir($handle))){ // as long as storing the next file to $file is successful, continue
              if($file != '.' && $file != '..'){
                  $path = $source . '/' . $file;
                  if(is_file($basePath . $path)){
                      if(!is_file($basePath . $dest . '/' . $file) || $overwrite)
                      if(!@copy($basePath . $path, $basePath . $dest . '/' . $file)){
                          echo '<font color="red">File ('.$path.') could not be copied, likely a permissions problem.</font>';
                      }
                  } elseif(is_dir($basePath . $path)){
                      if(!is_dir($basePath . $dest . '/' . $file))
                        mkdir($basePath . $dest . '/' . $file); // make subdirectory before subdirectory is copied
                      copyFolder($path, $dest . '/' . $file, $overwrite, $basePath); //recurse!
                  }
              }
          }
          closedir($handle);
      }
  }
}
?>