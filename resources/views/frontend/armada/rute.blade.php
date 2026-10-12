@extends('frontend.nasabah.main-dasbor')
@section('dasbor-content')

<div class="mb-4">
    <div class="d-flex justify-content-between align-items-center">
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

<div class="row g-4">
    {{-- Google Maps --}}
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-body p-0">
                <div id="map" style="height: 550px; width: 100%;"></div>
            </div>
        </div>
    </div>

    {{-- Informasi Rute --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body">
                <h6 class="fw-bold mb-3">
                    <i class="bi bi-info-circle text-success me-2"></i>
                    Informasi Rute
                </h6>
                
                <div class="mb-3 p-3 bg-light rounded-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Total Titik</span>
                        <span class="fw-bold">{{ $tugas->count() }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Total Jarak</span>
                        <span class="fw-bold">{{ number_format($optimasi->total_jarak ?? 0, 2) }} km</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Estimasi Waktu</span>
                        <span class="fw-bold">
                            @php
                                $jam = floor(($optimasi->total_waktu ?? 0) / 60);
                                $menit = ($optimasi->total_waktu ?? 0) % 60;
                            @endphp
                            {{ $jam > 0 ? $jam . ' jam ' : '' }}{{ $menit }} menit
                        </span>
                    </div>
                </div>

                <h6 class="fw-bold mt-4 mb-3">
                    <i class="bi bi-list-ol text-success me-2"></i>
                    Urutan Penjemputan
                </h6>

                <div class="list-group list-group-flush" style="max-height: 300px; overflow-y: auto;">
                    @foreach($tugas as $item)
                    <div class="list-group-item d-flex align-items-start gap-3 py-2 px-0 border-bottom">
                        <span class="badge bg-success rounded-pill mt-1" style="min-width: 28px;">
                            {{ $item->urutan }}
                        </span>
                        <div class="flex-grow-1">
                            <div class="fw-semibold small">{{ $item->permintaan->nasabah->nama_nasabah ?? 'Nasabah' }}</div>
                            <div class="text-muted small">{{ Str::limit($item->permintaan->alamat_penjemputan ?? '-', 35) }}</div>
                            <div class="text-muted small">
                                <span class="badge bg-light text-dark">{{ ucfirst($item->permintaan->jenis_sampah ?? '-') }}</span>
                                {{ number_format($item->permintaan->berat_sampah ?? 0, 1) }} kg
                            </div>
                        </div>
                        <span class="text-muted small">{{ number_format($item->jarak ?? 0, 2) }} km</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

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
    .list-group-item:hover {
        background-color: #f8f9fa;
    }
</style>
@endpush

@push('scripts')
<script>
    // =====================================================
    //  DEFINISIKAN FUNGSI GLOBAL SEBELUM GOOGLE MAPS LOAD
    // =====================================================
    function initMap() {
        console.log('✅ initMap dipanggil');
        
        try {
            // Ambil data dari controller
            const depot = { 
                lat: parseFloat({{ $points['depot']['lat'] ?? -2.5333 }}), 
                lng: parseFloat({{ $points['depot']['lng'] ?? 140.7167 }}) 
            };

            console.log(depot);
            const tujuan = @json($points['tujuan'] ?? []);
            
            console.log('📍 Depot:', depot);
            console.log('📍 Jumlah Tujuan:', tujuan.length);

            // Cek elemen map
            const mapElement = document.getElementById('map');
            if (!mapElement) {
                console.error('❌ Elemen #map tidak ditemukan');
                return;
            }

            // Inisialisasi peta
            const map = new google.maps.Map(mapElement, {
                zoom: 13,
                center: depot,
                mapTypeControl: false,
                streetViewControl: false,
                fullscreenControl: true,
                zoomControl: true,
            });

            console.log('✅ Map berhasil diinisialisasi');

            // Jika tidak ada tujuan
            if (!tujuan || tujuan.length === 0) {
                mapElement.innerHTML = `
                    <div class="d-flex align-items-center justify-content-center h-100 bg-light">
                        <div class="text-center">
                            <i class="bi bi-geo-alt fs-1 text-muted"></i>
                            <h6 class="mt-3">Belum ada titik penjemputan</h6>
                            <p class="text-muted small">Silakan lakukan optimasi rute terlebih dahulu.</p>
                        </div>
                    </div>
                `;
                return;
            }

            // Inisialisasi Directions
            const directionsService = new google.maps.DirectionsService();
            const directionsRenderer = new google.maps.DirectionsRenderer({
                map: map,
                suppressMarkers: true,
                polylineOptions: {
                    strokeColor: '#0d5c2e',
                    strokeWeight: 5,
                    strokeOpacity: 0.8,
                }
            });

            const waypoints = [];

            // Buat marker untuk depot
            new google.maps.Marker({
                position: depot,
                map: map,
                icon: {
                    url: 'https://maps.google.com/mapfiles/ms/icons/red-dot.png',
                    scaledSize: new google.maps.Size(40, 40),
                },
                label: {
                    text: 'DEPOT',
                    color: 'white',
                    fontWeight: 'bold',
                    fontSize: '12px',
                }
            });

            // Buat marker untuk setiap tujuan
            tujuan.forEach((item, index) => {
                const position = { 
                    lat: parseFloat(item.lat), 
                    lng: parseFloat(item.lng) 
                };
                
                if (index < tujuan.length - 1) {
                    waypoints.push({
                        location: position,
                        stopover: true
                    });
                }

                new google.maps.Marker({
                    position: position,
                    map: map,
                    icon: {
                        url: `https://maps.google.com/mapfiles/ms/icons/${index === 0 ? 'green' : 'blue'}-dot.png`,
                        scaledSize: new google.maps.Size(32, 32),
                    },
                    label: {
                        text: (index + 1).toString(),
                        color: 'white',
                        fontWeight: 'bold',
                        fontSize: '12px',
                    }
                });
            });

            // Gambar rute
            if (tujuan.length > 0) {
                const lastPosition = { 
                    lat: parseFloat(tujuan[tujuan.length - 1].lat), 
                    lng: parseFloat(tujuan[tujuan.length - 1].lng) 
                };

                directionsService.route({
                    origin: depot,
                    destination: lastPosition,
                    waypoints: waypoints,
                    travelMode: google.maps.TravelMode.DRIVING,
                }, function(result, status) {
                    if (status === google.maps.DirectionsStatus.OK) {
                        directionsRenderer.setDirections(result);
                        console.log('✅ Route berhasil digambar');
                    } else {
                        console.warn('⚠️ Directions API gagal:', status);
                    }
                });
            }

            // Sesuaikan zoom
            const bounds = new google.maps.LatLngBounds();
            bounds.extend(depot);
            tujuan.forEach(item => {
                bounds.extend({ 
                    lat: parseFloat(item.lat), 
                    lng: parseFloat(item.lng) 
                });
            });
            map.fitBounds(bounds);

            console.log('✅ Semua selesai');

        } catch (error) {
            console.error('❌ Error initMap:', error);
            document.getElementById('map').innerHTML = `
                <div class="d-flex align-items-center justify-content-center h-100 bg-light">
                    <div class="text-center">
                        <i class="bi bi-exclamation-triangle fs-1 text-danger"></i>
                        <h6 class="mt-3 text-danger">Error: ${error.message}</h6>
                        <button onclick="location.reload()" class="btn btn-sm btn-outline-secondary mt-2">
                            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
                        </button>
                    </div>
                </div>
            `;
        }
    }

    console.log('✅ Script initMap didefinisikan');
</script>

{{-- Load Google Maps API dengan callback yang benar --}}
<script
src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAPS_KEY') }}&callback=initMap&loading=async&v=weekly"
async
defer>

console.log("Google API Loaded :", typeof google);
</script>
@endpush