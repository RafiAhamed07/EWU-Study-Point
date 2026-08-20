<?php

require_once '../config/db.php';
require_once '../includes/functions.php';

// Auto-create password_resets table if not exists for seamless XAMPP execution
$conn->query("
	CREATE TABLE IF NOT EXISTS password_resets (
		id INT UNSIGNED NOT NULL AUTO_INCREMENT,
		email VARCHAR(191) NOT NULL,
		otp VARCHAR(10) NOT NULL,
		expires_at TIMESTAMP NOT NULL,
		created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY idx_password_resets_email (email)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

if (is_logged_in()) {
	redirect('../index.php');
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$email = trim($_POST['email'] ?? '');

	if ($email === '') {
		$errors[] = 'Please enter your email address.';
	} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$errors[] = 'Please enter a valid email address.';
	} else {
		// Check if user exists
		$stmt = $conn->prepare('SELECT id, name FROM users WHERE email = ?');
		$stmt->bind_param('s', $email);
		$stmt->execute();
		$user = $stmt->get_result()->fetch_assoc();
		$stmt->close();

		if (!$user) {
			$errors[] = 'No account found with this email address.';
		} else {
			// Generate 6-digit numeric OTP
			$otp = sprintf('%06d', random_int(100000, 999999));

			// Remove old OTP records for this email
			$del_stmt = $conn->prepare('DELETE FROM password_resets WHERE email = ?');
			$del_stmt->bind_param('s', $email);
			$del_stmt->execute();
			$del_stmt->close();

			// Store OTP with 15-minute expiry
			$ins_stmt = $conn->prepare('INSERT INTO password_resets (email, otp, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE))');
			$ins_stmt->bind_param('ss', $email, $otp);
			$ins_stmt->execute();
			$ins_stmt->close();

			// Send email via PHP mail()
			$subject = 'EWU Study Point - Password Reset OTP';
			$message = "Hello " . $user['name'] . ",\r\n\r\n";
			$message .= "Your OTP for resetting your EWU Study Point password is: " . $otp . "\r\n";
			$message .= "This OTP is valid for 15 minutes.\r\n\r\n";
			$message .= "If you did not request this password reset, please ignore this email.\r\n\r\n";
			$message .= "Best regards,\r\nEWU Study Point Team";

			$headers = "From: noreply@std.ewubd.edu\r\n";
			$headers .= "Reply-To: noreply@std.ewubd.edu\r\n";
			$headers .= "X-Mailer: PHP/" . phpversion();

			$mail_sent = @mail($email, $subject, $message, $headers);

			$_SESSION['reset_email'] = $email;
			// Store in session for offline/XAMPP localhost testing if mail server is unconfigured
			$_SESSION['debug_otp'] = $otp;
			$_SESSION['info_message'] = 'An OTP has been generated and sent to ' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '.';

			redirect('reset_password.php');
		}
	}
}

$page_title = 'Forgot Password — EWU Study Point';
require_once '../includes/header.php';
?>

<main>
	<div class="notice-card form-card">
		<h1>Forgot Password</h1>
		<p style="color: #5F5E5A; margin-bottom: 16px; font-size: 13px;">
			Enter your registered EWU email address. We will send you a 6-digit OTP to reset your password.
		</p>

		<?php if (!empty($errors)): ?>
			<div class="form-errors">
				<ul>
					<?php foreach ($errors as $error): ?>
						<li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<form method="post" action="forgot_password.php">
			<div>
				<label for="email">Email Address</label>
				<input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" placeholder="e.g. yourname@std.ewubd.edu" required>
			</div>

			<button type="submit">Send OTP</button>
		</form>

		<p style="margin-top: 14px;">
			Remember your password? <a href="login.php">Back to Login</a>
		</p>
	</div>
</main>

<?php require_once '../includes/footer.php'; ?>
