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

// 月のオフセット値（URLパラメータから取得）
$monthOffset = isset($_GET['month']) ? (int)$_GET['month'] : 0;

// 今日の日付
$today = date('Y-m-d');

// 表示する月の最初の日を取得
$firstDayOfMonth = date('Y-m-01', strtotime("first day of $monthOffset month"));
$monthTitle = date('Y年n月', strtotime($firstDayOfMonth)); // 月の表示

// 最初の日の曜日
$firstDayOfWeek = date('w', strtotime($firstDayOfMonth));

// その月の最終日
$daysInMonth = date('t', strtotime($firstDayOfMonth));

// 曜日名
$daysOfWeek = ['日', '月', '火', '水', '木', '金', '土'];

// 現在のユーザーIDを取得
$user_id = $_SESSION["user_id"];

// 月間スケジュールを取得する準備
$schedules = [];
for ($day = 1; $day <= $daysInMonth; $day++) {
    $currentDate = date('Y-m-d', strtotime("$firstDayOfMonth +".($day - 1)." days"));
    $sql = "SELECT start_datetime, end_datetime, location, description FROM schedules WHERE user_id = ? AND DATE(start_datetime) = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $currentDate);
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
    <title>月間スケジュール</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .highlight {
            background-color: #fff3cd !important; /* 黄色のハイライト */
        }
        .calendar {
            table-layout: fixed;
        }
        .calendar th, .calendar td {
            height: 150px;
            vertical-align: top;
        }
        .calendar th {
            text-align: center;
            background-color: #f8f9fa;
        }
        .calendar td {
            padding: 5px;
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
                        <a class="nav-link" href="week_schedule.php">週間表示</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="month_schedule.php">月間表示</a>
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
        <h2 class="mb-4">月間スケジュール - <?= htmlspecialchars($monthTitle) ?></h2>
        <div class="d-flex justify-content-between mb-3">
            <div>
                <a href="month_schedule.php?month=<?= $monthOffset - 1 ?>" class="btn btn-outline-primary">前の月</a>
                <a href="month_schedule.php?month=<?= $monthOffset + 1 ?>" class="btn btn-outline-primary">次の月</a>
            </div>
            <div>
                <a href="schedule_list.php" class="btn btn-secondary">スケジュール一覧に戻る</a>
            </div>
        </div>
        <table class="table table-bordered calendar">
            <thead>
                <tr>
                    <?php foreach ($daysOfWeek as $day): ?>
                        <th><?= htmlspecialchars($day) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <?php
                    // 空白を入れて月の1日が始まる曜日まで埋める
                    for ($i = 0; $i < $firstDayOfWeek; $i++) {
                        echo "<td></td>";
                    }

                    for ($day = 1; $day <= $daysInMonth; $day++) {
                        $currentDate = date('Y-m-d', strtotime("$firstDayOfMonth +".($day - 1)." days"));
                        $dayOfWeek = date('w', strtotime($currentDate));
                        $highlightClass = ($currentDate === $today) ? 'highlight' : '';

                        echo "<td class='$highlightClass'>";
                        echo "<strong>$day</strong><br>";

                        if (!empty($schedules[$currentDate])) {
                            foreach ($schedules[$currentDate] as $event) {
                                echo "<div class='mb-2'>";
                                echo "<span class='badge bg-info text-dark'>" . htmlspecialchars(date('H:i', strtotime($event['start_datetime']))) . " - " . htmlspecialchars(date('H:i', strtotime($event['end_datetime']))) . "</span><br>";
                                echo "<strong>場所:</strong> " . htmlspecialchars($event['location']) . "<br>";
                                echo "<strong>内容:</strong> " . htmlspecialchars($event['description']) . "<br>";
                                echo "</div>";
                            }
                        } else {
                            echo "<p>スケジュールなし</p>";
                        }

                        echo "</td>";

                        // 週の終わり（土曜日）で行を終了
                        if ($dayOfWeek == 6 && $day != $daysInMonth) {
                            echo "</tr><tr>";
                        }
                    }

                    // 最後の週の余白を埋める
                    $lastDayOfMonth = date('w', strtotime("$firstDayOfMonth +".($daysInMonth - 1)." days"));
                    for ($i = $lastDayOfMonth + 1; $i < 7; $i++) {
                        echo "<td></td>";
                    }
                    ?>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
