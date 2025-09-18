<?php
require_once '../config/database.php';
require_once '../config/functions.php';
require_once '../includes/auth.php';
requireTeam();

$teamId = $_SESSION['team_id'];
$team = $pdo->query("SELECT * FROM teams WHERE id = $teamId")->fetch(PDO::FETCH_ASSOC);

// Get team bids
$stmt = $pdo->prepare("SELECT b.*, p.name AS player_name, p.country, p.role, p.base_price, p.status, p.sold_price, p.sold_to, t2.name AS sold_to_team 
                      FROM bids b 
                      JOIN players p ON b.player_id = p.id 
                      LEFT JOIN teams t2 ON p.sold_to = t2.id 
                      WHERE b.team_id = ? 
                      ORDER BY b.bid_time DESC");
$stmt->execute([$teamId]);
$bids = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get team auto bids
$stmt = $pdo->prepare("SELECT ab.*, p.name AS player_name, p.country, p.role, p.base_price, p.status 
                      FROM auto_bids ab 
                      JOIN players p ON ab.player_id = p.id 
                      WHERE ab.team_id = ? 
                      ORDER BY ab.created_at DESC");
$stmt->execute([$teamId]);
$autoBids = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bids - <?php echo htmlspecialchars($team['name']); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .bids-container {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .bid-item {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            transition: transform 0.3s;
        }
        
        .bid-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .bid-item h3 {
            margin-bottom: 10px;
        }
        
        .bid-item .amount {
            font-weight: bold;
            color: #007bff;
            font-size: 18px;
        }
        
        .bid-item .time {
            font-size: 14px;
            color: #6c757d;
        }
        
        .bid-item .status {
            font-weight: bold;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .status-won {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-lost {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .status-active {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .auto-bids-container {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .auto-bid-item {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            transition: transform 0.3s;
        }
        
        .auto-bid-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .auto-bid-item h3 {
            margin-bottom: 10px;
        }
        
        .auto-bid-item .max-bid {
            font-weight: bold;
            color: #28a745;
            font-size: 18px;
        }
        
        .auto-bid-item .time {
            font-size: 14px;
            color: #6c757d;
        }
        
        .auto-bid-item .status {
            font-weight: bold;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .status-active {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .bid-actions {
            margin-top: 10px;
            display: flex;
            gap: 10px;
        }
    </style>
</head>
<body>
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
    
    <div class="container">
        <h1>My Bids</h1>
        
        <div class="bids-container">
            <h2>Bid History</h2>
            
            <?php if (empty($bids)): ?>
                <div class="alert alert-info">No bids placed yet.</div>
            <?php else: ?>
                <?php foreach ($bids as $bid): ?>
                    <div class="bid-item">
                        <h3><?php echo htmlspecialchars($bid['player_name']); ?></h3>
                        <p><?php echo htmlspecialchars($bid['country']); ?> | <?php echo htmlspecialchars($bid['role']); ?></p>
                        <p>Base Price: <?php echo number_format($bid['base_price'], 2); ?></p>
                        <p>Your Bid: <span class="amount"><?php echo number_format($bid['amount'], 2); ?></span></p>
                        <p class="time"><?php echo date('M j, Y g:i A', strtotime($bid['bid_time'])); ?></p>
                        
                        <?php if ($bid['status'] === 'Sold'): ?>
                            <?php if ($bid['sold_to'] == $teamId): ?>
                                <p class="status status-won">Won</p>
                                <p>Final Price: <?php echo number_format($bid['sold_price'], 2); ?></p>
                            <?php else: ?>
                                <p class="status status-lost">Lost</p>
                                <p>Sold to <?php echo htmlspecialchars($bid['sold_to_team']); ?> for <?php echo number_format($bid['sold_price'], 2); ?></p>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="status status-active">Active</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="auto-bids-container">
            <h2>Auto Bids</h2>
            
            <?php if (empty($autoBids)): ?>
                <div class="alert alert-info">No auto bids set.</div>
            <?php else: ?>
                <?php foreach ($autoBids as $bid): ?>
                    <div class="auto-bid-item">
                        <h3><?php echo htmlspecialchars($bid['player_name']); ?></h3>
                        <p><?php echo htmlspecialchars($bid['country']); ?> | <?php echo htmlspecialchars($bid['role']); ?></p>
                        <p>Base Price: <?php echo number_format($bid['base_price'], 2); ?></p>
                        <p>Maximum Bid: <span class="max-bid"><?php echo number_format($bid['max_bid'], 2); ?></span></p>
                        <p class="time"><?php echo date('M j, Y g:i A', strtotime($bid['created_at'])); ?></p>
                        
                        <?php if ($bid['status'] === 'Available'): ?>
                            <p class="status status-active">Active</p>
                        <?php else: ?>
                            <p class="status status-inactive">Inactive</p>
                        <?php endif; ?>
                        
                        <div class="bid-actions">
                            <form method="post" action="../api/set_auto_bid.php" style="display: inline;">
                                <input type="hidden" name="player_id" value="<?php echo $bid['player_id']; ?>">
                                <input type="hidden" name="team_id" value="<?php echo $teamId; ?>">
                                <input type="hidden" name="max_bid" value="0">
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to remove this auto bid?')">Remove</button>
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