<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Successfully Unsubscribed</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="bg-white p-8 rounded-lg shadow-md max-w-md w-full text-center">
        <h1 class="text-2xl font-bold text-green-600 mb-4">You have been unsubscribed</h1>
        <p class="text-gray-600 mb-6">The email address <strong>{{ $email }}</strong> has been successfully removed from our mailing list.</p>
        <a href="/" class="text-indigo-600 hover:underline">Return to Home</a>
    </div>
</body>
</html>