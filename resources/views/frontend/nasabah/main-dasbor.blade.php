@extends('frontend.main.layout-main')
@section('content')

<!-- ====== HERO / PAGE TITLE ====== -->
<!-- ====== HERO / PAGE TITLE ====== -->
<section class="py-3">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="bg-light p-4 rounded-3 shadow-sm position-relative">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h1 class="display-6 fw-bold text-dark mb-1">
                                <i class="bi bi-house-fill text-primary me-2"></i>
                                Selamat Datang, {{ Auth::user()->name }}!
                            </h1>
                            <p class="text-muted mb-0 mt-2">
                                <i class="bi bi-calendar3 me-1"></i> 
                                Kelola permintaan penjemputan sampah Anda dengan mudah
                            </p>
                        </div>
                        
                        <!-- ====== NOTIFICATION ICON ====== -->
                        <div class="position-relative">
                            <button type="button" 
                                    class="btn btn-light position-relative rounded-circle p-3 shadow-sm border-0"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#notificationModal"
                                    style="width: 56px; height: 56px; transition: all 0.3s ease;">
                                <i class="bi bi-bell-fill fs-4 text-primary"></i>
                                @if(isset($unreadCount) && $unreadCount > 0)
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" 
                                          style="font-size: 11px; padding: 5px 8px; border: 2px solid white;">
                                        {{ $unreadCount }}
                                        <span class="visually-hidden">notifikasi belum dibaca</span>
                                    </span>
                                @endif
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


