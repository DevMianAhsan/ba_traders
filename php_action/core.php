<?php 

if (!ob_get_level()) {
    ob_start();
}
include_once "db_connect.php";
include_once "includes/functions.php";
include_once "includes/code.php";

$currentPage = basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: ($_SERVER['SCRIPT_NAME'] ?? ''));
$scriptName = basename($_SERVER['SCRIPT_NAME'] ?? '');
$getpage = (!empty($currentPage) && $currentPage !== 'chairman') ? $currentPage : $scriptName;

if (!isset($_SESSION['userId']) && $getpage !== "index.php" && $getpage !== "login.php" && $scriptName !== "index.php" && $scriptName !== "login.php") {
?>
	 <script>window.location.assign('index.php');
	  </script>
<?php
} else {
    @$fetchedUserData = fetchRecord($dbc, "users", "user_id", @$_SESSION['userId']);
    @$fetchedUserRole = $fetchedUserData['user_role'];
    @$checkurlvalidQ = mysqli_query($dbc, "SELECT * FROM privileges WHERE user_id ='" . ($_SESSION["userId"] ?? '') . "' AND nav_url='$getpage' ");
    if ($checkurlvalidQ && mysqli_num_rows($checkurlvalidQ) > 0) {
        $userPrivileges = mysqli_fetch_assoc($checkurlvalidQ);
    }
}
?>