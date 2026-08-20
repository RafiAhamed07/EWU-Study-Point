-- EWU Study Point Database Schema
-- MySQL 8+, InnoDB engine, utf8mb4 charset

CREATE TABLE IF NOT EXISTS users (
	id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	student_id VARCHAR(50) NOT NULL,
	name VARCHAR(150) NOT NULL,
	email VARCHAR(191) NOT NULL,
	password_hash VARCHAR(255) NOT NULL,
	department VARCHAR(150) NOT NULL,
	role ENUM('student', 'admin') NOT NULL DEFAULT 'student',
	is_banned BOOLEAN NOT NULL DEFAULT FALSE,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	UNIQUE KEY uq_users_student_id (student_id),
	UNIQUE KEY uq_users_email (email),
	CONSTRAINT chk_users_email_domain CHECK (
        LOWER(email) REGEXP '^[a-z0-9._%+-]+@std\\.ewubd\\.edu$'
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS discussions (
	id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	user_id INT UNSIGNED NOT NULL,
	title VARCHAR(255) NOT NULL,
	description TEXT NOT NULL,
	department VARCHAR(150) NOT NULL,
	course_name VARCHAR(150) NOT NULL,
	faculty_name VARCHAR(150) NOT NULL,
	topic VARCHAR(150) NOT NULL,
	vote_score INT NOT NULL DEFAULT 0,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	KEY idx_discussions_user_id (user_id),
	KEY idx_discussions_department_course_name (department, course_name),
	CONSTRAINT fk_discussions_user_id
		FOREIGN KEY (user_id) REFERENCES users (id)
		ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS discussion_attachments (
	id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	discussion_id INT UNSIGNED NOT NULL,
	file_path VARCHAR(255) NOT NULL,
	file_type VARCHAR(100) NOT NULL,
	PRIMARY KEY (id),
	KEY idx_discussion_attachments_discussion_id (discussion_id),
	CONSTRAINT fk_discussion_attachments_discussion_id
		FOREIGN KEY (discussion_id) REFERENCES discussions (id)
		ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS comments (
	id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	discussion_id INT UNSIGNED NOT NULL,
	user_id INT UNSIGNED NOT NULL,
	parent_comment_id INT UNSIGNED NULL,
	content TEXT NOT NULL,
	vote_score INT NOT NULL DEFAULT 0,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	KEY idx_comments_discussion_id (discussion_id),
	KEY idx_comments_user_id (user_id),
	KEY idx_comments_parent_comment_id (parent_comment_id),
	CONSTRAINT fk_comments_discussion_id
		FOREIGN KEY (discussion_id) REFERENCES discussions (id)
		ON DELETE CASCADE,
	CONSTRAINT fk_comments_user_id
		FOREIGN KEY (user_id) REFERENCES users (id)
		ON DELETE CASCADE,
	CONSTRAINT fk_comments_parent_comment_id
		FOREIGN KEY (parent_comment_id) REFERENCES comments (id)
		ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS comment_attachments (
	id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	comment_id INT UNSIGNED NOT NULL,
	file_path VARCHAR(255) NOT NULL,
	file_type VARCHAR(100) NOT NULL,
	PRIMARY KEY (id),
	KEY idx_comment_attachments_comment_id (comment_id),
	CONSTRAINT fk_comment_attachments_comment_id
		FOREIGN KEY (comment_id) REFERENCES comments (id)
		ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS materials (
	id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	user_id INT UNSIGNED NOT NULL,
	title VARCHAR(255) NOT NULL,
	department VARCHAR(150) NOT NULL,
	course_name VARCHAR(150) NOT NULL,
	faculty_name VARCHAR(150) NOT NULL,
	material_type ENUM('hand_notes', 'lecture_sheet', 'lecture_slide', 'term_paper', 'previous_question', 'book', 'other') NOT NULL,
	file_path VARCHAR(255) NOT NULL,
	uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	KEY idx_materials_user_id (user_id),
	KEY idx_materials_department_course_name (department, course_name),
	CONSTRAINT fk_materials_user_id
		FOREIGN KEY (user_id) REFERENCES users (id)
		ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS discussion_votes (
	id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	discussion_id INT UNSIGNED NOT NULL,
	user_id INT UNSIGNED NOT NULL,
	vote_type ENUM('up', 'down') NOT NULL,
	PRIMARY KEY (id),
	UNIQUE KEY uq_discussion_votes_discussion_user (discussion_id, user_id),
	KEY idx_discussion_votes_discussion_id (discussion_id),
	KEY idx_discussion_votes_user_id (user_id),
	CONSTRAINT fk_discussion_votes_discussion_id
		FOREIGN KEY (discussion_id) REFERENCES discussions (id)
		ON DELETE CASCADE,
	CONSTRAINT fk_discussion_votes_user_id
		FOREIGN KEY (user_id) REFERENCES users (id)
		ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS comment_votes (
	id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	comment_id INT UNSIGNED NOT NULL,
	user_id INT UNSIGNED NOT NULL,
	vote_type ENUM('up', 'down') NOT NULL,
	PRIMARY KEY (id),
	UNIQUE KEY uq_comment_votes_comment_user (comment_id, user_id),
	KEY idx_comment_votes_comment_id (comment_id),
	KEY idx_comment_votes_user_id (user_id),
	CONSTRAINT fk_comment_votes_comment_id
		FOREIGN KEY (comment_id) REFERENCES comments (id)
		ON DELETE CASCADE,
	CONSTRAINT fk_comment_votes_user_id
		FOREIGN KEY (user_id) REFERENCES users (id)
		ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reports (
	id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	reporter_id INT UNSIGNED NOT NULL,
	discussion_id INT UNSIGNED NULL,
	comment_id INT UNSIGNED NULL,
	material_id INT UNSIGNED NULL,
	reason TEXT NOT NULL,
	status ENUM('pending', 'reviewed', 'dismissed') NOT NULL DEFAULT 'pending',
	reviewed_by INT UNSIGNED NULL,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	reviewed_at TIMESTAMP NULL DEFAULT NULL,
	PRIMARY KEY (id),
	KEY idx_reports_reporter_id (reporter_id),
	KEY idx_reports_discussion_id (discussion_id),
	KEY idx_reports_comment_id (comment_id),
	KEY idx_reports_material_id (material_id),
	KEY idx_reports_reviewed_by (reviewed_by),
	CONSTRAINT fk_reports_reporter_id
		FOREIGN KEY (reporter_id) REFERENCES users (id)
		ON DELETE CASCADE,
	CONSTRAINT fk_reports_discussion_id
		FOREIGN KEY (discussion_id) REFERENCES discussions (id)
		ON DELETE CASCADE,
	CONSTRAINT fk_reports_comment_id
		FOREIGN KEY (comment_id) REFERENCES comments (id)
		ON DELETE CASCADE,
	CONSTRAINT fk_reports_material_id
		FOREIGN KEY (material_id) REFERENCES materials (id)
		ON DELETE CASCADE,
	CONSTRAINT fk_reports_reviewed_by
		FOREIGN KEY (reviewed_by) REFERENCES users (id)
		ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS password_resets (
	id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	email VARCHAR(191) NOT NULL,
	otp VARCHAR(10) NOT NULL,
	expires_at TIMESTAMP NOT NULL,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	KEY idx_password_resets_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stationary_items (
	id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	user_id INT UNSIGNED NOT NULL,
	title VARCHAR(255) NOT NULL,
	category ENUM('books', 'calculator', 'drawing_tools', 'lab_coat', 'electronics', 'stationery', 'other') NOT NULL,
	price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
	condition_type ENUM('new', 'like_new', 'used') NOT NULL DEFAULT 'used',
	contact_info VARCHAR(255) NOT NULL,
	description TEXT NOT NULL,
	image_path VARCHAR(255) NULL,
	status ENUM('available', 'sold') NOT NULL DEFAULT 'available',
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	KEY idx_stationary_category (category),
	KEY idx_stationary_user_id (user_id),
	CONSTRAINT fk_stationary_user_id
		FOREIGN KEY (user_id) REFERENCES users (id)
		ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;