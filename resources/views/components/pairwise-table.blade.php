{{-- File: resources/views/components/pairwise-table.blade.php --}}
@props([
    'items',
    'formAction',
    'checkConsistencyUrl',
    'inputName' => 'comparisons',
    'existingComparisons' => collect(),
])

<div>
    <form action="{{ $formAction }}" id="pairwise-form" method="POST">
        @csrf
        <div class="rounded-lg bg-white p-6 shadow-xl">
            {{-- Slot untuk judul dan deskripsi dari halaman pemanggil --}}
            {{ $slot }}

            <div class="overflow-x-auto">
                <table class="min-w-full border border-gray-200 bg-white">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="border-b border-gray-200 px-4 py-3 text-left text-sm font-semibold text-gray-600">
                                {{ class_basename($items->first()) }}
                            </th>
                            @foreach ($items as $item)
                                <th class="border-b border-gray-200 px-4 py-3 text-sm font-semibold text-gray-600">
                                    {{ $item->name }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $rowItem)
                            <tr class="hover:bg-gray-50">
                                <td class="border-b border-gray-200 px-4 py-3 font-semibold text-gray-700">
                                    {{ $rowItem->name }}
                                </td>
                                @foreach ($items as $colItem)
                                    <td class="border-b border-gray-200 px-4 py-3 text-center">
                                        @if ($rowItem->id === $colItem->id)
                                            <input class="w-20 rounded border-gray-300 bg-gray-200 text-center" disabled
                                                readonly type="text" value="1">
                                        @elseif ($rowItem->id < $colItem->id)
                                            @php
                                                $existing = $existingComparisons->get(
                                                    $rowItem->id . '-' . $colItem->id,
                                                );
                                                $selectedValue = $existing ? $existing->value : 1;
                                            @endphp
                                            <select
                                                class="comparison-select w-full rounded-md border border-gray-300 p-2 focus:border-blue-500 focus:ring-blue-500"
                                                data-col-id="{{ $colItem->id }}" data-row-id="{{ $rowItem->id }}"
                                                name="{{ $inputName }}[{{ $rowItem->id }}][{{ $colItem->id }}]"
                                                required>
                                                <option @selected($selectedValue == 1) value="1">1: Sama penting
                                                </option>
                                                <option @selected($selectedValue == 3) value="3">3: Sedikit lebih
                                                    penting</option>
                                                <option @selected($selectedValue == 5) value="5">5: Cukup lebih
                                                    penting</option>
                                                <option @selected($selectedValue == 7) value="7">7: Jauh lebih
                                                    penting</option>
                                                <option @selected($selectedValue == 9) value="9">9: Mutlak lebih
                                                    penting</option>
                                                <option @selected(abs($selectedValue - 1 / 3) < 0.001) value="{{ 1 / 3 }}">1/3
                                                </option>
                                                <option @selected(abs($selectedValue - 1 / 5) < 0.001) value="{{ 1 / 5 }}">1/5
                                                </option>
                                                <option @selected(abs($selectedValue - 1 / 7) < 0.001) value="{{ 1 / 7 }}">1/7
                                                </option>
                                                <option @selected(abs($selectedValue - 1 / 9) < 0.001) value="{{ 1 / 9 }}">1/9
                                                </option>
                                            </select>
                                        @else
                                            <span
                                                class="reciprocal-span rounded bg-gray-100 px-3 py-1 font-mono text-gray-700"
                                                id="reciprocal-cell-{{ $colItem->id }}-{{ $rowItem->id }}"></span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-6 flex items-center justify-end space-x-4">
                {{-- Hasil CR akan otomatis muncul di sini --}}
                <div class="font-mono text-sm" id="cr-result-container"></div>
                {{-- Tombol "Cek Konsistensi" dihapus --}}
                <button
                    class="rounded-lg bg-blue-600 px-6 py-2 font-bold text-white shadow-md transition duration-300 ease-in-out hover:bg-blue-700"
                    type="submit">
                    Simpan & Lanjutkan
                </button>
            </div>
        </div>
    </form>

    <script>
        (function() {
            const form = document.querySelector('#pairwise-form');

            if (!form) return;

            const selects = form.querySelectorAll('.comparison-select');
            const resultContainer = form.querySelector('#cr-result-container');
            const checkUrl = '{{ $checkConsistencyUrl }}';
            let debounceTimeout;

            function renderReciprocals() {
                const values = {};
                selects.forEach(select => {
                    const rowId = select.dataset.rowId;
                    const colId = select.dataset.colId;
                    if (!values[rowId]) values[rowId] = {};
                    values[rowId][colId] = parseFloat(select.value);
                });

                form.querySelectorAll('.reciprocal-span').forEach(span => {
                    const rowId = span.id.split('-')[3];
                    const colId = span.id.split('-')[2];
                    const value = values[colId]?.[rowId];
                    span.textContent = value ? (1 / value).toFixed(4) : '-';
                });
            }

            async function checkConsistency() {
                resultContainer.innerHTML = `<span class="text-gray-500">Menghitung...</span>`;
                const formData = new FormData(form);
                try {
                    const response = await fetch(checkUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value,
                            'Accept': 'application/json',
                        },
                        body: formData,
                    });
                    const result = await response.json();
                    if (response.ok) {
                        const crValue = parseFloat(result.cr).toFixed(4);
                        const status = result.ok ?
                            `<span class="font-semibold text-green-600">(Konsisten)</span>` :
                            `<span class="font-semibold text-red-600">(Tidak Konsisten)</span>`;
                        resultContainer.innerHTML = `CR: ${crValue} ${status}`;
                    } else {
                        resultContainer.innerHTML =
                            `<span class="text-red-600">${result.message || 'Gagal.'}</span>`;
                    }
                } catch (error) {
                    resultContainer.innerHTML = `<span class="text-red-600">Error koneksi.</span>`;
                }
            }

            // Fungsi debounce untuk menunda eksekusi
            function debounceCheck() {
                clearTimeout(debounceTimeout);
                debounceTimeout = setTimeout(checkConsistency, 500); // Tunda 500ms
            }

            selects.forEach(select => {
                select.addEventListener('input', () => {
                    renderReciprocals(); // Update nilai kebalikan secara langsung
                    debounceCheck(); // Panggil pengecekan konsistensi dengan jeda
                });
            });

            // Panggil fungsi ini saat halaman pertama kali dimuat
            renderReciprocals();
            checkConsistency(); // Lakukan pengecekan awal saat halaman dimuat
        })();
    </script>
</div>
