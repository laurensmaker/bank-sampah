@extends('frontend.nasabah.main-dasbor')
@section('dasbor-content')

{{-- Header --}}
<div class="mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">Selamat Datang, {{ $armada->nama_armada ?? 'Armada' }}</h4>
            <p class="text-muted small mb-0">{{ now()->isoFormat('dddd, D MMMM Y') }}</p>
            @if($optimasiAktif && isset($optimasiHariIni))
            <span class="badge bg-success mt-1">
                <i class="bi bi-check-circle me-1"></i> Rute Aktif
            </span>
            @endif
        </div>
        {{-- @if($optimasiAktif && isset($tugas) && $tugas->isNotEmpty())
        <a href="{{ route('armada.rute') }}" class="btn btn-outline-success rounded-pill">
            <i class="bi bi-geo-alt me-2"></i> Lihat Rute
        </a>
        @endif --}}
        <div class="d-flex gap-2 align-items-center flex-wrap">
            
            {{-- ✅ Badge: jumlah permintaan baru --}}
            @if(isset($permintaanMenungguBelumDiproses) && $permintaanMenungguBelumDiproses > 0)
                <span class="badge bg-warning text-dark">
                    <i class="bi bi-bell-fill me-1"></i>
                    {{ $permintaanMenungguBelumDiproses }} permintaan baru
                </span>
            @endif

            {{-- ✅ Tombol Lihat Rute (jika ada tugas) --}}
            @if(isset($optimasiAktif) && $optimasiAktif)
                <a href="{{ route('armada.rute') }}" class="btn btn-outline-success rounded-pill">
                    <i class="bi bi-geo-alt me-2"></i> Lihat Rute
                </a>
            @endif

            {{-- ✅ Tombol Optimasi Rute --}}
            @if(isset($adaPermintaanMenunggu) && $adaPermintaanMenunggu)
                {{-- Ada permintaan baru → tombol MUNCUL --}}
                <a href="{{ route('armada.optimasi.proses') }}" 
                class="btn btn-success rounded-pill px-4"
                onclick="return confirm('⚠️ Anda akan menjadi PRIORITAS pembagian rute.\n\n{{ $permintaanMenungguBelumDiproses ?? 0 }} permintaan baru akan dioptimasi.\n\nLanjutkan?')">
                    <i class="bi bi-robot me-2"></i> Optimasi Rute
                    <span class="badge bg-light text-dark ms-1">
                        {{ $permintaanMenungguBelumDiproses ?? 0 }} baru
                    </span>
                </a>
            @else
                {{-- Tidak ada permintaan baru → tombol mati --}}
                <button type="button" class="btn btn-secondary rounded-pill px-4" disabled>
                    <i class="bi bi-check-circle me-2"></i> Semua Sudah Dioptimasi
                </button>
            @endif
        </div>
    </div>
</div>

{{-- ✅ NOTIFIKASI KAPASITAS PENUH --}}
@if(isset($optimasiAktif) && $optimasiAktif)
    @if(isset($sisaKapasitas) && $sisaKapasitas <= 0)
    <div class="alert alert-warning border-0 rounded-4 d-flex align-items-center gap-3 mb-4">
        <i class="bi bi-exclamation-triangle-fill fs-3 text-warning"></i>
        <div>
            <h6 class="mb-1 fw-bold">⚠️ Kapasitas Armada Penuh!</h6>
            <p class="mb-0 small">
                Kapasitas armada hari ini sudah penuh ({{ number_format($kapasitasTerpakai ?? 0, 1) }} kg dari {{ $armada->kapasitas ?? 25 }} kg).
                @if(isset($permintaanBesok) && $permintaanBesok > 0)
                <br><strong>{{ $permintaanBesok }} permintaan</strong> akan diproses besok.
                @endif
            </p>
        </div>
    </div>
    @elseif(isset($sisaKapasitas) && $sisaKapasitas > 0 && $sisaKapasitas < 10)
    <div class="alert alert-info border-0 rounded-4 d-flex align-items-center gap-3 mb-4">
        <i class="bi bi-info-circle-fill fs-3 text-info"></i>
        <div>
            <h6 class="mb-1 fw-bold">Sisa Kapasitas: {{ number_format($sisaKapasitas, 1) }} kg</h6>
            <p class="mb-0 small">
                Kapasitas armada masih tersisa {{ number_format($sisaKapasitas, 1) }} kg untuk hari ini.
                @if(isset($permintaanBesok) && $permintaanBesok > 0)
                <br>{{ $permintaanBesok }} permintaan sudah dijadwalkan untuk besok.
                @endif
            </p>
        </div>
    </div>
    @endif
