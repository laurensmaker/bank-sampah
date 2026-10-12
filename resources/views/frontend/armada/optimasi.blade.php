@extends('frontend.nasabah.main-dasbor')

@section('dasbor-content')

{{-- ── HEADER ──────────────────────────────────────────────── --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Optimasi Rute Penjemputan</h4>
        <p class="text-muted small mb-0">Pembagian MTSP · Urutan Nearest Neighbor</p>
    </div>
    <form action="{{ route('optimasi.proses') }}" method="POST">
        @csrf
        <button type="submit" class="btn btn-primary rounded-pill px-4">
            <i class="bi bi-geo-alt me-1"></i> Jalankan Optimasi
            @if(isset($jumlahMenunggu) && $jumlahMenunggu > 0)
                <span class="badge bg-warning text-dark ms-1">{{ $jumlahMenunggu }}</span>
            @endif
        </button>
    </form>
</div>

{{-- ── FLASH ────────────────────────────────────────────────── --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show rounded-3">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show rounded-3">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- ── SUMMARY CARDS ────────────────────────────────────────── --}}
@if(isset($optimasi))
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 text-center py-3">
            <div class="fs-3 fw-bold text-primary">{{ $optimasi->jumlah_armada }}</div>
            <div class="text-muted small">Armada</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 text-center py-3">
            <div class="fs-3 fw-bold text-success">{{ $optimasi->jumlah_permintaan }}</div>
            <div class="text-muted small">Permintaan</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 text-center py-3">
            <div class="fs-3 fw-bold text-warning">{{ number_format($optimasi->total_jarak, 1) }}</div>
            <div class="text-muted small">Total Jarak (km)</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 text-center py-3">
            <div class="fs-3 fw-bold text-info">{{ $optimasi->total_waktu }}</div>
            <div class="text-muted small">Total Waktu (mnt)</div>
        </div>
    </div>
</div>
@endif

{{-- ── KONTEN UTAMA ─────────────────────────────────────────── --}}
<div class="row g-4">

    {{-- PETA --}}
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 pt-3 pb-2 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="bi bi-map me-1"></i> Peta Rute</h6>
                @if(isset($rutes) && $rutes->count())
                <div class="d-flex gap-2 flex-wrap">
                    @foreach($rutes as $idArmada => $detail)
                    <button class="btn btn-sm btn-outline-primary rounded-pill btn-armada"
                            data-armada="{{ $idArmada }}"
                            onclick="tampilkanRute({{ $idArmada }})">
                        {{ $detail->first()->armada->nama_armada }}
                    </button>
                    @endforeach
                </div>
                @endif
            </div>
            <div class="card-body p-0">
                {{-- Tinggi eksplisit + display block agar Maps bisa render --}}
                <div id="map" style="height:480px;width:100%;display:block;border-radius:0 0 1rem 1rem;"></div>
            </div>
        </div>
    </div>

    {{-- INFO ARMADA + DAFTAR TITIK --}}
    <div class="col-lg-4">

        <div class="card border-0 shadow-sm rounded-4 mb-3">
            <div class="card-header bg-white border-0 pt-3 pb-2">
                <h6 class="fw-bold mb-0"><i class="bi bi-truck me-1"></i> Armada</h6>
            </div>
            <div class="card-body">
                @if(isset($armada))
                <div class="mb-2">
                    <span class="text-muted small d-block">Nama Armada</span>
                    <span class="fw-semibold">{{ $armada->nama_armada }}</span>
                </div>
                <div class="mb-2">
                    <span class="text-muted small d-block">Jenis Kendaraan</span>
                    <span class="fw-semibold">{{ $armada->jenis_kendaraan }}</span>
                </div>
                <div class="mb-2">
                    <span class="text-muted small d-block">Kapasitas</span>
                    <span class="fw-semibold">{{ $armada->kapasitas }} kg</span>
                </div>
                <div>
                    <span class="text-muted small d-block">Status</span>
                    <span class="badge bg-success rounded-pill">{{ $armada->status_armada }}</span>
                </div>
                @else
                <p class="text-muted small mb-0">Belum ada data armada.</p>
                @endif
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 pt-3 pb-2">
                <h6 class="fw-bold mb-0"><i class="bi bi-list-ol me-1"></i> Urutan Titik</h6>
            </div>
            <div class="card-body p-0">
                <div id="daftarTitik" style="max-height:320px;overflow-y:auto;">

                    @if(isset($rutes) && $rutes->count())

                        @php $idArmadaPertama = $rutes->keys()->first(); @endphp

                        @foreach($rutes as $idArmada => $detailList)
                        <div class="armada-titik" data-armada="{{ $idArmada }}"
                             style="{{ $idArmada != $idArmadaPertama ? 'display:none;' : '' }}">

                            @foreach($detailList as $detail)
                            <div class="d-flex align-items-start gap-2 px-3 py-2 border-bottom">
                                <span class="badge bg-primary rounded-circle mt-1"
                                      style="min-width:24px;height:24px;line-height:16px;font-size:11px;">
                                    {{ $detail->urutan }}
                                </span>
                                <div class="flex-grow-1">
                                    {{-- ✅ nama dari relasi nasabah --}}
                                    <div class="fw-semibold small">
                                        {{ $detail->permintaan->nasabah->nama_nasabah ?? '-' }}
                                    </div>
                                    {{-- ✅ alamat dari field alamat_penjemputan --}}
                                    <div class="text-muted" style="font-size:11px;">
                                        {{ $detail->permintaan->alamat_penjemputan ?? '-' }}
                                    </div>
                                    <div class="text-muted" style="font-size:11px;">
                                        <i class="bi bi-arrow-right-short"></i>{{ $detail->jarak }} km
                                        &nbsp;·&nbsp;
                                        <i class="bi bi-clock"></i> {{ $detail->estimasi_waktu }} mnt
                                    </div>
                                </div>
                            </div>
                            @endforeach

                            <div class="px-3 py-2 bg-light small text-muted d-flex justify-content-between">
                                <span>Total Jarak Armada</span>
                                <span class="fw-semibold">
                                    {{ number_format($detailList->last()->total_jarak, 2) }} km
                                </span>
                            </div>
                        </div>
                        @endforeach

                    @else
                        <div class="text-center text-muted py-4 small">
                            <i class="bi bi-geo-alt fs-3 d-block mb-2 opacity-50"></i>
                            Belum ada rute. Klik <strong>Jalankan Optimasi</strong>.
                        </div>
                    @endif

                </div>
            </div>
        </div>

    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════
     SIAPKAN DATA PETA (PHP → JS)
     Letakkan di sini, SEBELUM @push scripts, agar variabel
     JS sudah tersedia ketika Google Maps callback dipanggil
════════════════════════════════════════════════════════════ --}}
@php
    $petaData   = [];
    $defaultLat = -2.5333;   // Jayapura (fallback)
    $defaultLng = 140.7167;

    if (isset($rutes) && $rutes->count()) {
        foreach ($rutes as $idArmada => $detailList) {
            $armadaObj = $detailList->first()->armada;

            $titik = $detailList->map(fn($d) => [
                'urutan' => $d->urutan,
                'lat'    => (float) $d->permintaan->latitude,
                'lng'    => (float) $d->permintaan->longitude,
                // ✅ nama & alamat dari relasi yang benar
                'nama'   => optional($d->permintaan->nasabah)->nama_nasabah
                                ?? 'Titik ' . $d->urutan,
                'alamat' => $d->permintaan->alamat_penjemputan ?? '',
                'berat'  => $d->permintaan->berat_sampah ?? 0,
                'jarak'  => $d->jarak,
                'waktu'  => $d->estimasi_waktu,
            ])->values();

            $petaData[$idArmada] = [
                'nama'     => $armadaObj->nama_armada,
                // ✅ field depot sesuai migration baru
                'lat_awal' => (float) $armadaObj->latitude_depot,
                'lng_awal' => (float) $armadaObj->longitude_depot,
                'titik'    => $titik,
            ];
        }

        // Default center peta = depot armada pertama
        $armadaPertama = $rutes->first()->first()->armada;
        $defaultLat    = (float) $armadaPertama->latitude_depot;
        $defaultLng    = (float) $armadaPertama->longitude_depot;
    }
