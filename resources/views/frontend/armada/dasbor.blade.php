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
        <div class="d-flex gap-2">
            @if($optimasiAktif && isset($tugas) && $tugas->isNotEmpty())
            <a href="{{ route('armada.rute') }}" class="btn btn-outline-success rounded-pill">
                <i class="bi bi-geo-alt me-2"></i> Lihat Rute
            </a>
            @endif
            
            @if(isset($adaPermintaanMenunggu) && $adaPermintaanMenunggu)
            <a href="{{ route('armada.optimasi.proses') }}" 
               class="btn btn-success rounded-pill px-4"
               onclick="return confirm('Apakah Anda yakin ingin melakukan optimasi rute penjemputan?')">
                <i class="bi bi-robot me-2"></i> Optimasi Rute
            </a>
            @else
            <button type="button" class="btn btn-success rounded-pill px-4" disabled>
                <i class="bi bi-robot me-2"></i> Optimasi Rute
                <span class="badge bg-light text-dark ms-1">(Tidak ada permintaan)</span>
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

{{-- Kartu ringkasan --}}
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
            <div class="fs-2 fw-bold text-warning">{{ ($totalTugas ?? 0) - ($totalSelesai ?? 0) }}</div>
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
@if(isset($optimasiAktif) && $optimasiAktif && isset($tugas) && $tugas->isNotEmpty())
<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="card-header bg-white border-0 py-3">
        <h5 class="fw-bold mb-0">
            <i class="bi bi-list-check text-success me-2"></i>
            Daftar Penjemputan Hari Ini (Urutan Rute)
        </h5>
        <small class="text-muted">
            Total {{ $tugas->count() }} titik penjemputan
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
                    @foreach($tugas as $item)
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
                            @if($status !== 'Selesai' && $status !== 'Dibatalkan')
                            <form action="{{ route('armada.selesai', $item->id_permintaan) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-success rounded-pill" 
                                        onclick="return confirm('Apakah Anda yakin penjemputan ini sudah selesai?')">
                                    <i class="bi bi-check2 me-1"></i> Selesai
                                </button>
                            </form>
                            @elseif($status === 'Selesai')
                            <span class="text-success small">
                                <i class="bi bi-check-circle-fill me-1"></i> Selesai
                            </span>
                            @else
                            <span class="text-danger small">
                                <i class="bi bi-x-circle-fill me-1"></i> Dibatalkan
                            </span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white border-0 py-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="text-muted small">
                    <i class="bi bi-info-circle me-1"></i>
                    Total jarak: <strong>{{ number_format($totalJarak ?? 0, 2) }} km</strong>
                    @if(isset($kapasitasTerpakai))
                    | Berat: <strong>{{ number_format($kapasitasTerpakai, 1) }} kg</strong>
                    @endif
                </div>
                <a href="{{ route('armada.rute') }}" class="btn btn-outline-success rounded-pill px-4">
                    <i class="bi bi-geo-alt me-2"></i> Lihat Rute di Google Maps
                </a>
            </div>
        </div>
    </div>
</div>
@else
<div class="card border-0 shadow-sm rounded-4 p-5 text-center text-muted">
    <i class="bi bi-inbox fs-1 mb-3 opacity-50"></i>
    <h5>Belum Ada Tugas Penjemputan</h5>
    <p class="small mb-0">Silakan klik tombol "Optimasi Rute" untuk membagi permintaan penjemputan.</p>
    @if(isset($adaPermintaanMenunggu) && !$adaPermintaanMenunggu)
    <p class="text-danger small mt-2">
        <i class="bi bi-info-circle me-1"></i> 
        Tidak ada permintaan dengan status "Menunggu". 
        Pastikan ada permintaan yang sudah dibuat oleh nasabah.
    </p>
    @endif
</div>
@endif

@endsection