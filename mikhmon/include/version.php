<?php
if (!isset($_SESSION["mikhmon"])) {
    header("Location:../admin.php?id=login");
  } else {
        $_SESSION["v"] = "4.01 SMKN 1 Siantar Edition (2026)";
    
    }
