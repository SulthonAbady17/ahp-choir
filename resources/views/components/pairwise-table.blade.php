@props(['items', 'formAction', 'inputName' => 'comparisons'])

<div>
    <form action="{{ $formAction }}" id="pairwise-form" method="POST">
        @csrf
        <div class="rounded-lg bg-white p-6 shadow-xl">
            {{-- Judul bisa disesuaikan dari halaman yang memanggil --}}
            {{ $slot }}

            <div class="overflow-x-auto">
                <table class="min-w-full border border-gray-200 bg-white">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="border-b border-gray-200 px-4 py-3 text-left text-sm font-semibold text-gray-600">
                                {{ $items->first()->name ?? 'Items' }}
                            </th>
                            @foreach ($items as $item)
                                <th class="border-b border-gray-200 px-4 py-3 text-sm font-semibold text-gray-600">
                                    {{ $item->name }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $item_row)
                            <tr class="hover:bg-gray-50">
                                <td class="border-b border-gray-200 px-4 py-3 font-semibold text-gray-700">
                                    {{ $item_row->name }}
                                </td>
                                @foreach ($items as $item_col)
                                    <td class="border-b border-gray-200 px-4 py-3 text-center">
                                        @if ($item_row->id === $item_col->id)
                                            <input
                                                class="w-20 rounded border-gray-300 bg-gray-200 text-center disabled:opacity-50"
                                                disabled readonly type="text" value="1">
                                        @elseif ($item_row->id < $item_col->id)
                                            <select
                                                class="w-full rounded-md border border-gray-300 p-2 focus:border-blue-500 focus:ring-blue-500"
                                                data-left="{{ $item_col->id }}" data-right="{{ $item_row->id }}"
                                                id="comparison-select"
                                                name="{{ $inputName }}[{{ $item_row->id }}][{{ $item_col->id }}]"
                                                required>
                                                <option value="1">1: Sama penting</option>
                                                <option value="3">3: Sedikit lebih penting</option>
                                                <option value="5">5: Cukup lebih penting</option>
                                                <option value="7">7: Jauh lebih penting</option>
                                                <option value="9">9: Mutlak lebih penting</option>
                                                <option value="{{ 1 / 3 }}">1/3: Sedikit kurang penting
                                                </option>
                                                <option value="{{ 1 / 5 }}">1/5: Cukup kurang penting</option>
                                                <option value="{{ 1 / 7 }}">1/7: Jauh kurang penting</option>
                                                <option value="{{ 1 / 9 }}">1/9: Mutlak kurang penting</option>
                                            </select>
                                        @else
                                            <span class="reciprocal italic text-gray-500"
                                                data-left="{{ $item_row->id }}" data-right="{{ $item_col->id }}"
                                                id="reciprocal-cell-{{ $item_col->id }}-{{ $item_row->id }}"></span>
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
