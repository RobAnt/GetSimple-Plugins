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
add_filter('content','clientfiles_display');

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
      clientfiles_erasedir(urldecode($_GET['delclient']));          
    }    
    
    //handle client page data
    if ((isset($_GET['manageclient'])) || (isset($_POST['submitfilenew'])))
    {

      if (isset($_GET['manageclient']))
      {
        $client = urldecode($_GET['manageclient']);
      }
      elseif (isset($_POST['submitfilenew']))
      {
        $client = urldecode($_POST['client']);      
      } 
      
      $client_dir = $clientfiles_dir . $client  . '/';
      
      //process file upload
      if (isset($_POST['submitfilenew']))
      {
        $target_path = $client_dir . basename( $_FILES['uploadedfile']['name']); 
        if(move_uploaded_file($_FILES['uploadedfile']['tmp_name'], $target_path)) 
        {
          echo "(The file ".  basename( $_FILES['uploadedfile']['name']). " has been uploaded.)<br>";
        } 
        else
        {
          echo "Error uploading the file, please try again!<br>";
        } 
      }       
      
      //
      // display list of client files
      //      

      //delete a file
      if (isset($_GET['delfile']))
      {
        $delfile = (urldecode($_GET['delfile'])); 
        if (substr($delfile,0,1) <> '.')
        {
          unlink($client_dir . '/' . $delfile ) or exit('Unable to delete ' . $delfile  .  ', check folder content privileges.');
        } 
      }

      $dir_handle = @opendir($client_dir) or exit('Unable to open the folder ' . $client_dir . ', check the folder privileges.');
      $filearray = array();

      while ($filename = readdir($dir_handle))
      {
        if ((!is_dir($client_dir.$filename)) && (substr($filename,0,1) <> '.')) //ignore directories and dot files.
        {
          $filearray [] = $filename;
        }
      }
      echo $client . ' Area Files:<br><br>';
      
      // generate client area list:
      if (count($filearray) == 0)
      {
        echo 'No client files.<br><br>';
      }
      else
      {
        sort($filearray);
        foreach ($filearray as $clientfile)
        {
          echo '<a href="/plugins/clientfiles/dlfile.php?client=' . urlencode($client) . '&getfile=' . urlencode($clientfile) . '" title="Download File">' . $clientfile . '</a>&nbsp;&nbsp;';
          echo '<a href="load.php?id=clientfiles' . '&manageclient=' . urlencode($client) . '&delfile=' . urlencode($clientfile) . '" title="Delete File">X</a><br>';
        }
      }
      echo '<hr>';
 
      // File Upload form
      echo '<form name="clientfilenew" enctype="multipart/form-data" action="load.php?id=clientfiles" method="post">';
      echo '<input type="hidden" name="MAX_FILE_SIZE" value="10000000" />';
      echo '<input type="hidden" name="client" value="' . urlencode($client) . '" />';
      echo 'Upload File: <input type="file" name="uploadedfile">&nbsp;&nbsp;';
      echo '<input type="submit" name="submitfilenew" value="Upload File" />';
      echo '</form>';    
        
      echo '<hr>';     
      echo '<a href="load.php?id=clientfiles">Back to Client File Areas</a>';

    }    
    else
    {
      //
      //  display main client list of folders
      //
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
        echo 'No client file areas set up.<br>';
      }
      else
      {
        sort($dirarray);
        echo 'Client File Areas:<br><br>';
        foreach ($dirarray as $clientdir)
        {
          echo '<a href="load.php?id=clientfiles' . '&manageclient=' . urlencode($clientdir) . '" title="Manage File Area">' . $clientdir . '</a>&nbsp;&nbsp;';
          echo '<a href="load.php?id=clientfiles' . '&delclient=' . urlencode($clientdir) . '" title="Delete Client File Area">X</a><br>';
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
  }
  else
  {
    // error message - no client file page created.
    echo "Folder .../data/other/clientfiles_cache doesn't exist!";
  }
}

/***********************************************************************************
*
* Frontend display
*
***********************************************************************************/
function clientfiles_display($contents)
{  
    $tmp_content = $contents;

    $location = stripos($tmp_content,"(% clientfiles %)");
    
    if ($location !== FALSE)
    { 
      $tmp_content = str_replace("(% clientfiles %)","",$tmp_content);
    
      $start_content = substr($tmp_content, 0 ,$location);
      $end_content = substr($tmp_content, $location, strlen($tmp_content)-$location );
    
      $clientfiles_content = "booger!";
    
      $tmp_content = $start_content . $clientfiles_content . $end_content;
    }
    
    return $tmp_content;
}
?>