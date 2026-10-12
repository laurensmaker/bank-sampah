@extends('frontend.nasabah.main-dasbor')
@section('dasbor-content')

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

@if(session('success'))
<script>
    Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: '{{ session('success') }}',
        confirmButtonColor: '#2dae6e',
        confirmButtonText: 'OK',
        timer: 3000,
        timerProgressBar: true,
        showConfirmButton: true,
    });
</script>
@endif

@if(session('error'))
<script>
    Swal.fire({
        icon: 'error',
        title: 'Gagal!',
        text: '{{ session('error') }}',
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Coba Lagi',
        timer: 4000,
        timerProgressBar: true
    });
</script>
@endif

<div class="profile-tickets">
    <div class="pro-add-title d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Data Permintaan Penjemputan</h4>
        <a href="{{ route('nasabah.permintaan-penjemputan') }}" class="btn btn-success rounded-pill">
            <i class="fa fa-plus me-2"></i> Buat Permintaan Baru
        </a>
    </div>

    @if($nasabah->isEmpty())
    <div class="text-center py-5">
        <i class="fa fa-inbox fa-3x text-muted mb-3"></i>
        <h5 class="text-muted">Belum ada permintaan penjemputan</h5>
        <p class="text-muted small">Silakan buat permintaan penjemputan baru</p>
        <a href="{{ route('nasabah.permintaan-penjemputan') }}" class="btn btn-success rounded-pill">
            <i class="fa fa-plus me-2"></i> Buat Permintaan Baru
        </a>
    </div>
    @else
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>No</th>
                    <th>Tanggal Penjemputan</th>
                    <th>Jenis Sampah</th>
                    <th>Berat Sampah</th>
                    <th>Alamat</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($nasabah as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($item->tanggal)->format('d/m/Y H:i') }}</td>
                    <td>
                        <span class="badge bg-light text-dark">
                            <i class="fa fa-trash me-1"></i>
                            {{ ucfirst($item->jenis_sampah) }}
                        </span>
                    </td>
                    <td>
                        <span class="fw-bold">{{ number_format($item->berat_sampah, 2) }}</span> kg
                    </td>
                    <td>
                        <span class="text-muted small" title="{{ $item->alamat_penjemputan }}">
                            {{ Str::limit($item->alamat_penjemputan, 30) }}
                        </span>
                    </td>
                    <td>
                        @php
                            // Gunakan statusTerkini, bukan statusPenjemputan
                            $status = optional($item->statusTerkini)->status ?? 'Menunggu';
                            
                            $badgeClass = match($status) {
                                'Menunggu' => 'bg-warning text-dark',
                                'Dijadwalkan' => 'bg-primary text-white',
                                'Diproses' => 'bg-info text-white',
                                'Dijemput' => 'bg-info text-white',
                                'Selesai' => 'bg-success text-white',
                                'Dibatalkan' => 'bg-danger text-white',
                                default => 'bg-secondary text-white'
                            };
                            
                            $icon = match($status) {
                                'Menunggu' => 'fa-clock',
                                'Dijadwalkan' => 'fa-calendar-check',
                                'Diproses' => 'fa-spinner',
                                'Dijemput' => 'fa-truck',
                                'Selesai' => 'fa-check-circle',
                                'Dibatalkan' => 'fa-times-circle',
                                default => 'fa-circle'
                            };
                        @endphp
                        <span class="badge {{ $badgeClass }} py-2 px-3">
                            <i class="fa {{ $icon }} me-1"></i>
                            {{ $status }}
                        </span>
                    </td>
                    <td>
                        @if($status === 'Menunggu')
                        <button type="button" class="btn btn-sm btn-danger rounded-pill" 
                                onclick="confirmCancel({{ $item->id }})">
                            <i class="fa fa-times me-1"></i> Batalkan
                        </button>
                        <form id="cancel-form-{{ $item->id }}" 
                              action="{{ route('nasabah.permintaan.batalkan', $item->id) }}" 
                              method="POST" 
                              style="display: none;">
                            @csrf
                            @method('DELETE')
                        </form>
                        @elseif($status === 'Selesai')
                        <span class="text-success small">
                            <i class="fa fa-check-circle me-1"></i> Selesai
                        </span>
                        @elseif($status === 'Dibatalkan')
                        <span class="text-danger small">
                            <i class="fa fa-times-circle me-1"></i> Dibatalkan
                        </span>
                        @else
                        <span class="text-muted small">
                            <i class="fa fa-info-circle me-1"></i> Diproses
                        </span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if(method_exists($nasabah, 'links'))
    <div class="d-flex justify-content-center mt-4">
        {{ $nasabah->links() }}
    </div>
    @endif
    @endif
</div>

@endsection

@push('scripts')
<script>
    // Fungsi konfirmasi pembatalan dengan SweetAlert2
    function confirmCancel(id) {
        Swal.fire({
            title: 'Apakah Anda yakin?',
            text: "Permintaan penjemputan yang dibatalkan tidak dapat dikembalikan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Batalkan!',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Submit form pembatalan
                document.getElementById('cancel-form-' + id).submit();
            }
        });
    }

    // Auto-hide alert setelah 5 detik
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                if (alert) {
                    const closeButton = alert.querySelector('.btn-close');
                    if (closeButton) {
                        closeButton.click();
                    }
                }
            });
        }, 5000);
    });
</script>
@endpush

@push('styles')
<style>
    .profile-tickets {
        background: #fff;
        border-radius: 20px;
        padding: 25px;
        box-shadow: 0 5px 20px rgba(0,0,0,.05);
    }
    .pro-add-title h4 {
        color: #0d5c2e;
        font-weight: 600;
    }
    .table th {
        font-weight: 600;
        color: #2d3436;
        border-bottom: 2px solid #e8f5ee;
    }
    .table td {
        vertical-align: middle;
        border-bottom: 1px solid #f0f0f0;
    }
    .table tbody tr:hover {
        background-color: #f8fffc;
    }
    .badge {
        font-weight: 500;
        padding: 6px 12px;
        border-radius: 20px;
    }
    .btn-success {
        background: linear-gradient(135deg, #0d5c2e, #2dae6e);
        border: none;
        transition: all 0.3s ease;
    }
    .btn-success:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(45,174,110,.3);
    }
    .btn-danger {
        transition: all 0.3s ease;
    }
    .btn-danger:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(220,53,69,.3);
    }
    @media(max-width: 768px) {
        .profile-tickets {
            padding: 15px;
        }
        .table {
            font-size: 13px;
        }
        .pro-add-title {
            flex-direction: column;
            gap: 10px;
        }
        .pro-add-title .btn {
            width: 100%;
        }
    }
</style>
@endpush