<?php
/*
Plugin name: Validate uploads
Description: Options to perform various validation tasks (filename, type, ...) on uploaded entries
*/
if (!defined("ADMIN_DIR")) exit();

function validatearchive_rename( $data )
{
  if (get_setting("validatearchive_rename"))
  {
    $extension = pathinfo($data["filename"],PATHINFO_EXTENSION);
    $data["filename"] = $data["data"]["title"] . " by " . $data["data"]["author"] . "." . $extension;
  }
}

add_hook("admin_common_handleupload_beforesanitize","validatearchive_rename");


function validatearchive_validate( $params )
{
  if (!is_uploaded_file($params["dataArray"]["localFileName"]))
  {
    return;
  }
  
  $type = get_setting("validatearchive_type") ?? "all";
  if ($type != "all")
  {
    $f = fopen( $params["dataArray"]["localFileName"], "rb" );
    if ($f)
    {
      $header = fread($f,16);
      switch($type)
      {
        case "zip":
          {
            if (substr($header,0,2)!="PK")
              $params["output"]["error"] = "You must upload a ZIP!";
          } break;
        case "ziprar":
          {
            if (substr($header,0,2)!="PK"
             && substr($header,0,4)!="Rar!")
              $params["output"]["error"] = "You must upload either a ZIP or RAR!";
          } break;
      }
      fclose($f);
    }
  }

  $fileiddiz = get_setting("validatearchive_fileiddiz") ?? "nothing";
  if ($fileiddiz != "nothing")
  {
    $zip = new ZipArchive();
    $found = false;
    if ($zip->open($params["dataArray"]["localFileName"]) === true) 
    {
      if ($zip->locateName('file_id.diz', ZipArchive::FL_NOCASE | ZipArchive::FL_NODIR) === false)
      {
        switch($fileiddiz)
        {
          case "error":
            {
              $params["output"]["error"] = "Your ZIP must contain a file_id.diz!";
            } break;
          case "generate":
            {
              $str = $params["dataArray"]["title"]."\n";
              $str .= "by\n";
              $str .= $params["dataArray"]["author"]."\n";
              $str .= "\n";
              $str .= "Released at ".get_setting("party_name")."\n";
              $str .= "on ".date("Y-m-d")."\n";
              $str .= "\n";
              $str .= "----------------------------------------\n";
              $snark = array(
                "Dutifully generated by Wuhu\n",
                "Negligence compensation by Wuhu\n",
                "Done by Wuhu, cos the author didn't\n",
                "Wuhu follows the rules, so you don't have to\n",
              );
              $str .= $snark[array_rand($snark)];
              $str .= "http://wuhu.function.hu\n";
              $zip->addFromString('file_id.diz', $str);
            } break;
        }      
      }
      $zip->close();
    }
  }
}

add_hook("admin_common_handleupload_beforecompocheck","validatearchive_validate");

function validatearchive_addmenu( &$data )
{
  $data["links"]["pluginoptions.php?plugin=validate-archive"] = "validation";
}

add_hook("admin_menu","validatearchive_addmenu");

function validatearchive_activation()
{
  if (get_setting("validatearchive_rename") === null)
    update_setting("validatearchive_rename",false);
  if (get_setting("validatearchive_type") === null)
    update_setting("validatearchive_type","all");
  if (get_setting("validatearchive_fileiddiz") === null)
    update_setting("validatearchive_fileiddiz","nothing");
}

add_activation_hook( __FILE__, "validatearchive_activation" );
?>