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
    throw new Exception('<div class="error">The username '. $data['username'] .
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

  # Get the user inserted
  $id = $conn->lastInsertID();

  # Get the Trainee ID from roles
  $stmt = $conn->query("SELECT id FROM roles WHERE name = 'Trainee'");
  $result = $stmt->fetch(PDO::FETCH_ASSOC);
  $role_id = $result['id'];

  # Insert a default role for the new user
  $stmt = $conn->prepare("INSERT INTO role_to_user(role_id, user_id, main)
                          VALUES (:role, :user, TRUE)");
  $stmt->bindParam(':role', $role_id);
  $stmt->bindParam(':user', $id);
  $stmt->execute();
}

# The logic for checking status or punching in are almost the same.
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
      # Get the current primary role for the user
      $stmt = $conn->prepare("SELECT role_id FROM role_to_user
                              WHERE user_id = :userid
                              AND main is TRUE");
      $stmt->bindParam(':userid', $userid);
      $stmt->execute();
      $result = $stmt->fetch(PDO::FETCH_ASSOC);
      $role_id = $result['role_id'];

      $stmt = $conn->prepare(
        "INSERT INTO timecards( time_in, job_id, user_id, role_id )
         VALUES ( current_timestamp(), :job_id, :user_id, :role_id )");
      $stmt->bindParam(':user_id', $userid);
      $stmt->bindParam(':job_id', $job);
      $stmt->bindParam(':role_id', $role_id);
      $stmt->execute();
    }
    return("New User!");
  # If both time_in and time_out are set on last entry, the user is
  # logged out and a new punch card entry needs to be created.
  } elseif ( isset($result['time_in']) && isset($result['time_out'])) {
    if($punch) {
      # Get the current primary role for the user
      $stmt = $conn->prepare("SELECT role_id FROM role_to_user
                              WHERE user_id = :userid
                              AND main is TRUE");
      $stmt->bindParam(':userid', $userid);
      $stmt->execute();
      $result = $stmt->fetch(PDO::FETCH_ASSOC);
      $role_id = $result['role_id'];

      # Insert the time punch
      $stmt = $conn->prepare(
        "INSERT INTO timecards( time_in, user_id, job_id, role_id )
         VALUES ( current_timestamp(), :user_id, :job_id, :role_id )");
      $stmt->bindParam(':user_id', $userid);
      $stmt->bindParam(':job_id', $job);
      $stmt->bindParam(':role_id', $role_id);
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
          action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
      <div class="labels">User Name:</div>
      <div class="inputs"><input type="text" name="username" tabindex="1" autofocus="autofocus">*</div><br>
      <div class="labels">First Name:</div>
      <div class="inputs"><input type="text" name="first_name" tabindex="2">*</div><br>
      <div class="labels">Last Name:</div>
      <div class="inputs"><input type="text" name="last_name" tabindex="3"></div><br>
      <div class="labels">E-mail:</div>
      <div class="inputs"><input type="text" name="email" tabindex="4"></div><br>
      <div class="labels">Phone:</div>
      <div class="inputs">(<input type="text" name="phone_area_code"
           maxlength="3" tabindex="5" size="3">)
                          <input type="text" name="phone_prefix" maxlength="3"
           tabindex="6" size="3"> - 
                          <input type="text" name="phone_last_four"
           maxlength="4" tabindex="7" size="4"></div><br>
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
  $query = "SELECT id, username, first_name, last_name, email, phone,
            active
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

    # Display e-mail if management or user input matches database
    if( $request == 'management' ||
      ( isset($row['email']) &&
         $row['email'] == $user_data['email']) ) {
      $results[$count]['email'] = $row['email'];
    } else {
      $results[$count]['email'] = NULL;
    }

    # Display phone if management or user input matches database
    if( $request == 'management' ||
      ( isset($row['phone']) &&
        $row['phone'] == $user_data['phone']) ) {
      $results[$count]['phone'] = $row['phone'];
    } else {
      $results[$count]['phone'] = NULL;
    }
    if($row['active'] == '1'){
      $results[$count]['active'] = 'true';
    } else { $results[$count]['active'] = 'false';}
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

function display_management_user_selection($conn, $users, $request) { ?>
  <form method="post"
        action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']);?>">
  <input type="hidden" name="users" value="true">
  <h2>Choose User to Edit:</h2>
  <table>
  <?php
  foreach($users as &$user){
    echo "<tr>";
      //echo '<input type="hidden" name="id" value="' . $user['id'] . '"></td>';
      echo '<td>' . $user['username'] . '</td>';
      echo '<td>' . $user['first_name'] . '</td>';
      echo '<td>' . $user['last_name'] . '</td>';
      if($user['phone']){
        echo '<td>(' . substr($user['phone'],0,3) . ') ' .
                       substr($user['phone'],3,3) . '-' .
                       substr($user['phone'],6,4) . '</td>';
      } else { echo '<td></td>'; }
      echo '<td>' . $user['email'] . '</td>';
      if($user['active'] == 'true'){ echo '<td>Active</td>';
      } else { echo "<td>Inactive</td>"; }
      echo '<td><button type="submit" name="edit" value="' . $user['id'] .
        '">Edit</button></td>';
      echo '<td><button type="submit" name="hours" value="' . $user['id'] .
        '">Lookup / Change Hours</button></td>';
      $user_status = punch_check_status($conn, $user['id'], NULL, FALSE,
        $request);
      if( $user_status == "New User!" || $user_status == "Clocked out" ) {
        echo '<td><select name="job_' . $user['id'] . '">' . get_jobs($conn)  .
               '</select></td>
              <td><button type="submit" name="logout" value="' . $user['id'] .
             '">Login</button></td>';
      } else {
        echo '<td>&nbsp;</td>
              <td><button type="submit" name="logout" value="' . $user['id'] .
             '">Logout</button></td>';
      }
    echo "</tr>";
  }
  echo "</table></form>";
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
        "INTERVAL :number " . $term . "
        ) AS DATETIME )
      GROUP BY t.job_id");

    $count=0;
    $stmt->bindParam(':userid', $userid);
    $stmt->bindParam(':number', $number, PDO::PARAM_INT);
    try{
      $stmt->execute();
    } catch (Exception $e){
      $stmt->debugDumpParams(); echo "<br><br>";
    }
    echo "<table style='border-style:solid;border-color:black;border-width:2px'><caption>" . $first['first_name'] . "'s Hours</caption>";
    echo "<tr><th>Time Span</th><th>Job</th><th>Hours</th></tr>";
    $total=0;
    while ( $results[$count] = $stmt->fetch(PDO::FETCH_ASSOC) ) {
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

    # If there weren't any hours in the given $range
    if($total == 0) {
      echo "<tr><td>$range</td>
                <td colspan='2'>No data found in given time span.</td></tr>";
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

function edit($conn, $userid, $reedit){
  $stmt = $conn->prepare("SELECT username, first_name, last_name,
                                 phone, email, active, id
                          FROM users where id = :userid");
  $stmt->bindParam(':userid',$userid);
  $stmt->execute();
  $results=$stmt->fetch(PDO::FETCH_ASSOC); ?>

<?php if(! $reedit) {
  echo '<div class="warn">The user edit form was submitted,
         but nothing was changed.</div>';
}?>
<h1>Edit User:</h1>
<form method="post" action="<?php
  echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">

  <!-- Get the original values.  When submitting form
       compare original values to edited values and only
       update those that are different.  -->
  <input type="hidden" name="orig_userid"
         value="<?php echo $results['id'];?>">
  <input type="hidden" name="orig_username"
         value="<?php echo $results['username'];?>">
  <input type="hidden" name="orig_first_name"
         value="<?php echo $results['first_name'];?>">
  <input type="hidden" name="orig_last_name"
         value="<?php echo $results['last_name'];?>">
  <input type="hidden" name="orig_phone"
         value="<?php echo $results['phone'];?>">
  <input type="hidden" name="orig_email"
         value="<?php echo $results['email'];?>">
  <input type="hidden" name="orig_active"
         value="<?php if($results['active']){ echo 'true'; }
                      else{echo 'false';}?>">
  <input type="hidden" name="userid" value="<?php echo $userid;?>">

  <!-- The form with user data filled out. -->
  <div class="input_wrapper">
    <div class="label">Username: </div>
      <div class="input"><input type="text" name="username"
           value="<?php echo $results['username'];?>"></div>
  </div>
  <div class="input_wrapper">
    <div class="label">First Name: </div>
      <input type="text" name="first_name"
             value="<?php echo $results['first_name'];?>">
  </div>
  <div class="input_wrapper">
    <div class="label">Last Name: </div>
      <input type="text" name="last_name"
             value="<?php echo $results['last_name'];?>">
  </div>
  <div class="input_wrapper">
    <div class="label">Password: </div>
    <input type="text" name="password"
           value="">
  </div>
  <div class="input_wrapper">
    <div class="label">Phone: </div>
    <input type="text" name="phone"
           value="<?php echo $results['phone'];?>">
  </div>
  <div class="input_wrapper">
    <div class="label">E-mail: </div>
      <input type="text" name="email"
           value="<?php echo $results['email'];?>">
  </div>
  <div class="input_wrapper">
    <div class="label">active: </div>
      <input type="checkbox" name="active" value="active" <?php
      if($results['active'] == TRUE) {
        echo "checked ";} ?>>
  </div>
  <input type="hidden" name="edit_submitted" value="true">
  <input type="submit">
</form>
<?php
}

function update_user($conn, $post) {
  // Build array of values to update
  global $_POST;
  $updates=NULL;
  if( $post['username'] != $post['orig_username'] ) {
    $updates['username'] = $post['username'];
  }
  if( $post['first_name'] != $post['orig_first_name'] ) {
    $updates['first_name'] = $post['first_name'];
  }
  if( $post['last_name'] != $post['orig_last_name'] ) {
    $updates['last_name'] = $post['last_name'];
  }
  if( $post['password'] ) {
    $updates['password'] = password_hash($post['password'], PASSWORD_DEFAULT);
  }
  if( $post['phone'] != $post['orig_phone'] ) {
    $updates['phone'] = $post['phone'];
  }
  if( $post['email'] != $post['orig_email'] ) {
    $updates['email'] = $post['email'];
  }
  if( (isset($post['active']) && $post['orig_active'] == 'false') ||
      ((! isset($post['active']) && $post['orig_active'] == 'true') )){
    if(isset($post['active'])){
      $updates['active'] = 'TRUE';
    } else {
      $updates['active'] = 'FALSE';
    }
  }

  // If nothing was changed, redisplay the edit form
  if(! $updates) {
    edit($conn, $post['orig_userid'], false);
    return "resubmit";
  }

  // Build the prepared statement SQL
  $sql = "UPDATE users SET ";
  $count=sizeof($updates);
  foreach( array_keys($updates) as $key){
    $sql .= $key . " = :$key";
    $count--;

    // If we aren't at the last parameter, the SQL needs a comma.
    if($count > 0){
      $sql .= ", ";
    }
  }
  $sql .= " WHERE id = :id";
  $stmt = $conn->prepare($sql);

  // The SQL is generated, now need to bind the parameters
  foreach( array_keys($updates) as $key){
    // Handle MySQL boolean
    if($key == "active"){
      if( $updates[$key] == "TRUE" ){
        $updates[$key] = 1;
      } else {
        $updates[$key] = 0;
      }
    }
    $stmt->bindParam(':' . $key, $updates[$key]);
  }
  $stmt->bindParam(":id", $post['orig_userid']);

  $_POST['edit_submitted'] = true;

  return ($stmt->execute());
}

function logged_in_users($conn) {
  $query="SELECT u.id AS id, t.time_in as time_in, u.username AS username,
                 u.first_name AS first_name, u.last_name AS last_name
          FROM users u, timecards t
          WHERE u.id = t.user_id
          AND time_out IS NULL";
  $count=0;
?>
  <div class="logged_in_users">
    <form method="post" action="management.php">
    <table>
<?php
  $stmt = $conn->query($query);
  while($results=$stmt->fetch(PDO::FETCH_ASSOC)){
    if($count == 0){?>
      <caption>Logged In Users</caption>
      <tr>
        <th>Time In</th><th>Username</th><th>First Name</th>
        <th>Last Name</th><th>Logout</th>
      </tr>
     <?php }
    echo "<tr>
            <td><input type=\"hidden\" name=\"username\"
                 value=\"{$results['username']}\">
                {$results['time_in']}</td>
            <td>{$results['username']}</td>
            <td>{$results['first_name']}</td>
            <td>{$results['last_name']}</td>
            <td>
              <button type=\"submit\"
                      name=\"logout_user\" value=\"{$results['id']}\">
                Logout</button>
            </td>
          </tr>";
    $count++;
  }
  if ($count === 0){
    echo '<tr><td>Nobody Logged In</td></tr></table></form></div>';
  } else { ?>
    </table></form>
  <div class="logout_all"><form method="post" action="management.php">
    <button type="submit" name="logout_all" value='true'>
     Log Everybody Out</button>
  </form></div>
  </div>
<?php }
}

function log_everybody_out($conn) {
  # Should be wrapped in a try/catch
  $conn->query("UPDATE timecards SET time_out = now()
                WHERE time_out IS NULL");
}

function edit_hours($conn, $userid, $num_punches){
  # Get the names of the jobs
  $query = "SELECT name, id FROM jobs";
  $stmt = $conn->query($query);
  $count=0;
  while($results = $stmt->fetch(PDO::FETCH_ASSOC)){
    $jobs[$count]['name'] = $results['name'];
    $jobs[$count]['id'] = $results['id'];
    $count++;
  }

  # Get the names of the roles 
  $query = "SELECT name, id FROM roles";
  $stmt = $conn->query($query);
  $count=0;
  while($results = $stmt->fetch(PDO::FETCH_ASSOC)){
    $roles[$count]['name'] = $results['name'];
    $roles[$count]['id'] = $results['id'];
    $count++;
  }

  # Get Individual Timecard Lines
  $query="SELECT u.username AS username, u.first_name AS first_name,
          u.last_name AS last_name,
          t.time_in AS time_in, t.time_out AS time_out, t.id AS t_id,
          j.name AS job, r.name AS role
          FROM timecards t, jobs j, roles r, users u
          WHERE t.user_id = :id
          AND j.id = t.job_id
          AND r.id = t.role_id
          AND u.id = t.user_id
          ORDER BY t.id DESC
          LIMIT :limit";
  $stmt = $conn->prepare($query);
  $stmt->bindParam(":id", $userid);
  $stmt->bindValue(":limit", intval($num_punches), PDO::PARAM_INT);
  $stmt->execute();

  $count=0;
  echo '<div class="form_container">
        <form method="post" action="management.php">';
  echo '<input type="hidden" name="edit_hours" value="true">';
  while($results[$count] = $stmt->fetch(PDO::FETCH_ASSOC)){
    # Only want to do this once
    if($count==0){
      echo "<h1>Editing values for 
        {$results[$count]['username']}
        {$results[$count]['first_name']}
        {$results[$count]['last_name']}
        </h1>";
      echo "<h2>You may only update 1 row at a time.</h2>";
      echo "<table>";
    }
    echo "<tr>";
      $t_id = $results[$count]['t_id'];
      echo "<td><input name=\"time_in_$t_id\"
            type=\"text\"" .
           " value=\"{$results[$count]['time_in']}\">";
      echo '<input type="hidden" name="time_in_orig_' . $t_id .
           '" value="' . $results[$count]['time_in'] . '"></td>';
      echo "<td><input name=\"time_out_$t_id\"
            type=\"text\"" .
           " value=\"{$results[$count]['time_out']}\">";
      echo '<input type="hidden" name="time_out_orig_' . $t_id .
           '" value="' . $results[$count]['time_out'] . '"></td>';
      echo "<td><select name=\"job_$t_id\">";
      $for_count=0;
      foreach($jobs as $job){
        echo "<option value=\"{$job['id']}\" label=\"{$job['name']}\"";
        if( $results[$count]['job'] == $job['name'] ) {
          echo ' selected="selected" ';
          $job_orig = $job['id'];
        } 
        echo ">{$job['name']}</option>";
        $for_count++;
      }
      echo "</select>";
      echo '<input type="hidden" name="job_orig_' . $t_id .
           '" value="' . $job_orig . '">';
      echo "</td><td>
            <select name=\"role_$t_id\">";
      $for_count=0;
      foreach($roles as $role){
        echo "<option value=\"{$role['id']}\" label=\"{$role['name']}\"";
        if( $results[$count]['role'] == $role['name'] ) {
          echo ' selected="selected" ';
          $role_orig = $role['id'];
        } 
        echo ">{$role['name']}</option>";
        $for_count++;
      }
      echo "</select>";
      echo '<input type="hidden" name="role_orig_' . $t_id .
           '" value="' . $role_orig . '">';
      echo '</td><td><button type="submit" value="' . $t_id .
           '" name="hours_submitted">Submit</button></td>';
    echo "</tr>";
    $count++;
  }
  echo "</table></form></div>";
}

function hours_submitted($conn, $submitted){
  $time_id = $submitted['hours_submitted'];
  if( $submitted["time_in_$time_id"] ==
        $submitted["time_in_orig_$time_id"] &&
      $submitted["time_out_$time_id"] ==
        $submitted["time_out_orig_$time_id"] &&
      $submitted["role_$time_id"] ==
        $submitted["role_orig_$time_id"] &&
      $submitted["job_$time_id"] ==
        $submitted["job_orig_$time_id"] ){
    echo '<div class="warn">There weren\'t any changes.</div>';
  } else {
    $stmt = $conn->prepare("UPDATE timecards
                            SET time_in = :time_in,
                            time_out = :time_out,
                            job_id = :job_id,
                            role_id = :role_id
                            WHERE id = :time_id");
    $stmt->bindParam(':time_in', $submitted["time_in_$time_id"]);
    $stmt->bindParam(':time_out', $submitted["time_out_$time_id"]);
    $stmt->bindValue(":role_id", intval($submitted["role_$time_id"]),
                     PDO::PARAM_INT );
    $stmt->bindValue(":job_id", intval($submitted["job_$time_id"]),
                     PDO::PARAM_INT );
    $stmt->bindValue(":time_id", intval($time_id), PDO::PARAM_INT);
    $stmt->execute();
    echo '<div class="info">Hours updated.</div>';
  }
}

function display_roles($conn){
  $query="SELECT id, name, description, active FROM roles";
  $stmt = $conn->prepare($query);
  $stmt->execute();
  echo '<div class="role_container">';
  echo '<form method="post"
         action="' . htmlspecialchars($_SERVER["PHP_SELF"]) . '">';
  echo "<table id='roles'>";
  echo '<caption>Roles</caption>';
  echo "<tr>
          <th>&nbsp;</th>
          <th>Name</th>
          <th>Description</th>
          <th>Active</th>
        </tr>";
  $count=0;
  while($result = $stmt->fetch(PDO::FETCH_ASSOC)){
    echo '<tr><td><input type="hidden" name="orig_id[' . $count . ']" value="' . $result['id']  .'">';
    echo '<input type="hidden" name="orig_name[' . $count . ']" value="' . $result['name']  .'">';
    echo '<input type="hidden" name="orig_description[' . $count . ']" value="' . $result['description']  .'">';
    echo '<input type="hidden" name="orig_active[' . $count . ']" value="' . $result['active']  .'">';
    echo '&nbsp;</td>
            <td><input name="new_id[' . $count . ']" type="hidden" value="' . $result['id'] . '">
                <input type="text" name="new_name[' . $count . ']" value="' . $result['name']  . '"></td>
            <td><input type="text" name="new_description[' . $count . ']" value="' . $result['description'] . '"></td>';
            if($result['active'] == 1){
              echo '<td><input type="checkbox" name="new_active[' . $count . ']" checked></td>';
            } else {
              echo '<td><input type="checkbox" name="new_active[' . $count . ']"></td>';
            }
          echo '</tr>';
    $count++;
  }
  echo '<tr>
          <td>Create New Role:</td>
          <td><input name="new_id[' . $count . ']" type="hidden" value="-1">
              <input type="text" name="new_name[' . $count . ']"></td>
          <td><input type="text" name="new_description[' . $count . ']"></td>
          <td><input type="checkbox" name="new_active[' . $count . ']" checked></td>
        </tr>';
  echo "</table><div class=\"submit\"><input type=\"submit\" name=\"modify_roles\"></div></form></div>";
}

function process_roles($conn){
  $count=0;
  $update="UPDATE roles SET name=:name, description=:description, active=:active WHERE id=:id";
  #echo '<td>' . var_dump($_POST) . '</td>';
  foreach($_POST['orig_id'] as $id){
    if( isset($_POST['new_active'][$count])){
      $_POST['new_active'][$count] = "1";
    } else {
      $_POST['new_active'][$count] = "0";
    }
    if( $_POST['orig_id'][$count] != $_POST['new_id'][$count] ||
        $_POST['orig_name'][$count] != $_POST['new_name'][$count] ||
        $_POST['orig_description'][$count] != $_POST['new_description'][$count] ||
        $_POST['orig_active'][$count] != $_POST['new_active'][$count] ){
        $stmt = $conn->prepare($update);
        $stmt->bindParam(':id', $_POST['new_id'][$count]);
        $stmt->bindParam(':name', $_POST['new_name'][$count]);
        $stmt->bindParam(':description', $_POST['new_description'][$count]);
        $stmt->bindParam(':active', $_POST['new_active'][$count]);

        if($stmt->execute()){
          echo '<br><td class="info">Role ' .
                $_POST['orig_name'][$count] . ' successfully updated.</td>';
        } else {
          echo '<td class="error">There was a problem updating the roles.  Please contact the system maintainers and let them know!.</td>';
        }
    }
    $count++;
  }

  if(isset($_POST['new_name'][$count]) && 
           $_POST['new_name'][$count] != ""){
    if(isset($_POST['new_description'][$count]) &&
             $_POST['new_description'][$count] != ""){
      $insert="INSERT INTO roles(name, description, active)
               VALUES(:name,:description,:active)";

      if( isset($_POST['new_active'][$count])){
        $_POST['new_active'][$count] = "1";
      } else {
        $_POST['new_active'][$count] = "0";
      }

      $stmt = $conn->prepare($insert);
      $stmt->bindParam(':name', $_POST['new_name'][$count]);
      $stmt->bindParam(':description', $_POST['new_description'][$count]);
      $stmt->bindParam(':active', $_POST['new_active'][$count]);
      if($stmt->execute()){
        echo '<br><td class="info">New role ' . 
             $_POST['new_name'][$count] . ' successfully created.</td>';
      } else {
        echo '<td class="error">There was a problem creating the role.  Please contact the system maintainers and let them know!.</td>';
      }
    } else {
      echo '<td class="warn">When creating a new role both name and description must be entered.</td>';
    }
  }
}

function display_jobs($conn){
  $query="SELECT id, name, description, active FROM jobs";
  $stmt = $conn->prepare($query);
  $stmt->execute();
  echo '<div class="role_container">';
  echo '<form method="post"
         action="' . htmlspecialchars($_SERVER["PHP_SELF"]) . '">';
  echo "<table id='jobs'>";
  echo '<caption>Jobs</caption>';
  echo "<tr>
          <th>&nbsp;</th>
          <th>Name</th>
          <th>Description</th>
          <th>Active</th>
        </tr>";
  $count=0;
  while($result = $stmt->fetch(PDO::FETCH_ASSOC)){
    echo '<tr><td><input type="hidden" name="orig_id[' . $count . ']" value="' . $result['id']  .'">';
    echo '<input type="hidden" name="orig_name[' . $count . ']" value="' . $result['name']  .'">';
    echo '<input type="hidden" name="orig_description[' . $count . ']" value="' . $result['description']  .'">';
    echo '<input type="hidden" name="orig_active[' . $count . ']" value="' . $result['active']  .'">';
    echo '&nbsp;</td>
            <td><input name="new_id[' . $count . ']" type="hidden" value="' . $result['id'] . '">
                <input type="text" name="new_name[' . $count . ']" value="' . $result['name']  . '"></td>
            <td><input type="text" name="new_description[' . $count . ']" value="' . $result['description'] . '"></td>';
            if($result['active'] == 1){
              echo '<td><input type="checkbox" name="new_active[' . $count . ']" checked></td>';
            } else {
              echo '<td><input type="checkbox" name="new_active[' . $count . ']"></td>';
            }
          echo '</tr>';
    $count++;
  }
  echo '<tr>
          <td>Create New Job:</td>
          <td><input name="new_id[' . $count . ']" type="hidden" value="-1">
              <input type="text" name="new_name[' . $count . ']"></td>
          <td><input type="text" name="new_description[' . $count . ']"></td>
          <td><input type="checkbox" name="new_active[' . $count . ']" checked></td>
        </tr>';
  echo "</table><div class=\"submit\"><input type=\"submit\" name=\"modify_jobs\"></div></form>";
}

function process_jobs($conn){
  $count=0;
  $update="UPDATE jobs SET name=:name, description=:description, active=:active WHERE id=:id";
  #echo '<td>' . var_dump($_POST) . '</td>';
  foreach($_POST['orig_id'] as $id){
    if( isset($_POST['new_active'][$count])){
      $_POST['new_active'][$count] = "1";
    } else {
      $_POST['new_active'][$count] = "0";
    } 
    if( $_POST['orig_id'][$count] != $_POST['new_id'][$count] ||
        $_POST['orig_name'][$count] != $_POST['new_name'][$count] ||
        $_POST['orig_description'][$count] != $_POST['new_description'][$count] ||      
        $_POST['orig_active'][$count] != $_POST['new_active'][$count] ){
        $stmt = $conn->prepare($update); 
        $stmt->bindParam(':id', $_POST['new_id'][$count]);
        $stmt->bindParam(':name', $_POST['new_name'][$count]);
        $stmt->bindParam(':description', $_POST['new_description'][$count]);
        $stmt->bindParam(':active', $_POST['new_active'][$count]);
        
        if($stmt->execute()){
          echo '<br><td class="info">Job ' .
                $_POST['orig_name'][$count] . ' successfully updated.</td>';
        } else {
          echo '<td class="error">There was a problem updating the jobs.  Please contact the system maintainers and let them know!.</td>';
        }
    }   
    $count++;
  } 
  
  if(isset($_POST['new_name'][$count]) &&
           $_POST['new_name'][$count] != ""){
    if(isset($_POST['new_description'][$count]) &&
             $_POST['new_description'][$count] != ""){
      $insert="INSERT INTO jobs(name, description, active)
               VALUES(:name,:description,:active)";

      if( isset($_POST['new_active'][$count])){
        $_POST['new_active'][$count] = "1";
      } else {
        $_POST['new_active'][$count] = "0";
      } 
      
      $stmt = $conn->prepare($insert);
      $stmt->bindParam(':name', $_POST['new_name'][$count]);
      $stmt->bindParam(':description', $_POST['new_description'][$count]);
      $stmt->bindParam(':active', $_POST['new_active'][$count]);
      if($stmt->execute()){
        echo '<br><td class="info">New job ' .
             $_POST['new_name'][$count] . ' successfully created.</td>';
      } else {
        echo '<td class="error">There was a problem creating the job.  Please contact the system maintainers and let them know!.</td>';
      }
    } else {
      echo '<td class="warn">When creating a new job both name and description must be entered.</td>';
    }
  } 
}

function display_reports(){
  echo '<td class="reports"><h1>Reports</h1>
          <form method="post"
          action="' . htmlspecialchars($_SERVER["PHP_SELF"]) . '">
            Enter integers: 1, 2, 10, etc.<br>
            Show anybody logged in for more than <input type="text" name="at_least_hours"> hours.
            <input type="hidden" name="contiguous_hours_report">
          </form>
        </td>';
}

function contiguous_hours_report($conn){
  $query="SELECT u.username, ";
  if( isset($_POST['at_least_hours']) && is_numeric($_POST['at_least_hours'])){
  } else {
    echo '<div class="warn">Hours must be entered, and only contain digits 0 - 9.</div>';
  }
}
?>