@endif

{{-- ✅ NOTIFIKASI PERMINTAAN BESOK --}}
@if(isset($permintaanBesok) && $permintaanBesok > 0)
<div class="alert alert-secondary border-0 rounded-4 d-flex align-items-center gap-3 mb-4">
    <i class="bi bi-calendar-plus fs-3 text-secondary"></i>
    <div>
        <h6 class="mb-1 fw-bold">📅 Ada Permintaan untuk Besok</h6>
        <p class="mb-0 small">
            Terdapat <strong>{{ $permintaanBesok }} permintaan</strong> yang akan diproses besok 
            karena kapasitas armada hari ini sudah penuh.
        </p>
    </div>
</div>
@endif

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 text-center py-3">
            <div class="fs-2 fw-bold text-primary">{{ $totalTugas ?? 0 }}</div>
            <div class="text-muted small">Total Tugas</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 text-center py-3">
            <div class="fs-2 fw-bold text-success">{{ $totalSelesai ?? 0 }}</div>
            <div class="text-muted small">Selesai</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 text-center py-3">
            {{-- ✅ Belum dijemput = tugasAktif --}}
            <div class="fs-2 fw-bold text-warning">{{ $tugasAktif->count() ?? 0 }}</div>
            <div class="text-muted small">Belum Dijemput</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 text-center py-3">
            <div class="fs-2 fw-bold text-info">{{ number_format($totalJarak ?? 0, 1) }}</div>
            <div class="text-muted small">Total Jarak (km)</div>
        </div>
    </div>
</div>

{{-- ✅ KARTU KAPASITAS --}}
@if(isset($optimasiAktif) && $optimasiAktif)
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-4 p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small">Kapasitas Terpakai</div>
                    <div class="fs-4 fw-bold">{{ number_format($kapasitasTerpakai ?? 0, 1) }} kg</div>
                </div>
                <div class="text-end">
                    <div class="text-muted small">Sisa Kapasitas</div>
                    <div class="fs-4 fw-bold text-{{ ($sisaKapasitas ?? 0) <= 0 ? 'danger' : 'success' }}">
                        {{ number_format($sisaKapasitas ?? 0, 1) }} kg
                    </div>
                </div>
            </div>
            <div class="progress mt-2" style="height: 8px;">
                @php
                    $totalKapasitas = $armada->kapasitas ?? 25;
                    $persentase = ($totalKapasitas > 0) ? (($kapasitasTerpakai ?? 0) / $totalKapasitas) * 100 : 0;
                @endphp
                <div class="progress-bar bg-{{ $persentase >= 100 ? 'danger' : ($persentase >= 80 ? 'warning' : 'success') }}" 
                     role="progressbar" 
                     style="width: {{ min($persentase, 100) }}%"
                     aria-valuenow="{{ $persentase }}" 
                     aria-valuemin="0" 
                     aria-valuemax="100">
                </div>
            </div>
            <div class="text-muted small mt-1">
                {{ number_format($persentase, 1) }}% dari {{ $totalKapasitas }} kg
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-4 p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small">Permintaan Hari Ini</div>
                    <div class="fs-4 fw-bold text-primary">{{ $totalTugas ?? 0 }}</div>
                </div>
                <div class="text-end">
                    <div class="text-muted small">Jadwal Besok</div>
                    <div class="fs-4 fw-bold text-secondary">{{ $permintaanBesok ?? 0 }}</div>
                     @if(isset($permintaanBesok) && $permintaanBesok > 0)
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill mx-2" 
                            data-bs-toggle="modal" data-bs-target="#modalPermintaanBesok">
                        <i class="bi bi-eye me-1"></i> Lihat Detail
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endif

{{-- Info Optimasi --}}
@if(!isset($optimasiAktif) || !$optimasiAktif)
<div class="alert alert-info border-0 rounded-4 d-flex align-items-center gap-3 mb-4">
    <i class="bi bi-info-circle-fill fs-3"></i>
    <div>
        <h6 class="mb-1 fw-bold">Belum Ada Optimasi Rute</h6>
        <p class="mb-0 small">
            Klik tombol <strong>"Optimasi Rute"</strong> untuk membagi permintaan penjemputan ke armada.
            @if(isset($adaPermintaanMenunggu) && !$adaPermintaanMenunggu)
            <br><span class="text-danger">⚠️ Tidak ada permintaan dengan status "Menunggu".</span>
            @endif
        </p>
    </div>
