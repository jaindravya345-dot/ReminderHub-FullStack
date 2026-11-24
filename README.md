# ReminderHub – Daily Reminder Management System

**A smart and user-friendly web application to organize daily tasks, manage reminders, and improve productivity.**

## About the Project

ReminderHub is a full-stack web-based reminder management system developed using PHP, MySQL, HTML, CSS, and JavaScript.

It allows users to create personal accounts, log in securely, manage daily reminders, track completed tasks, and organize their schedules through a clean and responsive dashboard.

## Features

- User Registration and Login
- Secure Session-Based Authentication
- Create New Reminders
- Edit and Delete Reminders
- Mark Reminders as Completed
- Dashboard with Reminder Statistics
- Today's and Upcoming Reminders
- Pending and Completed Reminders
- Search and Sort Reminders
- Reminder Categories and Priority Levels
- Responsive User Interface
- Light and Dark Theme Support
- Browser Notification and Service Worker Support

## Technologies Used

| Technology | Purpose |
|---|---|
| HTML5 | Website structure |
| CSS3 | Styling and responsive design |
| JavaScript | Frontend functionality |
| PHP | Backend development |
| MySQL | Database management |
| XAMPP | Local development server |
| Git and GitHub | Version control and project hosting |

## Project Structure

```text
ReminderHub-FullStack/
│
├── api/
│   ├── auth.php
│   ├── reminders.php
│   └── push-subscribe.php
│
├── assets/
│   ├── css/
│   │   └── app.css
│   └── js/
│       ├── app.js
│       └── auth.js
│
├── config/
│   ├── bootstrap.php
│   └── page.php
│
├── database/
│   └── schema.sql
│
├── index.php
├── login.php
├── register.php
├── logout.php
├── service-worker.js
└── README.md
```

## Installation and Setup

### Requirements

- Windows operating system
- XAMPP
- PHP
- MySQL
- Web browser

### Step 1: Download the Project

Clone or download this GitHub repository into your XAMPP `htdocs` folder.

### Step 2: Start XAMPP

Start Apache and MySQL.

### Step 3: Create the Database

1. Open phpMyAdmin or MySQL Workbench.
2. Create a database named `reminderhub`.
3. Import the `database/schema.sql` file.

### Step 4: Configure Database Connection

Create a private `config/local.php` file on your computer with your own database credentials.

Do not upload this private file to GitHub.

### Step 5: Run the Project

Open your browser and visit:

```text
http://localhost/ReminderHub-Website-Project/ReminderHub-FullStack/
```

## Database

The project uses MySQL to store user accounts, reminders, and push subscription information.

The database structure is provided in:

`database/schema.sql`

## Future Enhancements

- Automated scheduled reminder delivery
- Advanced notification management
- Email reminders
- Mobile application support
- Cloud deployment

## Developer

**GitHub:** [jaindravya345-dot](https://github.com/jaindravya345-dot)

**Project Name:** ReminderHub – Daily Reminder Management System

## License

This project is developed for educational and learning purposes.

---

**ReminderHub – A calmer way to keep life in order.**