<!-- ====== NOTIFICATION MODAL ====== -->
<div class="modal fade" id="notificationModal" tabindex="-1" aria-labelledby="notificationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <!-- Modal Header -->
            <div class="modal-header border-0 pb-0">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary bg-opacity-10 p-2 rounded-3">
                        <i class="bi bi-bell-fill fs-4 text-primary"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold" id="notificationModalLabel">
                            Notifikasi
                        </h5>
                        <p class="text-muted small mb-0">
                            <span id="unreadCountText">{{ isset($unreadCount) ? $unreadCount : 0 }}</span> notifikasi belum dibaca
                        </p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body pt-3">
                <!-- Filter Tabs -->
                <ul class="nav nav-tabs nav-justified mb-3 border-0 gap-2" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active rounded-3 border-0 fw-semibold" 
                                id="all-tab" 
                                data-bs-toggle="tab" 
                                data-bs-target="#all" 
                                type="button" 
                                role="tab">
                            <i class="bi bi-list-ul me-1"></i> Semua
                            <span class="badge bg-secondary ms-1" id="allCount">{{ isset($notifications) ? $notifications->count() : 0 }}</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-3 border-0 fw-semibold" 
                                id="unread-tab" 
                                data-bs-toggle="tab" 
                                data-bs-target="#unread" 
                                type="button" 
                                role="tab">
                            <i class="bi bi-envelope me-1"></i> Belum Dibaca
                            <span class="badge bg-danger ms-1" id="unreadBadge">{{ isset($unreadCount) ? $unreadCount : 0 }}</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-3 border-0 fw-semibold" 
                                id="read-tab" 
                                data-bs-toggle="tab" 
                                data-bs-target="#read" 
                                type="button" 
                                role="tab">
                            <i class="bi bi-check2-all me-1"></i> Sudah Dibaca
                            <span class="badge bg-success ms-1" id="readCount">{{ isset($readCount) ? $readCount : 0 }}</span>
                        </button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content">
                    <!-- All Notifications -->
                    <div class="tab-pane fade show active" id="all" role="tabpanel">
                        <div class="notification-list" id="allNotifications">
                            @if(isset($notifications) && $notifications->count() > 0)
                                @foreach($notifications as $notification)
                                    <div class="notification-item d-flex align-items-start gap-3 p-3 rounded-3 mb-2 
                                          {{ $notification->status_baca == 'read' ? 'bg-light' : 'bg-primary bg-opacity-10' }} 
                                          notification-item-{{ $notification->id }}" 
                                          data-id="{{ $notification->id }}"
                                          data-status="{{ $notification->status_baca }}"
                                          style="cursor: pointer; transition: all 0.2s ease;"
                                          onclick="markAsRead({{ $notification->id }})">
                                        
                                        <!-- Icon -->
                                        <div class="flex-shrink-0">
                                            <div class="rounded-circle p-2 {{ $notification->status_baca == 'read' ? 'bg-secondary bg-opacity-10' : 'bg-primary bg-opacity-20' }}"
                                                 style="width: 44px; height: 44px; display: flex; align-items: center; justify-content: center;">
                                                <i class="bi bi-info-circle 
                                                      {{ $notification->status_baca == 'read' ? 'text-secondary' : 'text-primary' }} fs-5">
                                                </i>
                                            </div>
                                        </div>
                                        
                                        <!-- Content -->
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <h6 class="fw-semibold mb-1 {{ $notification->status_baca == 'read' ? 'text-dark' : 'text-primary' }}">
                                                    Notifikasi Penjemputan
                                                    @if($notification->status_baca == 'unread')
                                                        <span class="badge bg-primary ms-2" style="font-size: 8px;">BARU</span>
                                                    @endif
                                                </h6>
                                                <small class="text-muted flex-shrink-0 ms-2">
                                                    {{ \Carbon\Carbon::parse($notification->created_at)->diffForHumans() }}
                                                </small>
                                            </div>
                                            <p class="mb-1 text-muted small">
                                                {{ $notification->isi_notifikasi ?? 'Tidak ada pesan' }}
                                            </p>
                                            <div class="d-flex align-items-center gap-3 mt-1">
                                                <small class="text-muted">
                                                    <i class="bi bi-calendar3 me-1"></i>
                                                    {{ \Carbon\Carbon::parse($notification->tanggal_notifikasi)->format('d M Y') }}
                                                </small>
                                                @if($notification->status_baca == 'unread')
                                                    <small class="text-warning status-text-{{ $notification->id }}">
                                                        <i class="bi bi-dot"></i> Belum dibaca
                                                    </small>
                                                @else
                                                    <small class="text-success status-text-{{ $notification->id }}">
                                                        <i class="bi bi-check2-circle me-1"></i> Sudah dibaca
                                                    </small>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <!-- Empty State -->
                                <div class="text-center py-5">
                                    <div class="bg-light rounded-circle p-4 d-inline-block mb-3">
                                        <i class="bi bi-inbox fs-1 text-muted"></i>
                                    </div>
                                    <h6 class="fw-semibold text-dark">Tidak Ada Notifikasi</h6>
                                    <p class="text-muted small">Belum ada notifikasi untuk Anda saat ini</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Unread Notifications -->
                    <div class="tab-pane fade" id="unread" role="tabpanel">
                        <div class="notification-list" id="unreadNotifications">
                            @php
                                $unreadNotifications = isset($notifications) ? $notifications->where('status_baca', 'unread') : collect([]);
                            @endphp
                            @if($unreadNotifications->count() > 0)
                                @foreach($unreadNotifications as $notification)
                                    <div class="notification-item d-flex align-items-start gap-3 p-3 rounded-3 mb-2 bg-primary bg-opacity-10 
                                          notification-item-{{ $notification->id }}" 
                                          data-id="{{ $notification->id }}"
                                          data-status="{{ $notification->status_baca }}"
                                          style="cursor: pointer; transition: all 0.2s ease;"
                                          onclick="markAsRead({{ $notification->id }})">
                                        <div class="flex-shrink-0">
                                            <div class="rounded-circle p-2 bg-primary bg-opacity-20"
                                                 style="width: 44px; height: 44px; display: flex; align-items: center; justify-content: center;">
                                                <i class="bi bi-info-circle text-primary fs-5"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <h6 class="fw-semibold mb-1 text-primary">
                                                    Notifikasi Penjemputan
                                                    <span class="badge bg-primary ms-2" style="font-size: 8px;">BARU</span>
                                                </h6>
                                                <small class="text-muted flex-shrink-0 ms-2">
                                                    {{ \Carbon\Carbon::parse($notification->created_at)->diffForHumans() }}
                                                </small>
                                            </div>
                                            <p class="mb-1 text-muted small">
                                                {{ $notification->isi_notifikasi ?? 'Tidak ada pesan' }}
                                            </p>
                                            <div class="d-flex align-items-center gap-3 mt-1">
                                                <small class="text-muted">
                                                    <i class="bi bi-calendar3 me-1"></i>
                                                    {{ \Carbon\Carbon::parse($notification->tanggal_notifikasi)->format('d M Y') }}
                                                </small>
                                                <small class="text-warning status-text-{{ $notification->id }}">
                                                    <i class="bi bi-dot"></i> Belum dibaca
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="text-center py-5">
                                    <div class="bg-light rounded-circle p-4 d-inline-block mb-3">
                                        <i class="bi bi-envelope-check fs-1 text-muted"></i>
                                    </div>
                                    <h6 class="fw-semibold text-dark">Semua Notifikasi Telah Dibaca</h6>
                                    <p class="text-muted small">Tidak ada notifikasi yang belum dibaca</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Read Notifications -->
                    <div class="tab-pane fade" id="read" role="tabpanel">
                        <div class="notification-list" id="readNotifications">
                            @php
                                $readNotifications = isset($notifications) ? $notifications->where('status_baca', 'read') : collect([]);
                            @endphp
                            @if($readNotifications->count() > 0)
                                @foreach($readNotifications as $notification)
                                    <div class="notification-item d-flex align-items-start gap-3 p-3 rounded-3 mb-2 bg-light
                                          notification-item-{{ $notification->id }}" 
                                          data-id="{{ $notification->id }}"
                                          data-status="{{ $notification->status_baca }}">
                                        <div class="flex-shrink-0">
                                            <div class="rounded-circle p-2 bg-secondary bg-opacity-10"
                                                 style="width: 44px; height: 44px; display: flex; align-items: center; justify-content: center;">
                                                <i class="bi bi-info-circle text-secondary fs-5"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <h6 class="fw-semibold mb-1 text-dark">
                                                    Notifikasi Penjemputan
                                                </h6>
                                                <small class="text-muted flex-shrink-0 ms-2">
                                                    {{ \Carbon\Carbon::parse($notification->created_at)->diffForHumans() }}
                                                </small>
                                            </div>
                                            <p class="mb-1 text-muted small">
                                                {{ $notification->isi_notifikasi ?? 'Tidak ada pesan' }}
                                            </p>
                                            <div class="d-flex align-items-center gap-3 mt-1">
                                                <small class="text-muted">
                                                    <i class="bi bi-calendar3 me-1"></i>
                                                    {{ \Carbon\Carbon::parse($notification->tanggal_notifikasi)->format('d M Y') }}
                                                </small>
                                                <small class="text-success status-text-{{ $notification->id }}">
                                                    <i class="bi bi-check2-circle me-1"></i> Sudah dibaca
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="text-center py-5">
                                    <div class="bg-light rounded-circle p-4 d-inline-block mb-3">
                                        <i class="bi bi-inbox fs-1 text-muted"></i>
                                    </div>
                                    <h6 class="fw-semibold text-dark">Belum Ada Notifikasi</h6>
                                    <p class="text-muted small">Tidak ada notifikasi yang sudah dibaca</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer border-0 pt-0">
                <div class="d-flex justify-content-between w-100">
                    <button type="button" class="btn btn-link text-muted text-decoration-none" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i> Tutup
                    </button>
                    @if(isset($unreadCount) && $unreadCount > 0)
                        <button type="button" class="btn btn-primary rounded-pill px-4" 
                                id="markAllBtn"
                                onclick="markAllAsRead()">
                            <i class="bi bi-check2-all me-1"></i> Tandai Semua Dibaca
                            <span class="badge bg-light text-dark ms-1" id="markAllBadge">{{ $unreadCount }}</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>





