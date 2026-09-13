<?php

session_start();

require_once "config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION["user_id"];
$demat_id = (int) ($_GET["demat_id"] ?? 0);

if ($demat_id <= 0) {
    die("Invalid Demat account.");
}


/*
 * Check whether this Demat account
 * belongs to the logged-in user.
 */

$stmt = $conn->prepare(
    "SELECT id, account_name, account_holder, broker_name, boid
     FROM demat_accounts
     WHERE id = ? AND user_id = ?
     LIMIT 1"
);

$stmt->bind_param("ii", $demat_id, $user_id);
$stmt->execute();

$result = $stmt->get_result();
$demat = $result->fetch_assoc();

$stmt->close();


/*
 * Don't allow access to another user's Demat.
 */

if (!$demat) {
    die("Demat account not found.");
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Holdings | Portfolio Manager</title>

</head>

<body>

<h1>Holdings</h1>

<h2>
    <?= htmlspecialchars($demat["account_name"]) ?>
</h2>

<p>
    Account Holder:
    <?= htmlspecialchars($demat["account_holder"]) ?>
</p>

<p>
    Broker:
    <?= htmlspecialchars($demat["broker_name"]) ?>
</p>

<p>
    Demat ID:
    <?= $demat_id ?>
</p>

</body>

</html>