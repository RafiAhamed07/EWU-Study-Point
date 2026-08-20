<?php
$page_title = $page_title ?? 'EWU Study Point';
$site_root = '/ewu_study_point/ewu-study-point';
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?></title>
	<link rel="stylesheet" href="<?php echo $site_root; ?>/assets/css/style.css">
</head>
<body>

<header class="site-header">
	<!-- Top Bar -->
	<div class="top-navbar">
		<a class="site-brand" href="<?php echo $site_root; ?>/index.php">
			<img src="<?php echo $site_root; ?>/assets/images/ewu_logo.png" alt="EWU Study Point" class="site-logo">
			<div class="brand-text">
				<span class="brand-title">EWU Study Point</span>
				<span class="brand-subtitle">East West University</span>
			</div>
		</a>

		<form class="header-search" method="GET" action="<?php echo $site_root; ?>/materials/index.php">
			<input type="text" name="search" placeholder="Search materials, books, course codes (e.g. CSE106, MAT101)..." aria-label="Search">
			<button type="submit">Search</button>
		</form>

		<div class="top-nav-actions">
			<?php if (!empty($_SESSION['user_id'])): ?>
				<a href="<?php echo $site_root; ?>/profile/view.php?id=<?php echo (int) $_SESSION['user_id']; ?>" class="user-greeting">
					<span>Hi, <?php echo htmlspecialchars(explode(' ', trim($_SESSION['name'] ?? 'Student'))[0], ENT_QUOTES, 'UTF-8'); ?></span>
				</a>
				<?php if (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
					<a href="<?php echo $site_root; ?>/admin/dashboard.php" class="badge badge-gold" style="padding: 6px 12px;">Admin Panel</a>
				<?php endif; ?>
				<a href="<?php echo $site_root; ?>/auth/logout.php" class="btn-nav-secondary">Logout</a>
			<?php else: ?>
				<a href="<?php echo $site_root; ?>/auth/login.php" class="btn-nav-secondary">Login</a>
				<a href="<?php echo $site_root; ?>/auth/register.php" class="btn-nav-primary">Register</a>
			<?php endif; ?>
		</div>
	</div>

	<!-- Retail Category Menu Bar -->
	<nav class="retail-menu-bar">
		<div class="retail-menu-container">
			<ul class="retail-nav-links">
				<!-- Study Materials Dropdown -->
				<li class="nav-item">
					<a href="<?php echo $site_root; ?>/materials/index.php" class="nav-link">
						<span>Study Materials</span>
						<span class="dropdown-arrow">&#9660;</span>
					</a>
					<div class="dropdown-menu">
						<div class="dropdown-header">Academic Resources</div>
						<a href="<?php echo $site_root; ?>/materials/index.php" class="dropdown-item">All Study Materials</a>
						<a href="<?php echo $site_root; ?>/materials/index.php?material_type=book" class="dropdown-item">Books &amp; Textbooks <span class="menu-tag-badge">New</span></a>
						<a href="<?php echo $site_root; ?>/materials/index.php?material_type=hand_notes" class="dropdown-item">Hand Notes</a>
						<a href="<?php echo $site_root; ?>/materials/index.php?material_type=lecture_sheet" class="dropdown-item">Lecture Sheets</a>
						<a href="<?php echo $site_root; ?>/materials/index.php?material_type=lecture_slide" class="dropdown-item">Lecture Slides</a>
						<a href="<?php echo $site_root; ?>/materials/index.php?material_type=term_paper" class="dropdown-item">Term Papers</a>
						<a href="<?php echo $site_root; ?>/materials/index.php?material_type=previous_question" class="dropdown-item">Previous Exam Questions</a>
						<div class="dropdown-divider"></div>
						<a href="<?php echo $site_root; ?>/materials/upload.php" class="dropdown-item" style="color: var(--ewu-blue); font-weight: 700;">+ Upload Study Material</a>
					</div>
				</li>

				<!-- Stationery Buy & Sell Dropdown -->
				<li class="nav-item">
					<a href="<?php echo $site_root; ?>/stationary/index.php" class="nav-link">
						<span>Stationery Market</span>
						<span class="menu-tag-badge">Buy &amp; Sell</span>
						<span class="dropdown-arrow">&#9660;</span>
					</a>
					<div class="dropdown-menu">
						<div class="dropdown-header">Student Marketplace</div>
						<a href="<?php echo $site_root; ?>/stationary/index.php" class="dropdown-item">Browse All Items</a>
						<a href="<?php echo $site_root; ?>/stationary/index.php?category=books" class="dropdown-item">Used Books &amp; Notes</a>
						<a href="<?php echo $site_root; ?>/stationary/index.php?category=calculator" class="dropdown-item">Scientific Calculators</a>
						<a href="<?php echo $site_root; ?>/stationary/index.php?category=lab_coat" class="dropdown-item">Lab Coats (CHE/PHY/BIO)</a>
						<a href="<?php echo $site_root; ?>/stationary/index.php?category=drawing_tools" class="dropdown-item">Drawing &amp; Drafting Tools</a>
						<a href="<?php echo $site_root; ?>/stationary/index.php?category=electronics" class="dropdown-item">Electronics &amp; Kits</a>
						<a href="<?php echo $site_root; ?>/stationary/index.php?category=stationery" class="dropdown-item">Notebooks &amp; General Stationery</a>
						<div class="dropdown-divider"></div>
						<a href="<?php echo $site_root; ?>/stationary/create.php" class="dropdown-item" style="color: var(--ewu-red); font-weight: 700;">+ Post an Item for Sale</a>
					</div>
				</li>

				<!-- Discussions Dropdown -->
				<li class="nav-item">
					<a href="<?php echo $site_root; ?>/discussions/index.php" class="nav-link">
						<span>Discussions</span>
						<span class="dropdown-arrow">&#9660;</span>
					</a>
					<div class="dropdown-menu">
						<div class="dropdown-header">Community Forum</div>
						<a href="<?php echo $site_root; ?>/discussions/index.php" class="dropdown-item">All Discussions</a>
						<a href="<?php echo $site_root; ?>/discussions/create.php" class="dropdown-item" style="color: var(--ewu-blue); font-weight: 700;">+ Ask a Question</a>
					</div>
				</li>
			</ul>

			<div style="display: flex; gap: 8px;">
				<a href="<?php echo $site_root; ?>/stationary/create.php" class="menu-sell-btn">+ Sell an Item</a>
			</div>
		</div>
	</nav>
</header>
