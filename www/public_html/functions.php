<?php
function test_input($data) {
  if(isset($data) && $data != '') {
    $data = trim($data);
    # $data = stripslashes($data);
    $data = htmlspecialchars($data);
  } else {
    $data = NULL;
  }
  return $data;
}

function validate_new_user($conn, $data) {
  if( (!$data['username']) || (!$data['first_name']) ) {
    throw new Exception('<div class="error">Username and first name ' .
                        'are required fields.</div>');
  }

  # Check to see if username already exists.
  $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM users
                          WHERE USERNAME = :username" );
  $stmt->bindParam(':username', $data['username']);
  $stmt->execute();
  $result = $stmt->fetch(PDO::FETCH_ASSOC);
  if($result['count'] > 0) {
    throw new Exception('<div class="error">The username ' . $data['username'] . 
                        ' already exists!</div>');
  }

  # Check to see if e-mail is valid
  if(! (($data['email']) == ''     ||
         $data['email']  == NULL ||
         (filter_var($data['email'],FILTER_VALIDATE_EMAIL)) )) {
    throw new Exception('<div class="error">The email ' . $data['email'] .
                        ' is not valid.</div>');
  }

  if( isset( $data['phone_area_code']) ||
      isset( $data['phone_prefix']) ||
      isset( $data['phone_last_four']) ) {
    # Phone numbers may only contain numbers
    if( ! ( is_numeric($data['phone_area_code']) &&
            is_numeric($data['phone_prefix']) &&
            is_numeric($data['phone_last_four']) ) ) {
      throw new Exception('<div class="error">Phone numbers must be ' .
                          'numbers only.</div>');
    }

    # Phone numbers must be 10 digits
    if( ! ( strlen($data['phone_area_code']) == 3 && 
            strlen($data['phone_prefix']) == 3 &&
            strlen($data['phone_last_four']) == 4 ) ) {
      throw new Exception('<div class="error"> If you enter a phone number ' .
                          'it must be the area code and 7 digits.</div>');
    }
  }
}

function insert_user($conn, $data) {
  // prepare sql and bind parameters
  $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, username,
                            phone, email)
                          VALUES (:first_name, :last_name, :username,
                            :phone, :email);");

  $stmt->bindParam(':first_name', $data['first_name']);
  $stmt->bindParam(':last_name',  $data['last_name']);
  $stmt->bindParam(':username',   $data['username']);
  $stmt->bindParam(':email',      $data['email']);
  $stmt->bindParam(':phone',      $data['phone']);
  $stmt->execute();
}


