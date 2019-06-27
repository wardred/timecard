<?php require"functions.php"; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>FreeGeek Timecard</title>
</head>
<body>
  <div class="head">
    <h1><a href="index.php">FreeGeek</a> - 
        <a href="hours.php">Check Hours</a>
    </h1>
  </div>
    <?php $users=display_lookup_form();
      if(isset($_POST['lookup'])) {
        #HAVE TO TAKE STUFF FROM CREATE, PUT IN FUNCTIONS,
        #THEN USE HERE
      }
     ?>
</body>
</html>
