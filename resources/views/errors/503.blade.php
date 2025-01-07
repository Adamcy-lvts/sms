<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Maintenance | School Management System</title>
    <!-- Import Inter font for premium feel -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.13.3/cdn.js" defer></script>

    <style>
        /* Reset and base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1e293b;
            line-height: 1.5;
        }

        /* Container styles */
        .maintenance-container {
            max-width: 90%;
            width: 700px;
            padding: 3rem;
            background: white;
            border-radius: 24px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05),
                0 8px 10px -6px rgba(0, 0, 0, 0.02);
            text-align: center;
            animation: fadeIn 0.6s ease-out;
        }

        /* Update logo container styles */
        .logo-container {
            margin-bottom: 2rem;
            display: inline-flex;
            padding: 2rem;
            background: #f0f9ff;
            border-radius: 20px;
            position: relative;
            overflow: hidden;
        }

        .icon-stack {
            position: relative;
            width: 64px;
            height: 64px;
        }

        .icon-background {
            position: absolute;
            width: 64px;
            height: 64px;
            color: #bae6fd;
        }

        .icon-middle {
            position: absolute;
            width: 48px;
            height: 48px;
            left: 8px;
            top: 8px;
            color: #0ea5e9;
        }

        .icon-front {
            position: absolute;
            width: 32px;
            height: 32px;
            left: 16px;
            top: 16px;
            color: #0369a1;
        }

        .animate-cog {
            animation: spin 10s linear infinite;
            transform-origin: center;
        }

        .animate-sparkles {
            animation: sparkle 3s ease-in-out infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        @keyframes sparkle {
            0%, 100% {
                opacity: 1;
                transform: scale(1);
            }
            50% {
                opacity: 0.6;
                transform: scale(0.9);
            }
        }

        /* Text styles */
        h1 {
            font-size: 1.875rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: #0f172a;
        }

        .subtitle {
            font-size: 1.125rem;
            color: #64748b;
            margin-bottom: 2rem;
        }

        /* Progress bar */
        .progress-container {
            background: #f1f5f9;
            border-radius: 9999px;
            height: 12px;
            width: 100%;
            margin: 2rem 0;
            overflow: hidden;
            position: relative;
        }

        .progress-bar {
            position: absolute;
            height: 100%;
            width: 50%;
            background: linear-gradient(90deg, #0ea5e9, #2563eb);
            border-radius: 9999px;
            animation: progressSlide 2.5s ease-in-out infinite;
        }

        @keyframes progressSlide {
            0% {
                transform: translateX(-100%);
            }
            100% {
                transform: translateX(200%);
            }
        }

        /* Status indicator */
        .status-indicator {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
            color: #64748b;
            font-size: 0.875rem;
        }

        .pulse {
            width: 8px;
            height: 8px;
            background-color: #22c55e;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        /* Info box */
        .info-box {
            margin-top: 2rem;
            padding: 1rem;
            background: #f0f9ff;
            border-radius: 12px;
            font-size: 0.875rem;
            color: #0369a1;
        }

        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 1;
            }

            50% {
                transform: scale(1.5);
                opacity: 0.5;
            }

            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        /* Responsive adjustments */
        @media (max-width: 640px) {
            .maintenance-container {
                padding: 2rem;
                margin: 1rem;
            }

            h1 {
                font-size: 1.5rem;
            }

            .subtitle {
                font-size: 1rem;
            }
        }
    </style>
</head>

<body>
    <div class="maintenance-container">
        <!-- Replace the logo container content -->
        <div class="logo-container">
            <div class="icon-stack">
                <x-heroicon-o-cog-8-tooth class="icon-background animate-cog" />
                <x-heroicon-o-command-line class="icon-middle animate-sparkles" />
                <x-heroicon-s-wrench-screwdriver class="icon-front" />
            </div>
        </div>

        <!-- Main content -->
        <h1>System Maintenance in Progress</h1>
        <p class="subtitle">We're performing scheduled maintenance to improve your experience.</p>

        <!-- Progress indicator -->
        <div class="progress-container">
            <div class="progress-bar"></div>
        </div>

        <!-- Status -->
        <div class="status-indicator">
            <div class="pulse"></div>
            <span>Maintenance in progress...</span>
        </div>

        <!-- Info box -->
        <div class="info-box">
            <p>Our team is working diligently to complete the maintenance. Expected completion time: 2 hours.</p>
        </div>
    </div>
</body>

</html>
