{{-- File: resources/views/pages/comparison/alternatives.blade.php --}}

{{--
    Halaman ini menggunakan layout utama dan komponen tabel yang sama.
    Perhatikan bagaimana kita melewatkan data yang berbeda ke dalam komponen.
--}}

<x-layouts.app title="Perbandingan Alternatif untuk: {{ $criterion->name }}">

    {{--
        Kita memanggil komponen `pairwise-table` yang sama, tetapi dengan data yang berbeda:
        - :items="$alternatives" -> Sekarang kita membandingkan alternatif.
        - :formAction -> Route ini akan menuju ke method store untuk alternatif.
        - checkConsistencyUrl -> Route ini akan menuju ke method checkConsistency untuk alternatif.
    --}}
    <x-pairwise-table :checkConsistencyUrl="route('comparison.alternatives.check', ['criterion' => $criterion->id])" :existingComparisons="$existingComparisons" :formAction="route('comparison.alternatives.store', ['criterion' => $criterion->id])" :items="$alternatives">
        {{-- Konten di dalam slot ini memberikan konteks pada halaman --}}
        <h2 class="mb-2 text-2xl font-semibold text-gray-700">
            Perbandingan Alternatif Berdasarkan Kriteria
        </h2>
        <p class="mb-6 text-gray-500">
            Seberapa penting setiap alternatif (kandidat) dibandingkan dengan alternatif lainnya,
            <span class="font-semibold text-gray-800">khusus untuk kriteria "{{ $criterion->name }}"</span>?
        </p>

    </x-pairwise-table>

    {{-- Skrip JavaScript tetap di sini karena spesifik untuk halaman ini --}}
    {{-- <script>
        function renderReciprocals() {
            const selects = document.querySelectorAll('#comparison-select');
            const values = {};
            selects.forEach(s => {
                const left = s.dataset.left;
                const right = s.dataset.right;
                // Create nested object if it doesn't exist
                if (!values[left]) {
                    values[left] = {};
                }
                values[left][right] = parseFloat(s.value);
            });

            document.querySelectorAll('.reciprocal').forEach(span => {
                const {
                    left,
                    right
                } = span.dataset;
                // Look for the reciprocal value
                const value = values[right] ? values[right][left] : undefined;
                span.textContent = value ? (1 / value).toFixed(4) : '-';
            });
        }


        document.querySelectorAll('#comparison-select').forEach(s => s.addEventListener('change', renderReciprocals));

        document.querySelectorAll('#comparison-select').forEach(s => s.addEventListener('change', async () => {
            const form = document.getElementById('pairwise-form');
            const fd = new FormData(form);
            const res = await fetch(
                '{{ route('comparison.alternatives.check', ['criterion' => $criterion->id]) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                    },
                    body: fd
                });
            const data = await res.json();
            document.getElementById('cr-result').textContent =
                `CR: ${Number(data.cr).toFixed(4)} (${data.ok ? 'OK' : 'Terlalu tinggi'})`;
        }));

        renderReciprocals();
    </script> --}}
</x-layouts.app>
