<?php

require_once '../config/db.php';
require_once '../includes/functions.php';

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$email = trim($_POST['email'] ?? '');
	$password = $_POST['password'] ?? '';

	if ($email === '') {
		$errors[] = 'Email is required.';
	}

	if ($password === '') {
		$errors[] = 'Password is required.';
	}

	if (empty($errors)) {
		$stmt = $conn->prepare('SELECT id, name, password_hash, role, is_banned FROM users WHERE email = ?');
		$stmt->bind_param('s', $email);
		$stmt->execute();
		$result = $stmt->get_result();
		$user = $result->fetch_assoc();
		$stmt->close();

		if (!$user || !password_verify($password, $user['password_hash'])) {
			$errors[] = 'Invalid email or password.';
		} elseif ((int) $user['is_banned'] === 1) {
			$errors[] = 'Your account has been suspended. Contact admin.';
		} else {
			session_regenerate_id(true);

			$_SESSION['user_id'] = $user['id'];
			$_SESSION['name'] = $user['name'];
			$_SESSION['role'] = $user['role'];

			redirect('../index.php');
		}
	}
}

$page_title = 'Login — EWU Study Point';
require_once '../includes/header.php';
?>

<main>
	<div class="notice-card form-card">
		<h1>Log in</h1>

		<?php if (!empty($errors)): ?>
			<div class="form-errors">
				<ul>
					<?php foreach ($errors as $error): ?>
						<li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<form method="post" action="login.php">
			<div>
				<label for="email">Email</label>
				<input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" required>
			</div>

			<div>
				<label for="password">Password</label>
				<input type="password" id="password" name="password" required>
			</div>

			<button type="submit">Login</button>
		</form>

		<p>
			New user? <a href="register.php">Register here</a>
		</p>
	</div>
</main>

<?php require_once '../includes/footer.php'; ?>
