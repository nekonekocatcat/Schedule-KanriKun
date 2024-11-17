<?php
session_start();

// ログイン確認
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// データベース接続
$conn = new mysqli("localhost", "root", "root", "schedule_app", 8889);
if ($conn->connect_error) {
    die("データベース接続に失敗しました: " . $conn->connect_error);
}

$error = "";
$success = "";

// タグがPOSTで送信された場合
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tag_name = $_POST["tag_name"];

    if (!empty($tag_name)) {
        // タグの重複確認
        $stmt = $conn->prepare("SELECT id FROM tags WHERE name = ?");
        $stmt->bind_param("s", $tag_name);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 0) {
            // タグの追加
            $stmt = $conn->prepare("INSERT INTO tags (name) VALUES (?)");
            $stmt->bind_param("s", $tag_name);

            if ($stmt->execute()) {
                $success = "タグが正常に追加されました。";
            } else {
                $error = "タグの追加に失敗しました。";
            }
        } else {
            $error = "同じ名前のタグが既に存在します。";
        }

        $stmt->close();
    } else {
        $error = "タグ名を入力してください。";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>新規タグ作成</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>新しいタグを追加</h2>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <form action="add_tag.php" method="POST">
            <div class="mb-3">
                <label for="tag_name" class="form-label">タグ名</label>
                <input type="text" class="form-control" id="tag_name" name="tag_name" required>
            </div>
            <button type="submit" class="btn btn-primary">タグを追加</button>
            <a href="schedule_list.php" class="btn btn-secondary">スケジュール一覧に戻る</a>
        </form>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
