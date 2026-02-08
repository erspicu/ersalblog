<?php
require_once 'auth.php';
albumLogout();
header('Location: login.php');
exit;
