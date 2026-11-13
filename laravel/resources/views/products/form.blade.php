<section class="panel form-panel">
    @if ($categories->isEmpty())
        <div class="alert-warning" role="alert">
            Belum ada kategori. Tambahkan kategori sebelum membuat produk.
        </div>
    @endif

    <form action="{{ $product->exists ? route('products.update', $product) : route('products.store') }}"
          method="POST">
        @csrf
        @if ($product->exists)
            @method('PUT')
        @endif

        <div class="form-field">
            <label for="name">Nama produk <span class="required">*</span></label>
            <input id="name" name="name" type="text" value="{{ old('name', $product->name) }}"
                   maxlength="255" required autofocus aria-describedby="name-error">
            @error('name')
                <p class="field-error" id="name-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-field">
            <label for="sku">SKU <span class="required">*</span></label>
            <input id="sku" name="sku" type="text" value="{{ old('sku', $product->sku) }}"
                   maxlength="100" required aria-describedby="sku-error">
            @error('sku')
                <p class="field-error" id="sku-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-field">
            <label for="unit">Satuan <span class="required">*</span></label>
            <input id="unit" name="unit" type="text" value="{{ old('unit', $product->unit ?? 'unit') }}"
                   maxlength="30" required aria-describedby="unit-error">
            @error('unit')
                <p class="field-error" id="unit-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-field">
            <label for="category_id">Kategori <span class="required">*</span></label>
            <select id="category_id" name="category_id" required aria-describedby="category-error">
                <option value="">Pilih kategori</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}"
                        @selected((string) old('category_id', $product->category_id) === (string) $category->id)>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
            @error('category_id')
                <p class="field-error" id="category-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-field">
            <label for="description">Deskripsi <span class="muted">(opsional)</span></label>
            <textarea id="description" name="description" rows="4" maxlength="5000"
                      aria-describedby="description-error">{{ old('description', $product->description) }}</textarea>
            @error('description')
                <p class="field-error" id="description-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-grid">
            <div class="form-field">
                <label for="purchase_price">Harga beli (Rp) <span class="required">*</span></label>
                <input id="purchase_price" name="purchase_price" type="number" value="{{ old('purchase_price', $product->purchase_price ?? '0') }}"
                       min="0" step="0.01" required aria-describedby="purchase-price-error">
                @error('purchase_price')
                    <p class="field-error" id="purchase-price-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-field">
                <label for="selling_price">Harga jual (Rp) <span class="required">*</span></label>
                <input id="selling_price" name="selling_price" type="number" value="{{ old('selling_price', $product->selling_price ?? '0') }}"
                       min="0" step="0.01" required aria-describedby="selling-price-error">
                @error('selling_price')
                    <p class="field-error" id="selling-price-error">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="form-field">
            <label for="minimum_stock">Batas minimum stok <span class="required">*</span></label>
            <input id="minimum_stock" name="minimum_stock" type="number"
                   value="{{ old('minimum_stock', $product->minimum_stock ?? 5) }}"
                   min="0" step="1" required aria-describedby="minimum-stock-error">
            @error('minimum_stock')
                <p class="field-error" id="minimum-stock-error">{{ $message }}</p>
            @enderror
            <p class="form-help">Produk akan ditandai menipis saat stok sama dengan atau di bawah batas ini.</p>
        </div>

        @if ($product->exists)
            <p class="stock-note">Stok saat ini: <strong>{{ number_format($product->stock, 0, ',', '.') }}</strong> unit. Stok hanya berubah melalui transaksi.</p>
        @endif

        <div class="form-actions">
            <a class="button button-secondary" href="{{ $product->exists ? route('products.show', $product) : route('products.index') }}">Batal</a>
            <button class="button button-primary" type="submit" @disabled($categories->isEmpty())>
                {{ $product->exists ? 'Simpan perubahan' : 'Simpan produk' }}
            </button>
        </div>
    </form>
</section>
