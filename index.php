
<?php require_once __DIR__ . '/config/page.php'; ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="theme-color" content="#111421">
    <title>ReminderHub · Your day, remembered</title>
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
<div class="app-shell">

    <aside class="sidebar" id="sidebar">
        <a class="brand" href="index.php">
            <span class="brand-mark">✓</span>Reminder<span class="brand-light">Hub</span>
        </a>

        <div class="workspace-label">YOUR SPACE</div>

        <nav class="nav">
            <button class="nav-link active" data-view="overview"><span>▦</span> Overview</button>
            <button class="nav-link" data-view="all"><span>☷</span> All reminders <b id="nav-count">0</b></button>
            <button class="nav-link" data-view="today"><span>◷</span> Today</button>
            <button class="nav-link" data-view="upcoming"><span>▣</span> Upcoming</button>
            <button class="nav-link" data-view="completed"><span>✓</span> Completed</button>
        </nav>

        <div class="sidebar-bottom">
            <div class="tip-card">
                <div class="tip-icon">✦</div>
                <strong>A thought for today</strong>
                <p>You don't have to do it all. Just start with what matters most.</p>
            </div>

            <div class="profile">
                <div class="avatar" id="user-avatar">RH</div>
                <div class="profile-info">
                    <strong id="user-name">My workspace</strong>
                    <small id="user-email">Personal account</small>
                </div>
                <a class="logout-button" id="logout" href="logout.php" title="Sign out">↗</a>
            </div>
        </div>
    </aside>

    <main class="main">
        <header class="topbar">
            <button class="mobile-menu" id="mobile-menu" aria-label="Open menu">☰</button>
            <div class="breadcrumb">
                Workspace <span>/</span> <strong id="breadcrumb-current">Overview</strong>
            </div>
            <div class="top-actions">
                <span class="today-date" id="today-date"></span>
                <button class="icon-button" id="theme-toggle" title="Toggle theme">◐</button>
                <div class="avatar small-avatar" id="top-avatar">RH</div>
            </div>
        </header>

        <section class="content">
            <div class="welcome-row">
                <div>
                    <p class="eyebrow" id="greeting">YOUR PERSONAL SPACE</p>
                    <h1 id="page-title">Make today <span>count.</span></h1>
                    <p class="subheading" id="page-subtitle">A little structure leaves more room for life.</p>
                </div>
                <button class="primary-button" id="add-reminder">
                    <span>＋</span> New reminder
                </button>
            </div>

            <!-- Five dashboard cards -->
            <section class="stats-grid">

                <article class="stat-card">
                    <div class="stat-top">Due today <span class="stat-icon blue">◷</span></div>
                    <div class="stat-number" id="stat-today">0</div>
                    <div class="stat-foot">Reminders on your schedule today</div>
                </article>

                <article class="stat-card">
                    <div class="stat-top">Coming up <span class="stat-icon purple">▣</span></div>
                    <div class="stat-number" id="stat-upcoming">0</div>
                    <div class="stat-foot">Waiting around the corner</div>
                </article>

                <article class="stat-card">
                    <div class="stat-top">Completed <span class="stat-icon green">✓</span></div>
                    <div class="stat-number" id="stat-completed">0</div>
                    <div class="stat-foot">A little progress adds up</div>
                </article>

                <article class="stat-card">
                    <div class="stat-top">All reminders <span class="stat-icon orange">☷</span></div>
                    <div class="stat-number" id="stat-all">0</div>
                    <div class="stat-foot">Your plans in one place</div>
                </article>

                <article class="stat-card">
                    <div class="stat-top">Pending reminders <span class="stat-icon orange">◷</span></div>
                    <div class="stat-number" id="stat-pending">0</div>
                    <div class="stat-foot">Still waiting to be completed</div>
                </article>

            </section>

            <section class="panel">
                <div class="panel-header">
                    <div>
                        <h2 id="list-title">Today's reminders</h2>
                        <p id="list-description">Your next steps, all in one view.</p>
                    </div>

                    <div class="list-tools">
                        <label class="search-box">
                            <span>⌕</span>
                            <input id="search" type="search" placeholder="Find a reminder...">
                        </label>

                        <select id="sort" aria-label="Sort reminders">
                            <option value="date">Soonest first</option>
                            <option value="priority">Priority</option>
                            <option value="title">Name A–Z</option>
                        </select>
                    </div>
                </div>

                <div class="reminder-list" id="reminder-list"></div>

                <div class="empty-state" id="empty-state" hidden>
                    <div class="empty-illustration">✧</div>
                    <h3>Your space is clear</h3>
                    <p>Add a reminder when something needs a place on your schedule.</p>
                    <button class="secondary-button" id="empty-add">＋ Create a reminder</button>
                </div>
            </section>

            <p class="prototype-note" id="status-note">Connected to your ReminderHub account.</p>
        </section>
    </main>
</div>

<div class="modal-backdrop" id="modal-backdrop" hidden>
    <section class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-title">
        <div class="modal-header">
            <div>
                <p class="eyebrow">MAKE SPACE FOR WHAT MATTERS</p>
                <h2 id="modal-title">New reminder</h2>
            </div>
            <button class="close-button" id="close-modal" aria-label="Close">×</button>
        </div>

        <form id="reminder-form">
            <label class="field-label">
                What do you need to remember?
                <input id="reminder-title" maxlength="120" required placeholder="e.g. Pay electricity bill">
            </label>

            <div class="form-row">
                <label class="field-label">
                    Date
                    <input id="reminder-date" type="date" required>
                </label>
                <label class="field-label">
                    Time
                    <input id="reminder-time" type="time" value="09:00" required>
                </label>
            </div>

            <div class="form-row">
                <label class="field-label">
                    Category
                    <select id="reminder-category">
                        <option>Personal</option>
                        <option>Health</option>
                        <option>Work</option>
                        <option>Payments</option>
                        <option>Family</option>
                    </select>
                </label>

                <label class="field-label">
                    Priority
                    <select id="reminder-priority">
                        <option value="normal">Normal</option>
                        <option value="high">High</option>
                        <option value="low">Low</option>
                    </select>
                </label>
            </div>

            <label class="field-label">
                Notes <span class="optional">(optional)</span>
                <textarea id="reminder-notes" rows="3" maxlength="1000" placeholder="Add a little detail..."></textarea>
            </label>

            <div class="modal-actions">
                <button type="button" class="secondary-button" id="cancel-modal">Cancel</button>
                <button type="submit" class="primary-button">Save reminder</button>
            </div>
        </form>
    </section>
</div>

<div class="toast" id="toast" role="status" aria-live="polite"></div>

<script src="assets/js/app.js"></script>
</body>
</html>
