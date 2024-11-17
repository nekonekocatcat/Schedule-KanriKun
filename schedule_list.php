<?php
session_start();

// ユーザーがログインしていなければ、ログインページにリダイレクト
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// データベース接続
$conn = new mysqli("localhost", "root", "root", "schedule_app", 8889);
if ($conn->connect_error) {
    die("データベース接続に失敗しました: " . $conn->connect_error);
}

// 現在のユーザーIDを取得
$user_id = $_SESSION["user_id"];

// ユーザーのスケジュールと関連するタグを取得
$sql = "
    SELECT schedules.id, schedules.start_datetime, schedules.end_datetime, schedules.location, 
           schedules.description, schedules.status, GROUP_CONCAT(tags.name ORDER BY tags.name) AS tags
    FROM schedules
    LEFT JOIN schedule_tags ON schedules.id = schedule_tags.schedule_id
    LEFT JOIN tags ON schedule_tags.tag_id = tags.id
    WHERE schedules.user_id = ?
    GROUP BY schedules.id
    ORDER BY schedules.start_datetime
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>スケジュール一覧</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* スケジュールのスタイル */
        .completed {
            opacity: 0.6; /* 完了したスケジュールを薄く表示 */
        }
        .status-label {
            font-weight: bold;
            padding: 5px;
            border-radius: 5px;
        }
        .status-label.pending {
            color: white;
            background-color: #17a2b8; /* 未完了のラベル色 */
        }
        .status-label.completed {
            color: white;
            background-color: #ced4da; /* 完了のラベル色 */
        }
    </style>
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
    
    <div class="container">
        <h2 class="mt-4">スケジュールの一覧</h2>
        <p><a href="logout.php" class="btn btn-secondary">ログアウト</a></p>
        
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ステータス</th>
                    <th>開始日時</th>
                    <th>終了日時</th>
                    <th>場所</th>
                    <th>内容</th>
                    <th>タグ</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()) : ?>
                    <?php 
                        $statusClass = ($row["status"] == "完了") ? "completed" : "";
                    ?>
                    <tr class="<?= $statusClass ?>">
                        <td>
                            <form action="update_status.php" method="POST">
                                <!-- チェックボックスで完了状態を変更 -->
                                <input type="checkbox" name="status" 
                                       value="完了" 
                                       <?= $row["status"] == "完了" ? 'checked' : '' ?> 
                                       onclick="this.form.submit()">
                                <!-- 完了というテキストを表示 -->
                                <span><?= $row["status"] == "完了" ? "完了" : "未完了" ?></span>
                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                            </form>
                        </td>
                        <td><?= htmlspecialchars($row["start_datetime"]) ?></td>
                        <td><?= htmlspecialchars($row["end_datetime"]) ?></td>
                        <td><?= htmlspecialchars($row["location"]) ?></td>
                        <td><?= htmlspecialchars($row["description"]) ?></td>
                        <td>
                            <!-- タグをカンマ区切りで表示 -->
                            <?= htmlspecialchars($row["tags"] ?? '') ?>
                        </td>
                        <td>
                            <a href="edit_schedule.php?id=<?= $row["id"] ?>" class="btn btn-sm btn-primary">編集</a>
                            <a href="delete_schedule.php?id=<?= $row["id"] ?>" class="btn btn-sm btn-danger" onclick="return confirm('本当に削除しますか？')">削除</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <p><a href="add_schedule.php" class="btn btn-primary">新しいスケジュールを追加</a></p>
        <p><a href="add_tag.php" class="btn btn-primary">タグを追加</a></p>
    </div>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
