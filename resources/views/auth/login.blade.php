<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login - Sistem Bakery</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-gray-50">

    <div class="min-h-screen flex items-center justify-center px-4">

        <div class="w-full max-w-[390px] bg-white border border-gray-200 rounded-lg px-7 py-8 shadow-sm">

            {{-- Logo & Header --}}
            <div class="flex flex-col items-center mb-6">

                <div class="w-12 h-12 border border-gray-300 bg-gray-50 flex items-center justify-center mb-3">
                    <span class="text-xs text-gray-400">
                        Logo
                    </span>
                </div>

                <h1 class="text-xl font-bold text-gray-900">
                    Sistem Bakery
                </h1>

                <p class="text-sm text-gray-500 mt-1">
                    Masuk ke Sistem
                </p>

            </div>



            {{-- Login Form --}}
            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="space-y-4">

                    {{-- Email --}}
                    <div>
                        <label for="email" class="block text-sm text-gray-700 mb-1">
                            Email
                        </label>

                        <div class="relative">

                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <rect x="3" y="5" width="18" height="14" rx="2" />
                                    <path d="M3 7l9 6 9-6" />
                                </svg>
                            </span>

                            <input id="email" name="email" type="email" value="{{ old('email') }}"
                                placeholder="Alamat email Anda" required autofocus autocomplete="username"
                                class="w-full h-[42px] pl-9 pr-3 border border-gray-300 rounded text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:border-gray-500">

                        </div>

                    </div>


                    {{-- Password --}}
                    <div>
                        <label for="password" class="block text-sm text-gray-700 mb-1">
                            Password
                        </label>

                        <div class="relative">

                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <rect x="5" y="10" width="14" height="10" rx="2" />
                                    <path d="M8 10V7a4 4 0 018 0v3" />
                                </svg>
                            </span>

                            <input id="password" name="password" type="password" placeholder="Password Anda" required
                                autocomplete="current-password"
                                class="w-full h-[42px] pl-9 pr-10 border border-gray-300 rounded text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:border-gray-500">

                            <button type="button" onclick="togglePassword()"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                                <svg id="eyeIcon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                            </button>

                        </div>

                    </div>


                    {{-- Login Button --}}
                    <button type="submit"
                        class="w-full h-[42px] bg-gray-900 text-white text-sm font-semibold rounded hover:bg-gray-700 transition">
                        Masuk
                    </button>

                </div>

            </form>


            {{-- Register --}}
            <p class="text-center text-sm text-gray-500 mt-4">
                Belum memiliki akun sebagai Owner?

                <a href="{{ route('register') }}" class="text-gray-900 font-semibold underline">
                    Daftar di sini
                </a>
            </p>

        </div>

    </div>


    {{-- Password Toggle --}}
    <script>
        function togglePassword() {
            const password = document.getElementById('password');

            if (password.type === 'password') {
                password.type = 'text';
            } else {
                password.type = 'password';
            }
        }
    </script>

</body>

</html>