<?php
// admin-manage-users.php
require_once("bootstrap.php");

// Solo admin
if (!isset($_SESSION['person_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit;
}

$adminModel = new AdminModel();
$currentAdminId = (int)$_SESSION['person_id'];

// --- GESTIONE AZIONI POST (role, attiva/disattiva) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['user_id'])) {
    $action = $_POST['action'];
    $userId = (int)$_POST['user_id'];

    // Per sicurezza: non permettere a un admin di disattivare se stesso
    $isSelf = ($userId === $currentAdminId);

    if ($action === 'make_admin' && !$isSelf) {
        $adminModel->promoteToAdmin($userId);
    } elseif ($action === 'make_user' && !$isSelf) {
        $adminModel->demoteToUser($userId);
    } elseif ($action === 'deactivate' && !$isSelf) {
        $adminModel->deleteUser($userId);
    } elseif ($action === 'reactivate') {
        $adminModel->restoreUser($userId);
    }

    // PRG pattern: redirect dopo il POST
    header('Location: index.php?page=manage_users');
    exit;
}

// --- RICERCA SEMPLICE ---
$search = trim($_GET['q'] ?? '');
$roleFilter = $_GET['role'] ?? null;
$showDeleted = isset($_GET['show_deleted']);

$users = $adminModel->getAllUsers($search, $roleFilter, $showDeleted);

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
                                <label for="manage-users-search" class="visually-hidden">Search users</label>
                                <input id="manage-users-search" type="text"
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
                                <caption class="visually-hidden">List of all users with name, email, role, status and actions</caption>
                                <thead class="table-light">
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Teacher</th>
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
                                            // Teacher / pending teacher flags
                                            $hasTeacherRow = !is_null($u['teacher_person_id']);

                                            $isTeacher        = false;
                                            $isTeacherPending = false;

                                            if ($hasTeacherRow) {
                                                $allNull =
                                                    is_null($u['teacher_department']) &&
                                                    is_null($u['teacher_unibo_site']) &&
                                                    is_null($u['teacher_phone_number']) &&
                                                    is_null($u['teacher_personal_site']);

                                                if ($allNull) {
                                                    $isTeacherPending = true; // row exists but all detail fields NULL
                                                } else {
                                                    $isTeacher = true;        // at least one teacher field filled
                                                }
                                            }

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
                                            <td>
                                                <?php if ($isTeacher): ?>
                                                    <span class="badge bg-info text-dark">Teacher</span>
                                                <?php elseif ($isTeacherPending): ?>
                                                    <span class="badge bg-warning text-dark">Pending</span>
                                                <?php else: ?>
                                                    <span class="text-muted small">—</span>
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
                                            <!-- From here -->
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
                                                
                                                     <!-- Review / Edit teacher (pending or active) -->
                                                    <?php if ($isTeacher || $isTeacherPending): ?>
                                                        <a href="index.php?page=admin_edit_teacher&user_id=<?php echo (int)$u['id']; ?>"
                                                        class="btn btn-outline-info btn-sm">
                                                            Review teacher
                                                        </a>
                                                    <?php endif; ?> 
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
