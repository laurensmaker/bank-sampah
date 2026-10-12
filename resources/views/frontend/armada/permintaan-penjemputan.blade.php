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
        showConfirmButton: true,
        timerProgressBar: true,
        didOpen: () => {
            // Animasi saat dibuka
        },
        willClose: () => {
            // Redirect atau reload setelah SweetAlert ditutup (opsional)
            
        }
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
    <div class="pro-add-title">
        <h4>Data Permintaan Penjemputan</h4>
    </div>
    <table class="table table-hover">
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Jenis Sampah</th>
                <th>Berat Sampah</th>
                <th>Status</th>
                {{-- <th>Aksi</th> --}}
            </tr>
        </thead>
        <tbody>
            @foreach($data as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->tanggal }}</td>
                <td>{{ $item->jenis_sampah }}</td>
                <td>{{ $item->berat_sampah }}</td>
                <td>
                    @php
                        $status = $item->statusPenjemputan->first()->status ?? 'Unknown';

                        $class = match($status) {
                            'Menunggu' => 'bg-warning',
                            'Dijadwalkan' => 'bg-primary',
                            'Dijemput' => 'bg-info',
                            'Selesai' => 'bg-success',
                            'Dibatalkan' => 'bg-danger',
                            default => 'bg-secondary'
                        };
                    @endphp

                    <span class="badge {{ $class }}">
                        {{ $status }}
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
    
@endsection