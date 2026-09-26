<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Invitation - AutoMail</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen py-12">
    <div class="max-w-md w-full bg-white shadow-md rounded-lg p-8">

        @if ($errors->any())
            <div class="mb-4 p-3 bg-red-100 border border-red-400 text-red-800 rounded text-sm">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <h2 class="text-xl font-bold text-gray-800 mb-2">You're invited to {{ $invitation->organization->name }}</h2>
        <p class="text-sm text-gray-600 mb-6">
            {{ $invitation->inviter?->name ?? 'A team member' }} invited <strong>{{ $invitation->email }}</strong>
            to join as a <strong>{{ ucfirst($invitation->role) }}</strong>.
        </p>

        @if ($mode === 'wrong_account')
            <p class="text-sm text-red-700 mb-4">
                You're currently logged in with a different account. Log out first, then open this invitation link again.
            </p>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full py-2 px-4 bg-gray-700 hover:bg-gray-800 text-white font-semibold rounded-md">
                    Log out
                </button>
            </form>
        @elseif ($mode === 'existing_account')
            <p class="text-sm text-gray-600 mb-4">
                You already have an AutoMail account. Log in to accept this invitation.
            </p>
            <a href="{{ route('login') }}" class="block text-center w-full py-2 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-md">
                Log in to accept
            </a>
        @else
            <p class="text-sm text-gray-600 mb-4">Create your account to join.</p>
            <form method="POST" action="{{ route('invitations.register', $invitation->token) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700">Your name</label>
                    <input type="text" name="name" value="{{ old('name') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" value="{{ $invitation->email }}" disabled class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Password</label>
                    <input type="password" name="password" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Confirm password</label>
                    <input type="password" name="password_confirmation" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
                </div>
                <button type="submit" class="w-full py-2 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-md">
                    Create account & join
                </button>
            </form>
        @endif
    </div>
</body>
</html>
