<?php
/** @var array $session */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Asset PM Console</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; margin: 0; padding: 2rem; background: #f3f4f6; color: #111827; }
        main { max-width: 980px; margin: 0 auto; display: grid; gap: 1.5rem; }
        section { background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(15, 23, 42, 0.12); padding: 1.75rem; }
        h1 { margin: 0 0 1rem 0; font-size: 2rem; }
        h2 { margin: 0 0 1rem 0; font-size: 1.3rem; }
        form { display: grid; gap: 0.8rem; }
        label { display: grid; gap: 0.35rem; font-weight: 600; }
        input, textarea, select { padding: 0.55rem 0.7rem; border: 1px solid #d1d5db; border-radius: 8px; font-size: 0.95rem; }
        textarea { resize: vertical; min-height: 96px; }
        button { background: #2563eb; border: none; color: #fff; border-radius: 8px; padding: 0.65rem 1rem; font-weight: 600; cursor: pointer; }
        button:hover { background: #1d4ed8; }
        .grid-2 { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); }
        .status { background: rgba(37, 99, 235, 0.1); color: #1d4ed8; border-radius: 8px; padding: 0.5rem 0.75rem; display: inline-flex; align-items: center; gap: 0.5rem; font-size: 0.9rem; }
        pre { background: #0f172a; color: #f8fafc; padding: 0.75rem; border-radius: 8px; overflow: auto; }
    </style>
</head>
<body>
    <main>
        <section>
            <h1>Asset PM Console</h1>
            <p class="status">
                <strong>Status:</strong>
                <?php if (!empty($session['uid'])): ?>
                    Logged in as <?= escape($session['email'] ?? ('UID ' . $session['uid'])) ?> (role <?= escape($session['role'] ?? 'n/a') ?>)
                <?php else: ?>
                    Not authenticated
                <?php endif; ?>
            </p>
        </section>

        <section>
            <h2>Login</h2>
            <form method="post" action="/login">
                <?= csrf_field() ?>
                <label>
                    Email
                    <input type="email" name="email" required>
                </label>
                <label>
                    Password
                    <input type="password" name="password" required>
                </label>
                <button type="submit">Login</button>
            </form>
        </section>

        <section>
            <h2>Create Project</h2>
            <form method="post" action="/projects/create">
                <?= csrf_field() ?>
                <label>
                    Name
                    <input type="text" name="name" required>
                </label>
                <label>
                    Location
                    <input type="text" name="location" required>
                </label>
                <label>
                    Starts At
                    <input type="datetime-local" name="starts_at" required>
                </label>
                <label>
                    Due At
                    <input type="datetime-local" name="due_at" required>
                </label>
                <label>
                    Quote Amount
                    <input type="number" step="0.01" name="quote_amount" value="0.00">
                </label>
                <label>
                    Note
                    <textarea name="note"></textarea>
                </label>
                <button type="submit">Create Project</button>
            </form>
        </section>

        <section>
            <h2>Create Device</h2>
            <form method="post" action="/devices/create">
                <?= csrf_field() ?>
                <label>
                    Code
                    <input type="text" name="code" required>
                </label>
                <label>
                    Model
                    <input type="text" name="model" required>
                </label>
                <label>
                    Serial (optional)
                    <input type="text" name="serial">
                </label>
                <label>
                    Photo URL (optional)
                    <input type="url" name="photo_url">
                </label>
                <button type="submit">Create Device</button>
            </form>
        </section>

        <section>
            <h2>Reserve Device</h2>
            <form method="post" action="/reservations/create">
                <?= csrf_field() ?>
                <label>
                    Project ID
                    <input type="number" name="project_id" min="1" required>
                </label>
                <label>
                    Device ID
                    <input type="number" name="device_id" min="1" required>
                </label>
                <label>
                    From
                    <input type="datetime-local" name="from" required>
                </label>
                <label>
                    To
                    <input type="datetime-local" name="to" required>
                </label>
                <button type="submit">Reserve</button>
            </form>
        </section>

        <section>
            <h2>Checkout Device</h2>
            <form method="post" action="/checkouts/create">
                <?= csrf_field() ?>
                <label>
                    Device ID
                    <input type="number" name="device_id" min="1" required>
                </label>
                <label>
                    Project ID (optional)
                    <input type="number" name="project_id" min="1">
                </label>
                <label>
                    Checkout Time
                    <input type="datetime-local" name="now" required>
                </label>
                <label>
                    Due Time
                    <input type="datetime-local" name="due" required>
                </label>
                <label>
                    Checkout Photo (optional)
                    <input type="url" name="photo">
                </label>
                <label>
                    Note (optional)
                    <textarea name="note"></textarea>
                </label>
                <button type="submit">Checkout</button>
            </form>
        </section>

        <section>
            <h2>Return Device</h2>
            <form method="post" action="/returns/create">
                <?= csrf_field() ?>
                <label>
                    Device ID
                    <input type="number" name="device_id" min="1" required>
                </label>
                <label>
                    Return Time
                    <input type="datetime-local" name="now" required>
                </label>
                <label>
                    Return Photo (optional)
                    <input type="url" name="photo">
                </label>
                <label>
                    Note (optional)
                    <textarea name="note"></textarea>
                </label>
                <button type="submit">Return</button>
            </form>
        </section>
    </main>
</body>
</html>
