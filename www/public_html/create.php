<!DOCTYPE html>
<?php require 'header.php';
      require 'functions.php';
?>
<html lang="en">
<head>
  <title>FreeGeek New User Form</title>
  <meta http-equiv="refresh"
        content="180; url=http://timecard.freegeektwincities.org/index.php">
</head>
<body>
  <div class="head"><h1><a href="index.php">FreeGeek</a> - 
                        <a href="create.php">New User Form</a></h1></div>
  <div>Not creating an account?
       <a href="index.php">Return to the Free Geek Timecard Homepage.</a>
  </div>
  <h2>User Input:</h2>
  <div class="form_container">
    <form method="post"
          action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
      <div class="labels-red">* - Required Fields</div><br>
      <div class="labels">User Name:</div>
      <div class="inputs"><input type="text" name="username" tabindex="1" autofocus="autofocus" required>*</div><br>
      <div class="labels">First Name:</div>
      <div class="inputs"><input type="text" name="first_name" tabindex="2" required>*</div><br>
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
    </form>
  </div>
<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $user_data['username']   = strtolower(test_input($_POST["username"]));
  $user_data['first_name'] = test_input($_POST["first_name"]);
  $user_data['last_name']  = test_input($_POST["last_name"]);
  $user_data['email']      = test_input($_POST["email"]);
  $user_data['phone_area_code']      = test_input($_POST["phone_area_code"]);
  $user_data['phone_prefix']         = test_input($_POST["phone_prefix"]);
  $user_data['phone_last_four']      = test_input($_POST["phone_last_four"]);

  try {
    validate_new_user($conn, $user_data);
  } catch (Exception $e) {
    echo $e->getMessage();
    exit();
  }

  # Concatenate the 3 phone nunber fields into 1 number.
  if( isset( $user_data['phone_area_code']) &&
      isset( $user_data['phone_prefix']) &&
      isset( $user_data['phone_last_four'] ) ){
    $user_data['phone'] = (int) $user_data['phone_area_code'] .
                                $user_data['phone_prefix'] .
                                $user_data['phone_last_four'];
  } else { $user_data['phone'] = NULL; }

  insert_user($conn, $user_data);
echo "Username: "   . $user_data['username']   . "<br>
      first_name: " . $user_data['first_name'] . "<br>
      last_name: "  . $user_data['last_name']  . "<br>
      email: "      . $user_data['email']      . "<br>
      phone: "      . $user_data['phone'];
} ?>
</body>
</html>
