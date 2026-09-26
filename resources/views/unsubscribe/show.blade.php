<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unsubscribe - AutoMail</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="max-w-md w-full bg-white shadow-md rounded-lg p-8 text-center">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">Manage Email Preferences</h2>

        <p class="text-gray-600 mb-6 text-sm">
            Do you want to stop receiving emails at
            <strong class="text-gray-800">{{ $maskedEmail }}</strong>?
        </p>

        <form method="POST" action="{{ $actionUrl }}" class="space-y-4">
            @csrf
            <button type="submit" class="w-full py-2 px-4 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-md shadow transition">
                Confirm Unsubscribe
            </button>
        </form>
    </div>
</body>
</html>
