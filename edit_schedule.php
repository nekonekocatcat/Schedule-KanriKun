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

// IDでスケジュールを取得
if (isset($_GET["id"])) {
    $id = $_GET["id"];
    $stmt = $conn->prepare("SELECT * FROM schedules WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $_SESSION["user_id"]);
    $stmt->execute();
    $result = $stmt->get_result();
    $schedule = $result->fetch_assoc();

    if (!$schedule) {
        die("スケジュールが見つかりません。");
    }

    // 現在のタグを取得
    $stmt = $conn->prepare("SELECT t.id, t.name FROM tags t 
                            LEFT JOIN schedule_tags st ON t.id = st.tag_id
                            WHERE st.schedule_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $tags_result = $stmt->get_result();
    $current_tags = [];
    while ($tag = $tags_result->fetch_assoc()) {
        $current_tags[] = $tag['id'];
    }
}

// POSTリクエストで編集内容が送信された場合
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $start_datetime = $_POST["start_datetime"];
    $end_datetime = $_POST["end_datetime"];
    $location = $_POST["location"];
    $description = $_POST["description"];
    // ステータスの更新は削除
    // $status = $_POST["status"]; // ステータスは送信しない
    $tags = isset($_POST["tags"]) ? $_POST["tags"] : []; // 選択されたタグ

    // スケジュールの更新
    $stmt = $conn->prepare("UPDATE schedules SET start_datetime = ?, end_datetime = ?, location = ?, description = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ssssii", $start_datetime, $end_datetime, $location, $description, $id, $_SESSION["user_id"]);

    if ($stmt->execute()) {
        // 既存のタグを削除
        $stmt = $conn->prepare("DELETE FROM schedule_tags WHERE schedule_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        // 新しいタグを追加
        foreach ($tags as $tag_id) {
            $stmt = $conn->prepare("INSERT INTO schedule_tags (schedule_id, tag_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $id, $tag_id);
            $stmt->execute();
        }

        header("Location: schedule_list.php");
        exit;
    } else {
        $error = "スケジュールの更新に失敗しました。";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>スケジュール編集</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
        function setEndDateMin() {
            // 開始日時フィールドから値を取得
            const startDate = document.getElementById("start_datetime").value;
            const endDateField = document.getElementById("end_datetime");

            // 終了日時の最小値を開始日時に設定
            endDateField.min = startDate;
        }
    </script>
</head>
<body>
    <!-- ナビゲーションバー -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="schedule_list.php">スケジュール管理</a>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="add_schedule.php">スケジュール追加</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="week_schedule.php">週間表示</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="month_schedule.php">月間表示</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">ログアウト</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- メインコンテンツ -->
    <div class="container">
        <h2 class="mb-4">スケジュール編集</h2>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form action="edit_schedule.php?id=<?= htmlspecialchars($id) ?>" method="POST">
    <div class="mb-3">
        <label for="start_datetime" class="form-label">開始日時</label>
        <input type="datetime-local" class="form-control" id="start_datetime" name="start_datetime" 
               value="<?= htmlspecialchars(date('Y-m-d\TH:i', strtotime($schedule['start_datetime']))) ?>" 
               required onchange="setEndDateMin()">
    </div>
    <div class="mb-3">
        <label for="end_datetime" class="form-label">終了日時</label>
        <input type="datetime-local" class="form-control" id="end_datetime" name="end_datetime" 
               value="<?= htmlspecialchars(date('Y-m-d\TH:i', strtotime($schedule['end_datetime']))) ?>" 
               required>
    </div>
    <div class="mb-3">
        <label for="location" class="form-label">場所</label>
        <input type="text" class="form-control" id="location" name="location" 
               value="<?= htmlspecialchars($schedule['location']) ?>" required>
    </div>
    <div class="mb-3">
        <label for="description" class="form-label">内容</label>
        <textarea class="form-control" id="description" name="description" rows="3" required><?= htmlspecialchars($schedule['description']) ?></textarea>
    </div>
    
    <!-- タグの入力 -->
    <div class="mb-3">
        <label for="tags" class="form-label">タグ</label>
        <select class="form-control" id="tags" name="tags[]" multiple>
            <?php
            // タグのリストを取得して表示
            $stmt = $conn->prepare("SELECT id, name FROM tags");
            $stmt->execute();
            $tags_result = $stmt->get_result();
            
            while ($tag = $tags_result->fetch_assoc()):
                // 現在選択されているタグをチェック
                $selected = in_array($tag['id'], $current_tags) ? 'selected' : '';
            ?>
                <option value="<?= htmlspecialchars($tag['id']) ?>" <?= $selected ?>><?= htmlspecialchars($tag['name']) ?></option>
            <?php endwhile; ?>
        </select>
    </div>

    <button type="submit" class="btn btn-primary">更新</button>
    <a href="schedule_list.php" class="btn btn-secondary">スケジュール一覧に戻る</a>
</form>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
