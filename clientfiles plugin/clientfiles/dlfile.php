<?php
# Include common.php
include('../../admin/inc/common.php');

if (cookie_check()) // Admin is logged in
{
 // serve file contents
 if (isset($_GET['client']) && isset($_GET['getfile']))
 {
   static $mimetypes = array(
             "doc" => "application/msword",
             "pdf" => "application/pdf",
             "ai" => "application/postscript",
             "eps" => "application/postscript",
             "swf" => "application/x-shockwave-flash",
             "zip" => "application/zip",
             "mp3" => "audio/mpeg",
             "bmp" => "image/bmp",
             "gif" => "image/gif",
             "jpeg" => "image/jpeg",
             "jpg" => "image/jpeg",
             "png" => "image/png",
             "tiff" => "image/tiff",
             "tif" => "image/tif",
             "txt" => "text/plain",
             "xml" => "text/xml",
             "mpeg" => "video/mpeg",
             "mpg" => "video/mpeg",
             "avi" => "video/x-msvideo",
             "wmv" => "video/x-msvideo",
   );

   $clientfiles_dir = GSDATAOTHERPATH.'clientfiles/';
   $client = urldecode($_GET['client']);
   $getfile = (urldecode($_GET['getfile'])); 
   $fileparts = explode(".", $getfile);
   $ext= $fileparts[(count($fileparts) - 1)];
   $client_dir = $clientfiles_dir . $client  . '/';
   
   if ((substr($getfile,0,1) <> '.' ) && is_file($client_dir . '/' . $getfile))
   {
     header('Content-Disposition: attachment; filename="' . $getfile . '"');
     if (!empty($mimetypes[$ext]) && trim($mimetypes[$ext]) != "")
     {
       header('Content-Type: ' . $mimetypes[$ext]);            
     }
     else
     {
       header('Content-Type: application/octet-stream');            
     }
     header('Content-Length: '.filesize($client_dir . '/' . $getfile));
     flush();
     readfile($client_dir . '/' . $getfile);
   } 
 }
}
else
{
  header('Location: 403');
  exit;
}
?>