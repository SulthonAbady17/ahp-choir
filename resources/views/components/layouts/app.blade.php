<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta content="width=device-width, initial-scale=1.0" name="viewport">
        {{-- Judul halaman bisa dinamis, dengan judul default 'AHP' --}}
        <title>{{ $title ?? 'AHP' }}</title>

        {{-- Memuat aset dari Vite --}}
        @vite('resources/css/app.css')
        @vite('resources/js/app.js')
    </head>

    <body class="bg-gray-100 font-sans">
        <div class="container mx-auto my-10 max-w-4xl px-4">

            <div class="mb-8 text-center">
                <h1 class="text-3xl font-bold text-gray-800">Seleksi Ketua Umum Paduan Suara</h1>
                <p class="mt-1 text-gray-600">Metode Analytical Hierarchy Process (AHP)</p>
            </div>

            {{-- Slot untuk konten utama dari setiap halaman --}}
            <main>
                {{ $slot }}
            </main>

        </div>
    </body>

</html>
