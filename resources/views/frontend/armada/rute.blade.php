@extends('frontend.nasabah.main-dasbor')
@section('dasbor-content')

{{-- ═══════════════════════════════════════════════════════ --}}
{{-- HEADER --}}
{{-- ═══════════════════════════════════════════════════════ --}}
<div class="mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">Rute Penjemputan</h4>
            <p class="text-muted small mb-0">
                {{ now()->isoFormat('dddd, D MMMM Y') }} | {{ $armada->nama_armada }}
            </p>
        </div>
        <a href="{{ route('armada.dasbor') }}" class="btn btn-outline-secondary rounded-pill">
            <i class="bi bi-arrow-left me-2"></i> Kembali
        </a>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════ --}}
{{-- ALERT: SEMUA TUGAS SELESAI --}}
{{-- ═══════════════════════════════════════════════════════ --}}
@if($tugas->isEmpty())
<div class="alert alert-success border-0 rounded-4 d-flex align-items-center gap-3 mb-4">
    <i class="bi bi-trophy-fill fs-2 text-warning"></i>
    <div>
        <h5 class="mb-1 fw-bold">🎉 Semua Tugas Selesai!</h5>
        <p class="mb-0 small">
            Anda sudah menyelesaikan semua titik penjemputan hari ini. 
            Tidak ada rute yang perlu ditampilkan.
        </p>
    </div>
</div>
@endif

