<x-layouts.app title="Perbandingan Kriteria">
    <x-pairwise-table :checkConsistencyUrl="route('comparison.criteria.check')" :existingComparisons="$existingComparisons" :formAction="route('comparison.criteria.store')" :items="$criteria">
        {{-- Konten ini akan masuk ke dalam $slot di komponen tabel --}}
        <h2 class="mb-2 text-2xl font-semibold text-gray-700">Matriks Perbandingan Antar Kriteria</h2>
        <p class="mb-6 text-gray-500">Pilih nilai yang paling merepresentasikan tingkat kepentingan satu kriteria
            dibandingkan dengan kriteria lainnya.</p>
    </x-pairwise-table>
</x-layouts.app>
