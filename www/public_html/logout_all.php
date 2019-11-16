<?php
  require "header.php";
  require "authorize.php";
  require "functions.php";

  authorize($conn);
  log_everybody_out($conn);

  echo "Everybody logged out!";
?>
