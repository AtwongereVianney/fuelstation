<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petrol Station Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script>
        // Redirect to login.php after 2 seconds
        setTimeout(function() {
            window.location.href = 'login.php';
        }, 2000);
    </script>
</head>
<body class="min-h-screen bg-gradient-to-br from-cyan-900 via-gray-900 to-emerald-900 flex items-center justify-center">
    <div class="flex flex-col items-center justify-center w-full px-4">
        <!-- Logo with glow -->
        <div class="mb-8">
            <div class="w-24 h-24 rounded-full bg-yellow-400 flex items-center justify-center text-5xl font-bold shadow-2xl ring-4 ring-yellow-300 animate-pulse">
                <span>⛽</span>
            </div>
        </div>
        <h1 class="text-4xl font-extrabold text-black mb-2 text-center drop-shadow-lg">Welcome to</h1>
        <h2 class="text-2xl font-bold text-yellow-400 mb-6 text-center drop-shadow">Petrol Station Management System</h2>
        <a href="login.php" class="px-10 py-4 bg-yellow-400 text-black text-xl font-bold rounded-full shadow-lg hover:bg-yellow-300 transition">Login to System</a>
        <div class="mt-6 text-yellow-200 text-lg font-semibold animate-pulse">Redirecting to login...</div>
    </div>
</body>
</html> 