<div class="row g-4">
    {{-- ═══════════════════════════════════════════════════════ --}}
    {{-- GOOGLE MAPS --}}
    {{-- ═══════════════════════════════════════════════════════ --}}
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white border-0 py-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h6 class="fw-bold mb-0">
                            <i class="bi bi-geo-alt-fill text-success me-2"></i>
                            Peta Rute Aktif
                        </h6>
                        <small class="text-muted" id="map-info">
                            <strong>{{ $tugas->count() }}</strong> titik penjemputan aktif
                            @if($tugas->count() > 0)
                            • Total jarak: <strong>{{ number_format($tugas->sum('jarak'), 2) }} km</strong>
                            @endif
                        </small>
                    </div>
                    <div class="d-flex gap-2">
                        <button id="btn-reset-map" class="btn btn-sm btn-outline-success rounded-pill" style="display: none;">
                            <i class="bi bi-arrow-repeat me-1"></i> Tampilkan Semua
                        </button>
                        <span class="badge bg-success" id="badge-status">
                            <i class="bi bi-signpost-split me-1"></i> Rute Aktif
                        </span>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                @if($tugas->isEmpty())
                <div class="d-flex align-items-center justify-content-center" style="height: 550px; background-color: #f8f9fa;">
                    <div class="text-center">
                        <i class="bi bi-check-circle-fill fs-1 text-success"></i>
                        <h6 class="mt-3">Tidak Ada Titik Penjemputan</h6>
                        <p class="text-muted small mb-0">Semua tugas hari ini sudah selesai.</p>
                    </div>
                </div>
                @else
                <div id="map" style="height: 550px; width: 100%;"></div>
                @endif
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════ --}}
    {{-- INFORMASI RUTE --}}
    {{-- ═══════════════════════════════════════════════════════ --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body">

                {{-- Info Ringkasan --}}
                <h6 class="fw-bold mb-3">
                    <i class="bi bi-info-circle text-success me-2"></i>
                    Informasi Rute
                </h6>

                <div class="mb-3 p-3 bg-light rounded-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Total Titik Aktif</span>
                        <span class="fw-bold">{{ $tugas->count() }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Total Jarak</span>
                        <span class="fw-bold">
                            {{ number_format($tugas->sum('jarak'), 2) }} km
                        </span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Estimasi Waktu</span>
                        <span class="fw-bold">
                            @php
                                $totalMenit = $tugas->sum('estimasi_waktu');
                                $jam = floor($totalMenit / 60);
                                $menit = $totalMenit % 60;
                            @endphp
                            {{ $jam > 0 ? $jam . ' jam ' : '' }}{{ $menit }} menit
                        </span>
                    </div>
                </div>

                {{-- Info Prioritas --}}
                @if(isset($optimasi) && $optimasi->armadaPrioritas)
                <div class="mb-3 p-3 rounded-3 {{ (int) $optimasi->id_armada_prioritas === (int) $armada->id ? 'bg-success bg-opacity-10 border border-success' : 'bg-info bg-opacity-10 border border-info' }}">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        @if((int) $optimasi->id_armada_prioritas === (int) $armada->id)
                            <i class="bi bi-star-fill text-warning"></i>
                            <strong class="small">Anda Armada Prioritas</strong>
                        @else
                            <i class="bi bi-info-circle-fill text-info"></i>
                            <strong class="small">Armada Prioritas</strong>
                        @endif
                    </div>
                    <div class="text-muted small">
                        Dibuat oleh <strong>{{ $optimasi->armadaPrioritas->nama_armada }}</strong>
                        @if($optimasi->created_at)
                        • {{ $optimasi->created_at->format('H:i') }}
                        @endif
                    </div>
                </div>
                @endif

                {{-- Urutan Penjemputan (KLIK UNTUK ZOOM) --}}
                <h6 class="fw-bold mt-4 mb-3">
                    <i class="bi bi-list-ol text-success me-2"></i>
                    Urutan Penjemputan
                </h6>

                @if($tugas->isEmpty())
                <div class="text-center text-muted py-4">
                    <i class="bi bi-inbox fs-3 opacity-50"></i>
                    <p class="small mb-0 mt-2">Tidak ada tugas aktif.</p>
                </div>
                @else
                <div class="list-group list-group-flush" style="max-height: 350px; overflow-y: auto;">
                    @foreach($tugas as $index => $item)
                    {{-- ✅ Tambahkan data-index & onclick --}}
                    <div class="list-group-item d-flex align-items-start gap-3 py-2 px-0 border-bottom task-item" 
                         style="cursor: pointer; transition: background 0.2s;"
                         data-index="{{ $index }}"
                         onclick="focusOnTask({{ $index }})"
                         title="Klik untuk melihat rute ke titik ini">
                        
                        <span class="badge bg-success rounded-pill mt-1" style="min-width: 28px;" id="badge-urutan-{{ $index }}">
                            {{ $item->urutan ?? "-" }}
                        </span>
                        <div class="flex-grow-1">
                            <div class="fw-semibold small text-dark">
                                {{ $item->permintaan->nasabah->nama_nasabah ?? 'Nasabah' }}
                            </div>
                            <div class="text-muted small">
                                {{ Str::limit($item->permintaan->alamat_penjemputan ?? '-', 35) }}
                            </div>
                            <div class="text-muted small mt-1">
                                <span class="badge bg-light text-dark">
                                    {{ ucfirst($item->permintaan->jenis_sampah ?? '-') }}
                                </span>
                                <span class="ms-1">{{ number_format($item->permintaan->berat_sampah ?? 0, 1) }} kg</span>
                            </div>
                        </div>
                        <span class="text-muted small text-end" style="min-width: 55px;">
                            {{ number_format($item->jarak ?? 0, 2) }} km
                        </span>
                    </div>
                    @endforeach
                </div>

                {{-- Tombol "Selesai" untuk tiap titik (opsional) --}}
                <div class="mt-3 d-grid gap-2">
                    <a href="{{ route('armada.dasbor') }}" class="btn btn-outline-success rounded-pill">
                        <i class="bi bi-check2-circle me-2"></i> Kelola Status Penjemputan
                    </a>
                </div>
                @endif

            </div>
        </div>
    </div>
</div>

@endsection

{{-- ═══════════════════════════════════════════════════════ --}}
{{-- STYLES --}}
{{-- ═══════════════════════════════════════════════════════ --}}
@push('styles')
<style>
    #map {
        min-height: 550px;
        width: 100%;
        background-color: #f8f9fa;
    }
    .list-group-item {
        border-left: none;
        border-right: none;
        padding-left: 0;
        padding-right: 0;
    }
    .list-group-item:first-child {
        border-top: none;
    }
    .list-group-item:last-child {
        border-bottom: none;
    }
    .task-item:hover {
        background-color: #e8f5e9 !important; /* Warna hijau muda */
    }
    .task-item.active {
        background-color: #c8e6c9 !important; /* Warna hijau saat diklik */
        border-left: 4px solid #198754 !important;
        padding-left: 10px !important;
    }
</style>
@endpush

{{-- ═══════════════════════════════════════════════════════ --}}
{{-- SCRIPTS --}}
{{-- ═══════════════════════════════════════════════════════ --}}
@push('scripts')
@if($tugas->isNotEmpty())
<script>
    // =====================================================
    //  GLOBAL VARIABLES
    // =====================================================
    let map, directionsService, directionsRenderer, infoWindow;
    let globalDepot;
    let globalTujuan = [];
    let activeTaskIndex = -1; // -1 berarti tampilkan semua

    // =====================================================
    //  INIT MAP
    // =====================================================
    function initMap() {
        console.log('✅ initMap dipanggil');
        
        try {
            globalDepot = { 
                lat: parseFloat({{ $points['depot']['lat'] ?? -2.5333 }}), 
                lng: parseFloat({{ $points['depot']['lng'] ?? 140.7167 }}) 
            };

            globalTujuan = @json($points['tujuan'] ?? []);
            
            const mapElement = document.getElementById('map');
            if (!mapElement) return;

            map = new google.maps.Map(mapElement, {
                zoom: 13,
                center: globalDepot,
                mapTypeControl: false,
                streetViewControl: false,
                fullscreenControl: true,
                zoomControl: true,
            });

            directionsService = new google.maps.DirectionsService();
            directionsRenderer = new google.maps.DirectionsRenderer({
                map: map,
                suppressMarkers: true,
                polylineOptions: {
                    strokeColor: '#0d5c2e',
                    strokeWeight: 5,
                    strokeOpacity: 0.8,
                }
            });

            infoWindow = new google.maps.InfoWindow();

            // Gambar rute awal (semua titik)
            drawRouteAll();

            // Event listener untuk tombol reset
            document.getElementById('btn-reset-map').addEventListener('click', function() {
                activeTaskIndex = -1;
                resetTaskStyles();
                drawRouteAll();
            });

        } catch (error) {
            console.error('❌ Error initMap:', error);
        }
    }

    // =====================================================
    //  DRAW ROUTE: SEMUA TITIK
    // =====================================================
    function drawRouteAll() {
        clearMap();
        
        // Reset UI text
        document.getElementById('map-info').innerHTML = `<strong>${globalTujuan.length}</strong> titik penjemputan aktif • Total jarak: <strong>${hitungTotalJarak(globalTujuan)} km</strong>`;
        document.getElementById('badge-status').className = 'badge bg-success';
        document.getElementById('badge-status').innerHTML = '<i class="bi bi-signpost-split me-1"></i> Rute Aktif';
        document.getElementById('btn-reset-map').style.display = 'none';

        if (!globalTujuan || globalTujuan.length === 0) return;

        const waypoints = [];
        const bounds = new google.maps.LatLngBounds();
        bounds.extend(globalDepot);

        // Marker Depot
        new google.maps.Marker({
            position: globalDepot,
            map: map,
            icon: { url: 'https://maps.google.com/mapfiles/ms/icons/red-dot.png', scaledSize: new google.maps.Size(40, 40) },
            label: { text: 'DEPOT', color: 'white', fontWeight: 'bold', fontSize: '10px' }
        });

        // Loop semua tujuan
        globalTujuan.forEach((item, index) => {
            const position = { lat: parseFloat(item.lat), lng: parseFloat(item.lng) };
            bounds.extend(position);

            if (index < globalTujuan.length - 1) {
                waypoints.push({ location: position, stopover: true });
            }

            // Marker Tujuan
            const nomorUrut = (item.urutan && item.urutan > 0) ? item.urutan : (index + 1);
            const marker = new google.maps.Marker({
                position: position,
                map: map,
                icon: { 
                    url: `https://maps.google.com/mapfiles/ms/icons/${index === 0 ? 'green' : 'blue'}-dot.png`, 
                    scaledSize: new google.maps.Size(32, 32) 
                },
                label: { text: nomorUrut.toString(), color: 'black', fontWeight: 'bold', fontSize: '14px' }
            });

            // Info Window
            const content = generateInfoWindowContent(item, nomorUrut);
            marker.addListener('click', () => {
                infoWindow.setContent(content);
                infoWindow.open(map, marker);
            });
        });

        // Gambar polyline rute
        if (globalTujuan.length > 0) {
            const lastPosition = { lat: parseFloat(globalTujuan[globalTujuan.length - 1].lat), lng: parseFloat(globalTujuan[globalTujuan.length - 1].lng) };
            directionsService.route({
                origin: globalDepot,
                destination: lastPosition,
                waypoints: waypoints,
                travelMode: google.maps.TravelMode.DRIVING,
            }, function(result, status) {
                if (status === google.maps.DirectionsStatus.OK) {
                    directionsRenderer.setDirections(result);
                }
            });
        }

        map.fitBounds(bounds);
    }

    // =====================================================
    //  DRAW ROUTE: TITIK SPESIFIK (FITUR BARU)
    // =====================================================
    function focusOnTask(index) {
        if (!globalTujuan || globalTujuan.length === 0) return;

        // Simpan state & ubah style UI
        activeTaskIndex = index;
        resetTaskStyles();
        document.querySelector(`.task-item[data-index="${index}"]`).classList.add('active');
        
        // Tampilkan tombol reset
        document.getElementById('btn-reset-map').style.display = 'inline-block';

        // Ambil data target
        const targetItem = globalTujuan[index];
        const targetLatLng = { lat: parseFloat(targetItem.lat), lng: parseFloat(targetItem.lng) };
        
        // Tentukan titik START
        // Jika index = 0, start dari Depot
        // Jika index > 0, start dari titik sebelumnya (index - 1) -> Asumsi: rute berurutan
        let startPoint = globalDepot;
        let startName = "Depot";
        
        if (index > 0) {
            const prevItem = globalTujuan[index - 1];
            startPoint = { lat: parseFloat(prevItem.lat), lng: parseFloat(prevItem.lng) };
            startName = prevItem.nama || "Titik Sebelumnya";
        }

        // Update Map Info
        document.getElementById('map-info').innerHTML = `
            Fokus ke titik <strong>#${targetItem.urutan || (index + 1)}</strong> - ${targetItem.nama} <br>
            <span class="text-success"><i class="bi bi-geo-alt-fill"></i> Rute dari: ${startName}</span>
        `;
        document.getElementById('badge-status').className = 'badge bg-primary';
        document.getElementById('badge-status').innerHTML = '<i class="bi bi-crosshair me-1"></i> Fokus Titik';

        // Bersihkan map
        clearMap();

        const bounds = new google.maps.LatLngBounds();
        bounds.extend(startPoint);
        bounds.extend(targetLatLng);

        // 1. Marker START
        new google.maps.Marker({
            position: startPoint,
            map: map,
            icon: { 
                url: index === 0 ? 'https://maps.google.com/mapfiles/ms/icons/red-dot.png' : 'https://maps.google.com/mapfiles/ms/icons/orange-dot.png', 
                scaledSize: new google.maps.Size(40, 40) 
            },
            label: { 
                text: index === 0 ? 'DEPOT' : 'PREV', 
                color: 'white', 
                fontWeight: 'bold', 
                fontSize: '10px' 
            }
        });

        // 2. Marker TARGET (Besar & Hijau)
        const nomorUrut = (targetItem.urutan && targetItem.urutan > 0) ? targetItem.urutan : (index + 1);
        const markerTarget = new google.maps.Marker({
            position: targetLatLng,
            map: map,
            icon: { 
                url: 'https://maps.google.com/mapfiles/ms/icons/green-dot.png', 
                scaledSize: new google.maps.Size(48, 48) 
            },
            label: { text: nomorUrut.toString(), color: 'white', fontWeight: 'bold', fontSize: '16px' },
            zIndex: 999,
            animation: google.maps.Animation.BOUNCE
        });

        // Info Window Target
        const content = generateInfoWindowContent(targetItem, nomorUrut);
        markerTarget.addListener('click', () => {
            infoWindow.setContent(content);
            infoWindow.open(map, markerTarget);
        });

        // 3. Gambar Rute (Start -> Target)
        directionsService.route({
            origin: startPoint,
            destination: targetLatLng,
            travelMode: google.maps.TravelMode.DRIVING,
        }, function(result, status) {
            if (status === google.maps.DirectionsStatus.OK) {
                directionsRenderer.setDirections(result);
            } else {
                console.warn('⚠️ Directions API gagal:', status);
            }
        });

        // Zoom ke rute yang digambar
        map.fitBounds(bounds);
        
        // Auto stop animasi bounce setelah 2 detik
        setTimeout(() => { markerTarget.setAnimation(null); }, 2000);
    }

    // =====================================================
    //  HELPER FUNCTIONS
    // =====================================================
    
    function clearMap() {
        directionsRenderer.set('directions', null);
        if (window.markers) {
            window.markers.forEach(m => m.setMap(null));
        }
        window.markers = [];
    }

    function resetTaskStyles() {
        document.querySelectorAll('.task-item').forEach(el => el.classList.remove('active'));
    }

    function generateInfoWindowContent(item, nomorUrut) {
        return `
            <div style="min-width: 200px;">
                <h6 style="margin: 0 0 6px 0; font-weight: bold;">
                    #${nomorUrut} - ${item.nama || 'Nasabah'}
                </h6>
                <div style="font-size: 12px; color: #666; margin-bottom: 6px;">
                    ${item.alamat || '-'}
                </div>
                <div style="font-size: 12px;">
                    <span style="background: #e9ecef; padding: 2px 6px; border-radius: 4px;">
                        ${item.jenis_sampah || '-'}
                    </span>
                    <strong>${item.berat || 0} kg</strong>
                </div>
            </div>
        `;
    }

    function hitungTotalJarak(tujuan) {
        let total = 0;
        tujuan.forEach(item => total += parseFloat(item.jarak || 0));
        return total.toFixed(2);
    }

    console.log('✅ Script initMap siap');
</script>

{{-- Load Google Maps API dengan callback --}}
<script
    src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAPS_KEY') }}&callback=initMap&loading=async&v=weekly"
    async
    defer>
</script>
@endif
@endpush