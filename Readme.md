# EWU Study Point

A web-based academic support platform built for the students of East West University (EWU) — combining a Stack Overflow–style discussion forum with a course-material repository, so students can ask doubts, help each other, and share study resources (hand notes, lecture slides, previous questions, and more) in one place, organized by course, faculty, and department.

---

## Table of Contents

1. [Purpose & Motivation](#purpose--motivation)
2. [Novelty](#novelty)
3. [Tech Stack](#tech-stack)
4. [Project Structure](#project-structure)
5. [Database Schema](#database-schema)
6. [Features](#features)
7. [Design System](#design-system)
8. [Setup & Installation](#setup--installation)
9. [Development Process & Workflow](#development-process--workflow)
10. [Challenges Faced & How They Were Solved](#challenges-faced--how-they-were-solved)
11. [Security Measures](#security-measures)
12. [Known Limitations](#known-limitations)
13. [Future Work](#future-work)
14. [Credits](#credits)

---

## Purpose & Motivation

University students constantly run into the same two problems: getting stuck on a course doubt with no easy way to ask classmates, and hunting for study materials (notes, slides, previous question papers) scattered across private group chats, personal drives, and disappearing chat threads. EWU Study Point solves both in a single, EWU-only platform:

- **Doubt resolution** — a threaded, votable discussion forum modeled on Stack Overflow, where any student can post a question tagged with its course/faculty/department, and others can answer, reply, and upvote the most useful responses.
- **Material sharing** — a structured repository where students upload PDFs, images, or zipped archives of hand notes, lecture sheets, lecture slides, term papers, and previous questions, tagged and searchable by course and faculty.

The goal is to reduce the friction of peer academic support and to build an institutional memory of course material and solved doubts that persists beyond a single semester or chat group.

## Novelty

While generic Q&A forums and generic file-sharing tools both exist, EWU Study Point's novelty is in combining both under one identity-verified, course-structured system:

- **University-scoped identity** — signup is restricted to EWU student email addresses, so every discussion and upload is tied to a real, verifiable member of the university community rather than an anonymous internet stranger.
- **Course-first organization** — every discussion and every material is tagged by department, course, and faculty name at the point of creation, rather than relying on generic hashtags or manual categorization after the fact.
- **Unified reputation** — a single vote score per student aggregates contributions across both discussions *and* comments, rewarding people who genuinely help others rather than just active posters.
- **Community moderation with admin backstop** — students can report any discussion, comment, or material; a full admin panel lets moderators review, dismiss, or directly remove content, with a dashboard summarizing platform health at a glance.

## Tech Stack

A deliberately framework-free, dependency-light stack, chosen for transparency and ease of deployment on shared/student hosting environments:

| Layer | Technology |
|---|---|
| Structure | HTML5 |
| Styling | CSS3 (custom, no framework) |
| Interactivity | Vanilla JavaScript |
| Server logic | PHP 8.2 |
| Database | MySQL (via `mysqli`, prepared statements throughout) |
| Local environment | XAMPP (Apache + MySQL + PHP on Windows) |

No PHP framework, ORM, or JS framework is used — every query is a hand-written prepared statement, and every page is a plain PHP file. This keeps the codebase approachable for students learning full-stack fundamentals and avoids dependency/version management overhead.

## Project Structure

```
ewu-study-point/
│
├── config/
│   └── db.php                    → MySQL connection (mysqli)
│
├── includes/
│   ├── auth_check.php            → guards pages requiring any logged-in user
│   ├── admin_check.php           → guards pages requiring the admin role
│   ├── functions.php             → shared helpers (sanitization, EWU email validation, redirect())
│   ├── header.php                → shared page shell: <head>, nav, opens <body>
│   └── footer.php                → shared footer, closes </body></html>
│
├── assets/
│   ├── css/style.css             → the entire site's styling (noticeboard design system)
│   ├── js/                       → reserved for future client-side interactivity
│   └── images/                   → logo, hero photo, static assets
│
├── uploads/
│   ├── discussions/              → images/PDFs attached to discussion posts
│   ├── comments/                 → images/PDFs attached to comments
│   └── materials/                → uploaded study material files (PDF/image/ZIP)
│
├── auth/
│   ├── register.php              → EWU-email-gated signup
│   ├── login.php
│   └── logout.php
│
├── discussions/
│   ├── index.php                 → feed: filter, search, paginate
│   ├── create.php                → new discussion + multi-file attachment upload
│   ├── view.php                  → single discussion, attachments, threaded comments, voting
│   ├── edit.php                  → owner-only edit
│   └── delete.php                → owner-only delete, with attachment cleanup
│
├── materials/
│   ├── index.php                 → browse: filter by department/course/type, search
│   └── upload.php                → single-file upload (PDF/image/ZIP) with type tagging
│
├── profile/
│   └── view.php                  → public profile: posts, uploads, combined vote score
│
├── admin/
│   ├── dashboard.php             → platform-wide summary stats
│   ├── users.php                 → user list, search, ban/unban
│   ├── reports.php               → pending reports queue, dismiss or escalate to delete
│   └── moderate.php              → direct admin-initiated content deletion
│
├── vote_handler.php              → upvote/downvote toggle logic (discussions + comments)
├── comment_handler.php           → comment/reply submission
├── comment_delete.php            → owner-initiated comment deletion
├── report_handler.php            → report submission (discussion/comment/material)
│
├── sql/
│   └── schema.sql                → full database schema
│
└── index.php                     → landing page / redirect to feed if logged in
```

## Database Schema

Nine tables, all InnoDB with foreign keys and `ON DELETE CASCADE` where content ownership implies content lifecycle:

| Table | Purpose |
|---|---|
| `users` | Student ID, name, EWU email, password hash, department, role (`student`/`admin`), ban status |
| `discussions` | Question posts: title, description, department/course/faculty/topic tags, vote score |
| `discussion_attachments` | Multiple images/PDFs per discussion |
| `comments` | Threaded answers/replies (self-referencing `parent_comment_id`), vote score |
| `comment_attachments` | Attachments on individual comments |
| `materials` | Uploaded study files: title, department/course/faculty, material type, single file path |
| `discussion_votes` | One row per user per discussion voted on; unique constraint prevents duplicate votes |
| `comment_votes` | Same pattern, scoped to comments |
| `reports` | User-submitted reports; exactly one of `discussion_id`/`comment_id`/`material_id` set per row, plus status (`pending`/`reviewed`/`dismissed`) and reviewer audit fields |

Vote scores are **denormalized** (stored directly on `discussions`/`comments` and updated on every vote action) rather than computed live via `SUM()`, keeping feed and detail pages fast without a join-and-aggregate on every page load. A student's total reputation on their profile page is computed on-demand as the sum of both tables' scores for that user, since profile views are infrequent enough not to need denormalization.

## Features

### Discussions (Q&A forum)
- Create, edit, and delete your own discussion posts, tagged with department, course, faculty name, and topic
- Attach multiple images and/or PDFs to a discussion
- Threaded comments and replies, rendered via true recursion (unlimited depth, indentation visually capped for readability)
- Upvote/downvote on both discussions and individual comments, with toggle-off and direction-switch logic
- Report any discussion or comment for admin review
- Delete your own comments (cascades to any replies underneath)

### Study Materials
- Upload PDFs, images (JPEG/PNG), or ZIP archives
- Tag by department, course, faculty, and material type (hand notes, lecture sheet, lecture slide, term paper, previous question, other)
- Browse with filters and free-text search; no approval queue — uploads go live instantly
- Report inappropriate or incorrect materials

### Profiles
- Public profile per student: their discussions, their uploads, and a combined vote score across both

### Admin Panel
- Dashboard with platform-wide counts (users, discussions, materials, pending reports, banned users)
- User management: search, view profile, ban/unban (with a safety check preventing an admin from banning their own account)
- Reports queue: review pending reports with a content preview, dismiss, or delete the underlying content directly
- Direct content moderation independent of the report queue

## Design System

The visual identity — "Campus Noticeboard" — draws on the physical noticeboards common on EWU's campus, where students pin course notices and materials. Each discussion/material card resembles a note pinned to a board, with a small torn "tape tab" holding its course code.

- **Palette**: Ink Navy (`#1B2430`) background, Paper Cream (`#F6F1E4`) cards, Highlighter Amber (`#F2B705`) primary actions, Library Green (`#2F6F4E`) for materials, Stamp Red (`#C1443C`) for destructive/alert actions
- **Typography**: Fraunces (serif, display/headings), Inter (body text), IBM Plex Mono (course codes, vote counts, stamped elements)
- **Signature element**: the rotated "tape tab" tag on every card, distinguishing discussions (amber) from materials (green)

## Setup & Installation

1. Install [XAMPP](https://www.apachefriends.org/) (Apache + MySQL + PHP 8.2+)
2. Clone/copy this project into `htdocs/`
3. Create a MySQL database and import `sql/schema.sql` via phpMyAdmin
4. Update `config/db.php` with your database credentials
5. Ensure `uploads/discussions/`, `uploads/comments/`, and `uploads/materials/` exist and are writable
6. In `php.ini`, set `upload_max_filesize` and `post_max_size` to at least `40M` (or higher, to match the material upload limit) and restart Apache
7. Visit the project root in your browser and register an account using a valid EWU student email
8. To create an admin account, manually set that user's `role` column to `admin` in the `users` table via phpMyAdmin, then log out and back in

## Development Process & Workflow

This project was built using an AI-assisted, review-driven workflow: GitHub Copilot generated each file from a detailed, requirement-specific prompt, and every piece of generated code was reviewed against an explicit checklist before being accepted — covering SQL injection safety (prepared statements throughout, no string-concatenated queries), XSS prevention (consistent `htmlspecialchars()` escaping), file upload safety (real MIME-type sniffing via `finfo`, never trusting client-supplied type headers; randomized filenames to prevent path traversal), and business logic correctness (vote toggle math, cascading deletes, ownership checks). Complex files (e.g. `discussions/view.php`, file upload handlers) were built in multiple passes — query logic first, then display, then the highest-risk logic (file handling) in isolation — rather than generated in one shot.

## Challenges Faced & How They Were Solved

**Nested project folder path resolution.** The local XAMPP folder structure ended up nested one level deeper than assumed (`htdocs/ewu_study_point/ewu-study-point/`), which broke every hardcoded root-relative path across CSS links, navigation, and auth-guard redirects. Resolved by centralizing the root path into a single `$site_root` variable in `header.php`, and standardizing every other internal redirect to use paths *relative to the including file* instead of hardcoded absolute paths — a lesson that surfaced repeatedly (CSS 404s, handler form actions pointing to the wrong folder, `admin_check.php`/`auth_check.php` redirecting to a stale path) before being fixed consistently everywhere.

**Vote toggle/switch logic.** Implementing upvote/downvote with toggle-off (clicking the same direction again removes the vote) and direction-switching (up→down should swing the score by 2, not 1) required careful, explicitly-tested branching, wrapped in a database transaction to keep the vote row and the denormalized score column consistent even if a query fails mid-way.

**Atomic multi-file uploads.** Discussion posts allow multiple attachments, but a submission with one valid and one invalid file needed to fail as a whole — no partial posts with only some attachments saved. Solved with upfront validation of every file before any are moved to disk, and a database transaction wrapping the discussion insert and all attachment inserts together, with any already-moved files cleaned up via `unlink()` if the transaction ultimately fails.

**Contrast bugs from a reused dark-theme stylesheet.** Several form and comment sections silently rendered invisible text (cream-on-cream) because CSS originally written for the dark navy page background was reused inside light cream cards without adjustment. Solved by explicitly scoping light-background variants of every affected rule (`.form-card`, `.comment` inside `.notice-card`) rather than assuming one color scheme would work everywhere.

**Browser download-manager interaction.** Material downloads appeared to fail specifically when intercepted by a third-party download manager (IDM), despite the underlying files being verified present, readable, and correctly served by Apache via a dedicated diagnostic script. Resolved by removing the `download` HTML attribute from material links, letting the browser handle the file naturally via `target="_blank"` instead of triggering the download manager's blob-interception behavior.

**CSS rule layering/specificity conflicts.** Iterative styling passes on the same UI sections (comment threads, action buttons) occasionally left multiple competing rule blocks in the stylesheet with equal specificity, causing inconsistent rendering depending on file order. Resolved by consolidating each affected UI section into a single authoritative rule block and removing superseded duplicates, rather than continuing to layer patches.

## Security Measures

- **SQL injection**: every database query uses prepared statements with bound parameters — no query is ever built via string concatenation of user input
- **XSS**: all user-supplied output is passed through `htmlspecialchars()` before rendering; `nl2br()` is always applied *after* escaping, never before
- **File upload validation**: real MIME-type detection via `finfo_file()` on actual file content (never the client-supplied `$_FILES[...]['type']`), randomized filenames via `random_bytes()` to prevent path traversal or overwrite attacks, and file size limits enforced before any file is moved to disk
- **Authentication**: passwords hashed with PHP's `password_hash()`/`password_verify()`; session ID regenerated on login to prevent session fixation
- **Authorization**: every owner-only action (edit, delete) checks ownership both before rendering the UI *and* again in the query's `WHERE` clause as defense in depth; admin-only pages are guarded by a dedicated `admin_check.php`
- **CSRF surface**: destructive actions (delete, ban/unban) require POST requests, never accessible via a bare GET link

## Known Limitations

- No email verification step on registration beyond domain-pattern matching — a typo'd but domain-valid email cannot be confirmed as belonging to the registrant
- No password reset flow
- Deleting a comment does not recursively clean up file attachments belonging to its child replies (only the comment's own attachments are removed), leaving those specific files orphaned on disk
- No pagination/rate-limiting on comment or vote submissions
- Admin actions (ban, delete) have no undo/audit trail beyond the `reports.reviewed_by` field
- No real-time updates — voting, new comments, and new reports require a page reload to see
- Free-text department/course/faculty fields (chosen deliberately for flexibility) can lead to inconsistent tagging (e.g. "CSE110" vs "CSE 110") without a controlled vocabulary or autocomplete

## Future Work

- Email verification and password reset flows
- Autocomplete suggestions for department/course/faculty fields, drawn from existing entries, to reduce tagging inconsistency without sacrificing free-text flexibility
- Real-time notifications (new answer, new reply, report status change)
- Recursive attachment cleanup for deleted comment threads
- Rate limiting on votes/comments/reports to deter abuse
- Search relevance ranking beyond simple `LIKE` matching
- Mobile-optimized navigation (current responsive breakpoints cover basic stacking, not a dedicated mobile nav pattern)
- An audit log for admin actions
