# 🧩 Crossword Game - Interactive Educational Platform

A feature-rich web-based crossword puzzle game built with PHP and MySQL. Teachers can create custom crossword puzzles, and students can solve them with real-time scoring and detailed performance analytics.

![PHP](https://img.shields.io/badge/PHP-7.4+-777BB4?style=flat-square&logo=php)
![MySQL](https://img.shields.io/badge/MySQL-5.7+-4479A1?style=flat-square&logo=mysql&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-ES6+-F7DF1E?style=flat-square&logo=javascript&logoColor=black)
![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)

## 📑 Table of Contents

- [Features](#-features)
- [Technologies Used](#-technologies-used)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [Usage](#-usage)
- [Database Schema](#-database-schema)
- [Security Features](#-security-features)
- [Screenshots](#-screenshots)
- [Contributing](#-contributing)
- [License](#-license)
- [Contact](#-contact)

## ✨ Features

### 👨‍🏫 For Teachers

- **Puzzle Creation**
  - Live preview crossword builder
  - Auto-generate intersecting word layouts
  - Add clues for across/down words
  - Set time limits and difficulty levels
  - Warning system for words that don't fit

- **Student Management**
  - View all registered students
  - Activate/deactivate student accounts
  - Filter students by program
  - Export student data

- **Results Dashboard**
  - View detailed student performance
  - Word-by-word answer analysis
  - Filter results by student, puzzle, or status
  - Publish/hide results to students
  - Unlock puzzles for retries
  - Export results to CSV

- **Security Monitoring**
  - Login attempt logs
  - Failed login tracking
  - IP address monitoring
  - Security event logs

### 🎓 For Students

- **Interactive Gameplay**
  - Beautiful, responsive crossword grid
  - Real-time timer countdown
  - Auto-save functionality
  - Click or arrow key navigation
  - Across/Down clue switching

- **Performance Tracking**
  - View published results
  - Detailed score breakdowns
  - Correct/wrong word analysis
  - Time taken statistics
  - Historical performance data

- **User Profile**
  - Update personal information
  - Change password
  - View attempt history
  - Track progress over time

## 🛠️ Technologies Used

### Backend
- **PHP 7.4+** - Server-side scripting
- **MySQL 5.7+** - Database management
- **PDO/MySQLi** - Database connection

### Frontend
- **HTML5** - Markup
- **CSS3** - Styling with modern gradients
- **JavaScript (Vanilla)** - Interactive features
- **Responsive Design** - Mobile-friendly interface

### Security
- **bcrypt** - Password hashing
- **CSRF Protection** - Form security
- **XSS Prevention** - Input sanitization
- **SQL Injection Prevention** - Prepared statements
- **Session Security** - Secure session management

## 📦 Installation

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- XAMPP/WAMP/LAMP (for local development)

### Step 1: Clone the Repository

- https://github.com/Ashishraj191/CrossWord.git

### Step 2: Database Setup

1. Create a new MySQL database:

2. Import the database schema:

Or manually create tables using phpMyAdmin:


### Step 3: Configuration

1. Copy `config.example.php` to `config.php`:

2. Update database credentials in `config.php`:
    define('DB_HOST', 'localhost');
    define('DB_USER', 'your_username');
    define('DB_PASS', 'your_password');
    define('DB_NAME', 'crossword_game');


### Step 4: Set Permissions

chmod 755 logs/
chmod 755 uploads/
chmod 755 temp/


### Step 5: Access the Application

Open your browser and navigate to:
  http://localhost/crossword-game/

## ⚙️ Configuration

### config.php Options


    / Session Configuration
ini_set('session.cookie_lifetime', 0);
ini_set('session.gc_maxlifetime', 3600);

// Timezone
date_default_timezone_set('Asia/Kolkata');

// Application Settings
define('APP_NAME', 'Crossword Game');
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB

// Development Mode
$is_development = true; // Set to false in production


### Security Headers

Security headers are automatically set in `config.php`:
- X-Content-Type-Options: nosniff
- X-Frame-Options: SAMEORIGIN
- X-XSS-Protection: 1; mode=block
- Content-Security-Policy (HTTPS only)

## 📖 Usage

### For Teachers

1. **Register Account**
   - Navigate to `/auth/teacher_register.php`
   - Fill in name, email, and password
   - Click "Register"

2. **Create Puzzle**
   - Go to Dashboard → Create Puzzle
   - Enter puzzle title and time limit
   - Add words and clues (minimum 2 words)
   - Preview crossword in real-time
   - Click "Save Crossword Puzzle"

3. **Manage Students**
   - View all registered students
   - Activate/deactivate accounts
   - Filter by program (MSc AI, MSc CS)

4. **View Results**
   - Navigate to "Students Result"
   - Filter by student, puzzle, or status
   - Click "View Answer" to see detailed solutions
   - Publish/hide results
   - Unlock puzzles for retries

### For Students

1. **Register Account**
   - Navigate to `/auth/student_register.php`
   - Fill in name, email, program, and password
   - Click "Register"

2. **Play Puzzle**
   - Login and view available puzzles
   - Click "Play" on any active puzzle
   - Fill in answers using keyboard or click
   - Timer counts down automatically
   - Click "Submit Puzzle" when done

3. **View Results**
   - Check "View Result" for published results
   - See score, correct/wrong words
   - Review time taken



## 🔒 Security Features

### Authentication & Authorization
- ✅ Bcrypt password hashing (cost: 12)
- ✅ Session-based authentication
- ✅ Role-based access control (Student/Teacher)
- ✅ Session timeout (30 minutes)
- ✅ IP prefix validation

### Input Validation
- ✅ XSS prevention with `htmlspecialchars()`
- ✅ SQL injection prevention with prepared statements
- ✅ CSRF token protection
- ✅ Input sanitization for all user data
- ✅ Email validation with regex

### Rate Limiting
- ✅ Login attempt tracking
- ✅ IP-based rate limiting
- ✅ Failed login blocking (5 attempts in 15 minutes)

### Logging & Monitoring
- ✅ Login attempt logs (success/failure)
- ✅ Security event logs
- ✅ Puzzle activity logs
- ✅ Registration logs

### Secure Headers
- ✅ Content-Security-Policy
- ✅ X-Frame-Options: SAMEORIGIN
- ✅ X-Content-Type-Options: nosniff
- ✅ X-XSS-Protection

## 📸 Screenshots

### Teacher Dashboard
<img width="1920" hei<img width="1920" height="1080" alt="Screenshot (28)" src="https://github.com/user-attachments/assets/f501e53d-7e97-46fe-a714-0418f0375005" />
ght="1080" alt="Screenshot (31)" src="https://github.com/user-attachments/assets/b57a78f4-a97b-48d6-a90e-7460df16b7d4" />

### Student Dashboard
<img width="1920" height="1080" alt="Screenshot (27)" src="https://github.com/user-attachments/assets/abca8e42-1e1c-4633-b6dd-03b1dc27bc7c" />

### Create Puzzle - Live Preview
<img width="1920" height="1080" alt="Screenshot (29)" src="https://github.com/user-attachments/assets/03317b54-e83a-4981-88d9-5030522c53d3" />
<img width="1920" height="1080" alt="Screenshot (30)" src="https://github.com/user-attachments/assets/fbff6d85-c02e-432c-8712-f533e3414143" />

### Student Gameplay
<img width="1920" height="1080" alt="Screenshot (32)" src="https://github.com/user-attachments/assets/c12f213f-c438-4a17-b9df-90a4de0f44ba" />
<img width="1920" height="1080" alt="Screenshot (33)" src="https://github.com/user-attachments/assets/a6e38adc-84fe-40df-b7ee-957747d445bb" />


### Results Analytics
<img width="1920" height="1080" alt="Screenshot (34)" src="https://github.com/user-attachments/assets/b5b69a1b-b3c9-44a3-a9c9-44817e1dc1c2" />
<img width="1920" height="1080" alt="Screenshot (35)" src="https://github.com/user-attachments/assets/dd7e22f4-9b93-4b9e-ab9d-85ef2646270c" />


### Login Logs
<img width="1920" height="1080" alt="Screenshot (36)" src="https://github.com/user-attachments/assets/37384285-26ea-4a9f-8aa3-73c3548a98cd" />


## 🗂️ Project Structure

crossword-game/
├── assets/
│ ├── css/
│ │ ├── style.css
│ │ └── responsive.css
│ ├── js/
│ │ └── responsive.js
│ └── images/
├── auth/
│ ├── student_login.php
│ ├── student_register.php
│ ├── teacher_login.php
│ └── teacher_register.php
├── student/
│ ├── student_dashboard.php
│ ├── play_puzzle.php
│ ├── view_result.php
│ └── submit_puzzle.php
├── teacher/
│ ├── teacher_dashboard.php
│ ├── create_puzzle.php
│ ├── manage_puzzles.php
│ ├── edit_puzzle.php
│ ├── view_students.php
│ ├── manage_students.php
│ ├── view_login_logs.php
│ └── view_student_answer.php
├── functions/
│ └── helpers.php
├── logs/
│ ├── security.log
│ ├── puzzle_activity.log
│ └── php_errors.log
├── database/
│ └── schema.sql
├── config.php
├── index.php
├── logout.php
└── README.md

## 🤝 Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository
2. Create a new branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

### Coding Standards

- Follow PSR-12 PHP coding standards
- Use meaningful variable and function names
- Comment complex logic
- Write secure code (validate inputs, use prepared statements)
- Test thoroughly before submitting

## 📝 License

This project is licensed under the MIT License.

MIT License

Copyright (c) 2025 [Ashish Kumar]

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.
