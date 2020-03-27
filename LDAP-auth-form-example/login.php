<?php
// initialize session
session_start();
include('include/header_logger.inc.php');
include('authenticate.php');

// check to see if user is logged out
if (isset($_GET['out'])) {
    // destroy session
    session_unset();
    $_SESSION = array();
    unset($_SESSION['user'], $_SESSION['access']);
    session_destroy();
}

// check if login form has been submitted
if (isset($_POST['userLogin'])) {
    // run information through authenticator
    if (authenticate($_POST['userLogin'], $_POST['userPassword'])) {
        // authentication passed
        header("Location: index.php");
        die();
    } else {
        // authentication failed
        $error = 1;
    }
}

// output error to user
if (isset($error)) {
    echo '<div id="login_form_info">Login failed: Incorrect user name, password, or rights</div>';
}

// output logout success
if (isset($_GET['out'])) {
    echo '<div id="login_form_info">Logout successful</div>';
}
?>

<div id="login_form">
    <form action="login.php" method="post">
        <div class="form-group">
            <label for="userLogin">User</label>
            <input type="text" class="form-control" name="userLogin" id="userLogin" aria-describedby="emailHelp" placeholder="Enter username"/>
        </div>
        <div class="form-group">
            <label for="userPassword">Password</label>
            <input type="password" class="form-control" id="userPassword" name="userPassword" placeholder="Password">
        </div>
        <button type="submit" class="btn btn-primary">Submit</button>
    </form>
</div>

<?php
include('include/footer.inc.php');
