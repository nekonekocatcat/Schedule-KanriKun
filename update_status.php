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

// ステータス更新処理
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["id"])) {
    $id = $_POST["id"];
    $status = $_POST["status"];

    // ステータスが完了の場合は「完了」、それ以外は「未完了」
    if ($status == "完了") {
        $status = "完了";
    } else {
        $status = "未完了";
    }

    $stmt = $conn->prepare("UPDATE schedules SET status = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("sii", $status, $id, $_SESSION["user_id"]);

    if ($stmt->execute()) {
        header("Location: schedule_list.php");
        exit;
    } else {
        echo "ステータスの更新に失敗しました。";
    }

    $stmt->close();
}

$conn->close();
?>