@endphp

{{-- Variabel JS didefinisikan langsung di section (bukan di push)
     agar sudah ada sebelum callback initMap dipanggil --}}
<script>
    const PETA_DATA   = @json($petaData);
    const MAPS_KEY    = "{{ config('services.google_maps.key') }}";
    const DEFAULT_LAT = {{ $defaultLat }};
    const DEFAULT_LNG = {{ $defaultLng }};
</script>

@endsection


{{-- ═══════════════════════════════════════════════════════════
     GOOGLE MAPS SCRIPTS
════════════════════════════════════════════════════════════ --}}
@push('scripts')
<script>
// ── State global ────────────────────────────────────────────
let map, directionsService;
let directionsRenderer = {};
let markersAktif       = [];

const WARNA = ['#1a73e8','#e53935','#43a047','#fb8c00','#8e24aa','#00acc1'];

// ── initMap dipanggil Google sebagai callback ────────────────
function initMap() {
    map = new google.maps.Map(document.getElementById('map'), {
        center           : { lat: DEFAULT_LAT, lng: DEFAULT_LNG },
        zoom             : 13,
        mapTypeControl   : false,
        streetViewControl: false,
        fullscreenControl: true,
    });

    directionsService = new google.maps.DirectionsService();

    // Langsung tampilkan armada pertama
    const kunci = Object.keys(PETA_DATA);
    if (kunci.length > 0) {
        tampilkanRute(parseInt(kunci[0]));

        // Aktifkan tombol armada pertama
        const btnPertama = document.querySelector(
            `.btn-armada[data-armada="${kunci[0]}"]`
        );
        if (btnPertama) btnPertama.classList.add('active');
    }
}

