<?php
require_once '../config/database.php';
require_once '../config/functions.php';
require_once '../includes/auth.php';
requireAdmin();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_user'])) {
        $username = $_POST['username'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = $_POST['role'];
        $teamId = $_POST['team_id'] ?: null;
        
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role, team_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $password, $role, $teamId]);
        
        header('Location: users.php');
        exit;
    } elseif (isset($_POST['edit_user'])) {
        $id = $_POST['id'];
        $username = $_POST['username'];
        $role = $_POST['role'];
        $teamId = $_POST['team_id'] ?: null;
        
        // Update password only if provided
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET username = ?, password = ?, role = ?, team_id = ? WHERE id = ?");
            $stmt->execute([$username, $password, $role, $teamId, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET username = ?, role = ?, team_id = ? WHERE id = ?");
            $stmt->execute([$username, $role, $teamId, $id]);
        }
        
        header('Location: users.php');
        exit;
    } elseif (isset($_POST['delete_user'])) {
        $id = $_POST['id'];
        
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        
        header('Location: users.php');
        exit;
    }
}

// Get user to edit
$editUser = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $editUser = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get all users
$users = $pdo->query("SELECT u.*, t.name AS team_name FROM users u LEFT JOIN teams t ON u.team_id = t.id ORDER BY u.username")->fetchAll(PDO::FETCH_ASSOC);

// Get all teams
$teams = getTeams();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Cricket Auction System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .user-form {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .user-list {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        
        .user-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            transition: transform 0.3s;
        }
        
        .user-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .user-card h3 {
            margin-bottom: 10px;
        }
        
        .user-card .role {
            font-weight: bold;
            color: #007bff;
        }
        
        .user-card .team {
            font-size: 14px;
            color: #6c757d;
        }
        
        .user-actions {
            margin-top: 10px;
            display: flex;
            gap: 10px;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <h1>Manage Users</h1>
        
        <div class="user-form">
            <h2><?php echo $editUser ? 'Edit User' : 'Add New User'; ?></h2>
            <form method="post" action="">
                <?php if ($editUser): ?>
                    <input type="hidden" name="id" value="<?php echo $editUser['id']; ?>">
                <?php endif; ?>
                
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" value="<?php echo $editUser ? htmlspecialchars($editUser['username']) : ''; ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Password <?php echo $editUser ? '(leave blank to keep current)' : ''; ?></label>
                    <input type="password" class="form-control" id="password" name="password" <?php echo !$editUser ? 'required' : ''; ?>>
                </div>
                
                <div class="mb-3">
                    <label for="role" class="form-label">Role</label>
                    <select class="form-select" id="role" name="role" required>
                        <option value="">Select Role</option>
                        <option value="admin" <?php echo $editUser && $editUser['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="team" <?php echo $editUser && $editUser['role'] === 'team' ? 'selected' : ''; ?>>Team Representative</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="team_id" class="form-label">Team (for Team Representatives)</label>
                    <select class="form-select" id="team_id" name="team_id">
                        <option value="">Select Team</option>
                        <?php foreach ($teams as $team): ?>
                            <option value="<?php echo $team['id']; ?>" <?php echo $editUser && $editUser['team_id'] == $team['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($team['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" name="<?php echo $editUser ? 'edit_user' : 'add_user'; ?>" class="btn btn-primary">
                    <?php echo $editUser ? 'Update User' : 'Add User'; ?>
                </button>
                
                <?php if ($editUser): ?>
                    <a href="users.php" class="btn btn-secondary">Cancel</a>
                <?php endif; ?>
            </form>
        </div>
        
        <div class="user-list">
            <h2>All Users</h2>
            
            <?php if (empty($users)): ?>
                <div class="alert alert-info">No users found.</div>
            <?php else: ?>
                <?php foreach ($users as $user): ?>
                    <div class="user-card">
                        <h3><?php echo htmlspecialchars($user['username']); ?></h3>
                        <p>Role: <span class="role"><?php echo htmlspecialchars($user['role']); ?></span></p>
                        <?php if ($user['role'] === 'team' && $user['team_name']): ?>
                            <p>Team: <span class="team"><?php echo htmlspecialchars($user['team_name']); ?></span></p>
                        <?php endif; ?>
                        
                        <div class="user-actions">
                            <a href="users.php?edit=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                            <form method="post" action="" style="display: inline;">
                                <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                <button type="submit" name="delete_user" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this user?')">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>