</div>
@endif

{{-- Daftar Tugas --}}
{{-- ═══════════════════════════════════════════════════════ --}}
{{-- TABEL 1: TUGAS AKTIF (BELUM SELESAI) --}}
{{-- ═══════════════════════════════════════════════════════ --}}
    @if(isset($tugasAktif) && $tugasAktif->isNotEmpty())
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="fw-bold mb-0">
                    <i class="bi bi-list-check text-success me-2"></i>
                    Daftar Penjemputan Hari Ini (Urutan Rute)
                </h5>
                <small class="text-muted">
                    {{ $tugasAktif->count() }} titik penjemputan belum selesai
                </small>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>Nasabah</th>
                                <th>Alamat</th>
                                <th>Jenis Sampah</th>
                                <th>Berat</th>
                                <th>Jarak</th>
                                <th>Status</th>
                                <th style="width: 120px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tugasAktif as $item)
                            <tr>
                                <td class="fw-bold text-success text-center">{{ $item->urutan ?? '-' }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $item->permintaan->nasabah->nama_nasabah ?? 'Tidak Diketahui' }}</div>
                                    <small class="text-muted">ID: #{{ $item->id_permintaan }}</small>
                                </td>
                                <td>
                                    <span title="{{ $item->permintaan->alamat_penjemputan ?? '-' }}">
                                        {{ Str::limit($item->permintaan->alamat_penjemputan ?? '-', 30) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">
                                        <i class="bi bi-trash me-1"></i>
                                        {{ ucfirst($item->permintaan->jenis_sampah ?? '-') }}
                                    </span>
                                </td>
                                <td>
                                    <span class="fw-bold">{{ number_format($item->permintaan->berat_sampah ?? 0, 1) }}</span> kg
                                </td>
                                <td>
                                    <span class="text-muted">{{ number_format($item->jarak ?? 0, 2) }} km</span>
                                </td>
                                <td>
                                    @php
                                        $status = optional($item->permintaan->statusTerkini)->status ?? 'Menunggu';
                                        $badgeColor = match($status) {
                                            'Selesai' => 'success',
                                            'Diproses' => 'info',
                                            'Dijadwalkan' => 'primary',
                                            'Menunggu' => 'warning',
                                            'Dibatalkan' => 'danger',
                                            default => 'secondary'
                                        };
                                        $icon = match($status) {
                                            'Selesai' => 'bi-check-circle-fill',
                                            'Diproses' => 'bi-arrow-repeat',
                                            'Dijadwalkan' => 'bi-calendar-check',
                                            'Menunggu' => 'bi-clock',
                                            'Dibatalkan' => 'bi-x-circle-fill',
                                            default => 'bi-circle'
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $badgeColor }}">
                                        <i class="bi {{ $icon }} me-1"></i>
                                        {{ $status }}
                                    </span>
                                </td>
                                <td>
                                    <form action="{{ route('armada.selesai', $item->id_permintaan) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success rounded-pill" 
                                                onclick="return confirm('Apakah Anda yakin penjemputan ini sudah selesai?')">
                                            <i class="bi bi-check2 me-1"></i> Selesai
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════ --}}
    {{-- TABEL 2: RIWAYAT SELESAI --}}
    {{-- ═══════════════════════════════════════════════════════ --}}
    @if(isset($tugasSelesai) && $tugasSelesai->isNotEmpty())
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="card-header bg-success bg-opacity-10 border-0 py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold mb-0 text-success">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            Riwayat Selesai Hari Ini
                        </h5>
                        <small class="text-muted">
                            {{ $tugasSelesai->count() }} titik penjemputan sudah selesai
                        </small>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary rounded-pill" type="button" 
                            data-bs-toggle="collapse" data-bs-target="#riwayatSelesai">
                        <i class="bi bi-chevron-down me-1"></i> Tampilkan/Sembunyikan
                    </button>
                </div>
            </div>
            <div class="collapse" id="riwayatSelesai">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th>Nasabah</th>
                                    <th>Alamat</th>
                                    <th>Jenis Sampah</th>
                                    <th>Berat</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tugasSelesai as $item)
                                @php
                                    $status = optional($item->permintaan->statusTerkini)->status ?? 'Selesai';
                                    $isDibatalkan = $status === 'Dibatalkan';
                                @endphp
                                <tr class="{{ $isDibatalkan ? 'table-danger' : 'table-success' }} bg-opacity-25">
                                    <td class="text-muted text-center">{{ $item->urutan ?? '-' }}</td>
                                    <td>
                                        <div class="fw-semibold text-muted">{{ $item->permintaan->nasabah->nama_nasabah ?? '-' }}</div>
                                        <small class="text-muted">ID: #{{ $item->id_permintaan }}</small>
                                    </td>
                                    <td>
                                        <span class="text-muted" title="{{ $item->permintaan->alamat_penjemputan ?? '-' }}">
                                            {{ Str::limit($item->permintaan->alamat_penjemputan ?? '-', 30) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark">
                                            {{ ucfirst($item->permintaan->jenis_sampah ?? '-') }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-muted">{{ number_format($item->permintaan->berat_sampah ?? 0, 1) }}</span> kg
                                    </td>
                                    <td>
                                        @if($isDibatalkan)
                                        <span class="badge bg-danger">
                                            <i class="bi bi-x-circle-fill me-1"></i> Dibatalkan
                                        </span>
                                        @else
                                        <span class="badge bg-success">
                                            <i class="bi bi-check-circle-fill me-1"></i> Selesai
                                        </span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════ --}}
    {{-- KOSONG: TIDAK ADA TUGAS SAMA SEKALI --}}
    {{-- ═══════════════════════════════════════════════════════ --}}
    @if((!isset($tugasAktif) || $tugasAktif->isEmpty()) && (!isset($tugasSelesai) || $tugasSelesai->isEmpty()))
        <div class="card border-0 shadow-sm rounded-4 p-5 text-center text-muted">
            <i class="bi bi-inbox fs-1 mb-3 opacity-50"></i>
            <h5>Belum Ada Tugas Penjemputan</h5>
            <p class="small mb-0">Silakan klik tombol "Optimasi Rute" untuk membagi permintaan penjemputan.</p>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════ --}}
    {{-- SEMUA SELESAI --}}
    {{-- ═══════════════════════════════════════════════════════ --}}
    @if((!isset($tugasAktif) || $tugasAktif->isEmpty()) && isset($tugasSelesai) && $tugasSelesai->isNotEmpty())
        <div class="alert alert-success border-0 rounded-4 d-flex align-items-center gap-3 mb-4">
            <i class="bi bi-trophy-fill fs-3 text-warning"></i>
            <div>
                <h6 class="mb-1 fw-bold">🎉 Semua Tugas Hari Ini Selesai!</h6>
                <p class="mb-0 small">
                    Anda telah menyelesaikan <strong>{{ $tugasSelesai->count() }} titik penjemputan</strong> hari ini.
                    Terima kasih atas kerja kerasnya!
                </p>
            </div>
    </div>
    @endif
{{-- ═══════════════════════════════════════════════════════ --}}
{{-- MODAL: DETAIL PERMINTAAN BESOK --}}
{{-- ═══════════════════════════════════════════════════════ --}}
@if(isset($permintaanBesok) && $permintaanBesok > 0)
<div class="modal fade" id="modalPermintaanBesok" tabindex="-1" aria-labelledby="modalPermintaanBesokLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-secondary text-white border-0 rounded-top-4">
                <h5 class="modal-title" id="modalPermintaanBesokLabel">
                    <i class="bi bi-calendar-event me-2"></i> Jadwal Penjemputan Besok
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;" class="text-center">#</th>
                                <th>Nasabah</th>
                                <th>Alamat Penjemputan</th>
                                <th>Jenis Sampah</th>
                                <th>Berat</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($detailPermintaanBesok as $index => $item)
                            <tr>
                                <td class="text-center text-muted">{{ $index + 1 }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $item->nasabah->nama_nasabah ?? 'Nasabah' }}</div>
                                    <small class="text-muted">ID: #{{ $item->id }}</small>
                                </td>
                                <td>
                                    <span title="{{ $item->alamat_penjemputan }}">
                                        {{ Str::limit($item->alamat_penjemputan ?? '-', 40) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">
                                        {{ ucfirst($item->jenis_sampah ?? '-') }}
                                    </span>
                                </td>
                                <td>
                                    <span class="fw-bold">{{ number_format($item->berat_sampah ?? 0, 1) }}</span> kg
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                    Tidak ada data permintaan untuk besok.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-0">
                <div class="text-muted small me-auto">
                    Total: <strong>{{ $permintaanBesok }} permintaan</strong> 
                    | 
                    Berat: <strong>{{ number_format($detailPermintaanBesok->sum('berat_sampah'), 1) }} kg</strong>
                </div>
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endif
@endsection