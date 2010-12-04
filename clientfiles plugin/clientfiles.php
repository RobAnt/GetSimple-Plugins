<?php
/*
Plugin Name: Client Files
Description: a GetSimple CMS plugin or provide password protected client file pages and a back-end to manage them
Version: 0.1
Author: Rob Antonishen
Author URI: http://ffaat.poweredbyclear.com/
*/

// get correct id for plugin
$clientfiles_thisfile=basename(__FILE__, ".php");

// register plugin
register_plugin(
  $clientfiles_thisfile,
  'Client Files',
  '0.1',
  'Rob Antonishen',
  'http://ffaat.poweredbyclear.com/',
  'Provides a simple file manager with password protected access areas for Get Simple',
  'files',
  'clientfiles_manage'
);

// activate filter
add_action('files-sidebar','createSideMenu',array($clientfiles_thisfile,'Client Files'));


/***********************************************************************************
*
* Helper functions
*
***********************************************************************************/

/* clear the clientfiles directory */
function clientfiles_erasedir($client=NULL)
{
  $clientfiles_dir = GSDATAOTHERPATH.'clientfiles/'.$client;
  if (($client<>NULL) && (is_dir($clientfiles_dir)))
  {
    $dir_handle = @opendir($clientfiles_dir) or exit('Unable to open the folder ' . $clientfiles_dir .  ', check the folder privileges.');
    $filenames = array();

    while ($filename = readdir($dir_handle))
    {
      $filenames[] = $filename;
    }

    if (count($filenames) != 0)
    {
      foreach ($filenames as $file)
      {
        if (!($file == '.' || $file == '..'))
        {
          unlink($clientfiles_dir. '/' . $file) or exit('Unable to clean up the folder ' . $clientfiles_dir .  ', check folder content privileges.');
        }
      }
    }
    if (!rmdir($clientfiles_dir))
    {
      exit('Unable to erase the folder ' . $clientfiles_dir .  ', check folder privileges.');
    }
  }
}

function clientfiles_newdir($client=NULL, $pass=NULL)
{
  $message = "";
  $clientfiles_dir = GSDATAOTHERPATH.'clientfiles/';

  if (($client == "") || ($pass == ""))  // check for blank
  {
    $message = "Client name or password can not be left blank.<br>";
  }
  else
  {
    if (!is_dir($clientfiles_dir . $client . '/'))  // new dir - create it
    {
      if (!mkdir($clientfiles_dir . $client . '/'))
      {
        exit('Failed to create client folder...');
      }        
    }
    $pass = sha1($_POST["pass"])."\n";
    $passfile = $clientfiles_dir . $_POST['client'] . '/' . ".cfpassword";
    $fh = fopen($passfile, 'w') or die("can't create password file");
    fwrite($fh, $pass);
    fclose($fh);
  }
  return $message;
}


/***********************************************************************************
*
* Backend management page
*
***********************************************************************************/
function clientfiles_manage()
{

  $clientfiles_dir = GSDATAOTHERPATH.'clientfiles/';

  // create the directory if it doesn't exist. 
  if (!is_dir($clientfiles_dir))
  {
    if (!mkdir($clientfiles_dir))
    {
      exit('Failed to create folders...');
    }
  } 
  
  if (is_dir($clientfiles_dir))
  {    
    //create new client
    if (isset($_POST['submitclientnew']))
    {
      echo clientfiles_newdir($_POST['client'],$_POST['pass']);          
    } 

    //delete a client
    if (isset($_GET['delclient']))
    {
      clientfiles_erasedir($_GET['delclient']);          
    }
    
    
     
    
    //display backend
    $dir_handle = @opendir($clientfiles_dir) or exit('Unable to open the folder ' . $clientfiles_dir . ', check the folder privileges.');
    $dirarray = array();


    while ($filename = readdir($dir_handle))
    {
      if ((is_dir($clientfiles_dir.$filename)) && ($filename <> '.') && ($filename <> '..'))
      {
        $dirarray[] = $filename;
      }
    }

    
    // generate client area list:
    if (count($dirarray) == 0)
    {
      echo 'No client areas set up.<br>';
    }
    else
    {
      echo 'Client Areas:<br>';
      foreach ($dirarray as $clientdir)
      {
        echo '<a href="load.php?id=clientfiles' . '&manageclient=' . $clientdir . '" title="Manage Client Files">' . $clientdir . '</a>&nbsp;&nbsp;';
        echo '<a href="load.php?id=clientfiles' . '&delclient=' . $clientdir . '" title="Delete Client Files">X</a><br>';
      }
    }
    echo '<hr>';
    
    // New Client Area form
    echo '<form name="clientnew" action="load.php?id=clientfiles" method="post">';
    echo 'Name: <input type="text" size="20" name="client" value="">';
    echo '&nbsp;&nbsp;Password: <input type="password" size="20" name="pass" value="">';
    echo '&nbsp;&nbsp;<input type="submit" name="submitclientnew" value="Create Area" />';
    echo '</form>';
  }
  else
  {
    echo "Folder .../data/other/clientfiles_cache doesn't exist!";
  }
}
?>