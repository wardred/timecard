<!DOCTYPE html>
<?php require 'header.php';
      require 'functions.php';

$request=NULL;
?>
<html lang="en">
<head>
  <title>FreeGeek Timecard Management Screen</title>
</head>
<body>
  <div class="head"><h1><a href="index.php">FreeGeek Timecard</a> -
                        <a href="management.php">Management</a></h1>
  </div>
  <div>
<?php
if( $_SERVER["REQUEST_METHOD"] == "POST"
    && isset( $_POST['lookup']) ){
  $users=lookup_user($conn, $_POST, $_POST['request']);
  if(isset($users)){
    echo "If you don't see the user you're looking for search again.";
    display_management_user_selection($conn, $users, "lookup");
  }
  display_logout_all();
  display_lookup_form("management");
} elseif( isset($_POST['edit']) ){
  edit($conn, $_POST['edit'], true);
} elseif( isset($_POST['edit_submitted']) ){
  $updated = update_user($conn, $_POST);
  if( isset($updated) && ( $updated === "resubmit") ) {
    # Do nothing
  } elseif ( isset($updated) ){
    echo '<div class="info">User Updated</div>';
  } else {
    echo '<div class="warn">Something went wrong.
          Let the timecard maintainer know.</div>';
  }
} elseif( isset($_POST['logout_all']) ){
  log_everybody_out($conn);
  echo '<div class="info">Everybody logged out.</div>';
  display_logout_all();
  display_lookup_form($request);
} elseif( $_SERVER['REQUEST_METHOD'] == "POST" &&
          isset($_POST['logout']) ) {
  $jobid = "job_" . $_POST['logout'];
  $job=NULL;
  if( isset($_POST[$jobid]) ) {
    $job=$_POST[$jobid];
  }
  $status = punch_check_status($conn, $_POST['logout'], $job, TRUE,
        $request);
  if($status == "New User!" || $status == "Clocked in") {
    echo "<h1>Clocked in.</h1>";
  } else {
    echo "<h1>Clocked out.</h1>";
  }
} elseif(! $request ) {
  $request="management";
  display_logout_all();
  display_lookup_form($request);
}
?>
  </div>
</body>
</html>
