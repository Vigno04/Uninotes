<?php
// admin-manage-users.php
require_once("bootstrap.php");

// Solo admin
if (!isset($_SESSION['person_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit;
}

$currentAdminId = (int)$_SESSION['person_id'];

// --- GESTIONE AZIONI POST (role, attiva/disattiva) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['user_id'])) {
    $action = $_POST['action'];
    $userId = (int)$_POST['user_id'];

    // Per sicurezza: non permettere a un admin di disattivare se stesso
    $isSelf = ($userId === $currentAdminId);

    if ($action === 'make_admin' && !$isSelf) {
        $stmt = mysqli_prepare($conn, "UPDATE user SET role = 'admin' WHERE person_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    } elseif ($action === 'make_user' && !$isSelf) {
        $stmt = mysqli_prepare($conn, "UPDATE user SET role = 'user' WHERE person_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    } elseif ($action === 'deactivate' && !$isSelf) {
        $stmt = mysqli_prepare($conn, "UPDATE user SET deleted_at = NOW() WHERE person_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    } elseif ($action === 'reactivate') {
        // Riattivare se stessi va bene (es. in futuro con più admin)
        $stmt = mysqli_prepare($conn, "UPDATE user SET deleted_at = NULL WHERE person_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    // PRG pattern: redirect dopo il POST
    header('Location: index.php?page=manage_users');
    exit;
}

// --- RICERCA SEMPLICE ---
$search = trim($_GET['q'] ?? '');

$params = [];
$where  = '';
if ($search !== '') {
    $where = "WHERE (p.name LIKE ? OR p.surname LIKE ? OR p.email LIKE ?)";
    $like  = '%' . $search . '%';
    $params = [$like, $like, $like];
}

$sql = "
    SELECT 
        p.id,
        p.name,
        p.surname,
        p.email,
        u.role,
        u.created_at AS user_created,
        u.last_login,
        u.deleted_at,
        COUNT(n.id) AS note_count
    FROM person p
    JOIN user u ON p.id = u.person_id
    LEFT JOIN note n ON n.owner_id = u.person_id
    $where
    GROUP BY p.id, p.name, p.surname, p.email, u.role, u.created_at, u.last_login, u.deleted_at
    ORDER BY 
        (u.role = 'admin') DESC,   -- prima gli admin
        p.surname,
        p.name
";

if ($where !== '') {
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sss", ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = mysqli_query($conn, $sql);
}

$users = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
}
?>

<div class="container py-4 py-lg-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">

            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4 p-lg-5">
                    <h1 class="h4 mb-3">Manage users</h1>
                    <p class="text-muted mb-4">
                        View all UniNotes accounts. You can change roles and activate/deactivate users.
                    </p>

                    <!-- Barra di ricerca -->
                    <form method="get" class="mb-3">
                        <input type="hidden" name="page" value="manage_users">
                        <div class="row g-2 align-items-center">
                            <div class="col-sm-8 col-md-6">
                                <input type="text"
                                       name="q"
                                       value="<?php echo htmlspecialchars($search); ?>"
                                       class="form-control"
                                       placeholder="Search by name or email">
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-primary btn-sm">Search</button>
                            </div>
                            <?php if ($search !== ''): ?>
                                <div class="col-auto">
                                    <a href="index.php?page=manage_users" class="btn btn-outline-secondary btn-sm">
                                        Clear
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </form>

                    <?php if (empty($users)): ?>
                        <p class="text-muted">No users found.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Notes</th>
                                        <th>Joined</th>
                                        <th>Last login</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $u): ?>
                                        <?php
                                            $isDeleted = !is_null($u['deleted_at']);
                                            $isAdmin   = ($u['role'] === 'admin');
                                            $isSelfRow = ($u['id'] == $currentAdminId);
                                        ?>
                                        <tr class="<?php echo $isDeleted ? 'table-light text-muted' : ''; ?>">
                                            <td><?php echo htmlspecialchars($u['name'] . ' ' . $u['surname']); ?></td>
                                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $isAdmin ? 'danger' : 'secondary'; ?>">
                                                    <?php echo htmlspecialchars($u['role']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($isDeleted): ?>
                                                    <span class="badge bg-secondary">Disabled</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo (int)$u['note_count']; ?></td>
                                            <td>
                                                <?php echo htmlspecialchars(date('d/m/Y', strtotime($u['user_created']))); ?>
                                            </td>
                                            <td>
                                                <?php
                                                if ($u['last_login']) {
                                                    echo htmlspecialchars(date('d/m/Y H:i', strtotime($u['last_login'])));
                                                } else {
                                                    echo '<span class="text-muted small">Never</span>';
                                                }
                                                ?>
                                            </td>
                                            <td class="text-end">
                                                <div class="d-inline-flex gap-1">

                                                    <!-- Toggle ruolo -->
                                                    <?php if (!$isSelfRow): ?>
                                                        <form method="post" class="d-inline">
                                                            <input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>">
                                                            <?php if ($isAdmin): ?>
                                                                <input type="hidden" name="action" value="make_user">
                                                                <button type="submit" class="btn btn-outline-secondary btn-sm">
                                                                    Make user
                                                                </button>
                                                            <?php else: ?>
                                                                <input type="hidden" name="action" value="make_admin">
                                                                <button type="submit" class="btn btn-outline-warning btn-sm">
                                                                    Make admin
                                                                </button>
                                                            <?php endif; ?>
                                                        </form>
                                                    <?php endif; ?>

                                                    <!-- Attiva / disattiva -->
                                                    <form method="post" class="d-inline">
                                                        <input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>">
                                                        <?php if ($isDeleted): ?>
                                                            <input type="hidden" name="action" value="reactivate">
                                                            <button type="submit" class="btn btn-outline-success btn-sm">
                                                                Reactivate
                                                            </button>
                                                        <?php elseif (!$isSelfRow): ?>
                                                            <input type="hidden" name="action" value="deactivate">
                                                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                                                Disable
                                                            </button>
                                                        <?php endif; ?>
                                                    </form>

                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <div class="mt-4">
                        <a href="index.php?page=admindashboard" class="btn btn-outline-secondary btn-sm">
                            ← Back to dashboard
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
