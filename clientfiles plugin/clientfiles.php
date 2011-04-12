<?php
/*
Plugin Name: Client Files
Description: a GetSimple CMS plugin or provide password protected client file pages and a back-end to manage them
Version: 0.5
Author: Rob Antonishen
Author URI: http://ffaat.poweredbyclear.com/
*/

// get correct id for plugin
$thisfile=basename(__FILE__, ".php");

// register plugin
register_plugin(
  $thisfile,
  'Client Files',
  '0.5',
  'Rob Antonishen',
  'http://ffaat.poweredbyclear.com/',
  'Provides a simple file manager with password protected access areas for Get Simple',
  'files',
  'clientfiles_manage'
);

// activate filter
add_action('files-sidebar','createSideMenu',array($thisfile,'Client Files'));
add_action('index-pretemplate','clientfiles_pagestart');

add_filter('content','clientfiles_display');


/***********************************************************************************
*
* Helper functions
*
***********************************************************************************/

function clientfiles_erasedirconf($client=NULL)
{
  echo '<div style="display: block; text-align: center" class="error">';
  echo 'Are you sure you want to delete client area <strong>' . $client. '</strong> and all files uploaded for that client?<br />';
  echo '(This can NOT be undone)<br />';
  // Delete file form
  echo '<form name="clientdel" action="load.php?id=clientfiles" method="post">';
  echo '<input type="hidden" name="client" value="' . urlencode($client) . '" />';
  echo '<input type="submit" class="submit" name="delclientconf" value="Delete" />';
  echo '&nbsp;&nbsp;<input type="submit" class="submit" name="delclientcancel" value="Cancel" />';
  echo '</form>';
  echo '</div>';
}

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
    else
    {
      echo '<div style="display: block;" class="updated">Erased Client ' . $client. '.</div>';
    }
  }
}

function clientfiles_newdir($client="", $pass="")
{
  $message = "";
  $clientfiles_dir = GSDATAOTHERPATH.'clientfiles/';

  if (($client == "") || ($pass == ""))  // check for blank
  {
    $message = "Client name or password can not be left blank.<br />";
  }
  else
  {
    if (!is_dir($clientfiles_dir . $client . '/'))  // new dir - create it
    {
      $message = "Created client " . $client;
      if (!mkdir($clientfiles_dir . $client . '/'))
      {
        exit('Failed to create client folder...');
      }        
    }
    else
    {
      $message = "Updated client " . $client . " password.";
    }
    $pass = sha1($pass);
    $passfile = $clientfiles_dir . $client . '/' . '.cfpassword';
    $fh = fopen($passfile, 'w') or die("can't create password file");
    fwrite($fh, $pass);
    fclose($fh);
    
  }
  if ($message!="") echo '<div style="display: block;" class="updated">' . $message . '</div>';
  return;
}

function clientfiles_checkpass($client="", $hashpass="")
{
  // check for client folder
  $clientfiles_dir = GSDATAOTHERPATH.'clientfiles/';
  if (($client != "") && (is_dir($clientfiles_dir . $client . '/')) && (is_file($clientfiles_dir . $client . '/.cfpassword')))
  {
    $passfile = $clientfiles_dir . $client . '/' . '.cfpassword';
    $fh = fopen($passfile, 'r') or die("can't read password file");
    $filepass= fread($fh, filesize($passfile));
    fclose($fh);
     
    if ($hashpass == $filepass)
    {
     $_SESSION['cf_client'] = $client;
     $_SESSION['cf_password'] = $hashpass;
     return TRUE;
    }
  }
  return FALSE;
}

function clientfiles_checkloggedin()
{
  // check session variables
  if (isset($_SESSION['cf_client']) && isset($_SESSION['cf_password']))
  {
  // valid session varaibles? 
    if (clientfiles_checkpass($_SESSION['cf_client'], $_SESSION['cf_password']) === FALSE)
    {
      // Variables are incorrect, user not logged in */
      unset($_SESSION['cf_client']);
      unset($_SESSION['cf_password']);
      return FALSE;
    }
    return TRUE;
  }
  /* User not logged in */
  else
  {
    return FALSE;
  }
}

function clientfiles_format_bytes($size) 
{
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    for ($i = 0; $size >= 1024 && $i < 4; $i++) $size /= 1024;
    return round($size, 2).$units[$i];
}

