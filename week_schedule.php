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

// 週のオフセット値（URLパラメータから取得、デフォルトは 0 に設定）
$weekOffset = isset($_GET['week']) ? (int)$_GET['week'] : 0;

// 今日の日付を基準に週の開始日を計算
$today = date('Y-m-d');
$startOfWeek = date('Y-m-d', strtotime('last sunday', strtotime($today))); // 現在の週の日曜日を取得

// 週のオフセットを加算して表示する週の開始日を決定
$weekStart = date('Y-m-d', strtotime("$weekOffset week", strtotime($startOfWeek)));

// 表示する週の日付を決定
$weekDates = [];
for ($i = 0; $i < 7; $i++) {
    $weekDates[] = date('Y-m-d', strtotime("$weekStart +$i days"));
}


// 曜日名
$daysOfWeek = ['日', '月', '火', '水', '木', '金', '土'];

// 一週間の日付を取得してスケジュールを取得
$schedules = [];
for ($i = 0; $i < 7; $i++) {
    $currentDate = date('Y-m-d', strtotime("$weekStart +$i days"));
    $sql = "SELECT start_datetime, end_datetime, location, description FROM schedules WHERE user_id = ? AND DATE(start_datetime) = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $_SESSION["user_id"], $currentDate);
    $stmt->execute();
    $result = $stmt->get_result();

    $schedules[$currentDate] = [];
    while ($row = $result->fetch_assoc()) {
        $schedules[$currentDate][] = $row;
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>週間スケジュール</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .highlight {
            background-color: #fff3cd !important; /* 黄色のハイライト */
        }
        .calendar th, .calendar td {
            vertical-align: top;
            height: 150px; /* 高さを調整 */
        }
    </style>
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
                        <a class="nav-link active" href="week_schedule.php">週間表示</a>
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
        <h2 class="mb-4">週間スケジュール</h2>
        <div class="d-flex justify-content-between mb-3">
            <div>
                <!-- 「前の週」ボタン -->
                <a href="week_schedule.php?week=<?= $weekOffset - 1 ?>" class="btn btn-outline-primary">前の週</a>
                <!-- 「次の週」ボタン -->
                <a href="week_schedule.php?week=<?= $weekOffset + 1 ?>" class="btn btn-outline-primary">次の週</a>
            </div>
            <div>
                <a href="schedule_list.php" class="btn btn-secondary">スケジュール一覧に戻る</a>
            </div>
        </div>

        <table class="table table-bordered">
            <thead class="table-light">
                <tr>
                    <?php foreach ($daysOfWeek as $day): ?>
                        <th><?= htmlspecialchars($day) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <?php foreach ($schedules as $date => $events): ?>
                        <?php
                            // 今日の日付を強調表示
                            $highlightClass = ($date === $today) ? 'highlight' : '';
                            $formattedDate = date('n月j日', strtotime($date));
                        ?>
                        <td class="<?= $highlightClass ?>">
                            <strong><?= htmlspecialchars($formattedDate) ?> (<?= htmlspecialchars(date('D', strtotime($date))) ?>)</strong><br>
                            <?php if (!empty($events)): ?>
                                <?php foreach ($events as $event): ?>
                                    <div class="mb-2">
                                        <span class="badge bg-info text-dark"><?= htmlspecialchars(date('H:i', strtotime($event['start_datetime']))) ?> - <?= htmlspecialchars(date('H:i', strtotime($event['end_datetime']))) ?></span>
                                        <p class="mb-1"><strong>場所:</strong> <?= htmlspecialchars($event['location']) ?></p>
                                        <p class="mb-0"><strong>内容:</strong> <?= htmlspecialchars($event['description']) ?></p>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p>スケジュールなし</p>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
