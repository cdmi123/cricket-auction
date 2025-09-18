<?php

require_once '../config/database.php';
require_once '../config/functions.php';
require_once '../includes/auth.php';
requireAdmin();

// Include PhpSpreadsheet for Excel import
require_once '../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
// Handle Excel upload for bulk player import
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_players'])) {
	if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] === UPLOAD_ERR_OK) {
		$fileTmpPath = $_FILES['excel_file']['tmp_name'];
		$spreadsheet = IOFactory::load($fileTmpPath);
		$sheet = $spreadsheet->getActiveSheet();
		$rows = $sheet->toArray();
		$header = array_map('strtolower', $rows[0]);
		for ($i = 1; $i < count($rows); $i++) {
			$row = $rows[$i];
			$data = array_combine($header, $row);
			// Required fields: name, country, role, base_price
			if (empty($data['name']) || empty($data['country']) || empty($data['role']) || empty($data['base_price'])) continue;
			$specialty = isset($data['specialty']) ? $data['specialty'] : '';
			$statistics = isset($data['statistics']) ? $data['statistics'] : '';
			$image = '';
			$stmt = $pdo->prepare("INSERT INTO players (name, country, role, base_price, image, specialty, statistics) VALUES (?, ?, ?, ?, ?, ?, ?)");
			$stmt->execute([
				$data['name'],
				$data['country'],
				$data['role'],
				$data['base_price'],
				$image,
				$specialty,
				$statistics
			]);
		}
		header('Location: players.php');
		exit;
	}
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (isset($_POST['add_player'])) {
		$name = $_POST['name'];
		$country = $_POST['country'];
		$role = $_POST['role'];
		$basePrice = $_POST['base_price'];
		$specialty = $_POST['specialty'];
		$statistics = $_POST['statistics'];
		
		// Handle image upload
		$image = '';
		if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
			$uploadDir = '../uploads/';
			$image = time() . '_' . $_FILES['image']['name'];
			move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $image);
		}
		
		$stmt = $pdo->prepare("INSERT INTO players (name, country, role, base_price, image, specialty, statistics) VALUES (?, ?, ?, ?, ?, ?, ?)");
		$stmt->execute([$name, $country, $role, $basePrice, $image, $specialty, $statistics]);
		
		header('Location: players.php');
		exit;
	} elseif (isset($_POST['edit_player'])) {
		$id = $_POST['id'];
		$name = $_POST['name'];
		$country = $_POST['country'];
		$role = $_POST['role'];
		$basePrice = $_POST['base_price'];
		$specialty = $_POST['specialty'];
		$statistics = $_POST['statistics'];
		
		// Get current player data
		$stmt = $pdo->prepare("SELECT * FROM players WHERE id = ?");
		$stmt->execute([$id]);
		$currentPlayer = $stmt->fetch(PDO::FETCH_ASSOC);
		
		// Handle image upload
		$image = $currentPlayer['image'];
		if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
			$uploadDir = '../uploads/';
			// Delete old image if exists
			if ($currentPlayer['image'] && file_exists($uploadDir . $currentPlayer['image'])) {
				unlink($uploadDir . $currentPlayer['image']);
			}
			$image = time() . '_' . $_FILES['image']['name'];
			move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $image);
		}
		
		$stmt = $pdo->prepare("UPDATE players SET name = ?, country = ?, role = ?, base_price = ?, image = ?, specialty = ?, statistics = ? WHERE id = ?");
		$stmt->execute([$name, $country, $role, $basePrice, $image, $specialty, $statistics, $id]);
		
		header('Location: players.php');
		exit;
	} elseif (isset($_POST['delete_player'])) {
		$id = $_POST['id'];
		
		// Get player data to delete image
		$stmt = $pdo->prepare("SELECT * FROM players WHERE id = ?");
		$stmt->execute([$id]);
		$player = $stmt->fetch(PDO::FETCH_ASSOC);
		
		// Delete image if exists
		if ($player['image'] && file_exists('../uploads/' . $player['image'])) {
			unlink('../uploads/' . $player['image']);
		}
		
		$stmt = $pdo->prepare("DELETE FROM players WHERE id = ?");
		$stmt->execute([$id]);
		
		header('Location: players.php');
		exit;
	}
}

