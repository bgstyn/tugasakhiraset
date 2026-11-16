@extends('layouts.public')

@section('title', 'Laporkan Kerusakan - Inventaris TI')

@section('content')
<a href="{{ $asset->publicQrUrl() }}" class="ops-back">← Kembali ke aset</a>

<section class="ops-surface ops-public-panel">
    <header>
        <h1>Laporkan kerusakan</h1>
        <p>{{ $asset->name }} · {{ $asset->asset_id }}</p>
    </header>

    @if($errors->any())
        <div class="ops-flash" data-tone="error" role="alert">Periksa kolom yang ditandai sebelum mengirim ulang.</div>
    @endif

    <form action="{{ route('tickets.public.store', $asset->id, false) }}" method="POST" enctype="multipart/form-data" class="ops-form">
        @csrf
        <div class="ops-field">
            <label for="reporter_name">Nama pelapor</label>
            <input type="text" id="reporter_name" name="reporter_name" required maxlength="150" autocomplete="name"
                   value="{{ old('reporter_name') }}" @error('reporter_name') aria-invalid="true" aria-describedby="reporter_name-error" @enderror>
            @error('reporter_name') <p class="ops-field-error" id="reporter_name-error">{{ $message }}</p> @enderror
        </div>

        <div class="ops-field">
            <label for="reporter_contact">Email atau nomor ponsel <small>(opsional)</small></label>
            <input type="text" id="reporter_contact" name="reporter_contact" maxlength="100"
                   value="{{ old('reporter_contact') }}" @error('reporter_contact') aria-invalid="true" aria-describedby="reporter_contact-error" @enderror>
            @error('reporter_contact') <p class="ops-field-error" id="reporter_contact-error">{{ $message }}</p> @enderror
        </div>

        <div class="ops-field">
            <label for="priority">Prioritas</label>
            <select id="priority" name="priority" required @error('priority') aria-invalid="true" aria-describedby="priority-error" @enderror>
                @foreach(['low' => 'Rendah', 'medium' => 'Sedang', 'high' => 'Tinggi', 'critical' => 'Kritis'] as $key => $label)
                    <option value="{{ $key }}" @selected(old('priority', 'medium') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            @error('priority') <p class="ops-field-error" id="priority-error">{{ $message }}</p> @enderror
        </div>

        <div class="ops-field">
            <label for="description">Apa yang rusak?</label>
            <textarea id="description" name="description" required rows="5"
                      placeholder="Ceritakan gejala dan kapan masalah terjadi."
                      @error('description') aria-invalid="true" aria-describedby="description-error" @enderror>{{ old('description') }}</textarea>
            @error('description') <p class="ops-field-error" id="description-error">{{ $message }}</p> @enderror
        </div>

        <div class="ops-field">
            <label for="photo">Foto kerusakan <small>(opsional)</small></label>
            <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp"
                   aria-describedby="photo-hint @error('photo') photo-error @enderror" @error('photo') aria-invalid="true" @enderror>
            <small id="photo-hint">JPEG, PNG, atau WebP hingga 5 MB.</small>
            @error('photo') <p class="ops-field-error" id="photo-error">{{ $message }}</p> @enderror
        </div>

        <div class="ops-inline-actions">
            <button type="submit" class="ops-button ops-button-primary">Kirim laporan</button>
        </div>
    </form>
</section>
@endsection
