<?php
session_start();

// ログイン確認
if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

// DB接続
$conn = new mysqli("localhost", "root", "root", "schedule_app", 8889);
if ($conn->connect_error) {
    die("データベース接続に失敗しました: " . $conn->connect_error);
}

// スケジュール削除
if (isset($_GET["id"])) {
    $id = $_GET["id"];
    $stmt = $conn->prepare("DELETE FROM schedules WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $_SESSION["user_id"]);

    if ($stmt->execute()) {
        header("Location: schedule_list.php");
        exit;
    } else {
        echo "スケジュールの削除に失敗しました。";
    }
}
?>