// Get player to edit
$editPlayer = null;
if (isset($_GET['edit'])) {
	$id = $_GET['edit'];
	$stmt = $pdo->prepare("SELECT * FROM players WHERE id = ?");
	$stmt->execute([$id]);
	$editPlayer = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get all players
$players = getPlayers();

// Get auction for header badge
$auction = getAuction();
?>

<?php include_once '../includes/header.php' ?>

	<!-- Main Content -->

	<div class="main-container">
		<div class="container py-4">
			<div class="d-flex justify-content-between align-items-center mb-4">
				<h1><i class="fas fa-user-friends"></i> Manage Players</h1>
				<div class="d-flex gap-2">
					<a href="dashboard.php" class="btn btn-outline-primary"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
					<a href="teams.php" class="btn btn-primary"><i class="fas fa-users"></i> Manage Teams</a>
				</div>
			</div>

			<!-- Excel Upload Form -->
			<div class="card mb-4">
				<div class="card-header bg-success text-white">
					<h3><i class="fas fa-file-excel"></i> Import Players from Excel</h3>
				</div>
				<div class="card-body">
					<form method="post" enctype="multipart/form-data">
						<div class="mb-3">
							<label for="excel_file" class="form-label">Select Excel File (.xlsx)</label>
							<input type="file" class="form-control" id="excel_file" name="excel_file" accept=".xls,.xlsx" required>
						</div>
						<button type="submit" name="import_players" class="btn btn-success"><i class="fas fa-upload"></i> Upload & Import</button>
					</form>
					<div class="form-text mt-2">Excel columns required: <b>name, country, role, base_price</b>. Optional: specialty, statistics</div>
				</div>
			</div>

			<!-- Player Form -->
			<div class="card mb-4">
				<div class="card-header">
					<h3><i class="fas fa-plus-circle"></i> <?php echo $editPlayer ? 'Edit Player' : 'Add New Player'; ?></h3>
				</div>
				<div class="card-body">
					<form method="post" action="" enctype="multipart/form-data">
						<?php if ($editPlayer): ?>
							<input type="hidden" name="id" value="<?php echo $editPlayer['id']; ?>">
						<?php endif; ?>

						<div class="row">
							<div class="col-md-6">
								<div class="mb-3">
									<label for="name" class="form-label">Player Name</label>
									<input type="text" class="form-control" id="name" name="name" value="<?php echo $editPlayer ? htmlspecialchars($editPlayer['name']) : ''; ?>" required>
								</div>
							</div>
							<div class="col-md-6">
								<div class="mb-3">
									<label for="country" class="form-label">Country</label>
									<input type="text" class="form-control" id="country" name="country" value="<?php echo $editPlayer ? htmlspecialchars($editPlayer['country']) : ''; ?>" required>
								</div>
							</div>
						</div>

						<div class="row">
							<div class="col-md-6">
								<div class="mb-3">
									<label for="role" class="form-label">Role</label>
									<select class="form-select" id="role" name="role" required>
										<option value="">Select Role</option>
										<option value="Batsman" <?php echo $editPlayer && $editPlayer['role'] === 'Batsman' ? 'selected' : ''; ?>>Batsman</option>
										<option value="Bowler" <?php echo $editPlayer && $editPlayer['role'] === 'Bowler' ? 'selected' : ''; ?>>Bowler</option>
										<option value="All-rounder" <?php echo $editPlayer && $editPlayer['role'] === 'All-rounder' ? 'selected' : ''; ?>>All-rounder</option>
										<option value="Wicket-keeper" <?php echo $editPlayer && $editPlayer['role'] === 'Wicket-keeper' ? 'selected' : ''; ?>>Wicket-keeper</option>
									</select>
								</div>
							</div>
							<div class="col-md-6">
								<div class="mb-3">
									<label for="base_price" class="form-label">Base Price ($)</label>
									<div class="input-group">
										<span class="input-group-text"><i class="fas fa-dollar-sign"></i></span>
										<input type="number" class="form-control" id="base_price" name="base_price" value="<?php echo $editPlayer ? $editPlayer['base_price'] : ''; ?>" min="0" step="1000" required>
									</div>
								</div>
							</div>
						</div>

						<div class="row">
							<div class="col-md-6">
								<div class="mb-3">
									<label for="specialty" class="form-label">Specialty</label>
									<input type="text" class="form-control" id="specialty" name="specialty" value="<?php echo $editPlayer ? htmlspecialchars($editPlayer['specialty']) : ''; ?>">
								</div>
							</div>
							<div class="col-md-6">
								<div class="mb-3">
									<label for="image" class="form-label">Player Image</label>
									<input type="file" class="form-control" id="image" name="image" accept="image/*">
									<?php if ($editPlayer && $editPlayer['image']): ?>
										<div class="mt-2"><img class="player-avatar" src="../uploads/<?php echo htmlspecialchars($editPlayer['image']); ?>" alt="<?php echo htmlspecialchars($editPlayer['name']); ?>"></div>
									<?php endif; ?>
								</div>
							</div>
						</div>

						<div class="mb-3">
							<label for="statistics" class="form-label">Player Statistics (JSON format)</label>
							<textarea class="form-control" id="statistics" name="statistics" rows="3">{"matches": 50, "runs": 1500, "wickets": 25, "average": 35.5}<?php echo $editPlayer ? htmlspecialchars($editPlayer['statistics']) : ''; ?></textarea>
							<div class="form-text">Example: {"matches": 50, "runs": 1500, "wickets": 25, "average": 35.5}</div>
						</div>

						<button type="submit" name="<?php echo $editPlayer ? 'edit_player' : 'add_player'; ?>" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo $editPlayer ? 'Update Player' : 'Add Player'; ?></button>
						<?php if ($editPlayer): ?>
							<a href="players.php" class="btn btn-outline-secondary"><i class="fas fa-times"></i> Cancel</a>
						<?php endif; ?>
					</form>
				</div>
			</div>

			<!-- Players List -->
			<div class="card">
				<div class="card-header">
					<h3><i class="fas fa-list"></i> All Players</h3>
				</div>
				<div class="card-body">
					<?php if (empty($players)): ?>
						<div class="text-center text-muted py-5">
							<i class="fas fa-user-friends fa-3x mb-3"></i>
							<p>No players found. Add players to get started.</p>
						</div>
					<?php else: ?>
						<div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4 g-3">
							<?php foreach ($players as $player): ?>
								<div class="col">
									<div class="card h-100">
										<div class="card-body">
											<div class="d-flex align-items-center mb-3">
												<?php if ($player['image']): ?>
													<img class="player-avatar me-2" src="../uploads/<?php echo htmlspecialchars($player['image']); ?>" alt="<?php echo htmlspecialchars($player['name']); ?>">
												<?php else: ?>
													<div class="player-avatar me-2 bg-light d-flex align-items-center justify-content-center"><i class="fas fa-user"></i></div>
												<?php endif; ?>
												<div>
													<h5 class="mb-0"><?php echo htmlspecialchars($player['name']); ?></h5>
													<small class="text-muted"><?php echo htmlspecialchars($player['country']); ?> • <?php echo htmlspecialchars($player['specialty']); ?></small>
												</div>
											</div>
											<div class="d-flex justify-content-between align-items-center mb-2">
												<span class="text-muted">Base Price</span>
												<strong><?php echo number_format($player['base_price'], 2); ?></strong>
											</div>
											<div class="mb-2">
												<span class="badge <?php echo strtolower($player['status']) === 'sold' ? 'bg-success' : (strtolower($player['status']) === 'unsold' ? 'bg-warning' : 'bg-info'); ?>">
													<?php echo htmlspecialchars($player['status']); ?>
												</span>
											</div>
											<?php if ($player['status'] === 'Sold'): ?>
												<div class="small text-muted">Sold to <strong><?php echo htmlspecialchars($player['team_name']); ?></strong> for <strong><?php echo number_format($player['sold_price'], 2); ?></strong></div>
											<?php endif; ?>
										</div>
										<div class="card-footer bg-transparent d-flex gap-2">
											<a href="players.php?edit=<?php echo $player['id']; ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i> Edit</a>
											<form method="post" action="" onsubmit="return confirm('Are you sure you want to delete this player?')">
												<input type="hidden" name="id" value="<?php echo $player['id']; ?>">
												<button type="submit" name="delete_player" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Delete</button>
											</form>
										</div>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
	<?php include '../includes/footer.php'; ?>
</body>
</html>