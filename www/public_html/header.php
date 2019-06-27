<?php
  // Start the session
  require "../config.php";

  # Not sure if we're going to use sessions.
  # If we did it would probably be to keep a connection.
  #session_start();

  try {
    $conn = new PDO("mysql:host=" . $credentials['host'] . ";" .
                    "dbname=" . $credentials['db'],
                    $credentials['user'],
                    $credentials['pass']);
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    # echo "Connection successful!";
  } catch(PDOException $e) {
    # Need to log connection errors.
    # echo "Connection Failed: " . $e->getMessage();
  }
?>
