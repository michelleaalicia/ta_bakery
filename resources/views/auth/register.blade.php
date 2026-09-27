<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Daftar sebagai Owner - Sistem Bakery</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-gray-50">

    <div class="min-h-screen flex items-center justify-center px-4 py-8">

        <div class="w-full max-w-[390px] bg-white border border-gray-200 rounded-lg p-7 shadow-sm">

            {{-- Logo --}}
            <div class="flex flex-col items-center mb-6">

                <div class="w-12 h-12 border border-gray-300 bg-gray-50 flex items-center justify-center mb-3">
                    <span class="text-xs text-gray-400">
                        Logo
                    </span>
                </div>

                <h1 class="text-xl font-bold text-gray-900">
                    Sistem Bakery
                </h1>

                <p class="text-sm font-medium text-gray-700 mt-0.5">
                    Daftar sebagai Owner
                </p>

                <p class="text-xs text-gray-500 mt-1 text-center">
                    Daftarkan bakery Anda untuk mulai menggunakan sistem.
                </p>

            </div>


            <form method="POST" action="{{ route('register') }}">
                @csrf

                {{-- INFORMASI BAKERY --}}
                <div class="mb-6">

                    <h3
                        class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3 pb-2 border-b border-gray-100">
                        Informasi Bakery
                    </h3>

                    <div class="space-y-3">

                        {{-- Nama Bakery --}}
                        <div>
                            <label for="tenant_name" class="block text-sm text-gray-700 mb-1">
                                Nama Bakery
                            </label>

                            <input id="tenant_name" name="tenant_name" type="text" value="{{ old('tenant_name') }}"
                                placeholder="Nama bakery Anda" required
                                class="w-full h-[42px] px-3 border border-gray-300 rounded text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:border-gray-500">

                            @error('tenant_name')
                                <p class="text-xs text-red-500 mt-1">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>


                        {{-- Nomor Telepon --}}
                        <div>
                            <label for="phone_number" class="block text-sm text-gray-700 mb-1">
                                Nomor Telepon
                            </label>

                            <input id="phone_number" name="phone_number" type="text" value="{{ old('phone_number') }}"
                                placeholder="08xxxxxxxxxx" required
                                class="w-full h-[42px] px-3 border border-gray-300 rounded text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:border-gray-500">

                            @error('phone_number')
                                <p class="text-xs text-red-500 mt-1">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>


                        {{-- Alamat --}}
                        <div>
                            <label for="address" class="block text-sm text-gray-700 mb-1">
                                Alamat
                            </label>

                            <textarea id="address" name="address" rows="2" placeholder="Alamat lengkap bakery" required
                                class="w-full px-3 py-2 border border-gray-300 rounded text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:border-gray-500 resize-none">{{ old('address') }}</textarea>

                            @error('address')
                                <p class="text-xs text-red-500 mt-1">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                    </div>
                </div>


                {{-- INFORMASI PEMILIK --}}
                <div class="mb-6">

                    <h3
                        class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3 pb-2 border-b border-gray-100">
                        Informasi Pemilik
                    </h3>

                    <div class="space-y-3">

                        {{-- Nama Pemilik --}}
                        <div>
                            <label for="name" class="block text-sm text-gray-700 mb-1">
                                Nama Pemilik
                            </label>

                            <input id="name" name="name" type="text" value="{{ old('name') }}"
                                placeholder="Nama lengkap" required
                                class="w-full h-[42px] px-3 border border-gray-300 rounded text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:border-gray-500">

                            @error('name')
                                <p class="text-xs text-red-500 mt-1">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>


                        {{-- Email --}}
                        <div>
                            <label for="email" class="block text-sm text-gray-700 mb-1">
                                Email
                            </label>

                            <input id="email" name="email" type="email" value="{{ old('email') }}"
                                placeholder="Alamat email Anda" required
                                class="w-full h-[42px] px-3 border border-gray-300 rounded text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:border-gray-500">

                            @error('email')
                                <p class="text-xs text-red-500 mt-1">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>


                        {{-- Password --}}
                        <div>
                            <label for="password" class="block text-sm text-gray-700 mb-1">
                                Password
                            </label>

                            <input id="password" name="password" type="password" placeholder="Password Anda" required
                                class="w-full h-[42px] px-3 border border-gray-300 rounded text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:border-gray-500">

                            @error('password')
                                <p class="text-xs text-red-500 mt-1">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>


                        {{-- Konfirmasi Password --}}
                        <div>
                            <label for="password_confirmation" class="block text-sm text-gray-700 mb-1">
                                Konfirmasi Password
                            </label>

                            <input id="password_confirmation" name="password_confirmation" type="password"
                                placeholder="Ulangi password Anda" required
                                class="w-full h-[42px] px-3 border border-gray-300 rounded text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:border-gray-500">
                        </div>

                    </div>
                </div>


                {{-- BUTTON --}}
                <button type="submit"
                    class="w-full h-[42px] bg-gray-900 text-white text-sm font-semibold rounded hover:bg-gray-700 transition">
                    Daftar sebagai Owner
                </button>

            </form>


            {{-- LOGIN --}}
            <p class="text-center text-sm text-gray-500 mt-4">
                Sudah memiliki akun?

                <a href="{{ route('login') }}" class="text-gray-900 font-semibold underline">
                    Masuk
                </a>
            </p>

        </div>

    </div>

</body>

</html>