function clientfiles_clientlist() 
{
  //
  //  echos main client list of folders and new client form fields
  //
  $clientfiles_dir = GSDATAOTHERPATH.'clientfiles/';
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
    echo 'No client file areas set up.<br />';
  }
  else
  {
    echo '<table class="highlight" width="100%">';
    sort($dirarray);
    echo '<caption><strong>Client File Areas:</strong></caption>';
    echo '<tbody>';
    foreach ($dirarray as $clientdir)
    { 
      // count client files and size
      $clientinfo = "<span> (";
      $dir_handle = @opendir($clientfiles_dir.$clientdir);
      $clientfilecount = 0;
      $clientsize = 0;
      while ($filename = readdir($dir_handle))
      {
        if (substr($filename,0,1)<>".")
        {
          $clientfilecount += 1;
          $clientsize += filesize($clientfiles_dir.$clientdir."/".$filename);
        }
      }      
      $clientinfo .= $clientfilecount . " file";
      if ($clientfilecount <> 1) $clientinfo .= "s";
      $clientinfo .= ", " . clientfiles_format_bytes($clientsize) . ") </span>";

      echo '<tr><td ><a href="load.php?id=clientfiles' . '&manageclient=' . urlencode($clientdir) . '" title="Manage File Area">' . $clientdir. '</a></td>';
      echo '<td><span>' . $clientinfo . '</span></td>';      
      echo '<td width="15%"><a href="load.php?id=clientfiles' . '&changepass=' . urlencode($clientdir) . '" title="Change Client Password"><span>Password</span></a></td>';
      echo '<td class="delete" width="10px"><a href="load.php?id=clientfiles' . '&delclient=' . urlencode($clientdir) . '" title="Delete Client File Area">X</a></td></tr>';
    }
    echo '</tbody></table>';
  }
    
  // New Client Area form
  echo '<form name="clientnew" action="load.php?id=clientfiles" method="post">';
  echo 'Name: <input type="text" class="short text" style="width: 150px" name="client" value="">';
  echo '&nbsp;&nbsp;Password: <input type="password" class="short text" style="width: 150px" name="pass" value="">';
  echo '&nbsp;&nbsp;<input type="submit" class="submit" name="submitclientnew" value="New Client Area" />';
  echo '</form>';
}

function clientfiles_changepass($client)
{
  echo 'Set new password for Client <strong>' . $client. '</strong><br />.';
  // password form
  echo '<form name="changepass" action="load.php?id=clientfiles" method="post">';
  echo '<input type="hidden" name="client" value="' . urlencode($client) . '" />';
  echo '<input type="password" class="short text" style="width: 150px" name="pass" value="">';
  echo '&nbsp;&nbsp;<input type="submit" class="submit" name="changepassconf" value="Change Password" />';
  echo '&nbsp;&nbsp;<input type="submit" class="submit" name="changepasscancel" value="Cancel" />';
  echo '</form>';
  echo '</div>';
}

