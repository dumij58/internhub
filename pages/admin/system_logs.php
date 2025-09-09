<?php
require_once '../../includes/config.php';
requireAdmin();
$page_title = 'System Logs';
$db = getDB();
require_once '../../includes/header.php';
?>
<div class="admin-panel-tasks">
    <h2>All System Logs</h2>
    <table class="logs-table">
        <thead>
            <tr>
                <th>Log ID</th>
                <th>User ID</th>
                <th>Action</th>
                <th>Details</th>
                <th>User Agent</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $stmt = $db->query("SELECT * FROM system_logs ORDER BY created_at DESC");
            while ($log = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr>
                        <td>".escape($log['log_id'])."</td>
                        <td>".escape($log['user_id'])."</td>
                        <td>".escape($log['action'])."</td>
                        <td>".escape($log['details'])."</td>
                        <td>".escape($log['user_agent'])."</td>
                        <td>".escape($log['created_at'])."</td>
                    </tr>";
            }
            ?>
        </tbody>
    </table>
</div>
<?php require_once '../../includes/footer.php'; ?>
