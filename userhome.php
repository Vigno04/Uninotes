<?php
require_once("bootstrap.php");

// se qualcuno arriva qui senza login, lo rimando al login
if (!isset($_SESSION['person_id'])) {
    header('Location: login.php');
    exit;
}

$personId = $_SESSION['person_id'];

$sql = "
    SELECT 
        p.name,
        p.surname,
        p.email,
        p.profile_picture,
        p.created_at,
        u.role,
        u.last_login
    FROM person p
    JOIN user u ON p.id = u.person_id
    WHERE p.id = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $personId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$userData = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$userData) {
    header('Location: logout.php');
    exit;
}

$fullName  = $userData['name'] . ' ' . $userData['surname'];
$email     = $userData['email'];
$role      = $userData['role'];
$created   = $userData['created_at'];
$lastLogin = $userData['last_login'];

$profilePicture = $userData['profile_picture'];
if (empty($profilePicture)) {
    // puoi cambiarlo con un tuo file locale
    $profilePicture = 'https://via.placeholder.com/120?text=User';
}

// iniziali per il cerchio (GU nella UI che hai postato)
$initials = strtoupper(mb_substr($userData['name'], 0, 1) . mb_substr($userData['surname'], 0, 1));
?>

<div class="container py-4 py-lg-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10 col-xl-8">

            <!-- CARD PROFILO IN ALTO -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body d-flex flex-column flex-md-row align-items-md-center gap-3">
                    <div class="d-flex align-items-center gap-3 flex-grow-1">

                        <!-- Avatar tondo stile mockup -->
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:80px;height:80px;
                                    background:linear-gradient(135deg,#4e54c8,#8f94fb);
                                    color:#fff;font-size:1.6rem;font-weight:600;">
                            <?php echo htmlspecialchars($initials); ?>
                        </div>

                        <div>
                            <div class="fw-semibold mb-1">
                                <?php echo htmlspecialchars($email); ?>
                            </div>
                            <div class="d-flex flex-wrap align-items-center gap-2 small text-muted">
                                <?php if ($role === 'admin'): ?>
                                    <span class="badge rounded-pill bg-warning text-dark">
                                        Admin
                                    </span>
                                <?php else: ?>
                                    <span class="badge rounded-pill bg-secondary text-light">
                                        User
                                    </span>
                                <?php endif; ?>

                                <span>
                                    📅 Joined <?php echo htmlspecialchars(date('F Y', strtotime($created))); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="ms-md-auto">
                        <a href="logout.php" class="btn btn-outline-danger btn-sm">
                            Logout
                        </a>
                    </div>
                </div>
            </div>

            <!-- CARD PERSONAL INFORMATION -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4 p-lg-5">
                    <h2 class="h5 mb-1">Personal Information</h2>
                    <p class="text-muted small mb-4">Update your account details</p>

                    <!-- per ora il form è solo di visualizzazione (disabled) -->
                    <form>
                        <div class="mb-3">
                            <label class="form-label small text-muted">Full Name</label>
                            <input type="text"
                                   class="form-control"
                                   value="<?php echo htmlspecialchars($fullName); ?>"
                                   disabled>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small text-muted">Email</label>
                            <div class="input-group">
                                <span class="input-group-text">📧</span>
                                <input type="email"
                                       class="form-control"
                                       value="<?php echo htmlspecialchars($email); ?>"
                                       disabled>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small text-muted">University</label>
                            <input type="text"
                                   class="form-control"
                                   placeholder="Enter your university"
                                   disabled>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small text-muted">Bio</label>
                            <textarea class="form-control"
                                      rows="3"
                                      placeholder="Tell us about yourself"
                                      disabled></textarea>
                        </div>

                        <button type="button"
                                class="btn btn-primary w-100"
                                disabled>
                            Save Changes
                        </button>
                    </form>
                </div>
            </div>

            <!-- CARD ACTIVITY -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4 p-lg-5">
                    <h2 class="h6 mb-2">Activity</h2>
                    <p class="text-muted small mb-4">Your contribution to UniNotes</p>

                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <div class="border rounded-4 py-4 px-3 text-center bg-light">
                                <div class="fs-2 mb-2">📘</div>
                                <div class="fw-semibold">Notes Uploaded</div>
                                <div class="text-muted small">0</div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="border rounded-4 py-4 px-3 text-center bg-light">
                                <div class="fs-2 mb-2">📥</div>
                                <div class="fw-semibold">Notes Downloaded</div>
                                <div class="text-muted small">0</div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>
