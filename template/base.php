<?
require_once("bootstrap.php");
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($templateParams["title"] ?? "UniNotes"); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <p>[nav]</p>
    <p>[profile]</p>
    <p>[cerca]</p>
    <p>[carica]</p>

    <?php if (!empty($templateParams["is_admin"])): ?>
        <p>[admin]</p>
    <?php endif; ?>
</body>
</html>
