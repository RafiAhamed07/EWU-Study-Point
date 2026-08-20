<?php

require_once 'config/db.php';
require_once 'includes/functions.php';

$page_title = 'EWU Study Point — East West University Student Portal';
require_once 'includes/header.php';

// Fetch quick counts for hero metrics
$materials_cnt = (int) ($conn->query("SELECT COUNT(*) AS c FROM materials")->fetch_assoc()['c'] ?? 0);
$stationary_cnt = (int) ($conn->query("SELECT COUNT(*) AS c FROM stationary_items WHERE status = 'available'")->fetch_assoc()['c'] ?? 0);
$discussions_cnt = (int) ($conn->query("SELECT COUNT(*) AS c FROM discussions")->fetch_assoc()['c'] ?? 0);
?>

<main>
	<!-- Hero Section -->
	<div style="background: linear-gradient(135deg, #002B49 0%, #0A4A7A 100%); color: #FFFFFF; border-radius: 12px; padding: 48px 36px; margin-bottom: 36px; box-shadow: var(--shadow-md); position: relative; overflow: hidden;">
		<div style="max-width: 780px; position: relative; z-index: 2;">
			<span class="badge badge-gold" style="font-size: 12px; margin-bottom: 14px; padding: 4px 10px;">
				East West University Academic &amp; Student Hub
			</span>
			<h1 style="font-size: 34px; font-weight: 800; color: #FFFFFF; margin: 0 0 14px; line-height: 1.25; letter-spacing: -0.02em;">
				Study Smarter. Share Resources. Buy &amp; Sell Campus Essentials.
			</h1>
			<p style="font-size: 15.5px; color: #E2E8F0; line-height: 1.6; margin: 0 0 28px;">
				Access course lecture slides, previous semester question papers, textbooks, and buy or sell used calculators, lab coats, and engineering drawing tools all in one verified EWU student platform.
			</p>

			<div style="display: flex; gap: 14px; flex-wrap: wrap;">
				<a href="materials/index.php" class="btn-nav-primary" style="padding: 11px 22px; font-size: 14.5px;">
					📚 Browse Study Materials
				</a>
				<a href="stationary/index.php" class="btn-accent" style="padding: 11px 22px; font-size: 14.5px; background: var(--ewu-gold); color: #002B49 !important;">
					🛒 Stationery Marketplace
				</a>
				<a href="discussions/index.php" class="btn-secondary" style="padding: 11px 22px; font-size: 14.5px; background: rgba(255,255,255,0.15); color: #FFF; border-color: rgba(255,255,255,0.3);">
					💬 Community Discussions
				</a>
			</div>
		</div>
	</div>

	<!-- 3 Pillars Feature Grid -->
	<div style="margin-bottom: 36px;">
		<div style="text-align: center; margin-bottom: 24px;">
			<h2 style="font-size: 22px; font-weight: 800; color: var(--ewu-blue); margin: 0 0 6px;">Everything You Need For Your EWU Semester</h2>
			<p style="color: var(--text-muted); font-size: 14px; margin: 0;">Explore student-curated academic archives, peer marketplace, and class forum.</p>
		</div>

		<div class="card-grid" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));">
			<!-- Pillar 1: Materials -->
			<div class="item-card" onclick="window.location='materials/index.php'" style="padding: 24px;">
				<div style="font-size: 36px; margin-bottom: 12px;">📚</div>
				<h3 style="font-size: 18px; font-weight: 700; color: var(--ewu-blue); margin: 0 0 8px;">Study Materials &amp; Books</h3>
				<p style="color: var(--text-muted); font-size: 13.5px; line-height: 1.6; margin-bottom: 16px;">
					Download lecture notes, reference textbooks, presentation slides, and past midterm/final exam question papers organized by course code.
				</p>
				<div style="display: flex; justify-content: space-between; align-items: center; margin-top: auto; border-top: 1px solid var(--border-color); padding-top: 12px;">
					<span class="badge badge-blue"><?php echo $materials_cnt; ?> Resources Uploaded</span>
					<span style="font-weight: 700; font-size: 13px; color: var(--ewu-blue);">Explore &rarr;</span>
				</div>
			</div>

			<!-- Pillar 2: Stationery Marketplace -->
			<div class="item-card" onclick="window.location='stationary/index.php'" style="padding: 24px; border-top: 4px solid var(--ewu-gold);">
				<div style="font-size: 36px; margin-bottom: 12px;">🛒</div>
				<h3 style="font-size: 18px; font-weight: 700; color: var(--ewu-blue); margin: 0 0 8px;">Stationery &amp; Essentials</h3>
				<p style="color: var(--text-muted); font-size: 13.5px; line-height: 1.6; margin-bottom: 16px;">
					Buy and sell used scientific calculators (Casio fx-991EX), science lab coats, engineering drafters, sensor kits, and books at student-friendly prices.
				</p>
				<div style="display: flex; justify-content: space-between; align-items: center; margin-top: auto; border-top: 1px solid var(--border-color); padding-top: 12px;">
					<span class="badge badge-gold"><?php echo $stationary_cnt; ?> Items For Sale</span>
					<span style="font-weight: 700; font-size: 13px; color: var(--ewu-red);">Buy &amp; Sell &rarr;</span>
				</div>
			</div>

			<!-- Pillar 3: Discussions -->
			<div class="item-card" onclick="window.location='discussions/index.php'" style="padding: 24px;">
				<div style="font-size: 36px; margin-bottom: 12px;">💬</div>
				<h3 style="font-size: 18px; font-weight: 700; color: var(--ewu-blue); margin: 0 0 8px;">Community Discussions</h3>
				<p style="color: var(--text-muted); font-size: 13.5px; line-height: 1.6; margin-bottom: 16px;">
					Got stuck on a programming assignment, calculus derivation, or faculty review? Ask questions and discuss concepts directly with EWU students.
				</p>
				<div style="display: flex; justify-content: space-between; align-items: center; margin-top: auto; border-top: 1px solid var(--border-color); padding-top: 12px;">
					<span class="badge badge-green"><?php echo $discussions_cnt; ?> Discussions</span>
					<span style="font-weight: 700; font-size: 13px; color: var(--ewu-blue);">Join Forum &rarr;</span>
				</div>
			</div>
		</div>
	</div>
</main>

<?php require_once 'includes/footer.php'; ?>
