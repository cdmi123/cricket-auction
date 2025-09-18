<?php
// admin/team_details.php
// Displays all team data for admin

include '../includes/header.php';
include '../config/database.php';

// Check if team id is provided
$teamId = isset($_GET['id']) ? intval($_GET['id']) : null;
if ($teamId) {
    $sql = "SELECT * FROM teams WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$teamId]);
} else {
    $sql = "SELECT * FROM teams";
    $stmt = $pdo->query($sql);
}
?>
<style>
body {
    background: linear-gradient(135deg, #e3eafc 0%, #fff 100%);
    font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
    margin: 0;
    padding: 0;
}
.auction-header {
    width: 100%;
    background: linear-gradient(90deg, #388e3c 0%, black 100%);
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    padding: 2rem 0 1rem 0;
    text-align: center;
    margin-bottom: 0;
    color: #fff;
}
.auction-header h1 {
    font-size: 2.8rem;
    font-weight: 700;
    letter-spacing: 1px;
    margin-bottom: 0.5rem;
    color: #fff;
    text-shadow: 1px 1px 6px rgba(56,142,60,0.12);
}
.auction-header .subtitle {
    font-size: 1.2rem;
    color: #ffe082;
    margin-bottom: 0.5rem;
}
.auction-header .nav {
    margin-top: 1rem;
    font-size: 1rem;
}
.auction-header .nav a {
    color: #fff;
    margin: 0 1rem;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.2s;
}
.auction-header .nav a:hover {
    color: black;
}
.team-section-title {
    font-size: 2.5rem;
    font-weight: 700;
    text-align: center;
    margin-bottom: 2rem;
    letter-spacing: 1px;
    color: #1976d2;
}
.team-tabs {
    display: flex;
    justify-content: center;
    gap: 1rem;
    margin-bottom: 2rem;
    flex-wrap: wrap;
}
.team-tab {
    background: #f5f5f5;
    border: none;
    padding: 0.5rem 1.5rem;
    font-weight: 600;
    border-radius: 20px;
    cursor: pointer;
    transition: background 0.2s;
    font-size: 1.1rem;
}
.team-tab.active, .team-tab:hover {
    background: #1976d2;
    color: #fff;
}
.player-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 2.5rem 2rem;
    justify-content: center;
    margin-bottom: 3rem;
}
.player-card {
    width: 220px;
    text-align: center;
    background: #fff;
    border-radius: 24px;
    box-shadow: 0 4px 24px rgba(25,118,210,0.10);
    padding: 2rem 1rem 1.5rem 1rem;
    position: relative;
    margin-bottom: 1rem;
    transition: box-shadow 0.2s;
    border: 2px solid #e3eafc;
}
.player-card:hover {
    box-shadow: 0 8px 32px rgba(25,118,210,0.10);
    border-color: #1976d2;
}
.player-img {
    width: 90px;
    height: 90px;
    object-fit: cover;
    border-radius: 50%;
    border: 3px solid #1976d2;
    margin-bottom: 1rem;
    background: #e3eafc;
    box-shadow: 0 2px 8px rgba(25,118,210,0.08);
}
.player-name {
    font-weight: 700;
    font-size: 1.15rem;
    margin-bottom: 0.2rem;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}
.player-role {
    font-size: 1rem;
    color: #1976d2;
    font-weight: 600;
    margin-bottom: 0.2rem;
}
.player-country {
    font-size: 0.95rem;
    color: #888;
    margin-bottom: 0.2rem;
    text-transform: uppercase;
}
.player-base {
    font-size: 0.95rem;
    color: #1976d2;
    font-weight: 600;
}
.player-sold {
    font-size: 0.95rem;
    color: black;
    font-weight: 600;
}
.auction-footer {
    width: 100%;
    background: linear-gradient(90deg, #388e3c 0%, black 100%);
    box-shadow: 0 -2px 8px rgba(56,142,60,0.04);
    text-align: center;
    padding: 2rem 0 1rem 0;
    font-size: 1rem;
    color: #fff;
    position: fixed;
    left: 0;
    bottom: 0;
    z-index: 10;
}
.auction-footer .footer-heart {
    color: #ffe082;
    font-size: 1.2rem;
    vertical-align: middle;
}
</style>
<div class="container" style="max-width:1200px;margin:0 auto;min-height:70vh;">
    <div class="team-section-title">
        <?php
        // Get all teams for tabs
        $teamsTabStmt = $pdo->query("SELECT id, name FROM teams");
        $teamsTab = $teamsTabStmt->fetchAll(PDO::FETCH_ASSOC);
        $activeTeamId = isset($_GET['id']) ? intval($_GET['id']) : ($teamsTab[0]['id'] ?? null);
        if ($activeTeamId) {
            $selectedTeam = null;
            foreach ($teamsTab as $tabTeam) {
                if ($tabTeam['id'] == $activeTeamId) {
                    $selectedTeam = $tabTeam['name'];
                    break;
                }
            }
            echo 'Meet the Team: <span style="color:black">' . htmlspecialchars($selectedTeam) . '</span>';
        } else {
            echo 'Meet the Teams';
        }
        ?>
    </div>
    <?php
    // Get all teams for tabs
    $teamsTabStmt = $pdo->query("SELECT id, name FROM teams");
    $teamsTab = $teamsTabStmt->fetchAll(PDO::FETCH_ASSOC);
    $activeTeamId = isset($_GET['id']) ? intval($_GET['id']) : ($teamsTab[0]['id'] ?? null);
    ?>
    <div class="team-tabs">
        <!-- <?php foreach ($teamsTab as $tabTeam): ?>
            <a href="?id=<?= $tabTeam['id'] ?>" class="team-tab<?= ($activeTeamId == $tabTeam['id']) ? ' active' : '' ?>">
                <?= htmlspecialchars($tabTeam['name']) ?>
            </a>
        <?php endforeach; ?> -->
        <!-- <a href="team_details.php" class="team-tab<?= (!isset($_GET['id'])) ? ' active' : '' ?>">All</a> -->
    </div>
    <div class="player-grid">
        <?php
        // Show only selected team or all
        if ($activeTeamId) {
            $teamIds = [$activeTeamId];
        } else {
            $teamIds = array_column($teamsTab, 'id');
        }
        $hasPlayers = false;
        foreach ($teamIds as $teamId) {
            $teamName = '';
            foreach ($teamsTab as $tabTeam) {
                if ($tabTeam['id'] == $teamId) {
                    $teamName = htmlspecialchars($tabTeam['name']);
                    break;
                }
            }
            $playersStmt = $pdo->prepare("SELECT name, country, role, base_price, sold_price, image FROM players WHERE sold_to = ?");
            $playersStmt->execute([$teamId]);
            $players = $playersStmt->fetchAll(PDO::FETCH_ASSOC);
            if ($players && count($players) > 0) {
                $hasPlayers = true;
                foreach ($players as $player):
        ?>
            <div class="player-card">
                <img src="../uploads/<?= htmlspecialchars($player['image'] ?? 'default.png') ?>" alt="Player" class="player-img">
                <div class="player-name"> <?= htmlspecialchars($player['name']) ?> </div>
                <div class="player-role"> <?= htmlspecialchars($player['role']) ?> </div>
                <div class="player-country"> <?= htmlspecialchars($player['country']) ?> </div>
                <div class="player-base">Base: <?= htmlspecialchars($player['base_price']) ?></div>
                <?php if ($player['sold_price']): ?>
                    <div class="player-sold">Sold: <?= htmlspecialchars($player['sold_price']) ?></div>
                <?php endif; ?>
            </div>
        <?php
                endforeach;
            }
        }
        if (!$hasPlayers): ?>
            <div class="col-12"><div class="alert alert-info">No players assigned.</div></div>
        <?php endif; ?>
    </div>
</div>

</div>
<?php include '../includes/footer.php'; ?>