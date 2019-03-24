<?php
if(!defined('ROOT')) exit('No direct script access allowed');

if(!function_exists("fetch_package_list")) {
  function fetch_package_list($type,$more=false,$recache=false) {
    $type=strtoupper($type);

    if(!$recache && isset($_SESSION['PACKMAN'][$type])) return $_SESSION['PACKMAN'][$type];

    $data=[];

    $folders=[
        "local-dev"=>CMS_APPROOT."pluginsDev/",
        "local"=>CMS_APPROOT.PLUGINS_FOLDER,
        "global-dev"=>ROOT."pluginsDev/",
        "global"=>ROOT.PLUGINS_FOLDER,
    ];

    switch($type) {
      case "MODULES":
        foreach($folders as $srcType=>$pluginFolder) {
          if(is_dir($pluginFolder."modules/")) {
            $fs=scandir($pluginFolder."modules/");
            foreach($fs as $a) {
              if($a=="." || $a=="..") continue;
              if(is_dir($pluginFolder."modules/$a/")) {
                $pinfo=fetch_package_info($pluginFolder."modules/$a/",'MODULES',$srcType,$more);
                $data[$pinfo['packid']]=$pinfo;
              }
            }
          }
        }
        break;
      case "VENDORS":
        foreach($folders as $srcType=>$pluginFolder) {
          if(is_dir($pluginFolder."vendors/")) {
            $fs=scandir($pluginFolder."vendors/");
            foreach($fs as $a) {
              if($a=="." || $a=="..") continue;
              if(is_dir($pluginFolder."vendors/$a/")) {
                $pinfo=fetch_package_info($pluginFolder."vendors/$a/",'VENDORS',$srcType,$more);
                $data[$pinfo['packid']]=$pinfo;
              }
            }
          }
        }
        break;
      case "PACKAGES":
        foreach($folders as $srcType=>$pluginFolder) {
          if(is_dir($pluginFolder."modules/")) {
            $fs=scandir($pluginFolder."modules/");
            foreach($fs as $a) {
              if($a=="." || $a=="..") continue;
              if(is_dir($pluginFolder."modules/$a/") && is_file($pluginFolder."modules/$a/logiks.json")) {
                $pinfo=fetch_package_info($pluginFolder."modules/$a/",'MODULES',$srcType,$more);
                $data[$pinfo['packid']]=$pinfo;
              }
            }
          }
        }
        break;
    }

    $_SESSION['PACKMAN'][$type]=$data;

    return $data;
  }
	
  function fetch_package_info_fromid($packid, $type="MODULES", $more=true) {
    $pluginList=fetch_package_list($type);

    if(!isset($pluginList[$_POST['packid']])) {
      return false;
    }
    
    return fetch_package_info($pluginList[$_POST['packid']]['fullpath'], $pluginList[$_POST['packid']]['type'], 
                             $pluginList[$_POST['packid']]['category'], $more);
  }
  
  function fetch_package_info($pluginPath, $type= "modules", $srcType, $more = false) {
      $fname=basename($pluginPath);
      $pluginName = $fname;
      if(substr($pluginName,0,1)=="~") $pluginName = substr($pluginName,1);
      $info=[
        "packid"=>md5($pluginPath),
        "name"=>$pluginName,
        "packageid"=>$fname,
        "type"=>strtolower($type),
        "category"=>$srcType,
        "vers"=>".",
        "homepage"=>false,
        "bugs"=>false,
        "docs"=>false,
        "status"=>"OK",
        "created_on"=>date("d M,Y",filemtime($pluginPath)),// H:i:s
        "updated_on"=>date("d M,Y",filectime($pluginPath)),// H:i:s
        "path"=>str_replace(ROOT,"",str_replace(CMS_APPROOT,"",$pluginPath)),
        "fullpath"=>$pluginPath,
        
        "has_info"=>true,
        "is_configurable"=>false,
        "is_editable"=>false,
        "is_archivable"=>false,
        "is_archived"=>false,
        "has_error"=>false,
      ];

      if($more) {
        //$info['fullpath']=$pluginPath;
      }

      $configFile=[
        "local1"=>CMS_APPROOT.CFG_FOLDER."features/{$fname}.cfg",
    // 		"local2"=>CMS_APPROOT.CFG_FOLDER."{$fname}.cfg",
    // 		"global"=>ROOT.CFG_FOLDER."features/{$fname}.cfg",
    // 		"core"=>ROOT."config/{$fname}.cfg",
      ];

      foreach($configFile as $cfg) {
        if(file_exists($cfg)) {
          $info['is_configurable']=true;
          break;
        }
      }

      switch($type) {
        case "MODULES":
          if(is_file($pluginPath."logiks.json")) {
              $info['has_info']=true;
              $pluginInfo = json_decode(file_get_contents($pluginPath."logiks.json"),true);
              if($pluginInfo!=null && is_array($pluginInfo)) {
                  if(isset($pluginInfo['version'])) $info['vers'] = $pluginInfo['version'];
                  if(isset($pluginInfo['name'])) $info['name'] = $pluginInfo['name'];

                  if(isset($pluginInfo['homepage'])) $info['homepage'] = $pluginInfo['homepage'];
                  if(isset($pluginInfo['bugs'])) $info['bugs'] = $pluginInfo['bugs'];

                  if(isset($pluginInfo['docs'])) $info['docs'] = $pluginInfo['docs'];
                  elseif(isset($pluginInfo['wiki'])) $info['docs'] = $pluginInfo['wiki'];
                  else {
                      if(isset($info['homepage']) && substr($info['homepage'],0,33)=="https://github.com/LogiksPlugins/") {
                          $info['docs'] = "https://github.com/LogiksPlugins/{$info['packageid']}/wiki";
                      }
                  }

                  $errors = package_check_errors($pluginInfo,$type);
                  $info['has_error'] = !($errors===false);
                  $info['error_msg'] = $errors;
                
                  $info['logiksinfo'] = $pluginInfo;
              }
          }
          
          if(substr($fname,0,1)=="~") {
            $info['status'] = "ARCHIVE";
            $info['is_archived']=true;
          } else {
            $info['is_archivable']=($srcType=="local" || $srcType=="local-dev");
          }

          $info['is_editable']=is_file($pluginPath."cms.php");
          
          break;
        case "VENDORS":
          $info['has_info']=true;
          break;
        case "WIDGETS":
          $info['has_info']=false;
          $info['is_file']=is_file($pluginPath);
          break;
        case "PACKAGES":
            break;
      }
      return $info;
  }
  
  function package_more_info($packageInfo) {
    $data = ["dashlets"=>[],"widgets"=>[],"tables"=>[]];
    if(!isset($packageInfo['fullpath'])) return $data;
    $path = $packageInfo['fullpath'];
    
    if(file_exists($path."logiks.json")) {
      $pluginInfo = json_decode(file_get_contents($path."logiks.json"),true);
    } else {
      $pluginInfo = ["packageid"=>basename($path)];
    }
    
    //Find Dashlets
    $bpath = dirname(dirname($path))."/dashlets/";
    $fs = scandir($bpath);
    $fs = array_splice($fs,2);
    foreach($fs as $f) {
      if(substr($f,strlen($f)-4,4)=="json") {
        if(current(explode("-",$f))==$pluginInfo['packageid']) {
          $data['dashlets'][] = $f;
        }
      }
    }
    
    //Find Widgets
    $bpath = dirname(dirname($path))."/widgets/";
    $fs = scandir($bpath);
    $fs = array_splice($fs,2);
    foreach($fs as $f) {
      if(substr($f,strlen($f)-3,3)=="php") {
        if(current(explode("-",$f))==$pluginInfo['packageid']) {
          $data['widgets'][] = $f;
        }
      }
    }
    
    if(Database::checkConnection()>1) {
      if($pluginInfo && isset($pluginInfo['install_check']) && isset($pluginInfo['install_check']['tables'])) {
        foreach($pluginInfo['install_check']['tables'] as $t) {
            $t = processTableName($t);
            $data['tables'][$t] = "Not available";
        }
      }
      
      $tbls = _db()->get_tableList();
      foreach($data['tables'] as $t=>$s) {
        if(in_array($t,$tbls)) {
          $data['tables'][$t] = "Available";
        }
      }
    } else {
      if($pluginInfo && isset($pluginInfo['install_check']) && isset($pluginInfo['install_check']['tables'])) {
        foreach($pluginInfo['install_check']['tables'] as $t) {
            $data['tables'][$t] = "Not available";
        }
      }
    }
    return $data;
	}
	
  function package_check_errors($packageInfo,$type="modules") {
    if(isset($packageInfo['dependencies'])) {
      if(isset($packageInfo['dependencies']['core'])) {
        unset($packageInfo['dependencies']['core']);
      }
      if(!isset($packageInfo['install_check'])) $packageInfo['install_check'] = [];
      $packageInfo['install_check']['modules'] = $packageInfo['dependencies'];
    }
    foreach($packageInfo['install_check'] as $a=>$b) {
      switch(strtolower($a)) {
        case "db":
          if($b) {
            if(Database::checkConnection()<=1) {
              return "DB Connection required";
            }
          }
          break;
        case "tables":
          if(is_array($b)) {
            $tbls = _db()->get_tableList();
            $errTbl = [];
            foreach($b as $t) {
              $t = processTableName($t);
              if(!in_array($t,$tbls)) {
                $errTbl[] = $t;
              }
            }
            if(count($errTbl)>0) {
              return "Unable to find tables : ".implode(", ",$errTbl);
            }
          }
          break;
        case "modules":
          if(count($packageInfo['install_check']['modules'])>0) {
            $errTbl = [];
            foreach($packageInfo['install_check']['modules'] as $a=>$b) {
              if(!checkModule($a)) {
                $errTbl[]=$a;
              }
            }
            if(count($errTbl)>0) {
              return "Unable to find modules : ".implode(", ",$errTbl);
            }
          }
          break;
        case "vendors":
          if(count($packageInfo['install_check']['vendors'])>0) {
            $errTbl = [];
            foreach($packageInfo['install_check']['vendors'] as $a=>$b) {
              if(!checkVendor($a)) {
                $errTbl[]=$a;
              }
            }
            if(count($errTbl)>0) {
              return "Unable to find vendors : ".implode(", ",$errTbl);
            }
          }
          break;
      }
    }
    
    return false;
	}
}
?>