# Lookup a user
# Display the punch in form,
# and a button to lookup the user again if the incorrect user is displayed.
function lookup($conn, $data, $request) {
  // prepare sql and bind parameters
  $stmt = $conn->prepare("SELECT username, first_name, last_name
                          FROM users
                          WHERE username = :username;");
  $stmt->bindParam(':username', $data);
  $stmt->execute();
  $result = $stmt->fetch(PDO::FETCH_ASSOC);
  $username="";

  if( isset($result['username']) ) {
    $jobs = get_jobs($conn);
    echo '<div class="user_container">';
    if( isset($result['username']) ) {
      $username=$result['username'];
      echo '<div class="userinfo">Username: ' . $username . '</div>';
    }
    if( isset($result['first_name']) ) {
      $first_name=$result['first_name'];
      echo '<div class="userinfo">First Name: ' . $first_name . '</div>';
    }
    if( isset($result['last_name']) ) {
      $last_name=$result['last_name'];
      echo '<div class="userinfo">Last Name: ' . $last_name . '</div>';
    } ?>
    </div>
    <?php $punched_in = punch_check_status($conn, $username, 1, FALSE,
                                           $request);
     ?>
    <div class="punchin">If this is you
            <form method="post" action="<?php
                  echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
              <?php if ($punched_in == "Clocked out") { ?>
              Select a job: 
              <select name="job" autofocus="autofocus">
                <option disabled selected value>Select an Option </option>
                <?php echo $jobs; ?>
              </select> <?php } ?>
              <input type="hidden" value="<?php echo $username; ?>"
                     name="punchname">
              <?php if($punched_in == "Clocked out") { ?>
                <input type="submit" value="Punch IN" name="punch">
              <?php } else { ?>
                <input type="hidden" value="1" name="job">
                <input type="submit" value="Punch OUT" name="punch">
                <input type="hidden" value="<?php echo $request;?>"
                       name="request">
              <?php } ?>
            </form>
          </div>
          <?php display_lookup_form();
  } else {
    throw new Exception(display_checkin_form() .
          '<br><div class="error">Could not find user: ' . $data . '</div>');
  }
}

# The logic for checking statusor punching in are almost the same.
# If $punch is TRUE, punchin.  If not just return user's status.
function punch_check_status( $conn, $userid, $job, $punch, $request ) {
  $_POST['request'] = $request;
  $stmt = $conn->prepare(
    "SELECT t.id AS t_id, u.username AS username, u.first_name AS first,
       j.name AS job, t.time_in AS time_in, t.time_out AS time_out,
       u.id AS u_id
     FROM users u, jobs j, timecards t
     WHERE u.id = t.user_id
     AND j.id = t.job_id
     AND u.id = :userid
     ORDER BY t.id DESC LIMIT 1;");
  $stmt->bindParam(':userid', $userid);
  $stmt->execute();
  $result = $stmt->fetch(PDO::FETCH_ASSOC);

  # If the user has never clocked in
  if(! isset($result['t_id']) ){
    if($punch) {
      $stmt = $conn->prepare(
        "INSERT INTO timecards( time_in, job_id, user_id )
         VALUES ( current_timestamp(), :job_id, :user_id )");
      $stmt->bindParam(':user_id', $userid);
      $stmt->bindParam(':job_id', $job);
      $stmt->execute();
    }
    return("New User!");
  # If both time_in and time_out are set on last entry, the user is
  # logged out and a new punch card entry needs to be created.
  } elseif ( isset($result['time_in']) && isset($result['time_out'])) {
    if($punch) {
      $stmt = $conn->prepare(
        "INSERT INTO timecards( time_in, user_id, job_id )
         VALUES ( current_timestamp(), :user_id, :job_id)");
      $stmt->bindParam(':user_id', $userid);
      $stmt->bindParam(':job_id', $job);
      $stmt->execute();
    }
    if(! $punch ) {
      return("Clocked out");
    } else { return("Clocked in"); }
  # If only time_in is set, punch_out
  } else {
    if($punch){
      $stmt = $conn->prepare(
        "UPDATE timecards SET time_out = current_timestamp()
         WHERE user_id = :user_id
         AND id = :t_id");
      $stmt->bindParam(':user_id', $result['u_id']);
      $stmt->bindParam(':t_id', $result['t_id']);
      $stmt->execute();
    }
    if(! $punch ) {
      return("Clocked in");
    } else { return ("Clocked out"); }
  }
}

function display_checkin_form() { ?>
    <form method="post"
          action="/user.php">
      Username: <input type="text" name="username" tabindex="1"
                       autofocus="autofocus">
    </form>
<?php } 
function get_jobs($conn) {
  $jobs="";
  $stmt = $conn->query( "SELECT id, name, description 
                         FROM jobs
                         WHERE active IS TRUE ORDER BY name;");
  while ( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
    $jobs = $jobs . '<option value="' . $row['id'] . '">' . 
            $row['name'] ."</option>";
  }
  return $jobs;
}

function display_lookup_form($request) { ?>
  <h1>User Lookup:</h1>
  <div class="form_container">
    <form method="post"
          action="<?php htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
      <div class="labels">User Name:</div>
      <div class="inputs"><input type="text" name="username" tabindex="1" autofocus="autofocus">*</div><br>
      <div class="labels">First Name:</div>
      <div class="inputs"><input type="text" name="first_name" tabindex="2">*</div><br>
      <div class="labels">Last Name:</div>
      <div class="inputs"><input type="text" name="last_name" tabindex="3"></div><br>
      <div class="labels">E-mail:</div>
      <div class="inputs"><input type="text" name="email" tabindex="4"></div><br>
      <div class="labels">Phone:</div>
      <div class="inputs"><input type="text" name="phone_area_code" maxlength="3" tabindex="5">
                          <input type="text" name="phone_prefix" maxlength="3" tabindex="6">
                          <input type="text" name="phone_last_four" maxlength="4" tabindex="7"></div><br>
      <div class="inputs"><input type="submit" tabindex="8"></div>
      <input type="hidden" name="lookup" value="TRUE">
      <input type="hidden" name="request" value="<?php echo $request; ?>">
    </form>
  </div>

<?php
}

function lookup_user($conn, $user_data, $request) {
  $_POST['request'] = $request;
  $param_count = 0;
  $query = "SELECT id, username, first_name, last_name, email, phone
            FROM users
            WHERE ";

  $user_data['username']   = test_input($user_data['username']);
  $user_data['first_name'] = test_input($user_data['first_name']);
  $user_data['last_name']  = test_input($user_data['last_name']);
  $user_data['email']      = test_input($user_data['email']);
  $phone = $user_data['phone_area_code'] . 
           $user_data['phone_prefix'] . 
           $user_data['phone_last_four'];
  $user_data['phone']      = test_input($phone);

  if( isset($user_data['username']) ) {
    $param_count++;
    $query = $query . 'username like :username';
  }

  if( isset($user_data['first_name']) ) {
    if($param_count > 0){
      $query = $query . " OR ";
    }
    $param_count++;
    $query = $query . 'first_name like :first_name';
  }

  if( isset($user_data['last_name']) ) {
    if($param_count > 0){
      $query = $query . " OR ";
    }
    $param_count++;
    $query = $query . 'last_name like :last_name';
  }

  if( isset($user_data['email']) ) {
    if($param_count > 0){
      $query = $query . " OR ";
    }
    $param_count++;
    $query = $query . 'email like :email';
  }

  if( isset($user_data['phone']) ) {
    if($param_count > 0){
      $query = $query . " OR ";
    }
    $param_count++;
    $query = $query . 'phone = :phone';
  }
  $query = $query . ";";

  $stmt = $conn->prepare($query);

  if(isset($user_data['username']) ) {
    $user_data['username'] = '%' . $user_data['username'] . '%';
    $stmt->bindParam(':username', $user_data['username']);
  }

  if(isset($user_data['first_name']) ) {
    $user_data['first_name'] = '%' . $user_data['first_name'] . '%';
    $stmt->bindParam(':first_name', $user_data['first_name']);
  }

  if(isset($user_data['last_name']) ) {
    $user_data['last_name'] = '%' . $user_data['last_name'] . '%';
    $stmt->bindParam(':last_name', $user_data['last_name']);
  }

  if(isset($user_data['email']) ) {
    $stmt->bindParam(':email', $user_data['email']);
  }

  if(isset($user_data['phone']) ) {
    $stmt->bindParam(':phone', $user_data['phone']);
  }
  $stmt->execute();
#  $stmt->debugDumpParams(); #VERY HANDY TO SEE AND DEBUG SQL

  $results=NULL;
  $count=0;
  while ( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
    $results[$count]['id']         = $row['id'];
    $results[$count]['username']   = $row['username'];
    $results[$count]['first_name'] = $row['first_name'];
    $results[$count]['last_name']  = $row['last_name'];

    if(! ( isset($row['email']) &&
        $row['email'] == $user_data['email'] ) ) {
      $results[$count]['email'] = NULL;
    } else {
      $results[$count]['email'] = $row['email'];
    }

    if(! ( isset($row['phone']) &&
        $row['phone'] == $user_data['phone'] ) ) {
      $results[$count]['phone'] = NULL;
    } else {
      $results[$count]['phone'] = $row['phone'];
    }
   $count++;
  }
  return $results;
}

function display_user_selection($conn, $users, $request) {
  if($request=="checkin") {
    echo "Click the Punch In / Punch Out button next to your name, " .
         "if your name is displayed.";
  } elseif($request=="hours") {
    echo "Click the Check Hours button next to your name, " .
         "if your name is displayed.";
  }
  echo '<form method="post" ' .
       'action="' . htmlspecialchars($_SERVER["PHP_SELF"]) . '">';
  if($request == "checkin") {
    echo '<input type="hidden" name="punched" value="true">';
  } elseif($request == "hours") {
    echo '<input type="hidden" name="hours" value="true">';
  }
  echo '<input type="hidden" name="request" value="' . $request . '">';
  echo '<table>';
  foreach($users as &$user) {
    $user_status = punch_check_status($conn, $user['id'], NULL, FALSE,
                                      $request);
    echo "<tr>";
      echo "<td>" . $user['username']  . "</td>";
      echo "<td>" . $user['first_name']  . "</td>";
      echo "<td>" . $user['last_name']  . "</td>";
      echo "<td>" . $user['email']  . "</td>";
      echo "<td>" . $user['phone']  . "</td>";
      if($request == "checkin"){
        if( $user_status == "New User!" ||
            $user_status == "Clocked out" ) {
          echo '<td><select name="job_' . $user['id'] . '">' . get_jobs($conn)  .
               '</select></td><td>
                    <button type="submit" value="' . $user['id']  . 
                      '" name="id">Punch IN</button>
                </td>';
        } else {
          echo '<td>&nbsp;</td><td>
                  <button type="submit" value="' . $user['id'] .
                    '" name="id">Punch OUT</button>
                </td>';
        }
      } elseif($request == "hours") {
        echo '<td><button type="submit" value="' . $user['id'] . 
                    '" name="id">Check Hours</button></td>';
      }
    echo '</tr>';
  }
  echo '</table></form>';
}

function get_hours($conn, $userid, $request){
$check_range = array("1000 YEAR", "1 DAY", "1 WEEK", "1 MONTH", "1 YEAR");
$results = array();
  foreach($check_range as &$range) {
  $number = (int) explode(" ",$range)[0];
  $range=(string)$range;
  #$number = $number[0];
  $term= (string)explode(" ",$range)[1];
  #$term= $number[1];
  #echo "<h1>Number/Term: $number, $term</h1>";
  #echo "strlength range: " . strlen($range);
  $stmt = $conn->prepare("SELECT first_name FROM users
                           WHERE id = :userid");
  $stmt->bindParam(':userid', $userid);
  $stmt->execute();
  $first = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare("SELECT j.name as Name,
      sum( TIMESTAMPDIFF(SECOND, time_in, time_out)) AS Seconds
      FROM timecards t, jobs j
      WHERE t.user_id = :userid
      AND t.job_id = j.id
      AND time_in > CAST( DATE_SUB(CURDATE()," .
        #"INTERVAL :range )
        "INTERVAL :number " . $term . "
        ) AS DATETIME )
      GROUP BY t.job_id");

    $count=0;
    $stmt->bindParam(':userid', $userid);
    $stmt->bindParam(':number', $number, PDO::PARAM_INT);
    #$stmt->bindParam(':term', $term);
    #$stmt->bindParam(":range", $range, PDO::PARAM_STR, strlen($range));
    #echo "<h1>userid: " . $userid . "</h1>";
    #echo "<h1>range: "  . $range  . "</h1>";
    try{
      $stmt->execute();
    } catch (Exception $e){
      $stmt->debugDumpParams(); echo "<br><br>";
    }
    echo "<table style='border-style:solid;border-color:black;border-width:2px'><caption>" . $first['first_name'] . "'s Hours</caption>";
    echo "<tr><th>Span</th><th>Job</th><th>Hours</th></tr>";
    $total=0;
    while ( $results[$count] = $stmt->fetch(PDO::FETCH_ASSOC) ) {
      #echo "<h1>SECONDS!". $results[$count]['Name'] . " " .
      #                     $results[$count]['Seconds'] . "</h1>";
      echo "<tr><td>";
      if($count == 0){
        echo $range;
      }
      echo "</td><td>" . $results[$count]['Name'] . "</td>" .
               "<td>";
      if( $results[$count]['Seconds'] && $results[$count]['Seconds'] > 0 ) {
         echo display_time($results[$count]['Seconds']);
         $total += $results[$count]['Seconds'];
      } else { echo "0"; }
      echo "</td>" .
            "</tr>";
      $count++;
    }
    echo "<tr><td>Total</td><td colspan='2'>" . display_time($total) . "</td>";
    echo "</table>";
  }
}

function display_time($seconds){
  $interval = new DateInterval("PT{$seconds}S");
  $now = new DateTimeImmutable('now', new DateTimeZone('utc'));

  $difference = $now->diff($now->add($interval))->format('%a days, %h hours, %i minutes');
  #$difference = $now->diff($now->add($interval))->format('%a days, %h hours, %i minutes, %s seconds');
  return $difference;
}
/*
SELECT DATE_FORMAT( DATE('1970-12-31 23:59:59') +
INTERVAL sum( TIMESTAMPDIFF(SECOND, time_in, time_out)) SECOND,
'%y years %m months %j days %Hh:%im:%ss')
AS diff
FROM timecards WHERE user_id = 1 GROUP BY job_id
*/

/* THIS ONE I THINK
SELECT j.name as Name,
       sum( TIMESTAMPDIFF(SECOND, time_in, time_out)) AS Seconds
FROM timecards t, jobs j
WHERE t.user_id = 1
AND t.job_id = j.id
AND time_in > CAST( DATE_SUB(CURDATE(),
                    INTERVAL 6 DAY)
              AS DATETIME )
GROUP BY t.job_id */
?>