function clientfiles_filelist($client)
{
  //
  // display list of client files
  //
  $clientfiles_dir = GSDATAOTHERPATH.'clientfiles/';
  $client_dir = $clientfiles_dir . $client  . '/';   
  $dir_handle = @opendir($client_dir) or exit('Unable to open the folder ' . $client_dir . ', check the folder privileges.');
  $filearray = array();

  while ($filename = readdir($dir_handle))
  {
    if ((!is_dir($client_dir.$filename)) && (substr($filename,0,1) <> '.')) //ignore directories and dot files.
    {
      $filearray [] = array($filename, date("Y/m/d H:i:s", filemtime($client_dir.$filename)), '('.clientfiles_format_bytes(filesize($client_dir.$filename)).')');
    }
  }
  
  echo '<table class="highlight" width="100%">';
  echo '<caption><strong>Client ' . $client . ' files:</strong></caption>';
  echo '<tbody>';  
      
  // generate client area list:
  if (count($filearray) == 0)
  {
    echo '<tr><td>No client files.</td></tr>';
  }
  else
  {
    sort($filearray);
    foreach ($filearray as $clientfile)
    {
      echo '<tr><td><a href="../plugins/clientfiles/dlfile.php?client=' . urlencode($client) . '&getfile=' . urlencode($clientfile[0]) . '" title="Download File">' . $clientfile[0] . '</a> <span>' . $clientfile[2] . '</span></td>';
      echo '<td><span>' . $clientfile[1] . '</span></td>';
      echo '<td class="delete" width="10px"><a href="load.php?id=clientfiles' . '&manageclient=' . urlencode($client) . '&delfile=' . urlencode($clientfile[0]) . '" title="Delete File">X</a></td></tr>';
    }
  }
  echo '</tbody></table>';
 
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

function clientfiles_uploadfile($client, $targetfile, $tempfile)
{
  $clientfiles_dir = GSDATAOTHERPATH.'clientfiles/';
  $client_dir = $clientfiles_dir . $client  . '/';   
  $target_path = $client_dir . $targetfile; 
  if(move_uploaded_file($tempfile, $target_path)) 
  {
    echo '<div style="display: block;" class="updated">The file ' . $targetfile. ' has been uploaded.</div>';
  } 
  else
  {
    echo '<div style="display: block;" class="error">Unable to upload ' . $targetfile. ' please try again.</div>';
  } 
}

function clientfiles_delfileconf($client, $delfile)
{
  echo '<div style="display: block; text-align: center" class="error">';
  echo 'Are you sure you want to delete file <strong>' . $delfile . '</strong> from <strong>' . $client. '</strong> area?<br />';
  echo '(This can NOT be undone)<br />';
  // Delete file form
  echo '<form name="clientdelfile" action="load.php?id=clientfiles" method="post">';
  echo '<input type="hidden" name="client" value="' . urlencode($client) . '" />';
  echo '<input type="hidden" name="delfile" value="' . urlencode($delfile) . '" />';
  echo '<input type="submit" class="submit" name="delfileconf" value="Delete" />';
  echo '&nbsp;&nbsp;<input type="submit" class="submit" name="delfilecancel" value="Cancel" />';
  echo '</form>';
  echo '</div>';
}

function clientfiles_delfile($client, $delfile)
{
  $clientfiles_dir = GSDATAOTHERPATH.'clientfiles/';
  $client_dir = $clientfiles_dir . $client  . '/';
  if (substr($delfile,0,1) <> '.')
  {
    unlink($client_dir . '/' . $delfile ) or exit('Unable to delete ' . $delfile  .  ', check folder content privileges.');
  }
  echo '<div style="display: block;" class="updated">' . $delfile. ' deleted.</div>';
}

/***********************************************************************************
*
* start of front end page
*
***********************************************************************************/
function clientfiles_pagestart()
{
  //create session for clientpage login
  if (!isset($_SESSION))
  {
      session_start();
      session_regenerate_id();
  }
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
    //create new client or change an existing ones password
    if ((isset($_POST['submitclientnew'])) || (isset($_POST['changepassconf'])))
    {
      clientfiles_newdir($_POST['client'],$_POST['pass']);
      clientfiles_clientlist();          
    } 
    // display change client password form
    elseif (isset($_GET['changepass']))
    {
      clientfiles_changepass(urldecode($_GET['changepass']));
    }
    //delete a client confirmation
    elseif (isset($_GET['delclient']))
    {
      clientfiles_erasedirconf(urldecode($_GET['delclient']));         
    } 
    //del a client 
    elseif ((isset($_POST['delclientconf'])) && ($_POST['delclientconf']=="Delete"))
    {
      clientfiles_erasedir(urldecode($_POST['client']));
      clientfiles_clientlist();   
    }    
    //del client cancel
    elseif ((isset($_POST['delclientcancel'])) && ($_POST['delclientcancel']=="Cancel"))
    {
      clientfiles_clientlist();   
    }
    //process file upload
    elseif (isset($_POST['submitfilenew']) && (isset($_POST['client'])))
    {
      clientfiles_uploadfile(urldecode($_POST['client']), basename( $_FILES['uploadedfile']['name']), $_FILES['uploadedfile']['tmp_name']);
      clientfiles_filelist(urldecode($_POST['client']));
    }   
    //delete a file confirmation
    elseif ((isset($_GET['manageclient'])) && (isset($_GET['delfile'])))
    {  
      clientfiles_delfileconf(urldecode($_GET['manageclient']), urldecode($_GET['delfile']));
    }
    //del file
    elseif ((isset($_POST['delfileconf'])) && ($_POST['delfileconf']=="Delete"))
    {
      clientfiles_delfile(urldecode($_POST['client']), urldecode($_POST['delfile']));
      clientfiles_filelist(urldecode($_POST['client']));   
    } 
    //del file cancel
    elseif ((isset($_POST['delfilecancel'])) && ($_POST['delfilecancel']=="Cancel"))
    {
      clientfiles_filelist(urldecode($_POST['client']));   
    }     
    //manage client file list    
    elseif (isset($_GET['manageclient']))
    {
      clientfiles_filelist(urldecode($_GET['manageclient']));
    } 
    // display list of clients
    else
    {
      clientfiles_clientlist(); 
    }
  }
  else
  {
    // error message - no client file page created.
    echo "Folder .../data/other/clientfiles doesn't exist!";
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

    $goodlogin = FALSE;
    
    $clientfiles_content = '';
      
    if (isset($_POST['submitlogout']))
    {
      if (isset($_SESSION['cf_client'])) unset($_SESSION['cf_client']);
      if (isset($_SESSION['cf_password'])) unset($_SESSION['cf_password']);
    } 
   
    if (clientfiles_checkloggedin() === TRUE)
    {
      $goodlogin = TRUE;
    }
    
    if (isset($_POST['submitlogin']))
    {
      $goodlogin = clientfiles_checkpass($_POST['client'],sha1($_POST['pass']));  
      if ($goodlogin === FALSE)
      {
        $clientfiles_content .= 'Bad Login!<br />';  // message
      } 
    } 
      
    if ($goodlogin === FALSE)
    {
      $clientfiles_content .= 'Please log is to view client files.<br /><br />';
      
      // login form
      $clientfiles_content .= '<form name="login" action="#clientlogin" method="post">';
      $clientfiles_content .= 'Name: <input type="text" style="width: 150px" name="client" value="">';
      $clientfiles_content .= '&nbsp;&nbsp;Password: <input type="password" style="width: 150px" name="pass" value="">';
      $clientfiles_content .= '&nbsp;&nbsp;<input type="submit" name="submitlogin" value="Client Log In" />';
      $clientfiles_content .= '</form></br>';
    }
    else // logged in display stuff
    {
      $clientfiles_dir = GSDATAOTHERPATH.'clientfiles/';
      $client = $_SESSION['cf_client'];
      $client_dir = $clientfiles_dir . $client  . '/';
 
      //
      // display list of client files
      //   
      $dir_handle = @opendir($client_dir) or exit('Unable to open the folder ' . $client_dir . ', check the folder privileges.');
      $filearray = array();

      while ($filename = readdir($dir_handle))
      {
        if ((!is_dir($client_dir.$filename)) && (substr($filename,0,1) <> '.')) //ignore directories and dot files.
        {
          $filearray [] = array($filename, date("Y/m/d H:i:s", filemtime($client_dir.$filename)), '('.clientfiles_format_bytes(filesize($client_dir.$filename)).')');
        }
      }

      //
      // display client page if it exists - slug is "clientpage_<client name>"
      //
      $userpage_file = GSDATAPAGESPATH . "clientpage_" . $client . ".xml";
      if ( file_exists($userpage_file) )
      {
        $userpage_data = getXML($userpage_file);		
        $clientfiles_content .= stripslashes( html_entity_decode($userpage_data->content, ENT_QUOTES, 'UTF-8') );
      }
            
            
      $clientfiles_content .= '<table id="cf_table">';
      $clientfiles_content .= '<caption>Client: ' . $client . '</caption>';
      $clientfiles_content .= '<thead><tr><th>File</th><th>Date</th></tr></head>';
      
      // generate client area list:
      $clientfiles_content .= '<tbody>';
      $filecount = count($filearray);
      if ($filecount > 0)
      $rowclass="";
      {
        sort($filearray);
        foreach ($filearray as $clientfile)
        {
          $clientfiles_content .= '<tr' . $rowclass . '><td><a href="../plugins/clientfiles/dlfile.php?client=' . urlencode($client) 
                                  . '&getfile=' . urlencode($clientfile[0]) . '" title="Download File">' . $clientfile[0] 
                                  . '</a>&nbsp;' . $clientfile[2] . '</td><td>' . $clientfile[1] . '</td></tr>';
          if ($rowclass=="") {
            $rowclass=' class="alt"';
          }
          else
          {
            $rowclass="";
          }
        }
      }      

      $clientfiles_content .= '<tr' . $rowclass . '><td colspan="2">';
      if ($filecount==1)
      {
        $clientfiles_content .= "$filecount file";
      }
      else
      {
        $clientfiles_content .= "$filecount files";
      }
      $clientfiles_content .= '</td></tr>';
      $clientfiles_content .= '</tbody></table><br />';  
             
      // logout form
      $clientfiles_content .= '<form name="logout" action="#clientlogout" method="post">';
      $clientfiles_content .= '<input type="submit" name="submitlogout" value="Client Log Out" />';
      $clientfiles_content .= '</form></br>';        
    }
    
    // build page
    $tmp_content = $start_content . $clientfiles_content . $end_content;
  }
    
  return $tmp_content;
}
?>