<?php
session_start();

if (isset($_SESSION["user_id"])) {
    header("Location: schedule_list.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>スケジュールかんりくん</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <!-- ナビゲーションバー -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">スケジュールかんりくん</a>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    
                </ul>
            </div>
        </div>
    </nav>

    <!-- メインコンテンツ -->
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 text-center">
                <h2 class="mb-4">スケジュールかんりくんへようこそ！</h2>
                <a href="register.php" class="btn btn-primary btn-lg me-2">ユーザ新規登録</a>
                <a href="login.php" class="btn btn-secondary btn-lg">ログイン</a>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