<!-- ====== MAIN DASHBOARD AREA ====== -->
<section class="py-4">
    <div class="container-fluid">
        <div class="row g-4">
            
            <!-- ====== LEFT SIDEBAR ====== -->
            <div class="col-lg-3 col-md-4">
                <!-- Profile Card -->
                <div class="card border-0 shadow-lg rounded-4 mb-4 overflow-hidden">
                    <div class="card-body p-0">
                        <!-- Gradient Header -->
                        <div class="bg-primary bg-gradient p-4 text-white text-center">
                            <div class="position-relative d-inline-block mb-3">
                                <img src="{{ asset('frontend/img/testimonial/testi-1.jpg') }}" 
                                     alt="Profile Photo" 
                                     class="rounded-circle border border-3 border-white shadow-sm"
                                     width="90" height="90" 
                                     style="object-fit: cover;">
                                <span class="position-absolute bottom-0 end-0 bg-success rounded-circle border border-2 border-white" 
                                      style="width: 20px; height: 20px;"></span>
                            </div>
                            <h5 class="fw-bold mb-1 text-white">{{ Auth::user()->name }}</h5>
                            <span class="badge bg-white bg-opacity-25 text-white px-3 py-2 rounded-pill">
                                <i class="bi bi-person-circle me-1"></i> {{ ucfirst(Auth::user()->role) }}
                            </span>
                            <p class="small text-white-50 mt-2 mb-0">
                                <i class="bi bi-calendar-check me-1"></i> 
                                Bergabung {{ Auth::user()->created_at ? Auth::user()->created_at->format('d M Y') : 'Baru' }}
                            </p>
                        </div>
                        
                        <!-- Navigation Menu -->
                        <div class="p-3">
                            <div class="list-group list-group-flush">
                                @if(Auth::user()->role === 'nasabah')
                                    <a href="{{ route('nasabah.dasbor') }}" 
                                       class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3 rounded-3 mb-1 {{ request()->routeIs('nasabah.dasbor') ? 'active bg-primary text-white shadow-sm' : '' }}">
                                        <i class="bi bi-grid-fill fs-5"></i>
                                        <span class="fw-semibold">Dasbor</span>
                                        @if(request()->routeIs('nasabah.dasbor'))
                                            <span class="ms-auto badge bg-white text-primary rounded-pill">Aktif</span>
                                        @endif
                                    </a>
                                    
                                    <a href="{{ route('nasabah.permintaan-penjemputan') }}" 
                                       class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3 rounded-3 mb-1 {{ request()->routeIs('nasabah.permintaan-penjemputan') ? 'active bg-primary text-white shadow-sm' : '' }}">
                                        <i class="bi bi-plus-circle-fill fs-5"></i>
                                        <span class="fw-semibold">Buat Permintaan</span>
                                        @if(request()->routeIs('nasabah.permintaan-penjemputan'))
                                            <span class="ms-auto badge bg-white text-primary rounded-pill">Aktif</span>
                                        @endif
                                    </a>
                                    
                                    <a href="{{ route('nasabah.riwayat-penjemputan') }}" 
                                       class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3 rounded-3 mb-1 {{ request()->routeIs('nasabah.riwayat-penjemputan') ? 'active bg-primary text-white shadow-sm' : '' }}">
                                        <i class="bi bi-clock-history fs-5"></i>
                                        <span class="fw-semibold">Riwayat</span>
                                        @if(request()->routeIs('nasabah.riwayat-penjemputan'))
                                            <span class="ms-auto badge bg-white text-primary rounded-pill">Aktif</span>
                                        @endif
                                    </a>
                                @elseif(Auth::user()->role === 'armada')
                                    <a href="{{ route('armada.dasbor') }}" 
                                       class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3 rounded-3 mb-1 {{ request()->routeIs('armada.dasbor') ? 'active bg-primary text-white shadow-sm' : '' }}">
                                        <i class="bi bi-grid-fill fs-5"></i>
                                        <span class="fw-semibold">Dasbor</span>
                                        @if(request()->routeIs('armada.dasbor'))
                                            <span class="ms-auto badge bg-white text-primary rounded-pill">Aktif</span>
                                        @endif
                                    </a>
                                    
                                    {{-- <a href="{{ route('armada.permintaan-penjemputan') }}" 
                                       class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3 rounded-3 mb-1 {{ request()->routeIs('armada.permintaan-penjemputan') ? 'active bg-primary text-white shadow-sm' : '' }}">
                                        <i class="bi bi-list-task fs-5"></i>
                                        <span class="fw-semibold">Data Permintaan</span>
                                        @if(request()->routeIs('armada.permintaan-penjemputan'))
                                            <span class="ms-auto badge bg-white text-primary rounded-pill">Aktif</span>
                                        @endif
                                    </a> --}}
                                    
                                    <a href="{{ route('armada.rute') }}" 
                                       class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3 rounded-3 mb-1 {{ request()->routeIs('armada.optimasi') ? 'active bg-primary text-white shadow-sm' : '' }}">
                                        <i class="bi bi-geo-alt fs-5"></i>
                                        <span class="fw-semibold">Optimasi Rute</span>
                                        @if(request()->routeIs('armada.rute'))
                                            <span class="ms-auto badge bg-white text-primary rounded-pill">Aktif</span>
                                        @endif
                                    </a>
                                @endif
                                
                                <div class="dropdown-divider my-2"></div>
                                
                                <a href="{{ route('logout') }}" 
                                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                                   class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3 rounded-3 text-danger hover-danger">
                                    <i class="bi bi-box-arrow-right fs-5"></i>
                                    <span class="fw-semibold">Keluar</span>
                                </a>
                                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                    @csrf
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- ====== RIGHT CONTENT ====== -->
            <div class="col-lg-9 col-md-8">
                <div class="card border-0 shadow-lg rounded-4">
                    <div class="card-body p-4">
                        @yield('dasbor-content')
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</section>

