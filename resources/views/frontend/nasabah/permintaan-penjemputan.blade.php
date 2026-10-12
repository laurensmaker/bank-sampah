@extends('frontend.nasabah.main-dasbor')
@section('dasbor-content')

<div class="profile-form">
    <div class="pro-add-title">
        <h4>Permintaan Penjemputan</h4>
    </div>

    <form action="{{ route('nasabah.permintaan-penjemputan.store') }}" method="POST">
        @csrf

        <div class="form-container p-4">

            {{-- Row 1: Tanggal & Alamat --}}
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label fw-semibold mb-2">
                            <i class="fa fa-calendar text-success me-2"></i>Tanggal Penjemputan
                        </label>
                        <input type="datetime-local" name="tanggal"
                               class="form-control rounded-3 py-2"
                               style="border-color:#c8e6d9;" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label fw-semibold mb-2">
                            <i class="fa fa-map-marker-alt text-success me-2"></i>Alamat Penjemputan
                        </label>
                        <input type="text" name="alamat_penjemputan"
                               class="form-control rounded-3 py-2"
                               value="{{ $nasabah->alamat ?? '' }}" readonly
                               style="border-color:#c8e6d9;">
                    </div>
                </div>
            </div>

            {{-- Row 2: Jenis & Berat Sampah --}}
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label fw-semibold mb-2">
                            <i class="fa fa-trash-alt text-success me-2"></i>Jenis Sampah
                        </label>
                        <select name="jenis_sampah" class="form-select rounded-3 py-2"
                                style="border-color:#c8e6d9;" required>
                            <option value="" disabled selected>Pilih jenis sampah</option>
                            <option value="plastik">Plastik</option>
                            <option value="kertas">Kertas</option>
                            <option value="kaca">Kaca</option>
                            <option value="logam">Logam</option>
                            <option value="organik">Organik</option>
                            <option value="elektronik">Elektronik</option>
                            <option value="lainnya">Lainnya</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label fw-semibold mb-2">
                            <i class="fa fa-weight-hanging text-success me-2"></i>Berat Sampah (kg)
                        </label>
                        <div class="input-group">
                            <input type="number" step="0.01" name="berat_sampah"
                                   class="form-control rounded-3 py-2"
                                   placeholder="0.00" style="border-color:#c8e6d9;" required>
                            <span class="input-group-text bg-light rounded-3"
                                  style="border-color:#c8e6d9;">kg</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Submit --}}
            <div class="row">
                <div class="col-12">
                    <hr class="my-3" style="border-color:#c8e6d9;">
                    <button type="submit"
                            class="btn w-100 py-3 fw-bold text-white rounded-3"
                            style="background:linear-gradient(135deg,#0d5c2e,#2dae6e);border:none;font-size:16px;">
                        <i class="fa fa-paper-plane me-2"></i> Kirim Permintaan Penjemputan
                    </button>
                </div>
            </div>

        </div>
    </form>
</div>

{{-- ── STYLE ───────────────────────────────────────── --}}
<style>
.form-container {
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 5px 20px rgba(0,0,0,.05);
}
.form-label { 
    color: #0d5c2e; 
    font-size: 14px; 
    font-weight: 600;
}
.form-control:focus, .form-select:focus {
    border-color: #2dae6e;
    box-shadow: 0 0 0 3px rgba(45,174,110,.1);
}
.form-control:hover, .form-select:hover {
    border-color: #2dae6e;
}
.btn-success-gradient {
    background: linear-gradient(135deg,#0d5c2e,#2dae6e);
    border: none;
    transition: all 0.3s ease;
}
.btn-success-gradient:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(45,174,110,.3);
    background: linear-gradient(135deg,#0a4a24,#259d5e);
}
textarea {
    resize: vertical;
    min-height: 80px;
}
@media(max-width:768px){
    .form-container { padding: 1rem !important; }
}
</style>

@endsection