<?php
session_start();
error_reporting(0);
if (!isset($_SESSION["mikhmon"])) {
  header("Location:../admin.php?id=login");
} else {
  if ($removepprofile != "") {
    $API->comm("/ppp/profile/remove", array(
      ".id" => "$removepprofile",
    ));
  }
  echo "<script>window.location='./?ppp=profiles&session=" . $session . "'</script>";
}
?>