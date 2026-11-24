
let reminders = [], csrfToken = "";
let editingReminderId = null;
let currentView = "overview", toastTimer;

const $ = id => document.getElementById(id);

const todayISO = () => {
    const d = new Date();
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;
};

function prettyDate(v) {
    return new Date(v + "T12:00:00").toLocaleDateString(undefined, {
        month: "short",
        day: "numeric"
    });
}

function toast(msg) {
    $("toast").textContent = msg;
    $("toast").classList.add("show");
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => $("toast").classList.remove("show"), 2400);
}

async function api(url, options = {}) {
    const headers = {
        "Content-Type": "application/json",
        ...(options.headers || {})
    };

    if (options.method && options.method !== "GET") {
        headers["X-CSRF-Token"] = csrfToken;
    }

    const r = await fetch(url, { ...options, headers });
    const d = await r.json();

    if (!r.ok || !d.ok) {
        throw new Error(d.error || "Request failed");
    }

    return d;
}

async function init() {
    try {
        const auth = await api("api/auth.php");
        csrfToken = auth.csrfToken;

        if (!auth.authenticated) {
            location.href = "login.php";
            return;
        }

        const u = auth.user;

        $("user-name").textContent = u.name;
        $("user-email").textContent = u.email;

        $("user-avatar").textContent = $("top-avatar").textContent =
            u.name.split(/\s+/).map(x => x[0]).slice(0, 2).join("").toUpperCase();

        const data = await api("api/reminders.php");
        reminders = data.reminders;

        render();

    } catch (e) {
        if (e.message.includes("log in")) {
            location.href = "login.php";
        } else {
            $("status-note").textContent = e.message;
            toast(e.message);
        }
    }
}

function setView(v) {
    currentView = v;

    document.querySelectorAll(".nav-link").forEach(b => {
        b.classList.toggle("active", b.dataset.view === v);
    });

    const names = {
        overview: "Overview",
        all: "All reminders",
        today: "Today",
        upcoming: "Upcoming",
        completed: "Completed"
    };

    $("breadcrumb-current").textContent = names[v];

    $("page-title").innerHTML = v === "overview"
        ? 'Make today <span>count.</span>'
        : `${names[v]}. <span>One step at a time.</span>`;

    $("list-title").textContent =
        v === "overview" || v === "today"
            ? "Today's reminders"
            : names[v];

    render();
}

function filtered() {
    const q = $("search").value.trim().toLowerCase();

    let a = reminders.filter(r => {
        if (
            (currentView === "overview" || currentView === "today") &&
            (r.date !== todayISO() || r.done)
        ) return false;

        if (currentView === "upcoming" && (r.date <= todayISO() || r.done)) {
            return false;
        }

        if (currentView === "completed" && !r.done) {
            return false;
        }

        if (
            q &&
            !(r.title + " " + (r.notes || "") + " " + r.category)
                .toLowerCase().includes(q)
        ) return false;

        return true;
    });

    const s = $("sort").value;

    a.sort((x, y) =>
        s === "title"
            ? x.title.localeCompare(y.title)
            : s === "priority"
                ? ({ high: 0, normal: 1, low: 2 }[x.priority] -
                   { high: 0, normal: 1, low: 2 }[y.priority])
                : (x.date + "T" + x.time).localeCompare(y.date + "T" + y.time)
    );

    return a;
}

function render() {
    const t = reminders.filter(r => r.date === todayISO() && !r.done).length;
    const u = reminders.filter(r => r.date > todayISO() && !r.done).length;
    const c = reminders.filter(r => r.done).length;
    const p = reminders.filter(r => !r.done).length;

    $("stat-today").textContent = t;
    $("stat-upcoming").textContent = u;
    $("stat-completed").textContent = c;
    $("stat-all").textContent = reminders.length;
    $("stat-pending").textContent = p;
    $("nav-count").textContent = p;

    const a = filtered();
    const list = $("reminder-list");

    list.innerHTML = "";
    $("empty-state").hidden = a.length !== 0;
    list.hidden = a.length === 0;

    a.forEach(r => {
        const row = document.createElement("article");

        row.className = "reminder-item" + (r.done ? " completed" : "");

        row.innerHTML = `
            <button class="check-button ${r.done ? "done" : ""}"
                data-check="${r.id}" aria-label="Toggle completed">
                ${r.done ? "✓" : ""}
            </button>

            <div class="reminder-main">
                <div class="reminder-title"></div>
                <div class="reminder-note"></div>
            </div>

            <span class="tag ${r.category}">${r.category}</span>

            <div class="date-cell">
                <strong>${prettyDate(r.date)}</strong>${r.time}
            </div>

            <span class="priority ${r.priority}">
                ${r.priority === "high" ? "High priority" :
                  r.priority === "low" ? "Low priority" : "Normal"}
            </span>

            <button class="more-button" data-edit="${r.id}"
                aria-label="Edit reminder">Edit</button>

            <button class="more-button" data-delete="${r.id}"
                aria-label="Delete reminder">Delete</button>
        `;

        row.querySelector(".reminder-title").textContent = r.title;
        row.querySelector(".reminder-note").textContent =
            r.notes || "No additional notes";

        list.appendChild(row);
    });

    document.querySelectorAll("[data-check]").forEach(b => {
        b.onclick = () => toggleDone(Number(b.dataset.check));
    });

    document.querySelectorAll("[data-edit]").forEach(b => {
        b.onclick = () => editReminder(Number(b.dataset.edit));
    });

    document.querySelectorAll("[data-delete]").forEach(b => {
        b.onclick = () => deleteReminder(Number(b.dataset.delete));
    });
}

