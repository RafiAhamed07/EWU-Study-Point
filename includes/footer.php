<?php
$site_year = date('Y');
$site_root = $site_root ?? '/ewu_study_point/ewu-study-point';
?>
<footer class="site-footer">
	<div class="footer-container">
		<div class="footer-col">
			<h4>EWU Study Point</h4>
			<p style="font-size: 13px; line-height: 1.6; color: #94A3B8;">
				An open student-to-student platform for East West University students to share academic resources, discuss questions, and buy/sell everyday campus essentials.
			</p>
		</div>
		<div class="footer-col">
			<h4>Study Materials</h4>
			<ul>
				<li><a href="<?php echo $site_root; ?>/materials/index.php?material_type=book">Books &amp; Textbooks</a></li>
				<li><a href="<?php echo $site_root; ?>/materials/index.php?material_type=hand_notes">Hand Notes</a></li>
				<li><a href="<?php echo $site_root; ?>/materials/index.php?material_type=lecture_sheet">Lecture Sheets</a></li>
				<li><a href="<?php echo $site_root; ?>/materials/index.php?material_type=previous_question">Previous Exam Questions</a></li>
				<li><a href="<?php echo $site_root; ?>/materials/upload.php">Upload Resource</a></li>
			</ul>
		</div>
		<div class="footer-col">
			<h4>Stationery &amp; Marketplace</h4>
			<ul>
				<li><a href="<?php echo $site_root; ?>/stationary/index.php?category=calculator">Scientific Calculators</a></li>
				<li><a href="<?php echo $site_root; ?>/stationary/index.php?category=lab_coat">Lab Coats</a></li>
				<li><a href="<?php echo $site_root; ?>/stationary/index.php?category=drawing_tools">Drawing &amp; Drafting Tools</a></li>
				<li><a href="<?php echo $site_root; ?>/stationary/index.php?category=electronics">Electronics &amp; Kits</a></li>
				<li><a href="<?php echo $site_root; ?>/stationary/create.php">Sell an Item</a></li>
			</ul>
		</div>
		<div class="footer-col">
			<h4>Community</h4>
			<ul>
				<li><a href="<?php echo $site_root; ?>/discussions/index.php">Browse Discussions</a></li>
				<li><a href="<?php echo $site_root; ?>/discussions/create.php">Ask a Question</a></li>
				<li><a href="<?php echo $site_root; ?>/auth/login.php">Student Login</a></li>
				<li><a href="<?php echo $site_root; ?>/auth/register.php">Create Account</a></li>
			</ul>
		</div>
	</div>
	<div class="footer-bottom">
		<span>&copy; <?php echo $site_year; ?> EWU Study Point. Designed for East West University students.</span>
		<span>Built with standard PHP, MySQL, HTML &amp; CSS.</span>
	</div>
</footer>
</body>
</html>
