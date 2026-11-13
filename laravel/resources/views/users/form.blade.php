<section class="panel form-panel">
    <form action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}" method="POST">
        @csrf
        @if ($user->exists)
            @method('PUT')
        @endif

        <div class="form-field">
            <label for="name">Nama <span class="required">*</span></label>
            <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}"
                   maxlength="255" required autofocus aria-describedby="name-error">
            @error('name')
                <p class="field-error" id="name-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-field">
            <label for="email">Email <span class="required">*</span></label>
            <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}"
                   maxlength="255" required aria-describedby="email-error">
            @error('email')
                <p class="field-error" id="email-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-field">
            <label for="role">Role <span class="required">*</span></label>
            <select id="role" name="role" required aria-describedby="role-error">
                <option value="cashier" @selected(old('role', $user->role ?: 'cashier') === 'cashier')>Kasir</option>
                <option value="warehouse" @selected(old('role', $user->role) === 'warehouse')>Warehouse</option>
                <option value="admin" @selected(old('role', $user->role) === 'admin')>Admin</option>
            </select>
            @error('role')
                <p class="field-error" id="role-error">{{ $message }}</p>
            @enderror
            @if ($user->exists && $user->is(auth()->user()))
                <p class="form-help">Role Admin yang sedang digunakan tidak dapat diturunkan dari akun sendiri.</p>
            @endif
        </div>

        @if (! $user->exists)
            <p class="form-help">Akun akan aktif. Pengguna menerima tautan email untuk membuat kata sandi sebelum login.</p>
        @endif

        <div class="form-actions">
            <a class="button button-secondary" href="{{ route('users.index') }}">Batal</a>
            <button class="button button-primary" type="submit">
                {{ $user->exists ? 'Simpan perubahan' : 'Buat pengguna dan kirim tautan sandi' }}
            </button>
        </div>
    </form>
</section>
