<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Biometric Time In/Out</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #0f172a, #1e3a8a, #2563eb);
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            overflow-x: hidden;
        }

        .container {
            width: 100%;
            max-width: 450px;
        }

        .card {
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-radius: 25px;
            padding: 35px 25px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.25);
            color: white;
            text-align: center;
            animation: fadeIn 0.8s ease;
        }

        .icon {
            font-size: 70px;
            margin-bottom: 15px;
            animation: pulse 2s infinite;
            display: inline-block;
        }

        h2 {
            font-size: 28px;
            margin-bottom: 8px;
            font-weight: 700;
        }

        .subtitle {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 20px;
        }

        .datetime {
            margin-bottom: 25px;
            background: rgba(255,255,255,0.12);
            padding: 12px;
            border-radius: 15px;
            font-size: 14px;
            font-weight: 500;
        }

        .input-group {
            position: relative;
            margin-bottom: 20px;
        }

        .input-group input {
            width: 100%;
            padding: 15px 18px;
            border: none;
            outline: none;
            border-radius: 15px;
            font-size: 16px;
            background: rgba(255,255,255,0.95);
            color: #111827;
            transition: 0.3s ease;
            text-align: center;
        }

        .input-group input:focus {
            transform: scale(1.02);
            box-shadow: 0 0 0 4px rgba(255,255,255,0.2);
        }

        .buttons {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }

        button {
            flex: 1;
            min-width: 140px;
            padding: 14px;
            border: none;
            border-radius: 15px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.25s ease;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }

        .btn-in {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
        }

        .btn-out {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }

        button:hover {
            transform: translateY(-3px) scale(1.02);
        }

        button:active {
            transform: scale(0.98);
        }

        button:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .result {
            margin-top: 25px;
            padding: 15px;
            border-radius: 15px;
            font-weight: 600;
            display: none;
            animation: fadeIn 0.4s ease;
        }

        .success {
            background: rgba(34, 197, 94, 0.2);
            border: 1px solid rgba(34, 197, 94, 0.4);
            color: #dcfce7;
        }

        .error {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.4);
            color: #fee2e2;
        }

        .footer {
            margin-top: 20px;
            font-size: 12px;
            opacity: 0.8;
        }

        .loader {
            display: none;
            margin-top: 15px;
            font-size: 14px;
            opacity: 0.9;
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

        @media (max-width: 500px) {
            .card {
                padding: 28px 18px;
                border-radius: 20px;
            }

            h2 {
                font-size: 24px;
            }

            .icon {
                font-size: 60px;
            }

            button {
                min-width: 100%;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <div class="icon" id="handIcon">🖐️</div>
        <h2>Biometric Attendance</h2>
        <p class="subtitle">Scan fingerprint to record attendance</p>

        <div class="datetime" id="datetime"></div>

        <div class="input-group">
            <input type="text" id="fingerprint" placeholder="Enter Employee Key" />
        </div>

        <div class="buttons">
            <button class="btn-in" onclick="submitAttendance('timein.php', 'Time In')">Time In</button>
            <button class="btn-out" onclick="submitAttendance('timeout.php', 'Time Out')">Time Out</button>
        </div>

        <div class="loader" id="loader">⏳ Processing attendance...</div>

        <div id="result" class="result"></div>

        <div class="footer">
            Secure Employee Attendance Monitoring System
        </div>
    </div>
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

        document.getElementById("datetime").innerHTML = `
            📅 ${date}<br>🕒 ${time}
        `;
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
        document.querySelectorAll("button").forEach(btn => btn.disabled = isLoading);
    }

    function submitAttendance(url, actionType) {
        waveHand();
        let fp = document.getElementById("fingerprint").value.trim();

        if (fp === "") {
            showResult("⚠️ Please enter or scan an Employee Key first.", "error");
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
            showResult("❌ Connection failed. Please check your server.", "error");
            console.error(err);
        });
    }

    window.onload = () => {
        document.getElementById("fingerprint").focus();
    };
</script>
</body>
</html>