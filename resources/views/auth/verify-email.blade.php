<x-guest-layout>
    <div class="w-full">
        <div class="mb-6 text-center">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-gray-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-800" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="1.6">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3 7.5A2.5 2.5 0 015.5 5h13A2.5 2.5 0 0121 7.5v9a2.5 2.5 0 01-2.5 2.5h-13A2.5 2.5 0 013 16.5v-9z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 7l8 6 8-6" />
                </svg>
            </div>

            <h1 class="text-2xl font-bold text-gray-900">Verifikasi Email</h1>

            <p class="mt-2 text-sm text-gray-500">
                Kami telah mengirim tautan verifikasi ke
            </p>
            <p class="mt-1 text-sm font-semibold text-gray-800">
                {{ auth()->user()->email }}
            </p>
        </div>

        @if (session('status') == 'verification-link-sent')
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-800">
                Email verifikasi berhasil dikirim ulang. Silakan periksa kotak masuk kamu.
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                {{ session('error') }}
            </div>
        @endif

        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600">
            Klik tautan di email tersebut untuk menyelesaikan verifikasi.
            Belum menerima email? Periksa folder spam atau kirim ulang tautan verifikasi.
        </div>

        <form method="POST" action="{{ route('verification.send') }}" class="mt-6">
            @csrf
            <button type="submit" class="w-full rounded-lg bg-gray-900 px-4 py-3 text-sm font-semibold text-white">
                Kirim Ulang Email Verifikasi
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">
            @csrf
            <button type="submit" class="text-sm text-gray-500 underline">
                Keluar dari akun
            </button>
        </form>
    </div>
</x-guest-layout>