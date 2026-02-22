<?php

$destination_url = "https://planetrbx.com/?ref=toinou_ross";
header("HTTP/1.1 301 Moved Permanently");
header("Location: " . $destination_url);
exit();
?>
