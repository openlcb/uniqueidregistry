    <div class="navbar navbar-default navbar-fixed-top<?php if (defined('DEPLOYMENT') && DEPLOYMENT === 'test') echo ' test'; ?>">
      <div class="container-fluid">
        <div class="navbar-header">
          <a class="navbar-brand" href="."><img src="logo-ajs-1.svg" height="50" alt="OpenLCB"/><?php if (defined('DEPLOYMENT') && DEPLOYMENT === 'test') echo ' Test'; ?></a>
        </div>
        <div class="collapse navbar-collapse">
          <div class="navbar-left">
            <div class="btn-group">
              <a href="viewuid" class="btn btn-sm btn-default navbar-btn<?php if (basename($_SERVER['SCRIPT_NAME']) === 'viewuid.php') echo ' active'; ?>">Unique ID Ranges</a>
<?php
if ($user !== null && $user['person_is_moderator'] === 'y') {
?>
              <a href="viewuid?pending" class="btn btn-sm btn-default navbar-btn<?php if (basename($_SERVER['SCRIPT_NAME']) === 'viewuid.php' && isset($_GET['pending'])) echo ' active'; ?>">Pending Unique ID Requests</a>
<?php
}
?>
              <a href="requestuidrange" class="btn btn-sm btn-default navbar-btn<?php if (basename($_SERVER['SCRIPT_NAME']) === 'requestuidrange.php') echo ' active'; ?>">Request Unique ID Range</a>
<?php
if ($user !== null && $user['person_is_moderator'] === 'y') {
?>
              <a href="people" class="btn btn-sm btn-default navbar-btn<?php if (basename($_SERVER['SCRIPT_NAME']) === 'people.php') echo ' active'; ?>">People</a>
<?php
}
?>
            </div>
          </div>
          <div class="navbar-right">
<?php
if ($user !== null) {
?>
            <a href="profile" class="btn btn-sm navbar-btn<?php if (basename($_SERVER['SCRIPT_NAME']) === 'profile.php' && $person !== null && $user['person_id'] === $person['person_id']) echo ' active'; ?>"><span class="glyphicon glyphicon-user"></span> <?php echo htmlspecialchars(formatPersonName($user)); ?></a>
            <a href="logout" class="btn btn-sm btn-default navbar-btn"><span class="glyphicon glyphicon-log-out"></span> Logout</a>
<?php
} else {
?>
            <a href="register" class="btn btn-sm btn-default navbar-btn<?php if (basename($_SERVER['SCRIPT_NAME']) === 'register.php') echo ' active'; ?>"><span class="glyphicon glyphicon-edit"></span> Register</a>
            <a href="updatepassword" class="btn btn-sm btn-default navbar-btn<?php if (basename($_SERVER['SCRIPT_NAME']) === 'updatepassword.php') echo ' active'; ?>"><span class="glyphicon glyphicon-erase"></span> Update password</a>
            <a href="login" class="btn btn-sm btn-default navbar-btn<?php if (basename($_SERVER['SCRIPT_NAME']) === 'login.php') echo ' active'; ?>"><span class="glyphicon glyphicon-log-in"></span> Login</a>
<?php
}
?>
          </div>
        </div>
      </div>
    </div>
