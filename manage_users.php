<?php
// admin-manage-users.php
require_once("bootstrap.php");

// Solo admin
if (!isset($_SESSION['person_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit;
}

// Ricerca semplice per nome / email
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
        COUNT(n.id) AS note_count
    FROM person p
    JOIN user u ON p.id = u.person_id
    LEFT JOIN note n ON n.owner_id = u.person_id
    $where
    GROUP BY p.id, p.name, p.surname, p.email, u.role, u.created_at, u.last_login
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
                        View all UniNotes accounts. In a future version you’ll be able to edit roles and disable accounts.
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
                                        <th>Notes</th>
                                        <th>Joined</th>
                                        <th>Last login</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $u): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($u['name'] . ' ' . $u['surname']); ?></td>
                                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $u['role'] === 'admin' ? 'danger' : 'secondary'; ?>">
                                                    <?php echo htmlspecialchars($u['role']); ?>
                                                </span>
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
