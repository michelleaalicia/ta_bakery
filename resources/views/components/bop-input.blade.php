@props(['field' => 'bop_cost', 'amount' => 0, 'details' => collect()])
@php
    $existingRows = collect($details)->map(fn ($item) => [
        'category' => $item->category,
        'amount' => (int) $item->amount,
    ])->values()->all();
    $rows = old('bop_items', $existingRows);
    $mode = old('bop_mode', count($existingRows) ? 'detailed' : 'total');
    $categories = ['Penyusutan Peralatan', 'Transportasi / Ongkir', 'Kemasan / Packaging', 'Listrik', 'Gas', 'Air', 'Pemeliharaan Peralatan', 'Lainnya'];
@endphp
<div class="border rounded p-3 my-3" data-bop-widget>
    <h6 class="fw-semibold mb-3">Biaya Overhead Pabrik (BOP)</h6>
    <div class="d-flex flex-wrap gap-3 mb-3">
        <label><input type="radio" name="bop_mode" value="total" @checked($mode === 'total')> Input Total</label>
        <label><input type="radio" name="bop_mode" value="detailed" @checked($mode === 'detailed')> Rincian BOP</label>
    </div>
    <div data-bop-simple>
        <label class="form-label">Total BOP (Rp)</label>
        <input type="text" inputmode="numeric" data-rupiah name="{{ $field }}" class="form-control" value="{{ old($field, $amount === null ? '' : (int) $amount) }}" placeholder="Contoh: 20000" required>
        <div class="form-text">Masukkan total biaya jika tidak perlu dirinci.</div>
    </div>
    <div data-bop-detailed hidden>
        <div data-bop-rows>
            @foreach ($rows as $i => $item)
                <div class="border rounded p-3 mb-2" data-bop-row>
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label">Kategori</label>
                            <select class="form-select" name="bop_items[{{ $i }}][category]" required>
                                @foreach ($categories as $category)
                                    <option value="{{ $category }}" @selected(($item['category'] ?? '') === $category)>{{ $category }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Nominal (Rp)</label>
                            <input type="text" inputmode="numeric" data-rupiah class="form-control"
                                name="bop_items[{{ $i }}][amount]" value="{{ (int) ($item['amount'] ?? 0) }}"
                                placeholder="Contoh: 2000" required>
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="button" class="btn btn-danger btn-sm" data-bop-remove title="Hapus biaya" aria-label="Hapus biaya">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <button type="button" class="btn btn-secondary btn-sm" data-bop-add>
            <i class="bi bi-plus"></i> Tambah Biaya
        </button>
        <div class="mt-3">Total Rincian: <span data-bop-sum>Rp 0</span></div>
    </div>
</div>
@once
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-bop-widget]').forEach(widget => {
        const radios = widget.querySelectorAll('input[name="bop_mode"]');
        const simple = widget.querySelector('[data-bop-simple]');
        const detailed = widget.querySelector('[data-bop-detailed]');
        const rows = widget.querySelector('[data-bop-rows]');
        const summary = widget.querySelector('[data-bop-sum]');
        const categories = {{ Illuminate\Support\Js::from($categories) }};
        let counter = rows.children.length;
        const asDigits = value => String(value ?? '').replace(/\D/g, '');
        const currency = value => 'Rp ' + Number(value || 0).toLocaleString('id-ID');
        const formatField = el => {
            const digits = asDigits(el.value);
            el.value = digits ? Number(digits).toLocaleString('id-ID') : '';
        };
        const updateTotal = () => {
            let sum = 0;
            rows.querySelectorAll('input[name$="[amount]"]').forEach(input => { sum += Number(asDigits(input.value)) || 0; });
            summary.textContent = currency(sum);
        };
        const updateMode = () => {
            const isDetailed = widget.querySelector('input[name="bop_mode"]:checked')?.value === 'detailed';
            simple.hidden = isDetailed;
            detailed.hidden = !isDetailed;
            // Otomatis tampilkan satu baris saat mode rincian dipilih.
            if (isDetailed && rows.children.length === 0) {
                widget.querySelector('[data-bop-add]').click();
            }
            simple.querySelectorAll('input').forEach(input => { input.disabled = isDetailed; input.required = !isDetailed; });
            detailed.querySelectorAll('input, select').forEach(input => { input.disabled = !isDetailed; });
            updateTotal();
        };
        widget.querySelector('[data-bop-add]').addEventListener('click', () => {
            const i = counter++;
            const card = document.createElement('div');
            card.className = 'border rounded p-3 mb-2';
            card.setAttribute('data-bop-row', '');

            const row = document.createElement('div');
            row.className = 'row g-3';

            const categoryCol = document.createElement('div');
            categoryCol.className = 'col-md-5';
            const categoryLabel = document.createElement('label');
            categoryLabel.className = 'form-label';
            categoryLabel.textContent = 'Kategori';
            const select = document.createElement('select');
            select.className = 'form-select';
            select.name = `bop_items[${i}][category]`;
            select.required = true;
            categories.forEach(name => select.add(new Option(name, name)));
            categoryCol.append(categoryLabel, select);

            const amountCol = document.createElement('div');
            amountCol.className = 'col-md-5';
            const amountLabel = document.createElement('label');
            amountLabel.className = 'form-label';
            amountLabel.textContent = 'Nominal (Rp)';
            const amountInput = document.createElement('input');
            amountInput.type = 'text';
            amountInput.inputMode = 'numeric';
            amountInput.className = 'form-control';
            amountInput.placeholder = 'Contoh: 2000';
            amountInput.name = `bop_items[${i}][amount]`;
            amountInput.required = true;
            amountInput.setAttribute('data-rupiah', '');
            amountCol.append(amountLabel, amountInput);

            const actionCol = document.createElement('div');
            actionCol.className = 'col-md-1 d-flex align-items-end';
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'btn btn-danger btn-sm';
            remove.title = 'Hapus biaya';
            remove.setAttribute('aria-label', 'Hapus biaya');
            remove.setAttribute('data-bop-remove', '');
            const icon = document.createElement('i');
            icon.className = 'bi bi-trash';
            remove.appendChild(icon);
            actionCol.appendChild(remove);

            row.append(categoryCol, amountCol, actionCol);
            card.append(row);
            rows.appendChild(card);
            updateTotal();
        });
        widget.addEventListener('click', e => {
            if (e.target.closest('[data-bop-remove]')) { e.target.closest('[data-bop-row]').remove(); updateTotal(); }
        });
        widget.addEventListener('input', e => {
            if (e.target.matches('input[data-rupiah]')) { formatField(e.target); updateTotal(); }
        });
        radios.forEach(radio => radio.addEventListener('change', updateMode));
        widget.querySelectorAll('input[data-rupiah]').forEach(formatField);
        widget.closest('form')?.addEventListener('submit', () => {
            widget.querySelectorAll('input[data-rupiah]:not(:disabled)').forEach(input => input.value = asDigits(input.value));
        });
        updateMode();
    });
});
</script>
@endpush
@endonce
