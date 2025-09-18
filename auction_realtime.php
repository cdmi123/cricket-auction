<?php
require_once 'config/database.php';
require_once 'config/functions.php';
require_once 'includes/auth.php';

// If not logged in, redirect to login
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Get auction details
$auction = getAuction();
$currentPlayer = getCurrentPlayer();
$highestBid = $currentPlayer ? getHighestBid($currentPlayer['id']) : null;
$allBids = $currentPlayer ? getPlayerBids($currentPlayer['id']) : [];

// Get team details if logged in as team
$team = null;
if (isTeam()) {
    $teamId = $_SESSION['team_id'];
    $team = $pdo->query("SELECT * FROM teams WHERE id = $teamId")->fetch(PDO::FETCH_ASSOC);
}

// Get all teams for team overview
$allTeams = getTeams();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Real-Time Cricket Auction</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <!-- Congratulations Overlay -->
    <div id="congrats-overlay" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;z-index:9999;background:rgba(0,0,0,0.7);justify-content:center;align-items:center;flex-direction:column;">
        <div style="color:#fff;font-size:2.5rem;font-weight:bold;text-align:center;margin-bottom:2rem;">Congratulations!<br>Player Sold!</div>
        <div id="cracker-animation"></div>
    </div>
    <header style="background:#fff; border-bottom:1px solid #e3eafc;">
        <div class="container-fluid px-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between py-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-gavel" style="color:#1976d2;font-size:1.7rem;"></i>
                    <span class="fs-4 fw-bold" style="color:#1976d2;letter-spacing:1px;">Cricket Auction System</span>
                </div>
                <nav>
                    <ul class="nav">
                        <li class="nav-item">
                            <a class="nav-link px-2 text-primary fw-bold" href="index.php">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link px-2 text-dark" href="logout.php">Logout</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>
    <!-- Sold Button for Admin to assign player and get next -->


    <!-- Sold Button Script -->
    <script>
