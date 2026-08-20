<?php

require_once '../config/db.php';
require_once '../includes/functions.php';

if (is_logged_in()) {
	redirect('../index.php');
}

$email = $_SESSION['reset_email'] ?? trim($_GET['email'] ?? '');
if ($email === '') {
	redirect('forgot_password.php');
}

$errors = [];
$info_message = $_SESSION['info_message'] ?? '';
unset($_SESSION['info_message']);

$debug_otp = $_SESSION['debug_otp'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$email = trim($_POST['email'] ?? '');
	$otp = trim($_POST['otp'] ?? '');
	$password = trim($_POST['password'] ?? '');
	$confirm_password = trim($_POST['confirm_password'] ?? '');

	if ($email === '') {
		$errors[] = 'Email is required.';
	}

	if ($otp === '') {
		$errors[] = 'Please enter the 6-digit OTP.';
	}

	if (strlen($password) < 8) {
		$errors[] = 'New password must be at least 8 characters long.';
	}

	if ($password !== $confirm_password) {
		$errors[] = 'Password and confirm password do not match.';
	}

	if (empty($errors)) {
		// Check OTP validity and expiration
		$stmt = $conn->prepare('SELECT id FROM password_resets WHERE email = ? AND otp = ? AND expires_at >= NOW() ORDER BY id DESC LIMIT 1');
		$stmt->bind_param('ss', $email, $otp);
		$stmt->execute();
		$reset_record = $stmt->get_result()->fetch_assoc();
		$stmt->close();

		if (!$reset_record) {
			$errors[] = 'Invalid or expired OTP. Please check your code or request a new one.';
		} else {
			// Update user password
			$password_hash = password_hash($password, PASSWORD_DEFAULT);
			$update_stmt = $conn->prepare('UPDATE users SET password_hash = ? WHERE email = ?');
			$update_stmt->bind_param('ss', $password_hash, $email);
			$update_stmt->execute();
			$update_stmt->close();

			// Invalidate all OTPs for this email
			$del_stmt = $conn->prepare('DELETE FROM password_resets WHERE email = ?');
			$del_stmt->bind_param('s', $email);
			$del_stmt->execute();
			$del_stmt->close();

			// Clean up session
			unset($_SESSION['reset_email'], $_SESSION['debug_otp']);

			$_SESSION['success_message'] = 'Password has been reset successfully! Please log in with your new password.';
			redirect('login.php');
		}
	}
}

$page_title = 'Reset Password — EWU Study Point';
require_once '../includes/header.php';
?>

<main>
	<div class="notice-card form-card">
		<h1>Reset Password</h1>

		<?php if (!empty($info_message)): ?>
			<div style="background: rgba(47, 111, 78, 0.1); border: 1px solid var(--green); color: var(--green-text); border-radius: 4px; padding: 12px 16px; margin-bottom: 16px;">
				<?php echo htmlspecialchars($info_message, ENT_QUOTES, 'UTF-8'); ?>
			</div>
		<?php endif; ?>

		<?php if (!empty($debug_otp)): ?>
			<div style="background: rgba(242, 183, 5, 0.15); border: 1px solid var(--amber); color: var(--amber-text); border-radius: 4px; padding: 10px 14px; margin-bottom: 16px; font-size: 13px;">
				<strong>Local/XAMPP OTP Helper:</strong> Your OTP is <code><?php echo htmlspecialchars($debug_otp, ENT_QUOTES, 'UTF-8'); ?></code> (in case localhost mail server is not configured).
			</div>
		<?php endif; ?>

		<?php if (!empty($errors)): ?>
			<div class="form-errors">
				<ul>
					<?php foreach ($errors as $error): ?>
						<li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<form method="post" action="reset_password.php">
			<input type="hidden" name="email" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>">

			<div>
				<label>Account Email</label>
				<input type="text" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" disabled style="opacity: 0.8;">
			</div>

			<div>
				<label for="otp">Enter 6-Digit OTP</label>
				<input type="text" id="otp" name="otp" placeholder="e.g. 123456" maxlength="10" required>
			</div>

			<div>
				<label for="password">New Password (min. 8 characters)</label>
				<input type="password" id="password" name="password" required>
			</div>

			<div>
				<label for="confirm_password">Confirm New Password</label>
				<input type="password" id="confirm_password" name="confirm_password" required>
			</div>

			<button type="submit">Reset Password</button>
		</form>

		<p style="margin-top: 14px;">
			Didn't receive code? <a href="forgot_password.php">Request new OTP</a> &middot; <a href="login.php">Cancel</a>
		</p>
	</div>
</main>

<?php require_once '../includes/footer.php'; ?>
