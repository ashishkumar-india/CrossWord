<?php
require_once '../config.php';
require_once '../functions/helpers.php';

if (!isLoggedIn('teacher')) {
    redirect('auth/teacher_login.php');
}

$teacher = getCurrentUser('teacher');

// Get login logs
$login_logs = $conn->query("SELECT * FROM login_logs ORDER BY login_time DESC LIMIT 100");

// Get registration logs
$registration_logs = $conn->query("SELECT * FROM registration_logs ORDER BY registration_time DESC LIMIT 50");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Logs</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .log-table { margin: 20px 0; }
        .log-table th { background: #667eea; color: white; padding: 10px; }
        .log-table td { padding: 10px; border-bottom: 1px solid #e5e7eb; }
        .status-success { color: #10b981; font-weight: 600; }
        .status-failed { color: #ef4444; font-weight: 600; }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="container">
            <h1>🧩 Crossword Game</h1>
            <nav>
                <a href="teacher_dashboard.php">Dashboard</a>
                <a href="view_logs.php">View Logs</a>
                <a href="../logout.php">Logout</a>
            </nav>
        </div>
    </div>

    <div class="container dashboard">
        <div class="content-box">
            <h3>📊 Login Logs (Last 100)</h3>
            <div class="table-responsive">
                <table class="log-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Type</th>
                            <th>Email</th>
                            <th>IP Address</th>
                            <th>Status</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($log = $login_logs->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $log['id']; ?></td>
                                <td><?php echo ucfirst($log['user_type']); ?></td>
                                <td><?php echo htmlspecialchars($log['email']); ?></td>
                                <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                                <td class="status-<?php echo $log['status']; ?>">
                                    <?php echo ucfirst($log['status']); ?>
                                </td>
                                <td><?php echo formatDate($log['login_time']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="content-box">
            <h3>📝 Registration Logs (Last 50)</h3>
            <div class="table-responsive">
                <table class="log-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Type</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Program</th>
                            <th>IP Address</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($log = $registration_logs->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $log['id']; ?></td>
                                <td><?php echo ucfirst($log['user_type']); ?></td>
                                <td><?php echo htmlspecialchars($log['name']); ?></td>
                                <td><?php echo htmlspecialchars($log['email']); ?></td>
                                <td><?php echo htmlspecialchars($log['program'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                                <td><?php echo formatDate($log['registration_time']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
