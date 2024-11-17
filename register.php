<?php
session_start();

if (isset($_SESSION["user_id"])) {
    header("Location: schedule_list.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT); 

    // データベース接続
    $conn = new mysqli("localhost", "root", "root", "schedule_app", 8889); 
    if ($conn->connect_error) {
        die("データベース接続に失敗しました: " . $conn->connect_error);
    }

    // ユーザー登録処理
    $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $stmt->bind_param("ss", $username, $password);

    if ($stmt->execute()) {
        header("Location: login.php");
        exit;
    } else {
        $error = "登録に失敗しました。もう一度お試しください。";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ユーザ新規登録</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <!-- ナビゲーションバー -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">スケジュールかんりくん</a>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="register.php">ユーザ新規登録</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">ログイン</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- メインコンテンツ -->
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <h2 class="mb-4">ユーザ新規登録</h2>
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <form action="register.php" method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">ユーザー名</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">パスワード</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary">登録</button>
                    <a href="index.php" class="btn btn-secondary">戻る</a>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
