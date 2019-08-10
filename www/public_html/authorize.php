<?php
function authorize($conn){
  if( isset($_SERVER['PHP_AUTH_USER']) &&
      isset($_SERVER['PHP_AUTH_PW']) ) {
    $tmp_username = $_SERVER['PHP_AUTH_USER'];
    $tmp_pass     = $_SERVER['PHP_AUTH_PW'];

    $query = "SELECT u.username, u.password
                FROM users u, role_to_user ru, roles r
                WHERE u.active = TRUE
                AND r.name = 'Timecard Admin'
                AND ru.role_id = r.id
                AND ru.user_id = u.id
                AND username = :username";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':username', $tmp_username);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    #throw new Exception("username: {$result['username']}");
    if($stmt->rowCount() != 1){
#      throw new Exception('<div class="error">
#                             Invalid username or password.</div>');
      header('WWW-Authenticate: Basic realm="Login Required"');
      header('HTTP/1.0 401 Unauthorized');
      die ("Invalid username or password");
    }

    if(password_verify($tmp_pass, $result['password'])){
      session_start();
      #throw new Exception('STARTED!');
    } else {
      #throw new Exception('<div class="error">
      #                      Invalid username or password.</div>');
      header('WWW-Authenticate: Basic realm="Login Required"');
      header('HTTP/1.0 401 Unauthorized');
      die("Invalid username or password");
    }
  } else {
    header('WWW-Authenticate: Basic realm="Login Required"');
    header('HTTP/1.0 401 Unauthorized');
    die ("Please enter your username and password.");
  }
}
?>
