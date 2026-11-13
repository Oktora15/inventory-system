<section class="panel form-panel">
    <form action="{{ $category->exists ? route('categories.update', $category) : route('categories.store') }}"
          method="POST">
        @csrf
        @if ($category->exists)
            @method('PUT')
        @endif

        <div class="form-field">
            <label for="name">Nama kategori <span class="required">*</span></label>
            <input id="name" name="name" type="text" value="{{ old('name', $category->name) }}"
                   maxlength="255" required autofocus aria-describedby="name-error">
            @error('name')
                <p class="field-error" id="name-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-field">
            <label for="description">Deskripsi <span class="muted">(opsional)</span></label>
            <textarea id="description" name="description" rows="5" maxlength="5000"
                      aria-describedby="description-error">{{ old('description', $category->description) }}</textarea>
            @error('description')
                <p class="field-error" id="description-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-actions">
            <a class="button button-secondary" href="{{ route('categories.index') }}">Batal</a>
            <button class="button button-primary" type="submit">
                {{ $category->exists ? 'Simpan perubahan' : 'Simpan kategori' }}
            </button>
        </div>
    </form>
</section>
