<!-- logout.php -->
<?php
session_start();
session_unset();  // セッションの全変数を解除
session_destroy();  // セッションを破棄

// ログイン画面にリダイレクト
header("Location: index.php");
exit;
?>
