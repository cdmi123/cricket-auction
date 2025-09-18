<?php
require_once '../config/database.php';
require_once '../config/functions.php';
require_once '../includes/auth.php';
requireAdmin();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_team'])) {
        $name = $_POST['name'];
        $budget = $_POST['budget'];
        $maxPlayers = $_POST['max_players'];
        
        $stmt = $pdo->prepare("INSERT INTO teams (name, budget, remaining_budget, max_players) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $budget, $budget, $maxPlayers]);
        
        header('Location: teams.php');
        exit;
    } elseif (isset($_POST['edit_team'])) {
        $id = $_POST['id'];
        $name = $_POST['name'];
        $budget = $_POST['budget'];
        $maxPlayers = $_POST['max_players'];
        
        $stmt = $pdo->prepare("UPDATE teams SET name = ?, budget = ?, max_players = ? WHERE id = ?");
        $stmt->execute([$name, $budget, $maxPlayers, $id]);
        
        header('Location: teams.php');
        exit;
    } elseif (isset($_POST['delete_team'])) {
        $id = $_POST['id'];
        
        $stmt = $pdo->prepare("DELETE FROM teams WHERE id = ?");
        $stmt->execute([$id]);
        
        header('Location: teams.php');
        exit;
    }
}

// Get team to edit
$editTeam = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM teams WHERE id = ?");
    $stmt->execute([$id]);
    $editTeam = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get all teams
$teams = getTeams();
?>

<?php include_once '../includes/header.php' ?>
    <!-- Main Content -->
    <div class="main-container">
        <div class="container py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-users"></i> Manage Teams</h1>
                <div class="d-flex gap-2">
                    <a href="dashboard.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                    <a href="manage_auction.php" class="btn btn-primary">
                        <i class="fas fa-cog"></i> Manage Auction
                    </a>
                </div>
            </div>
            
            <!-- Team Form Section -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3>
                        <i class="fas fa-<?php echo $editTeam ? 'edit' : 'plus-circle'; ?>"></i> 
                        <?php echo $editTeam ? 'Edit Team' : 'Add New Team'; ?>
                    </h3>
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <?php if ($editTeam): ?>
                            <input type="hidden" name="id" value="<?php echo $editTeam['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Team Name</label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?php echo $editTeam ? htmlspecialchars($editTeam['name']) : ''; ?>" 
                                           placeholder="Enter team name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="budget" class="form-label">Budget ($)</label>
                                    <div class="input-group">
                                        <span class="input-group-text"></span>
                                        <input type="number" class="form-control" id="budget" name="budget" 
                                               value="<?php echo $editTeam ? $editTeam['budget'] : ''; ?>" 
                                               min="0" step="1000" placeholder="1000000" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="max_players" class="form-label">Maximum Players</label>
                                    <input type="number" class="form-control" id="max_players" name="max_players" 
                                           value="<?php echo $editTeam ? $editTeam['max_players'] : '25'; ?>" 
                                           min="1" max="50" required>
                                    <div class="form-text">Recommended: 15-25 players per team</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3 d-flex align-items-end">
                                    <div class="d-flex gap-2">
                                        <button type="submit" name="<?php echo $editTeam ? 'edit_team' : 'add_team'; ?>" 
                                                class="btn btn-primary">
                                            <i class="fas fa-<?php echo $editTeam ? 'save' : 'plus'; ?>"></i> 
                                            <?php echo $editTeam ? 'Update Team' : 'Add Team'; ?>
                                        </button>
                                        
                                        <?php if ($editTeam): ?>
                                            <a href="teams.php" class="btn btn-secondary">
                                                <i class="fas fa-times"></i> Cancel
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Teams List Section -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> All Teams</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($teams)): ?>
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-users fa-4x mb-3"></i>
                            <h4>No Teams Found</h4>
                            <p>Start by adding your first team using the form above.</p>
                        </div>
                    <?php else: ?>
                        <div class="team-grid">
                            <?php foreach ($teams as $team): ?>
                                <div class="team-card">
                                    <div class="team-header">
                                        <h4><i class="fas fa-shield-alt"></i> <?php echo htmlspecialchars($team['name']); ?></h4>
                                        <div class="team-status">
                                            <span class="badge bg-primary"><?php echo count(getTeamPlayers($team['id'])); ?>/<?php echo $team['max_players']; ?> Players</span>
                                        </div>
                                    </div>
                                    
                                    <div class="team-budget">
                                        <div class="budget-info">
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>Total Budget:</span>
                                                <span class="fw-bold"><?php echo number_format($team['budget'], 2); ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>Remaining:</span>
                                                <span class="fw-bold text-success"><?php echo number_format($team['remaining_budget'], 2); ?></span>
                                            </div>
                                            <div class="budget-bar">
                                                <div class="budget-spent" style="width: <?php echo $team['budget'] > 0 ? (($team['budget'] - $team['remaining_budget']) / $team['budget']) * 100 : 0; ?>%"></div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="team-stats">
                                        <div class="stat-item">
                                            <i class="fas fa-dollar-sign"></i>
                                            <span>Budget Used: <?php echo $team['budget'] > 0 ? number_format((($team['budget'] - $team['remaining_budget']) / $team['budget']) * 100, 1) : 0; ?>%</span>
                                        </div>
                                        <div class="stat-item">
                                            <i class="fas fa-users"></i>
                                            <span><?php echo count(getTeamPlayers($team['id'])); ?> Players</span>
                                        </div>
                                    </div>
                                    
                                    <div class="team-actions">
                                        <a href="teams.php?edit=<?php echo $team['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <form method="post" action="" style="display: inline;">
                                            <input type="hidden" name="id" value="<?php echo $team['id']; ?>">
                                            <button type="submit" name="delete_team" class="btn btn-sm btn-danger" 
                                                    onclick="return confirm('Are you sure you want to delete this team? This action cannot be undone.')">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                        <a href="team_details.php?id=<?php echo $team['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i> View Details
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Summary Stats -->
                        <div class="mt-4">
                            <div class="dashboard-stats">
                                <div class="stat-card">
                                    <i class="fas fa-users"></i>
                                    <h3><?php echo count($teams); ?></h3>
                                    <p>Total Teams</p>
                                </div>
                                <div class="stat-card">
                                    <i class="fas fa-user-friends"></i>
                                    <h3><?php echo array_sum(array_map(fn($team) => count(getTeamPlayers($team['id'])), $teams)); ?></h3>
                                    <p>Total Players</p>
                                </div>
                                <div class="stat-card">
                                    <i class="fas fa-dollar-sign"></i>
                                    <h3><?php echo number_format(array_sum(array_column($teams, 'budget')), 0); ?></h3>
                                    <p>Total Budget</p>
                                </div>
                                <div class="stat-card">
                                    <i class="fas fa-coins"></i>
                                    <h3><?php echo number_format(array_sum(array_column($teams, 'remaining_budget')), 0); ?></h3>
                                    <p>Remaining Budget</p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>