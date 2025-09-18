<?php
require_once '../config/database.php';
require_once '../config/functions.php';
require_once '../includes/auth.php';
requireTeam();

$teamId = $_SESSION['team_id'];
$team = $pdo->query("SELECT * FROM teams WHERE id = $teamId")->fetch(PDO::FETCH_ASSOC);
$players = getTeamPlayers($teamId);
$stats = getTeamStats($teamId);
$auction = getAuction();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Dashboard - <?php echo htmlspecialchars($team['name']); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1><i class="fas fa-gavel"></i> Cricket Auction System</h1>
                </div>
                <div class="col-md-6 text-end">
                    <nav>
                        <ul class="navbar-nav flex-row justify-content-end">
                            <li class="nav-item">
                                <a class="nav-link active" href="dashboard.php">Team Dashboard</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="players.php">Players</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="bids.php">My Bids</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="../auction_realtime.php">Live Auction</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="../logout.php">Logout</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="main-container">
        <div class="container py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-users"></i> <?php echo htmlspecialchars($team['name']); ?> Dashboard</h1>
                <?php if ($auction && isset($auction['status'])): ?>
                    <div class="status-badge status-<?php echo strtolower($auction['status']); ?>">
                        <?php echo $auction['status']; ?>
                    </div>
                <?php else: ?>
                    <div class="status-badge status-upcoming">
                        No Auction
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Team Info -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h4><i class="fas fa-wallet"></i> Budget Information</h4>
                        </div>
                        <div class="card-body">
                            <div class="team-budget">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Total Budget:</span>
                                    <span class="fw-bold"><?php echo number_format($team['budget'], 2); ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Spent:</span>
                                    <span class="fw-bold text-danger"><?php echo number_format($stats['total_spent'], 2); ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-3">
                                    <span>Remaining:</span>
                                    <span class="fw-bold text-success"><?php echo number_format($team['budget'] - $stats['total_spent'], 2); ?></span>
                                </div>
                                <div class="budget-bar">
                                    <div class="budget-spent" style="width: <?php echo ($stats['total_spent'] / $team['budget']) * 100; ?>%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h4><i class="fas fa-chart-bar"></i> Team Statistics</h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6">
                                    <div class="stat-item">
                                        <span class="stat-label">Players</span>
                                        <span class="stat-value"><?php echo count($players); ?></span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-item">
                                        <span class="stat-label">Average Price</span>
                                        <span class="stat-value"><?php echo count($players) > 0 ? number_format($stats['total_spent'] / count($players), 0) : 0; ?></span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-item">
                                        <span class="stat-label">Batsmen</span>
                                        <span class="stat-value"><?php echo count(array_filter($players, fn($p) => $p['role'] === 'Batsman')); ?></span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-item">
                                        <span class="stat-label">Bowlers</span>
                                        <span class="stat-value"><?php echo count(array_filter($players, fn($p) => $p['role'] === 'Bowler')); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Team Players -->
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-user-friends"></i> Your Players (<?php echo count($players); ?>)</h4>
                </div>
                <div class="card-body">
                    <?php if (empty($players)): ?>
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-users fa-3x mb-3"></i>
                            <h5>No Players Yet</h5>
                            <p>Start bidding in the auction to build your team!</p>
                            <a href="../auction_realtime.php" class="btn btn-primary">
                                <i class="fas fa-gavel"></i> Join Auction
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="player-grid">
                            <?php foreach ($players as $player): ?>
                                <div class="card">
                                    <div class="card-body text-center">
                                        <?php if ($player['image']): ?>
                                            <img src="<?php echo htmlspecialchars($player['image']); ?>" alt="<?php echo htmlspecialchars($player['name']); ?>" class="player-image mb-3">
                                        <?php else: ?>
                                            <div class="player-placeholder mb-3">
                                                <i class="fas fa-user"></i>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <h5><?php echo htmlspecialchars($player['name']); ?></h5>
                                        <p class="text-muted mb-2"><?php echo htmlspecialchars($player['role']); ?></p>
                                        <div class="player-role mb-3"><?php echo htmlspecialchars($player['country']); ?></div>
                                        <div class="fw-bold text-success"><?php echo number_format($player['sold_price'], 2); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="mt-4">
                <h3>Quick Actions</h3>
                <div class="action-buttons">
                    <a href="../auction_realtime.php" class="btn btn-primary">
                        <i class="fas fa-gavel"></i> Join Live Auction
                    </a>
                    <a href="players.php" class="btn btn-success">
                        <i class="fas fa-user-friends"></i> View All Players
                    </a>
                    <a href="bids.php" class="btn btn-info">
                        <i class="fas fa-history"></i> My Bidding History
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>