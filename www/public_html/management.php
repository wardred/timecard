<!DOCTYPE html>
<?php require 'header.php';
      require 'functions.php';
      require 'authorize.php';

if(! isset($_SESSION)){
  session_start();
}

authorize($conn);

$request=NULL;
?>
<html lang="en">
<head>
  <title>FreeGeek Volunteer Timesheet Management Screen</title>
  <link rel="stylesheet" type="text/css" href="style/main.css">
</head>
<body>
<?php
if( isset($_SESSION['logged_in']) && ($_SESSION['logged_in'])){
  echo '<div class="logout"><a href="logout.php">Logout</a></div>';
}
?>
  <div class="head"><h1><a href="index.php">FreeGeek Volunteer Timesheet</a> -
                        <a href="management.php">Management</a></h1>
  </div>
  <!--<div>-->
<?php
if( $_SERVER["REQUEST_METHOD"] == "POST"
    && isset( $_POST['lookup']) ){
  $users=lookup_user($conn, $_POST, $_POST['request']);
  if(isset($users)){
    echo "If you don't see the user you're looking for search again.";
    display_management_user_selection($conn, $users, "lookup");
  }
  logged_in_users($conn);
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
          Let the timesheet maintainer know.</div>';
  }
} elseif( isset($_POST['logout_all']) ){
  log_everybody_out($conn);
  echo '<div class="info">Everybody logged out.</div>';
  logged_in_users($conn);
  display_lookup_form($request);
} elseif( $_SERVER['REQUEST_METHOD'] == "POST" &&
          isset($_POST['logout_user'] )){
  punch_check_status( $conn, $_POST['logout_user'], '', true, '');
  echo "<div class=\"info\">{$_POST['username']} logged out.</div>";
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
  # Hours points to userid in this form.
} elseif( isset($_POST['hours'])){
  get_hours($conn, $_POST['hours'], "management");
  if(isset($_POST['num_punches'])){
    $num_punches = $_POST['num_punches'];
  } else {
    $num_punches = 20; # Default number of hours to pull
  }
  edit_hours($conn, $_POST['hours'], $num_punches);
} elseif( isset($_POST['hours_submitted'])){
  #echo var_dump($_POST);
  hours_submitted($conn,$_POST);
} elseif(isset($_POST['modify_roles'])){
  process_roles($conn);
} elseif(isset($_POST['modify_jobs'])){
  process_jobs($conn);
} elseif( isset($_POST['contiguous_hours_report'])){
  contiguous_hours_report($conn);
} elseif(! $request ) {
  $request="management";
  logged_in_users($conn);
  display_lookup_form($request);
  display_roles($conn);
  display_jobs($conn);
  display_reports();
}
?>
  </div>
</body>
</html>
