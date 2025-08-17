<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta content="width=device-width, initial-scale=1.0" name="viewport">
        <title>Laravel</title>

        @vite('resources/css/app.css')
        @vite('resources/js/app.js')
    </head>

    <body class="bg-gray-100 font-sans">
        <div class="container mx-auto my-10 max-w-4xl px-4">

            <!-- Header Halaman -->
            <div class="mb-8 text-center">
                <h1 class="text-3xl font-bold text-gray-800">Seleksi Ketua Umum Paduan Suara</h1>
                <p class="mt-1 text-gray-600">Metode Analytical Hierarchy Process (AHP)</p>
            </div>

            <div>
                <form action="{{ route('criterion.store') }}" id="pairwise-form" method="POST">
                    @csrf
                    <div class="rounded-lg bg-white p-6 shadow-xl">
                        <h2 class="mb-2 text-2xl font-semibold text-gray-700">Matriks Perbandingan Antar Kriteria</h2>
                        <p class="mb-6 text-gray-500">Pilih nilai yang paling merepresentasikan tingkat kepentingan satu
                            kriteria dibandingkan dengan kriteria lainnya.</p>
                        <div class="overflow-x-auto">
                            <table class="min-w-full border border-gray-200 bg-white">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th
                                            class="border-b border-gray-200 px-4 py-3 text-left text-sm font-semibold text-gray-600">
                                            Kriteria</th>
                                        <!-- Kolom Header Kriteria (Dinamis dari Laravel) -->
                                        @foreach ($criteria as $criterion)
                                            <th
                                                class="border-b border-gray-200 px-4 py-3 text-sm font-semibold text-gray-600">
                                                {{ $criterion->name }}
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($criteria as $criterion_row)
                                        <tr class="hover:bg-gray-50">
                                            <td class="border-b border-gray-200 px-4 py-3 font-semibold text-gray-700">
                                                {{ $criterion_row->name }}
                                            </td>
                                            @foreach ($criteria as $criterion_col)
                                                <td class="border-b border-gray-200 px-4 py-3 text-center">
                                                    @if ($criterion_row->id === $criterion_col->id)
                                                        <input
                                                            class="w-20 rounded border-gray-300 bg-gray-200 text-center disabled:opacity-50"
                                                            disabled readonly type="text" value="1">
                                                    @elseif ($criterion_row->id < $criterion_col->id)
                                                        <select
                                                            class="w-full rounded-md border border-gray-300 p-2 focus:border-blue-500 focus:ring-blue-500"
                                                            data-left="{{ $criterion_col->id }}"
                                                            data-right="{{ $criterion_row->id }}" id="comparison-select"
                                                            name="comparisons[{{ $criterion_row->id }}][{{ $criterion_col->id }}]"
                                                            required>
                                                            <option value="1">1: Sama penting</option>
                                                            <option value="3">3: Sedikit lebih penting</option>
                                                            <option value="5">5: Cukup lebih penting</option>
                                                            <option value="7">7: Jauh lebih penting</option>
                                                            <option value="9">9: Mutlak lebih penting</option>
                                                            <option value="{{ 1 / 3 }}">1/3: Sedikit kurang
                                                                penting
                                                            </option>
                                                            <option value="{{ 1 / 5 }}">1/5: Cukup kurang
                                                                penting
                                                            </option>
                                                            <option value="{{ 1 / 7 }}">1/7: Jauh kurang penting
                                                            </option>
                                                            <option value="{{ 1 / 9 }}">1/9: Mutlak kurang
                                                                penting
                                                            </option>
                                                        </select>
                                                    @else
                                                        <span class="reciprocal italic text-gray-500"
                                                            data-left="{{ $criterion_row->id }}"
                                                            data-right="{{ $criterion_col->id }}"
                                                            id="reciprocal-cell-{{ $criterion_col->id }}-{{ $criterion_row->id }}"></span>
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-6 text-right">
                            <span class="fw-semibold ms-3" id="cr-result"></span>
                            <button
                                class="rounded-lg bg-blue-600 px-6 py-2 font-bold text-white shadow-md transition duration-300 ease-in-out hover:bg-blue-700"
                                type="submit">
                                Simpan & Lanjutkan
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function renderReciprocals() {
                const selects = document.querySelectorAll('#comparison-select');
                const values = {};
                selects.forEach(s => {
                    values[s.dataset.left + '-' + s.dataset.right] = parseFloat(s.value);
                });

                document.querySelectorAll('.reciprocal').forEach(span => {
                    const {
                        left,
                        right
                    } = span.dataset;
                    const key = (left + '-' + right);
                    const value = values[key];
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
    </body>

</html>