<!-- ====== BOOTSTRAP ICONS ====== -->
@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
@endpush
<!-- ====== JAVASCRIPT UNTUK NOTIFIKASI ====== -->
<script>

const CSRF_TOKEN = '{{ csrf_token() }}';

function markAsRead(id) {
    const notificationItem = document.querySelector(`.notification-item-${id}`);
    if (!notificationItem || notificationItem.dataset.status === 'read') return;

    fetch(`/notifikasi/${id}/baca`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN
        },
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateNotificationItem(id);
            moveToReadTab(id);
            updateCounts(-1);
        }
    })
    .catch(error => console.error('Error:', error));
}

function markAllAsRead() {
    fetch('/notifikasi/baca-semua', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN
        },
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.querySelectorAll('.notification-item').forEach(item => {
                if (item.dataset.status === 'unread') {
                    updateNotificationItemElement(item);
                }
            });
            
            const unreadContainer = document.getElementById('unreadNotifications');
            unreadContainer.innerHTML = `
                <div class="text-center py-5">
                    <div class="bg-light rounded-circle p-4 d-inline-block mb-3">
                        <i class="bi bi-envelope-check fs-1 text-muted"></i>
                    </div>
                    <h6 class="fw-semibold text-dark">Semua Notifikasi Telah Dibaca</h6>
                    <p class="text-muted small">Tidak ada notifikasi yang belum dibaca</p>
                </div>
            `;
            
            const totalNotifications = document.querySelectorAll('#allNotifications .notification-item').length;
            document.getElementById('unreadCountText').textContent = '0';
            document.getElementById('unreadBadge').textContent = '0';
            document.getElementById('allCount').textContent = totalNotifications;
            document.getElementById('readCount').textContent = totalNotifications;
            
            const markAllBtn = document.getElementById('markAllBtn');
            if (markAllBtn) {
                markAllBtn.style.display = 'none';
            }
            
            const bellBadge = document.querySelector('.position-absolute.top-0.start-100');
            if (bellBadge) {
                bellBadge.style.display = 'none';
            }
        }
    })
    .catch(error => console.error('Error:', error));
}

