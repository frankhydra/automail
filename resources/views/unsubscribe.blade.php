<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Unsubscribe - AutoMail</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="bg-white p-8 rounded-lg shadow-md max-w-md w-full text-center">
        <h1 class="text-2xl font-bold text-gray-800 mb-4">Unsubscribe from AutoMail</h1>
        <p class="text-gray-600 mb-6">Are you sure you want to stop receiving emails from this organization?</p>
        
        <form method="POST" action="{{ route('unsubscribe.store') }}">
            @csrf
            <input type="hidden" name="email" value="{{ $email }}">
            <!-- For MVP simplicity, we pull the organization ID from request or session context -->
            <input type="hidden" name="organization_id" value="1"> 
            
            <button type="submit" class="w-full bg-red-600 text-white py-2 px-4 rounded hover:bg-red-700 transition">
                Confirm Unsubscribe
            </button>
        </form>
    </div>
</body>
</html>