// ── Tampilkan rute satu armada ───────────────────────────────
function tampilkanRute(idArmada) {

    // Sorot tombol aktif
    document.querySelectorAll('.btn-armada').forEach(btn => {
        btn.classList.toggle('active', parseInt(btn.dataset.armada) === idArmada);
    });

    // Tampilkan daftar titik yang sesuai
    document.querySelectorAll('.armada-titik').forEach(el => {
        el.style.display = parseInt(el.dataset.armada) === idArmada ? '' : 'none';
    });

    const data = PETA_DATA[idArmada];
    if (!data) return;

    const idx   = Object.keys(PETA_DATA).indexOf(String(idArmada));
    const warna = WARNA[idx % WARNA.length];

    // Hapus renderer lama
    Object.values(directionsRenderer).forEach(r => r.setMap(null));
    directionsRenderer = {};

    // Hapus marker lama
    markersAktif.forEach(m => m.setMap(null));
    markersAktif = [];

    const depot = { lat: data.lat_awal, lng: data.lng_awal };

    // ── Marker depot ────────────────────────────────────────
    const markerDepot = new google.maps.Marker({
        position : depot,
        map,
        title    : `Depot – ${data.nama}`,
        zIndex   : 10,
        icon     : {
            path        : google.maps.SymbolPath.CIRCLE,
            scale       : 11,
            fillColor   : warna,
            fillOpacity : 1,
            strokeColor : '#ffffff',
            strokeWeight: 3,
        },
    });

    const iwDepot = new google.maps.InfoWindow({
        content: `<div style="min-width:150px">
                    <b>${data.nama}</b><br>
                    <span style="color:#666;font-size:12px">Titik Berangkat (Depot)</span>
                  </div>`
    });
    markerDepot.addListener('click', () => iwDepot.open(map, markerDepot));
    markersAktif.push(markerDepot);

    if (!data.titik || data.titik.length === 0) {
        map.setCenter(depot);
        return;
    }

    // ── Marker tiap titik permintaan ────────────────────────
    data.titik.forEach(t => {
        const marker = new google.maps.Marker({
            position : { lat: t.lat, lng: t.lng },
            map,
            zIndex   : 5,
            title    : t.nama,
            label    : {
                text      : String(t.urutan),
                color     : '#ffffff',
                fontWeight: 'bold',
                fontSize  : '12px',
            },
            icon : {
                path        : google.maps.SymbolPath.CIRCLE,
                scale       : 15,
                fillColor   : warna,
                fillOpacity : 0.9,
                strokeColor : '#ffffff',
                strokeWeight: 2,
            },
        });

        const iw = new google.maps.InfoWindow({
            content: `
                <div style="min-width:190px;font-family:sans-serif">
                    <div style="font-weight:700;margin-bottom:4px">${t.urutan}. ${t.nama}</div>
                    <div style="color:#555;font-size:12px;margin-bottom:6px">${t.alamat}</div>
                    <hr style="margin:4px 0">
                    <div style="font-size:12px">Berat : <b>${t.berat} kg</b></div>
                    <div style="font-size:12px">Jarak : <b>${t.jarak} km</b></div>
                    <div style="font-size:12px">Waktu : <b>${t.waktu} menit</b></div>
                </div>`
        });

        marker.addListener('click', () => iw.open(map, marker));
        markersAktif.push(marker);
    });

    // ── Directions API ──────────────────────────────────────
    // Waypoints = semua titik kecuali yang terakhir
    const waypoints = data.titik.slice(0, -1).map(t => ({
        location : new google.maps.LatLng(t.lat, t.lng),
        stopover : true,
    }));

    const tujuanAkhir = data.titik[data.titik.length - 1];

    directionsRenderer[idArmada] = new google.maps.DirectionsRenderer({
        map,
        suppressMarkers : true,
        polylineOptions : {
            strokeColor  : warna,
            strokeWeight : 5,
            strokeOpacity: 0.75,
        },
    });

    directionsService.route({
        origin           : new google.maps.LatLng(depot.lat, depot.lng),
        destination      : new google.maps.LatLng(tujuanAkhir.lat, tujuanAkhir.lng),
        waypoints,
        travelMode       : google.maps.TravelMode.DRIVING,
        optimizeWaypoints: false,
    }, (result, status) => {
        if (status === 'OK') {
            directionsRenderer[idArmada].setDirections(result);
        } else {
            console.warn('Directions API gagal (' + status + '), pakai polyline.');
            gambarPolylineLurus(data, warna);
        }
    });

    // ── Fit bounds semua titik ──────────────────────────────
    const bounds = new google.maps.LatLngBounds();
    bounds.extend(depot);
    data.titik.forEach(t => bounds.extend({ lat: t.lat, lng: t.lng }));
    map.fitBounds(bounds, { top: 40, right: 40, bottom: 40, left: 40 });
}

// ── Fallback: garis lurus ────────────────────────────────────
function gambarPolylineLurus(data, warna) {
    const path = [{ lat: data.lat_awal, lng: data.lng_awal }];
    data.titik.forEach(t => path.push({ lat: t.lat, lng: t.lng }));

    new google.maps.Polyline({
        path,
        map,
        strokeColor  : warna,
        strokeWeight : 4,
        strokeOpacity: 0.7,
        icons: [{
            icon  : { path: google.maps.SymbolPath.FORWARD_CLOSED_ARROW },
            offset: '100%',
            repeat: '70px',
        }],
    });
}
</script>

{{-- ✅ Load Google Maps TANPA async/defer
     callback=initMap → dipanggil otomatis setelah library siap --}}
<script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}&libraries=geometry&callback=initMap"></script>

@endpush