function updateNotificationItem(id) {
    const allItem = document.querySelector(`#allNotifications .notification-item-${id}`);
    if (allItem) {
        updateNotificationItemElement(allItem);
    }
}

function updateNotificationItemElement(item) {
    item.classList.remove('bg-primary', 'bg-opacity-10');
    item.classList.add('bg-light');
    item.dataset.status = 'read';
    
    const iconContainer = item.querySelector('.rounded-circle');
    if (iconContainer) {
        iconContainer.classList.remove('bg-primary', 'bg-opacity-20');
        iconContainer.classList.add('bg-secondary', 'bg-opacity-10');
    }
    
    const icon = item.querySelector('.bi-info-circle');
    if (icon) {
        icon.classList.remove('text-primary');
        icon.classList.add('text-secondary');
    }
    
    const title = item.querySelector('h6');
    if (title) {
        title.classList.remove('text-primary');
        title.classList.add('text-dark');
        const badge = title.querySelector('.badge');
        if (badge) badge.remove();
    }
    
    const statusText = item.querySelector('[class*="status-text-"]');
    if (statusText) {
        statusText.className = statusText.className.replace('text-warning', 'text-success');
        statusText.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Sudah dibaca';
    }
}

function moveToReadTab(id) {
    const allItem = document.querySelector(`#allNotifications .notification-item-${id}`);
    if (!allItem) return;
    
    const readContainer = document.getElementById('readNotifications');
    const emptyState = readContainer.querySelector('.text-center');
    if (emptyState) {
        emptyState.remove();
    }
    
    const readItem = allItem.cloneNode(true);
    updateNotificationItemElement(readItem);
    readItem.removeAttribute('onclick');
    readItem.style.cursor = 'default';
    readContainer.appendChild(readItem);
    
    const unreadItem = document.querySelector(`#unreadNotifications .notification-item-${id}`);
    if (unreadItem) {
        unreadItem.remove();
    }
    
    const readCount = document.getElementById('readCount');
    if (readCount) {
        let count = parseInt(readCount.textContent) || 0;
        readCount.textContent = count + 1;
    }
}

function updateCounts(change) {
    const unreadCountText = document.getElementById('unreadCountText');
    const unreadBadge = document.getElementById('unreadBadge');
    const markAllBadge = document.getElementById('markAllBadge');
    
    let currentCount = parseInt(unreadCountText.textContent) || 0;
    let newCount = currentCount + change;
    if (newCount < 0) newCount = 0;
    
    unreadCountText.textContent = newCount;
    unreadBadge.textContent = newCount;
    if (markAllBadge) {
        markAllBadge.textContent = newCount;
    }
    
    const markAllBtn = document.getElementById('markAllBtn');
    if (markAllBtn) {
        if (newCount === 0) {
            markAllBtn.style.display = 'none';
        } else {
            markAllBtn.style.display = 'inline-flex';
        }
    }
}
</script>

@stack('scripts')
@endsection