// Real-time auction status update
function updateAuctionStatusRealtime() {
    fetch('api/check_auction_completion.php')
        .then(res => res.json())
        .then(data => {
            if (data.completed) {
                const statusBadge = document.querySelector('.status-badge');
                if (statusBadge && statusBadge.textContent !== 'Completed') {
                    statusBadge.textContent = 'Completed';
                    statusBadge.className = 'status-badge status-completed';
                    let mainContent = document.querySelector('.auction-main');
                    if (mainContent) {
                        mainContent.innerHTML = '<div class="text-center"><h2>Auction Completed</h2><p>All players have been sold.</p></div>';
                    }
                }
            }
        });
}
setInterval(updateAuctionStatusRealtime, 2000);
// Function to check if all players are sold and complete auction
function checkAllPlayersSoldAndCompleteAuction() {
    fetch('api/check_auction_completion.php')
        .then(res => res.json())
        .then(data => {
            if (data.completed) {
                fetch('api/set_auction_completed.php', { method: 'POST' })
                    .then(() => {
                        // Update auction status badge to Completed without reload
                        const statusBadge = document.querySelector('.status-badge');
                        if (statusBadge) {
                            statusBadge.textContent = 'Completed';
                            statusBadge.className = 'status-badge status-completed';
                        }
                        // Optionally, show a message or overlay
                        let mainContent = document.querySelector('.auction-main');
                        if (mainContent) {
                            mainContent.innerHTML = '<div class="text-center"><h2>Auction Completed</h2><p>All players have been sold.</p></div>';
                        }
                    });
            }
        });
}
    // Enable/disable Sold button based on bids
    function updateSoldButton() {
        const playerIdInput = document.getElementById('player-id');
        const soldBtn = document.getElementById('sold-btn');
        if (!playerIdInput || !soldBtn) return;
        const playerId = playerIdInput.value;
        fetch('api/get_player_bids.php?player_id=' + playerId)
            .then(res => res.json())
            .then(data => {
                if (Array.isArray(data) && data.length > 0) {
                    soldBtn.disabled = false;
                } else {
                    soldBtn.disabled = true;
                }
            });
    }
    setInterval(updateSoldButton, 1000);
    updateSoldButton();
    document.addEventListener('DOMContentLoaded', function() {
        var soldBtn = document.getElementById('sold-btn');
        var auctionCompletionInterval;
 

        if (soldBtn) {
            soldBtn.addEventListener('click', function() {
                soldBtn.disabled = true;
                soldBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                var playerId = document.getElementById('player-id').value;
                // Mark player as Sold first
                fetch('api/sell_player.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ player_id: playerId })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showCongratulations();
                        setTimeout(function() {
                            // Always use correct status filter and exclude current
                            var currentPlayerId = document.getElementById('player-id').value;
                            fetch('api/get_next_player.php?status=Unsold,Available&exclude_id=' + currentPlayerId)
                                .then(res => res.json())
                                .then(nextData => {
                                    if (nextData.success && nextData.player) {
                                        // Set bid amount to base price + 100000 when new player is loaded
                                        var bidInput = document.getElementById('bid-amount');
                                        if (bidInput && nextData.player.base_price) {
                                            bidInput.value = parseFloat(nextData.player.base_price) + 100000;
                                        }
                                        location.reload();
                                    } else {
                                        // If auction is completed, reload to show completed message
                                        if (nextData.message && nextData.message.toLowerCase().includes('auction completed')) {
                                            setTimeout(function() {
                                                location.reload();
                                            }, 500);
                                        } else {
                                            soldBtn.disabled = false;
                                            soldBtn.innerHTML = '<i class="fas fa-check-circle"></i> Sold & Next Player';
                                        }
                                    }
                                    // Call the new function to check auction completion
                                    checkAllPlayersSoldAndCompleteAuction();
                                });
                        }, 5000);
                    } else {
                        soldBtn.disabled = false;
                        soldBtn.innerHTML = '<i class="fas fa-check-circle"></i> Sold & Next Player';
                        checkAllPlayersSoldAndCompleteAuction();
                    }
                })
                .catch(() => {
                    soldBtn.disabled = false;
                    soldBtn.innerHTML = '<i class="fas fa-check-circle"></i> Sold & Next Player';
                    checkAllPlayersSoldAndCompleteAuction();
                });
            });
    // Cracker animation and congratulations
    function showCongratulations() {
        var overlay = document.getElementById('congrats-overlay');
        var cracker = document.getElementById('cracker-animation');
        overlay.style.display = 'flex';
        cracker.innerHTML = '';
        // Slower cracker effect: create 30 colored dots that animate outwards with staggered timing
        for (let i = 0; i < 30; i++) {
            let dot = document.createElement('div');
            dot.style.position = 'absolute';
            dot.style.width = '18px';
            dot.style.height = '18px';
            dot.style.borderRadius = '50%';
            dot.style.background = 'hsl(' + (i * 12) + ', 80%, 60%)';
            dot.style.left = '50%';
            dot.style.top = '50%';
            dot.style.transform = 'translate(-50%, -50%)';
            dot.style.opacity = '1';
            cracker.appendChild(dot);
            // Stagger the animation for each dot
            setTimeout(() => {
                dot.style.transition = 'all 2.5s cubic-bezier(.42,1.5,.58,1)';
                let angle = (i / 30) * 2 * Math.PI;
                let dist = 180 + Math.random() * 60;
                dot.style.left = (50 + Math.cos(angle) * dist) + '%';
                dot.style.top = (50 + Math.sin(angle) * dist) + '%';
                dot.style.opacity = '0';
            }, 300 + i * 80); // Each dot fires 80ms after the previous
        }
        setTimeout(function() {
            overlay.style.display = 'none';
            cracker.innerHTML = '';
        }, 5500);
    }
        }
    });
    </script>
    </li>
    </ul>
    </nav>
    </div>
    </div>
    </div>
    </header>

    <!-- Main Content -->
    <div class="main-container">
        <div class="auction-container">
            <!-- Main Auction Area -->
            <div class="auction-main">
                <!-- Auction Status -->
                <div class="text-center mb-4">
                    <h2>Live Auction</h2>
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

                <!-- Timer Display -->
                <!-- <div class="timer-display" id="timer-display"> -->
                <!-- </div> -->

                <!-- Current Player -->
                <?php if ($currentPlayer): ?>
                <div class="player-card">
                    <div id="player-info-section">
                        <!-- Player info will be loaded here by JS -->
                    </div>

                </div>
                <!-- Sold Button for Admin to assign player and get next -->
                <?php if (isAdmin() && $auction && $auction['status'] === 'Live' && $currentPlayer): ?>
                <div class="text-center mb-4">
                    <button id="sold-btn" class="btn btn-lg btn-success" <?php echo (empty($allBids) ? 'disabled' : ''); ?>><i class="fas fa-check-circle"></i> Sold & Next Player</button>
                    <button id="unsold-btn" class="btn btn-lg btn-danger ms-2" <?php echo (!empty($allBids) ? 'disabled' : ''); ?>><i class="fas fa-times-circle"></i> Unsold & Next Player</button>
                </div>
                <?php endif; ?>
                <!-- Sold Button Script -->
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    var soldBtn = document.getElementById('sold-btn');
                    var unsoldBtn = document.getElementById('unsold-btn');
                    if (soldBtn) {
                        soldBtn.addEventListener('click', function() {
                            soldBtn.disabled = true;
                            soldBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                            fetch('api/get_next_player.php')
                                .then(res => res.json())
                                .then(data => {
                                    if (data.success) {
                                        location.reload();
                                    } else {
                                        alert(data.message || 'Failed to move to next player.');
                                        soldBtn.disabled = false;
                                        soldBtn.innerHTML =
                                            '<i class="fas fa-check-circle"></i> Sold & Next Player';
                                    }
                                })
                                .catch(() => {
                                    alert('Error contacting server.');
                                    soldBtn.disabled = false;
                                    soldBtn.innerHTML =
                                    '<i class="fas fa-check-circle"></i> Sold & Next Player';
                                });
                        });
                    }
                    if (unsoldBtn) {
                        unsoldBtn.addEventListener('click', function() {
                            unsoldBtn.disabled = true;
                            unsoldBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                            var playerId = document.getElementById('player-id').value;
                            fetch('api/set_player_unsold.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ player_id: playerId })
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) {
                                    // Move to next unsold/available player only
                                    fetch('api/get_next_player.php?status=Unsold,Available&exclude_id=' + playerId)
                                        .then(res => res.json())
                                        .then(nextData => {
                                            if (nextData.success && nextData.player) {
                                                // Set bid amount to base price + 100000 when new player is loaded
                                                var bidInput = document.getElementById('bid-amount');
                                                if (bidInput && nextData.player.base_price) {
                                                    bidInput.value = parseFloat(nextData.player.base_price) + 100000;
                                                }
                                                setTimeout(function() {
                                                    location.reload();
                                                }, 500);
                                            } else {
                                                // If auction is completed, reload to show completed message
                                                if (nextData.message && nextData.message.toLowerCase().includes('auction completed')) {
                                                    setTimeout(function() {
                                                        location.reload();
                                                    }, 500);
                                                } else {
                                                    unsoldBtn.disabled = false;
                                                    unsoldBtn.innerHTML = '<i class="fas fa-times-circle"></i> Unsold & Next Player';
                                                }
                                            }
                                        });
                                } else {
                                    unsoldBtn.disabled = false;
                                    unsoldBtn.innerHTML = '<i class="fas fa-times-circle"></i> Unsold & Next Player';
                                }
                            })
                            .catch(() => {
                                unsoldBtn.disabled = false;
                                unsoldBtn.innerHTML = '<i class="fas fa-times-circle"></i> Unsold & Next Player';
                            });
                        });
                    }
                });
                </script>

                <!-- Current Bid -->
                <?php if (isTeam() && $team): ?>
                <div class="team-panel">
                    <h4><i class="fas fa-users"></i> Your Team</h4>
                    <h5><?php echo htmlspecialchars($team['name']); ?></h5>

                    <div class="team-budget">
                        <div class="d-flex justify-content-between">
                            <span>Budget Remaining:</span>
                            <input type="hidden" value="<?php echo $team['remaining_budget']; ?>" id="team-remaining-budget">
                            <span class="fw-bold"><?php echo number_format($team['remaining_budget'], 2); ?></span>
                        </div>
                        <div class="budget-bar">
                            <div class="budget-spent" style="width: <?php echo ($team['remaining_budget'] / 1000000) * 100; ?>%">
                            </div>
                        </div>`
                    </div>

                    <div class="team-players">
                        <h6>Your Players (<?php echo count(getTeamPlayers($team['id'])); ?>)</h6>
                        <?php foreach (getTeamPlayers($team['id']) as $player): ?>
                        <div class="team-player-item">
                            <div>
                                <strong><?php echo htmlspecialchars($player['name']); ?></strong>
                                <div class="player-role"><?php echo htmlspecialchars($player['role']); ?></div>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold"><?php echo number_format($player['sold_price'], 2); ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>


                <!-- Bidding Section -->
               
            </div>

            <!-- Sidebar -->
            <div class="auction-sidebar">
                <!-- Team Information -->

                <div class="current-bid">
                    <h4>Current Highest Bid</h4>
                    <?php if ($highestBid): ?>
                    <div class="bid-amount"><?php echo number_format($highestBid['amount'], 2); ?></div>
                    <p>By: <?php echo htmlspecialchars($highestBid['team_name']); ?></p>
                    <?php else: ?>
                    <div class="bid-amount">No Bids Yet</div>
                    <p>Be the first to bid!</p>
                    <?php endif; ?>
                </div>

                 <?php if (isTeam() && $auction && isset($auction['status']) && $auction['status'] === 'Live'): ?>
                <div class="bid-panel">
                    <h4>Place Your Bid</h4>

                    <!-- Quick Bid Buttons -->
                    <div class="quick-bid-buttons">
                        <?php
                                $currentAmount = $highestBid ? $highestBid['amount'] : $currentPlayer['base_price'];
                                // $increments = [1000, 5000, 10000, 25000, 50000, 100000];
                                $increments = [100000];

                                foreach ($increments as $increment):
                                    $bidAmount = $currentAmount + $increment;
                                ?>
                        <button class="quick-bid-btn" onclick="setBidAmount(<?php echo $bidAmount; ?>)">
                            +<?php echo number_format($increment); ?>
                        </button>
                        <?php endforeach; ?>
                    </div>

                    <!-- Manual Bid Input -->
                    <form id="bid-form">
                        <div class="input-group mb-3">
                            <!-- <span class="input-group-text"></span> -->
                            <input type="number" class="form-control" id="bid-amount" name="bid_amount" readonly
                                min="<?php echo ($highestBid ? $highestBid['amount'] + 1000 : $currentPlayer['base_price']); ?>"
                                step="1000" required>
                        </div>
                        <button type="submit" class="bid-button primary">
                            <i class="fas fa-gavel"></i> Place Bid
                        </button>
                    </form>
                </div>
                <?php endif; ?>
                <?php else: ?>
                <div class="auction-status">
                    <h2>No Player Currently Up for Auction</h2>
                    <p>The auction may be paused or completed.</p>
                </div>
                <?php endif; ?>
                <!-- All Teams Overview -->
                <!-- <div class="teams-panel">
                    <h4><i class="fas fa-trophy"></i> Teams Overview</h4>
                    <div class="team-grid">
                        <?php foreach ($allTeams as $teamData): ?>
                        <div class="team-card">
                            <h5><?php echo htmlspecialchars($teamData['name']); ?></h5>
                            <div class="budget"><?php echo number_format($teamData['budget'], 0); ?></div>
                            <div class="players-count">
                                <?php echo count(getTeamPlayers($teamData['id'])); ?> players
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div> -->

                <!-- Bid History -->
                <?php if ($currentPlayer): ?>
                <div class="bid-panel">
                    <h4><i class="fas fa-history"></i> Bid History</h4>
                    <div class="bid-history">
                        <?php foreach (array_reverse($allBids) as $bid): ?>
                        <div class="bid-item">
                            <div>
                                <div class="amount"><?php echo number_format($bid['amount'], 2); ?></div>
                                <div class="team"><?php echo htmlspecialchars($bid['team_name']); ?></div>
                            </div>
                            <div class="time"><?php echo date('H:i', strtotime($bid['bid_time'])); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Notification -->
    <!-- <div id="notification" class="notification"></div> -->

    <?php include 'includes/footer.php'; ?>

    <script>

    // Auto-refresh player info every 1 second
    function updatePlayerInfo() {
        fetch('api/get_current_player.php')
            .then(res => res.json())
            .then(data => {
                if (data.success && data.player) {
                    const player = data.player;
                    let html = `<input type="hidden" id="player-id" value="${player.id}">`;
                    if (player.image) {
                        html += `<img src="uploads/${player.image}" alt="${player.name}" class="player-image">`;
                    } else {
                        html += `<div class="player-placeholder"><i class="fas fa-user"></i></div>`;
                    }
                    html += `<h3>${player.name}</h3>`;
                    html += `<p class="text-muted">${player.role}</p>`;
                    html += `<div class="row">`;
                    html += `<div class="col-md-4"><div class="stat-item"><span class="stat-label">Age</span><span class="stat-value">${player.age}</span></div></div>`;
                    html += `<div class="col-md-4"><div class="stat-item"><span class="stat-label">Country</span><span class="stat-value">${player.country}</span></div></div>`;
                    html += `<div class="col-md-4"><div class="stat-item"><span class="stat-label">Base Price</span><span class="stat-value">${parseFloat(player.base_price).toLocaleString(undefined, {minimumFractionDigits:2})}</span></div></div>`;
                    html += `</div>`;
                    document.getElementById('player-info-section').innerHTML = html;
                    // Set bid input value to base price + 100000 or highest bid + 100000
                    var bidInput = document.getElementById('bid-amount');
                    if (bidInput) {
                        // If there is a highest bid, use that, else use base price
                        fetch('api/get_highest_bid.php?player_id=' + player.id)
                            .then(res => res.json())
                            .then(bidData => {
                                let startBid = 0;
                                if (bidData && bidData.amount) {
                                    startBid = parseFloat(bidData.amount) + 100000;
                                } else if (player.base_price) {
                                    startBid = parseFloat(player.base_price) + 100000;
                                }
                                bidInput.value = startBid;
                            });
                    }
                }
            });
    }
    setInterval(updatePlayerInfo, 1000);
    updatePlayerInfo();

   // Utility: Format numbers safely
function number_format(number, decimals = 2) {
    let n = parseFloat(number);
    if (isNaN(n)) return "0.00";
    return n.toLocaleString("en-IN", {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    });
}

// Handle bid form submission with AJAX
$('#bid-form').on('submit', function (e) {
    e.preventDefault();
    var playerId = $('#player-id').val();
    var bidAmount = $('#bid-amount').val();
    var $btn = $(this).find('button[type="submit"]');
    var $unsoldBtn = $('#unsold-btn');

    if (!bidAmount || !playerId) return;

    // Disable Unsold button automatically when placing a bid
    if ($unsoldBtn.length) {
        $unsoldBtn.prop('disabled', true);
    }

    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Placing Bid...');

    $.ajax({
        url: 'api/place_bid.php',
        method: 'POST',
        data: { player_id: playerId, bid_amount: bidAmount },
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                updateCurrentBid(playerId, function () {
                    $btn.prop('disabled', false).html('<i class="fas fa-gavel"></i> Place Bid');
                });
                updateBidHistory(playerId);
                $('#bid-amount').val('');
            } else {
                $btn.prop('disabled', false).html('<i class="fas fa-gavel"></i> Place Bid');
                alert(response.message || 'Bid failed.');
            }
        },
        error: function () {
            $btn.prop('disabled', false).html('<i class="fas fa-gavel"></i> Place Bid');
            alert('Server error. Try again.');
        }
    });
});

// Set bid amount from quick bid buttons
function setBidAmount(amount) {
    $('#bid-amount').val(amount);
    validateBidAmount();
}

// Validate bid amount against remaining budget
function validateBidAmount() {
    var bidAmount = parseFloat($('#bid-amount').val());
    var remainingBudget = parseFloat($('.team-budget .fw-bold').text().replace(/,/g, ''));
    var $btn = $('#bid-form').find('button[type="submit"]');
    if (isNaN(bidAmount) || isNaN(remainingBudget)) {
        $btn.prop('hidden', true);
        return;
    }
    if (bidAmount > remainingBudget) {
        $btn.prop('disabled', true);
    } else {
        $btn.prop('disabled', false);
    }
}

// Listen for manual input changes
$('#bid-amount').on('input', validateBidAmount);

// Auto-refresh current bid every 1 second
setInterval(function() {
    var playerId = $('#player-id').val();
    if (playerId) {
        updateCurrentBid(playerId);
        updateBidHistory(playerId);
    }
}, 1000);

// Update current highest bid section
function updateCurrentBid(playerId, callback) {
    $.get('api/get_highest_bid.php', { player_id: playerId }, function (data) {
        var $unsoldBtn = $('#unsold-btn');
        if (data && data.amount !== undefined) {
            $('.current-bid .bid-amount').text(number_format(data.amount, 2));
            $('.current-bid p').text('By: ' + (data.team_name || 'Unknown'));
            // Disable Unsold button if there is any bid
            if ($unsoldBtn.length) {
                $unsoldBtn.prop('disabled', true);
            }
            // ...existing code...
            var minBid = parseFloat(data.amount) + 100000;
            var increments = [100000];
            var quickBidHtml = '';
            increments.forEach(function(inc) {
                var bidAmount = minBid;
                quickBidHtml += '<button class="quick-bid-btn" onclick="setBidAmount(' + bidAmount + ')">+' + number_format(inc, 0) + '</button>';
            });
            $('.quick-bid-buttons').html(quickBidHtml);
            var currentVal = parseFloat($('#bid-amount').val());
            if(isNaN(currentVal)) {
                currentVal = 0;
            }
            var remainingBudget = parseFloat($('#team-remaining-budget').val());
            if (isNaN(currentVal) || remainingBudget < currentVal) {
                validateBidAmount();
            }
        } else {
            $('.current-bid .bid-amount').text('No Bids Yet');
            $('.current-bid p').text('Be the first to bid!');
            // Enable Unsold button if no bids
            if ($unsoldBtn.length) {
                $unsoldBtn.prop('disabled', false);
            }
            // ...existing code...
            var basePrice = parseFloat($('#bid-amount').attr('min'));
            if (isNaN(currentVal) || remainingBudget < basePrice) {
                validateBidAmount();
            }
        }
        if (typeof callback === 'function') callback();
    }, 'json');
}

// Update bid history section
function updateBidHistory(playerId) {
    $.get('api/get_player_bids.php', { player_id: playerId }, function (data) {
        if (Array.isArray(data)) {
            var html = '';
            data.reverse().forEach(function (bid) {
                html += '<div class="bid-item">';
                html += '<div><div class="amount">' + number_format(bid.amount, 2) + '</div>';
                html += '<div class="team">' + bid.team_name + '</div></div>';
                html += '<div class="time">' + bid.bid_time.substring(0, 5) + '</div>';
                html += '</div>';
            });
            $('.bid-history').html(html);
        }
    }, 'json');
}
    </script>
</body>