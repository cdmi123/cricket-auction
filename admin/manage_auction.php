<?php
require_once '../config/database.php';
require_once '../config/functions.php';
require_once '../includes/auth.php';
requireAdmin();

// Get all teams and players
$teams = getTeams();
$players = getPlayers();
$auction = getAuction();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_auction'])) {
        $name = $_POST['auction_name'];
        $startTime = $_POST['start_time'];
        $durationPerPlayer = $_POST['duration_per_player'];
        $minBidIncrement = $_POST['min_bid_increment'];
        
        try {
            $pdo->beginTransaction();
            
            // Create auction
            $stmt = $pdo->prepare("INSERT INTO auction (name, start_time, min_bid_increment, timer_seconds, status) 
                                  VALUES (?, ?, ?, ?, 'Upcoming')");
            $stmt->execute([$name, $startTime, $minBidIncrement, $durationPerPlayer]);
            $auctionId = $pdo->lastInsertId();
            
            // Calculate end time for each player
            $currentTime = new DateTime($startTime);
            $playerDuration = new DateInterval('PT' . $durationPerPlayer . 'S');
            
            // Create player schedule
            foreach ($players as $player) {
                $endTime = clone $currentTime;
                $endTime->add($playerDuration);
                
                $stmt = $pdo->prepare("INSERT INTO player_schedule (auction_id, player_id, start_time, end_time) 
                                      VALUES (?, ?, ?, ?)");
                $stmt->execute([$auctionId, $player['id'], $currentTime->format('Y-m-d H:i:s'), $endTime->format('Y-m-d H:i:s')]);
                
                $currentTime = clone $endTime;
                
                // Add a small break between players (5 seconds)
                $currentTime->add(new DateInterval('PT5S'));
            }
            
            $pdo->commit();
            $success = "Auction created successfully with player schedules!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error creating auction: " . $e->getMessage();
        }
    } elseif (isset($_POST['start_auction'])) {
        $auctionId = $_POST['auction_id'];
        
        // Get first player in schedule
        $stmt = $pdo->prepare("SELECT player_id FROM player_schedule WHERE auction_id = ? ORDER BY start_time ASC LIMIT 1");
        $stmt->execute([$auctionId]);
        $firstPlayer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($firstPlayer) {
            // Update auction status and set current player
            $stmt = $pdo->prepare("UPDATE auction SET status = 'Live', current_player_id = ? WHERE id = ?");
            $stmt->execute([$firstPlayer['player_id'], $auctionId]);
            
            // Set current player end time
            $stmt = $pdo->prepare("UPDATE auction SET current_player_end_time = 
                                  (SELECT end_time FROM player_schedule 
                                   WHERE auction_id = ? AND player_id = ?) 
                                  WHERE id = ?");
            $stmt->execute([$auctionId, $firstPlayer['player_id'], $auctionId]);
            
            $success = "Auction started successfully!";
        } else {
            $error = "No players found in the auction schedule.";
        }
    } elseif (isset($_POST['pause_auction'])) {
        $auctionId = $_POST['auction_id'];
        $stmt = $pdo->prepare("UPDATE auction SET status = 'Paused' WHERE id = ?");
        $stmt->execute([$auctionId]);
        $success = "Auction paused successfully!";
    } elseif (isset($_POST['resume_auction'])) {
        $auctionId = $_POST['auction_id'];
        $stmt = $pdo->prepare("UPDATE auction SET status = 'Live' WHERE id = ?");
        $stmt->execute([$auctionId]);
        $success = "Auction resumed successfully!";
    } elseif (isset($_POST['complete_auction'])) {
        $auctionId = $_POST['auction_id'];
        $stmt = $pdo->prepare("UPDATE auction SET status = 'Completed' WHERE id = ?");
        $stmt->execute([$auctionId]);
        $success = "Auction completed successfully!";
    }
}

// Get player schedules for the current auction
$schedules = [];
if ($auction) {
    $stmt = $pdo->prepare("SELECT ps.*, p.name AS player_name, p.country, p.role 
                          FROM player_schedule ps 
                          JOIN players p ON ps.player_id = p.id 
                          WHERE ps.auction_id = ? 
                          ORDER BY ps.start_time");
    $stmt->execute([$auction['id']]);
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<?php include_once '../includes/header.php' ?>

    <!-- Main Content -->
    <div class="main-container">
        <div class="container py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-cog"></i> Manage Auction</h1>
                <?php if ($auction && isset($auction['status'])): ?>
                    <div class="status-badge status-<?php echo strtolower($auction['status']); ?>">
                        <?php echo $auction['status']; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Alerts -->
            <?php if (isset($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <!-- Team Management Section -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3><i class="fas fa-users"></i> Team Management</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($teams)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-users fa-3x mb-3"></i>
                            <h5>No Teams Found</h5>
                            <p>Please add teams first before creating an auction.</p>
                            <a href="teams.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add Teams
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="team-grid">
                            <?php foreach ($teams as $team): ?>
                                <div class="team-card">
                                    <h4><i class="fas fa-shield-alt"></i> <?php echo htmlspecialchars($team['name']); ?></h4>
                                    <div class="team-budget">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Total Budget:</span>
                                            <span class="fw-bold"><?php echo number_format($team['budget'], 2); ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Remaining:</span>
                                            <span class="fw-bold text-success"><?php echo number_format($team['remaining_budget'], 2); ?></span>
                                        </div>
                                        <div class="budget-bar">
                                            <div class="budget-spent" style="width: <?php echo (($team['budget'] - $team['remaining_budget']) / $team['budget']) * 100; ?>%"></div>
                                        </div>
                                    </div>
                                    <div class="team-actions">
                                        <a href="teams.php?edit=<?php echo $team['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="teams.php?delete=<?php echo $team['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this team?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Player Management Section -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3><i class="fas fa-user-friends"></i> Player Management</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($players)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-user-friends fa-3x mb-3"></i>
                            <h5>No Players Found</h5>
                            <p>Please add players first before creating an auction.</p>
                            <a href="players.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add Players
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="dashboard-stats">
                            <div class="stat-card">
                                <i class="fas fa-users"></i>
                                <h3><?php echo count($players); ?></h3>
                                <p>Total Players</p>
                            </div>
                            <div class="stat-card">
                                <i class="fas fa-user-check"></i>
                                <h3><?php echo count(array_filter($players, fn($p) => $p['status'] === 'Available')); ?></h3>
                                <p>Available</p>
                            </div>
                            <div class="stat-card">
                                <i class="fas fa-user-tag"></i>
                                <h3><?php echo count(array_filter($players, fn($p) => $p['status'] === 'Sold')); ?></h3>
                                <p>Sold</p>
                            </div>
                            <div class="stat-card">
                                <i class="fas fa-user-times"></i>
                                <h3><?php echo count(array_filter($players, fn($p) => $p['status'] === 'Unsold')); ?></h3>
                                <p>Unsold</p>
                            </div>
                        </div>
                        
                        <div class="action-buttons">
                            <a href="players.php" class="btn btn-primary">
                                <i class="fas fa-cog"></i> Manage Players
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Auction Creation Section -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3><i class="fas fa-plus-circle"></i> Create New Auction</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($teams) || empty($players)): ?>
                        <div class="alert alert-warning" role="alert">
                            <i class="fas fa-exclamation-triangle"></i> Please add teams and players before creating an auction.
                        </div>
                    <?php else: ?>
                        <form method="post" action="">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="auction_name" class="form-label">Auction Name</label>
                                        <input type="text" class="form-control" id="auction_name" name="auction_name" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="start_time" class="form-label">Start Time</label>
                                        <input type="datetime-local" class="form-control" id="start_time" name="start_time" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="duration_per_player" class="form-label">Duration per Player (seconds)</label>
                                        <input type="number" class="form-control" id="duration_per_player" name="duration_per_player" min="10" max="300" value="30" required>
                                        <div class="form-text">Recommended: 30-60 seconds per player</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="min_bid_increment" class="form-label">Minimum Bid Increment ($)</label>
                                        <input type="number" class="form-control" id="min_bid_increment" name="min_bid_increment" min="1000" step="1000" value="10000" required>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" name="create_auction" class="btn btn-success">
                                <i class="fas fa-plus"></i> Create Auction
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Current Auction Section -->
            <?php if ($auction): ?>
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-gavel"></i> Current Auction: <?php echo htmlspecialchars($auction['name']); ?></h3>
                    </div>
                    <div class="card-body">
                        <div class="dashboard-stats">
                            <div class="stat-card">
                                <i class="fas fa-info-circle"></i>
                                <h3><?php echo ucfirst($auction['status']); ?></h3>
                                <p>Status</p>
                            </div>
                            
                            <div class="stat-card">
                                <i class="fas fa-calendar"></i>
                                <h3><?php echo date('M j', strtotime($auction['start_time'])); ?></h3>
                                <p>Start Date</p>
                            </div>
                            
                            <div class="stat-card">
                                <i class="fas fa-clock"></i>
                                <h3><?php echo $auction['timer_seconds']; ?>s</h3>
                                <p>Timer Duration</p>
                            </div>
                            
                            <div class="stat-card">
                                <i class="fas fa-dollar-sign"></i>
                                <h3><?php echo number_format($auction['min_bid_increment'], 0); ?></h3>
                                <p>Min Bid Increment</p>
                            </div>
                        </div>
                        
                        <div class="action-buttons">
                            <?php if ($auction['status'] === 'Upcoming'): ?>
                                <form method="post" action="" style="display: inline;">
                                    <input type="hidden" name="auction_id" value="<?php echo $auction['id']; ?>">
                                    <button type="submit" name="start_auction" class="btn btn-success">
                                        <i class="fas fa-play"></i> Start Auction
                                    </button>
                                </form>
                            <?php elseif ($auction['status'] === 'Live'): ?>
                                <form method="post" action="" style="display: inline;">
                                    <input type="hidden" name="auction_id" value="<?php echo $auction['id']; ?>">
                                    <button type="submit" name="pause_auction" class="btn btn-warning">
                                        <i class="fas fa-pause"></i> Pause Auction
                                    </button>
                                </form>
                            <?php elseif ($auction['status'] === 'Paused'): ?>
                                <form method="post" action="" style="display: inline;">
                                    <input type="hidden" name="auction_id" value="<?php echo $auction['id']; ?>">
                                    <button type="submit" name="resume_auction" class="btn btn-success">
                                        <i class="fas fa-play"></i> Resume Auction
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                            <?php if ($auction['status'] !== 'Completed'): ?>
                                <form method="post" action="" style="display: inline;">
                                    <input type="hidden" name="auction_id" value="<?php echo $auction['id']; ?>">
                                    <button type="submit" name="complete_auction" class="btn btn-danger">
                                        <i class="fas fa-stop"></i> Complete Auction
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                            <a href="../auction_realtime.php" class="btn btn-primary">
                                <i class="fas fa-eye"></i> View Auction
                            </a>
                        </div>
                        
                        <!-- Player Schedule -->
                        <div class="mt-4">
                            <h4><i class="fas fa-calendar-alt"></i> Player Schedule</h4>
                            <?php if (empty($schedules)): ?>
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-calendar-times fa-3x mb-3"></i>
                                    <p>No player schedule found. Create a new auction to generate schedules.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th><i class="fas fa-user"></i> Player</th>
                                                <th><i class="fas fa-flag"></i> Country</th>
                                                <th><i class="fas fa-tag"></i> Role</th>
                                                <th><i class="fas fa-clock"></i> Start Time</th>
                                                <th><i class="fas fa-clock"></i> End Time</th>
                                                <th><i class="fas fa-info-circle"></i> Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($schedules as $schedule): ?>
                                                <tr>
                                                    <td><strong><?php echo htmlspecialchars($schedule['player_name']); ?></strong></td>
                                                    <td><?php echo htmlspecialchars($schedule['country']); ?></td>
                                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($schedule['role']); ?></span></td>
                                                    <td><?php echo date('M j, g:i A', strtotime($schedule['start_time'])); ?></td>
                                                    <td><?php echo date('M j, g:i A', strtotime($schedule['end_time'])); ?></td>
                                                    <td>
                                                        <?php 
                                                        $now = new DateTime();
                                                        $startTime = new DateTime($schedule['start_time']);
                                                        $endTime = new DateTime($schedule['end_time']);
                                                        
                                                        if ($now < $startTime) {
                                                            echo '<span class="badge bg-info">Upcoming</span>';
                                                        } elseif ($now >= $startTime && $now <= $endTime) {
                                                            echo '<span class="badge bg-success">Live</span>';
                                                        } else {
                                                            echo '<span class="badge bg-secondary">Completed</span>';
                                                        }
                                                        ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>