<!DOCTYPE html>
<?php require 'header.php';
      require 'functions.php';
?>
<html lang="en">
<head>
  <title>FreeGeek Timecard Check In</title>
</head>
<body>
<?php
$request=NULL;
# User clicked either checkin or lookup account
if( ( isset($_GET['checkin']) && $_GET['checkin'] == 'true') ||
    ( isset($_POST['request']) && $_POST['request'] == 'checkin') ){
  ?> <h1><a href="index.php">FreeGeek</a> - 
      <a href="checkin.php?checkin=true">Time Clock</a></h1> <?php
  $request="checkin";
} else { # Should probably make this part of the if else blocks below
  ?> <h1><a href="index.php">FreeGeek</a> - 
      <a href="checkin.php?hours=true">Time Clock</a></h1> <?php
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
}

/*
# No form data entered
if( $_SERVER["REQUEST_METHOD"] != "POST" ) {
  # display_checkin_form();
  $users = display_lookup_form();

# Username submitted, need to confirm correct user.
} elseif (isset($_POST['username']) && (! isset($_POST['lookup'])) ) {
  $username = strtolower(test_input($_POST['username']));

  if($username){
    try {
      lookup($conn, $username);
    } catch(Exception $e) {
      echo $e->getMessage();
    }
  } else {
    echo '<div class="error">Username is required!</div>';
    display_checkin_form();
    display_lookup_form();
  }

# User lookup form submitted
} elseif( isset($_POST['lookup']) ) {
  if(isset($user_data['username'])){
    $user_data['username']   = strtolower(test_input($_POST["username"]));
  }
  $user_data['first_name'] = test_input($_POST["first_name"]);
  $user_data['last_name']  = test_input($_POST["last_name"]);
  $user_data['email']      = test_input($_POST["email"]);
  $user_data['phone_area_code']      = test_input($_POST["phone_area_code"]);
  $user_data['phone_prefix']         = test_input($_POST["phone_prefix"]);
  $user_data['phone_last_four']      = test_input($_POST["phone_last_four"]);

  # Concatenate the 3 phone nunber fields into 1 number.
  if( isset( $user_data['phone_area_code']) &&
      isset( $user_data['phone_prefix']) &&
      isset( $user_data['phone_last_four'] ) ){
    $user_data['phone'] = (int) $user_data['phone_area_code'] .
                                $user_data['phone_prefix'] .
                                $user_data['phone_last_four'];
    }

  lookup_user($conn, $user_data);

# The punch button was pressed
} elseif ( isset($_POST['punchname']) ) {
  if(! isset($_POST['job'])) {
    echo '<div class="error">You must select a job.</div>';
  } else {
    $status = punch_check_status($conn, $_POST['punchname'],
              $_POST['job'], TRUE);

    if( $status == "New User!") {
      echo "Hello new user!";
    } elseif( $status == "Clocked out") {
      echo "You were clocked out.  Clocking in.";
    } elseif( $status == "Clocked in") {
      echo "You were clocked in.  Clocking out.";
    }
  }
  display_checkin_form();
  display_lookup_form();
}
*/?>
</body>
</html>
