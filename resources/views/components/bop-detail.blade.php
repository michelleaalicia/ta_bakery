@props(['details' => collect(), 'total' => null])

<div class="table-responsive">
    <table class="table table-bordered align-middle">
        <thead>
            <tr>
                <th style="width: 60px;">No</th>
                <th>Kategori BOP</th>
                <th>Nominal</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($details as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $item->category }}</td>
                    <td>Rp {{ number_format($item->amount, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td>1</td>
                    <td>BOP Total (tanpa rincian)</td>
                    <td>Rp {{ number_format($total ?? 0, 0, ',', '.') }}</td>
                </tr>
            @endforelse
            <tr>
                <td colspan="2" class="fw-semibold">Total BOP</td>
                <td class="fw-semibold">Rp {{ number_format($total ?? $details->sum('amount'), 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>
</div>
