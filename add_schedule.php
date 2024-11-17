<?php
session_start();

// ログイン確認
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $start_datetime = $_POST["start_datetime"];
    $end_datetime = $_POST["end_datetime"];
    $location = $_POST["location"];
    $description = $_POST["description"];
    $status = "未完了"; 
    $tag_id = $_POST["tag_id"]; 

    // データベース接続
    $conn = new mysqli("localhost", "root", "root", "schedule_app", 8889);
    if ($conn->connect_error) {
        die("データベース接続に失敗しました: " . $conn->connect_error);
    }

    // スケジュール登録
    $stmt = $conn->prepare("INSERT INTO schedules (user_id, start_datetime, end_datetime, location, description, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $_SESSION["user_id"], $start_datetime, $end_datetime, $location, $description, $status);

    if ($stmt->execute()) {
        // 登録したスケジュールのIDを取得
        $schedule_id = $stmt->insert_id;

        if ($tag_id) {
            $stmt_tag = $conn->prepare("INSERT INTO schedule_tags (schedule_id, tag_id) VALUES (?, ?)");
            $stmt_tag->bind_param("ii", $schedule_id, $tag_id);
            $stmt_tag->execute();
            $stmt_tag->close();
        }

        header("Location: schedule_list.php");
        exit;
    } else {
        $error = "スケジュールの登録に失敗しました。";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>新規作成</title>
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
            <a class="navbar-brand" href="schedule_list.php">スケジュールかんりくん</a>
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
        <h2 class="mb-4">新しいスケジュールを追加</h2>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form action="add_schedule.php" method="POST">
            <div class="mb-3">
                <label for="start_datetime" class="form-label">開始日時</label>
                <input type="datetime-local" class="form-control" id="start_datetime" name="start_datetime" 
                       required onchange="setEndDateMin()">
            </div>
            <div class="mb-3">
                <label for="end_datetime" class="form-label">終了日時</label>
                <input type="datetime-local" class="form-control" id="end_datetime" name="end_datetime" required>
            </div>
            <div class="mb-3">
                <label for="location" class="form-label">場所</label>
                <input type="text" class="form-control" id="location" name="location" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">内容</label>
                <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
            </div>
            <!-- タグ選択 -->
			<div class="mb-3">
				<label for="tag_id" class="form-label">タグ</label>
				<select class="form-control" id="tag_id" name="tag_id">
					<option value="">タグなし</option>
					<?php
					// タグをデータベースから取得
					$conn = new mysqli("localhost", "root", "root", "schedule_app", 8889);
					if ($conn->connect_error) {
						die("データベース接続に失敗しました: " . $conn->connect_error);
					}
					$result = $conn->query("SELECT * FROM tags");
					if ($result) {
						while ($row = $result->fetch_assoc()) {
							var_dump($row); // デバッグ: 各タグデータが表示されるか確認
							if (!empty(trim($row['name']))) {
								echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['name']) . '</option>';
							}
						}
					} else {
						die("タグの取得に失敗しました: " . $conn->error);
					}
					?>
				</select>
			</div>
            <button type="submit" class="btn btn-success">スケジュールを追加</button>
            <a href="schedule_list.php" class="btn btn-secondary">スケジュール一覧に戻る</a>
        </form>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
