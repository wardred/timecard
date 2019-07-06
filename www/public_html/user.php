<!DOCTYPE html>
<?php require 'header.php';
      require 'functions.php';
?>
<html lang="en">
<head>
  <title>FreeGeek Timecard User Screen</title>
  <meta http-equiv="refresh"
        content="180;url=http://timecard.freegeektwincities.org/index.php">
</head>
<body>
<?php
$request=NULL;
# User clicked either checkin or lookup account
if( ( isset($_GET['checkin']) && $_GET['checkin'] == 'true') ||
    ( isset($_POST['request']) && $_POST['request'] == 'checkin') ){
  ?> <h1><a href="index.php">FreeGeek</a> - 
      <a href="user.php?checkin=true">Time Clock</a></h1>
  <div>Not checking in?
       <a href="index.php">Return to the Free Geek Timecard Homepage.</a>
  </div> <?php
  $request="checkin";
} else { # Should probably make this part of the if else blocks below
  ?> <h1><a href="index.php">FreeGeek</a> - 
      <a href="user.php?hours=true">Time Clock</a></h1>
  <div>Not checking hours?
       <a href="index.php">Return to the Free Geek Timecard Homepage.</a>
  </div> <?php
  $request="hours";
}

if( $_SERVER["REQUEST_METHOD"] != "POST" ) {
  display_lookup_form($request);
} elseif( isset($_POST['punched']) && $_POST['punched'] == 'true' ) {
  unset($_POST['punched']);
  $jobid = "job_" . $_POST['id'];
  $job=NULL;
  if( isset($_POST[$jobid]) ) {
    $job=$_POST[$jobid];
  }
  echo "<br>" . punch_check_status( $conn, $_POST['id'], $job, TRUE, 
                                    $_POST['request']) . "</br>";
} elseif ( isset($_POST['lookup']) && $_POST['request'] == "checkin" ) {
  $users=lookup_user($conn, $_POST, $_POST['request']);
  if(isset($users)) {
    echo "If your name is not listed, " .
    '<a href="create.php">create your username</a> ' .
    "or search again.<br><br>";
    display_user_selection($conn, $users, "checkin");

    echo "<br><br>Search again:";
    display_lookup_form($request);
  } else {
    echo '<div class="warning">Nobody found.</div>';
    display_lookup_form($request);
  }
  unset($_POST['lookup']);
} elseif( isset($_POST['id']) && $_POST['request'] == "hours" ) {
  $request="hours";
  $userid=$_POST['id'];
  $hours=get_hours($conn, $userid, $request);
} elseif ( isset($_POST['lookup']) && $_POST['request'] == "hours" ) {
  $request=$_POST['request'];
  $users=lookup_user($conn, $_POST, $request);
  if(isset($users)) {
    echo "If your name is not listed, " .
    '<a href="create.php">create your username</a> ' .
    "or search again.<br><br>";
    display_user_selection($conn, $users, $request);

    echo "<br><br>Search again:";
    display_lookup_form($request);
  } else {
    echo '<div class="warning">Nobody found.</div>';
    display_lookup_form($request);
  }
  unset($_POST['lookup']);
} ?>
</body>
</html>
