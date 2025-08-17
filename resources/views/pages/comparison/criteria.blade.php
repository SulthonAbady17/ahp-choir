<x-layouts.app title="Perbandingan Kriteria">
    <x-pairwise-table :formAction="route('criterion.store')" :items="$criteria">
        {{-- Konten ini akan masuk ke dalam $slot di komponen tabel --}}
        <h2 class="mb-2 text-2xl font-semibold text-gray-700">Matriks Perbandingan Antar Kriteria</h2>
        <p class="mb-6 text-gray-500">Pilih nilai yang paling merepresentasikan tingkat kepentingan satu kriteria
            dibandingkan dengan kriteria lainnya.</p>
    </x-pairwise-table>

    {{-- Skrip JavaScript tetap di sini karena spesifik untuk halaman ini --}}
    <script>
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
            const res = await fetch('{{ route('ahp.preview-cr') }}', {
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
    </script>
</x-layouts.app>
