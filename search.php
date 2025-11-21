<?php
// search.php

// se qualcuno apre direttamente search.php, rimandalo al router (opzionale)
if (basename($_SERVER['SCRIPT_NAME']) === 'search.php') {
    header('Location: index.php?page=search');
    exit;
}

$searchTerm = trim($_GET['q'] ?? '');
$results = [];

// se c'è un termine di ricerca, facciamo la query
if ($searchTerm !== '') {
    // cerco in titolo e contenuto delle note pubblicate
    $sql = "
        SELECT
            n.id,
            n.title,
            SUBSTRING(n.content, 1, 200) AS snippet,
            COALESCE(n.published_at, n.created_at) AS note_date,
            c.name AS course_name,
            t.name AS topic_name,
            CONCAT(po.name, ' ', po.surname) AS author_name
        FROM note n
        JOIN topic t ON n.topic_id = t.id
        JOIN course_offering co ON t.offering_id = co.id
        JOIN course c ON co.course_id = c.id
        LEFT JOIN user uo ON n.owner_id = uo.person_id
        LEFT JOIN person po ON uo.person_id = po.id
        WHERE n.status = 'published'
          AND (
                n.title   LIKE CONCAT('%', ?, '%')
             OR n.content LIKE CONCAT('%', ?, '%')
          )
        ORDER BY note_date DESC
        LIMIT 20
    ";

    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ss", $searchTerm, $searchTerm);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) {
            $results[] = $row;
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-lg-8 mx-auto">
            <h1 class="h4 mb-3">Cerca appunti</h1>
            <form class="input-group" method="get" action="index.php">
                <input type="hidden" name="page" value="search">
                <input
                    type="text"
                    class="form-control form-control-lg"
                    name="q"
                    placeholder="Cerca per titolo o contenuto..."
                    value="<?php echo htmlspecialchars($searchTerm); ?>"
                    autofocus
                >
                <button class="btn btn-primary btn-lg" type="submit">
                    Cerca
                </button>
            </form>
            <?php if ($searchTerm === ''): ?>
                <p class="text-muted small mt-2">
                    Inserisci una parola chiave per iniziare la ricerca.
                </p>
            <?php else: ?>
                <p class="text-muted small mt-2">
                    Risultati per: <strong><?php echo htmlspecialchars($searchTerm); ?></strong>
                    (<?php echo count($results); ?> trovati)
                </p>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($searchTerm !== ''): ?>
        <div class="row">
            <div class="col-lg-10 mx-auto">
                <?php if (empty($results)): ?>
                    <p class="text-muted">Nessun appunto trovato.</p>
                <?php else: ?>
                    <?php foreach ($results as $note): ?>
                        <div class="card mb-3 border-0 shadow-sm rounded-4">
                            <div class="card-body">
                                <h2 class="h5 mb-1">
                                    <?php echo htmlspecialchars($note["title"]); ?>
                                </h2>
                                <p class="mb-0 text-muted">
                                    <?php echo htmlspecialchars($note["course_name"]); ?>
                                </p>
                                <p class="mb-1 text-primary small">
                                    <?php echo htmlspecialchars($note["topic_name"]); ?>
                                </p>

                                <p class="mt-2 mb-2 small text-muted">
                                    <?php echo nl2br(htmlspecialchars($note["snippet"])); ?>...
                                </p>

                                <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
                                    <div class="text-muted small">
                                        <span class="me-3">
                                            👤 <?php echo htmlspecialchars($note["author_name"] ?? 'Anonimo'); ?>
                                        </span>
                                        <span>
                                            📅
                                            <?php
                                            $date = date("d M Y", strtotime($note["note_date"]));
                                            echo htmlspecialchars($date);
                                            ?>
                                        </span>
                                    </div>
                                    <a href="#"
                                       class="text-decoration-none small">
                                        👁 Visualizza
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
