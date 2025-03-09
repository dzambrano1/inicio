<?php
// Session configuration file
if (session_status() == PHP_SESSION_NONE) {
    ini_set('session.cookie_lifetime', 86400); // 1 day
    ini_set('session.gc_maxlifetime', 86400); // 1 day
    session_start();
}
?> 