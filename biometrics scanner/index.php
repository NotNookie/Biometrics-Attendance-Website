<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Biometric Time In/Out</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;700;800&family=Plus+Jakarta+Sans:wght@500;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --sidebar: #132238;
            --sidebar-soft: #1e293b;
            --bg: #f8fafc;
            --surface: #ffffff;
            --border: #e2e8f0;
            --text: #0f172a;
            --muted: #64748b;
            --teal: #14b8a6;
            --success: #22c55e;
            --danger: #ef4444;
            --shadow: 0 14px 36px rgba(15, 23, 42, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Plus Jakarta Sans", "Manrope", sans-serif;
        }

        body {
            min-height: 100vh;
            background: var(--bg);
            color: var(--text);
        }

        .scanner-layout {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 230px 1fr;
        }

        .scanner-sidebar {
            background: var(--sidebar);
            border-right: 1px solid #1f314a;
            color: #d3dfef;
            padding: 24px 18px;
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .brand {
            border-bottom: 1px solid #28415f;
            padding-bottom: 18px;
        }

        .brand strong {
            display: block;
            font-size: 2rem;
            line-height: 1;
            color: #f1f5f9;
        }

        .brand span {
            display: block;
            margin-top: 4px;
            color: #20d7c4;
            font-weight: 800;
        }

        .sidebar-note {
            color: #9db0c7;
            line-height: 1.6;
            font-size: 0.95rem;
        }

        .status-card {
            margin-top: auto;
            background: var(--sidebar-soft);
            border: 1px solid #2a415f;
            border-radius: 12px;
            padding: 12px;
        }

        .status-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.86rem;
            color: #d2dfed;
            margin-bottom: 8px;
        }

        .status-item:last-child {
            margin-bottom: 0;
        }

        .status-dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: var(--success);
            margin-right: 6px;
            display: inline-block;
        }

        .scanner-main {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .scanner-topbar {
            background: #ffffff;
            border-bottom: 1px solid var(--border);
            padding: 16px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .scanner-topbar h1 {
            font-size: 2rem;
            line-height: 1.1;
            margin: 0;
        }

        .scanner-topbar p {
            margin-top: 4px;
            color: var(--muted);
        }

        .top-link {
            display: inline-flex;
            align-items: center;
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 999px;
            color: #334155;
            font-weight: 700;
            background: #f8fafc;
            text-decoration: none;
            white-space: nowrap;
        }

        .top-link:hover {
            border-color: #c6d4e2;
            background: #f1f5f9;
        }

        .scanner-content {
            flex: 1;
            padding: 24px;
            display: grid;
            place-items: center;
        }

        .scanner-card {
            width: min(560px, 100%);
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: var(--shadow);
            padding: 28px;
            text-align: center;
        }

        .icon {
            width: 62px;
            height: 62px;
            margin: 0 auto 12px;
            border-radius: 999px;
            background: rgba(20, 184, 166, 0.14);
            color: #0f9488;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            animation: pulse 2s infinite;
        }

        h2 {
            font-size: 1.7rem;
            margin-bottom: 8px;
            font-weight: 700;
            color: var(--text);
        }

        .subtitle {
            font-size: 0.95rem;
            color: var(--muted);
            margin-bottom: 20px;
        }

        .datetime {
            margin-bottom: 25px;
            background: #f8fafc;
            border: 1px solid var(--border);
            padding: 12px;
            border-radius: 12px;
            font-size: 14px;
            color: #334155;
            font-weight: 600;
            line-height: 1.5;
        }

        .input-group {
            text-align: left;
            margin-bottom: 20px;
        }

        .input-group label {
            display: block;
            margin-bottom: 6px;
            color: var(--muted);
            font-size: 0.84rem;
            font-weight: 700;
        }

        .input-group input {
            width: 100%;
            padding: 15px 18px;
            border: 1px solid var(--border);
            outline: none;
            border-radius: 12px;
            font-size: 16px;
            background: #ffffff;
            color: #111827;
            transition: 0.3s ease;
            text-align: left;
        }

        .input-group input:focus {
            border-color: #98d8d1;
            box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.16);
        }

        .buttons {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .action-btn {
            flex: 1;
            min-width: 140px;
            padding: 14px;
            border: none;
            border-radius: 12px;
            font-size: 0.98rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.25s ease;
            box-shadow: 0 10px 20px rgba(15, 23, 42, 0.14);
            color: #ffffff;
        }

        .btn-in {
            background: linear-gradient(180deg, #1bc5b1, #14b8a6);
        }

        .btn-out {
            background: linear-gradient(180deg, #f06666, #ef4444);
        }

        .action-btn:hover {
            transform: translateY(-3px) scale(1.02);
        }

        .action-btn:active {
            transform: scale(0.98);
        }

        .action-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .result {
            margin-top: 25px;
            padding: 15px;
            border-radius: 12px;
            font-weight: 600;
            display: none;
            animation: fadeIn 0.4s ease;
            text-align: left;
        }

        .success {
            background: #eff9f3;
            border: 1px solid #c6e4d3;
            color: #2d7f54;
        }

        .error {
            background: #fff2f2;
            border: 1px solid #efc5c5;
            color: #9f3a3a;
        }

        .footer {
            margin-top: 20px;
            font-size: 0.82rem;
            color: var(--muted);
        }

        .loader {
            display: none;
            margin-top: 15px;
            font-size: 0.9rem;
            color: var(--muted);
        }

        .wave {
            animation: waveHand 0.6s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(25px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.08); opacity: 0.85; }
            100% { transform: scale(1); opacity: 1; }
        }

        @keyframes waveHand {
            0% { transform: rotate(0deg); }
            20% { transform: rotate(15deg); }
            40% { transform: rotate(-10deg); }
            60% { transform: rotate(15deg); }
            80% { transform: rotate(-5deg); }
            100% { transform: rotate(0deg); }
        }

        @media (max-width: 980px) {
            .scanner-layout {
                grid-template-columns: 1fr;
            }

            .scanner-sidebar {
                position: static;
                border-right: 0;
                border-bottom: 1px solid #1f314a;
                gap: 12px;
            }

            .status-card {
                margin-top: 0;
            }
        }

        @media (max-width: 640px) {
            .scanner-topbar {
                padding: 14px;
                flex-direction: column;
                align-items: flex-start;
            }

            .scanner-topbar h1 {
                font-size: 1.5rem;
            }

            .scanner-content {
                padding: 14px;
            }

            .scanner-card {
                padding: 22px 16px;
            }

            .action-btn {
                min-width: 100%;
            }
        }
    </style>
</head>
<body>
<div class="scanner-layout">
    <aside class="scanner-sidebar">
        <div class="brand">
            <strong>Attendance</strong>
            <span>System</span>
        </div>

        <p class="sidebar-note">
            Biometric attendance terminal for employee time in and time out recording.
        </p>

        <div class="status-card" aria-label="Scanner status">
            <div class="status-item">
                <span>Device</span>
                <span><span class="status-dot" aria-hidden="true"></span>Connected</span>
            </div>
            <div class="status-item">
                <span>Server</span>
                <span><span class="status-dot" aria-hidden="true"></span>Online</span>
            </div>
        </div>
    </aside>

    <main class="scanner-main">
        <header class="scanner-topbar">
            <div>
                <h1>Biometric Scanner</h1>
                <p>Record employee attendance from the scanning terminal.</p>
            </div>
            <a class="top-link" href="../admin/attendance.php">Open Attendance Logs</a>
        </header>

        <section class="scanner-content">
            <div class="scanner-card">
                <div class="icon" id="handIcon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 11V6a1 1 0 1 1 2 0v5"></path>
                        <path d="M10 11V5a1 1 0 1 1 2 0v6"></path>
                        <path d="M14 11V6a1 1 0 1 1 2 0v5"></path>
                        <path d="M18 11V8a1 1 0 1 1 2 0v6c0 4-2 8-7 8s-7-4-7-8v-3a1 1 0 1 1 2 0v1"></path>
                    </svg>
                </div>
                <h2>Scan Employee Key</h2>
                <p class="subtitle">Enter a valid employee key and choose Time In or Time Out.</p>

                <div class="datetime" id="datetime"></div>

                <div class="input-group">
                    <label for="fingerprint">Employee Key</label>
                    <input type="text" id="fingerprint" placeholder="Enter Employee Key" />
                </div>

                <div class="buttons">
                    <button class="action-btn btn-in" onclick="submitAttendance('timein.php', 'Time In')">Time In</button>
                    <button class="action-btn btn-out" onclick="submitAttendance('timeout.php', 'Time Out')">Time Out</button>
                </div>

                <div class="loader" id="loader">Processing attendance...</div>

                <div id="result" class="result"></div>

                <div class="footer">
                    Secure Employee Attendance Monitoring System
                </div>
            </div>
        </section>
    </main>
</div>

<script>
    function updateDateTime() {
        const now = new Date();
        const options = {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        };

        const date = now.toLocaleDateString('en-US', options);
        const time = now.toLocaleTimeString('en-US');

        document.getElementById("datetime").innerHTML =
            "Date: " + date + "<br>Time: " + time;
    }

    setInterval(updateDateTime, 1000);
    updateDateTime();

    function waveHand() {
        const hand = document.getElementById("handIcon");
        if (!hand) return;

        hand.classList.remove("wave");
        void hand.offsetWidth;
        hand.classList.add("wave");
    }

    const fingerprintInput = document.getElementById("fingerprint");

    fingerprintInput.addEventListener("focus", waveHand);
    fingerprintInput.addEventListener("click", waveHand);
    fingerprintInput.addEventListener("input", waveHand);

    fingerprintInput.addEventListener("keypress", function(e) {
        if (e.key === "Enter") {
            submitAttendance('timein.php', 'Time In');
        }
    });

    function showResult(message, type = "success") {
        const result = document.getElementById("result");
        result.style.display = "block";
        result.className = "result " + type;
        result.innerHTML = message;
    }

    function setLoading(isLoading) {
        document.getElementById("loader").style.display = isLoading ? "block" : "none";
        document.querySelectorAll(".action-btn").forEach(btn => btn.disabled = isLoading);
    }

    function submitAttendance(url, actionType) {
        waveHand();
        let fp = document.getElementById("fingerprint").value.trim();

        if (fp === "") {
            showResult("Please enter or scan an Employee Key first.", "error");
            return;
        }

        setLoading(true);

        fetch(url, {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "fingerprint=" + encodeURIComponent(fp)
        })
        .then(res => res.text())
        .then(data => {
            setLoading(false);

            const lower = data.toLowerCase();
            if (lower.includes("error") || lower.includes("not found") || lower.includes("failed") || lower.includes("invalid") || lower.includes("no time in")) {
                showResult(data, "error");
            } else {
                showResult(data, "success");
                document.getElementById("fingerprint").value = "";
                document.getElementById("fingerprint").focus();
            }
        })
        .catch(err => {
            setLoading(false);
            showResult("Connection failed. Please check your server.", "error");
            console.error(err);
        });
    }

    window.onload = () => {
        document.getElementById("fingerprint").focus();
    };
</script>
</body>
</html>