async function toggleDone(id) {
    const r = reminders.find(x => x.id === id);
    if (!r) return;

    try {
        await api("api/reminders.php", {
            method: "PATCH",
            body: JSON.stringify({ id, done: !r.done })
        });

        r.done = !r.done;
        render();

        toast(r.done
            ? "Reminder completed"
            : "Reminder moved back to pending");

    } catch (e) {
        toast(e.message);
    }
}

async function deleteReminder(id) {
    if (!confirm("Delete this reminder?")) return;

    try {
        await api("api/reminders.php", {
            method: "DELETE",
            body: JSON.stringify({ id })
        });

        reminders = reminders.filter(r => r.id !== id);

        render();
        toast("Reminder deleted");

    } catch (e) {
        toast(e.message);
    }
}

function editReminder(id) {
    const r = reminders.find(x => x.id === id);
    if (!r) return;

    editingReminderId = id;

    $("reminder-title").value = r.title;
    $("reminder-date").value = r.date;
    $("reminder-time").value = r.time;
    $("reminder-category").value = r.category;
    $("reminder-priority").value = r.priority;
    $("reminder-notes").value = r.notes || "";

    $("modal-backdrop").hidden = false;
    $("reminder-title").focus();
}

function openModal() {
    editingReminderId = null;

    $("reminder-form").reset();
    $("reminder-date").value = todayISO();
    $("reminder-time").value = "09:00";

    $("modal-backdrop").hidden = false;
    $("reminder-title").focus();
}

function closeModal() {
    $("modal-backdrop").hidden = true;
}

$("today-date").textContent = new Date().toLocaleDateString(undefined, {
    weekday: "short",
    month: "short",
    day: "numeric",
    year: "numeric"
});

const hr = new Date().getHours();

$("greeting").textContent =
    hr < 12 ? "A FRESH START" : "YOUR PERSONAL SPACE";

document.querySelectorAll(".nav-link").forEach(b => {
    b.onclick = () => {
        setView(b.dataset.view);
        $("sidebar").classList.remove("open");
    };
});

$("add-reminder").onclick = openModal;
$("empty-add").onclick = openModal;
$("close-modal").onclick = closeModal;
$("cancel-modal").onclick = closeModal;

$("modal-backdrop").onclick = e => {
    if (e.target === $("modal-backdrop")) {
        closeModal();
    }
};

$("search").addEventListener("input", render);
$("sort").addEventListener("change", render);

$("reminder-form").onsubmit = async e => {
    e.preventDefault();

    const payload = {
        title: $("reminder-title").value.trim(),
        date: $("reminder-date").value,
        time: $("reminder-time").value,
        category: $("reminder-category").value,
        priority: $("reminder-priority").value,
        notes: $("reminder-notes").value.trim()
    };

    try {
        if (editingReminderId !== null) {
            await api("api/reminders.php", {
                method: "PUT",
                body: JSON.stringify({
                    ...payload,
                    id: editingReminderId
                })
            });

            toast("Reminder updated successfully");
            editingReminderId = null;

        } else {
            await api("api/reminders.php", {
                method: "POST",
                body: JSON.stringify(payload)
            });

            toast("Reminder saved to your account");
        }

        const d = await api("api/reminders.php");
        reminders = d.reminders;

        closeModal();
        setView("all");

    } catch (err) {
        toast(err.message);
    }
};

init();

if ("serviceWorker" in navigator && "Notification" in window) {
    window.addEventListener("load", async () => {
        try {
            await navigator.serviceWorker.register("./service-worker.js");

            console.log("ReminderHub service worker registered!");

            if (Notification.permission === "default") {
                const permission = await Notification.requestPermission();
                console.log("Notification permission:", permission);
            } else {
                console.log("Notification permission:", Notification.permission);
            }

        } catch (error) {
            console.error("Notification setup failed:", error);
        }
    });
}
