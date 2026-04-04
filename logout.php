<?php
session_start();      // start session
session_unset();      // remove all session variables
session_destroy();    // destroy session

header("Location: index.php"); // redirect to login
exit();
?>
