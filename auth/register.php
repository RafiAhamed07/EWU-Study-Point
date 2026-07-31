<?php

require_once '../config/db.php';
require_once '../includes/functions.php';

$errors = [];
$name = '';
$student_id = '';
$email = '';
$department = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$name = sanitize_input($_POST['name'] ?? '');
	$student_id = sanitize_input($_POST['student_id'] ?? '');
	$email = sanitize_input($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
	$department = sanitize_input($_POST['department'] ?? '');

	if ($name === '') {
		$errors[] = 'Name is required.';
	}

	if ($student_id === '') {
		$errors[] = 'Student ID is required.';
	}

	if ($department === '') {
		$errors[] = 'Department is required.';
	}

	if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !validate_ewu_email($email)) {
		$errors[] = 'Please enter a valid EWU student email ending in @std.ewubd.edu.';
	}

	if (strlen($password) < 8) {
		$errors[] = 'Password must be at least 8 characters long.';
	}

	if ($password !== $confirm_password) {
		$errors[] = 'Password and confirm password do not match.';
	}

	if (empty($errors)) {
		$check_stmt = $conn->prepare('SELECT id FROM users WHERE email = ? OR student_id = ?');
		$check_stmt->bind_param('ss', $email, $student_id);
		$check_stmt->execute();
		$check_stmt->store_result();

		if ($check_stmt->num_rows > 0) {
			$errors[] = 'An account with this email or student ID already exists.';
		}

		$check_stmt->close();
	}

	if (empty($errors)) {
		$password_hash = password_hash($password, PASSWORD_DEFAULT);
		$role = 'student';

		$insert_stmt = $conn->prepare('INSERT INTO users (student_id, name, email, password_hash, department, role) VALUES (?, ?, ?, ?, ?, ?)');
		$insert_stmt->bind_param('ssssss', $student_id, $name, $email, $password_hash, $department, $role);
		$insert_stmt->execute();

		$_SESSION['user_id'] = $conn->insert_id;
		$_SESSION['name'] = $name;
		$_SESSION['role'] = $role;

		$insert_stmt->close();

		redirect('../index.php');
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Register</title>
</head>
<body>
	<h1>Create Account</h1>

	<?php if (!empty($errors)): ?>
		<ul>
			<?php foreach ($errors as $error): ?>
				<li><?php echo $error; ?></li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<form method="post" action="register.php">
		<div>
			<label for="name">Name</label>
			<input type="text" id="name" name="name" value="<?php echo $name; ?>" required>
		</div>

		<div>
			<label for="student_id">Student ID</label>
			<input type="text" id="student_id" name="student_id" value="<?php echo $student_id; ?>" required>
		</div>

		<div>
			<label for="email">Email</label>
			<input type="email" id="email" name="email" value="<?php echo $email; ?>" required>
		</div>

		<div>
			<label for="password">Password</label>
			<input type="password" id="password" name="password" required>
		</div>

		<div>
			<label for="confirm_password">Confirm Password</label>
			<input type="password" id="confirm_password" name="confirm_password" required>
		</div>

		<div>
			<label for="department">Department</label>
			<input type="text" id="department" name="department" value="<?php echo $department; ?>" required>
		</div>

		<button type="submit">Register</button>
	</form>
</body>
</html>
