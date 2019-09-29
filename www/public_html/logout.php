<?php
if(! isset($_SESSION)){
  session_start();
}

session_destroy();
if( isset($_SERVER['PHP_AUTH_USER']) ){
  unset($_SERVER['PHP_AUTH_USER']);
}

if( isset($_SERVER['PHP_AUTH_PW']) ){
  unset($_SERVER['PHP_AUTH_PW']);
}

if( isset($_SESISON)){
  unset($_SESSION);
}
?>

<html>
  <head>
    <title>FreeGeek Twin Cities Logout</title>
    <meta http-equiv="Refresh" content="30; url=index.php" />
  </head>
  <body>
    <div class="text">You are now logged out.</div>
    <div class="text">You should be returned home in 30 seconds.<br>
    You may press <a href="index.php">return home</a> to return home now.</div>
  </body>
</html>
