<?php
require_once 'config.php';

// تدمير الجلسة
session_destroy();

// إعادة التوجيه للرئيسية
header('Location: index.php');
exit();
?>
