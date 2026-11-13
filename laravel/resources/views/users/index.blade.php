@extends('layouts.app')

@section('title', 'Manajemen Pengguna')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">ADMINISTRASI</p>
            <h1>Manajemen pengguna</h1>
            <p class="muted">Admin mengelola akun, role, dan status aktif pengguna.</p>
        </div>
        <a class="button button-primary" href="{{ route('users.create') }}">Tambah pengguna</a>
    </div>

    @if ($errors->has('user'))
        <div class="alert-error" role="alert">{{ $errors->first('user') }}</div>
    @endif

    <section class="panel" aria-label="Daftar pengguna">
        <div class="panel-heading">
            <h2>Daftar pengguna</h2>
            <span class="count">{{ $users->total() }} akun</span>
        </div>

        @if ($users->isEmpty())
            <div class="empty-state">
                <h3>Belum ada pengguna</h3>
                <p class="muted">Tambahkan akun Admin atau Kasir untuk mulai menggunakan aplikasi.</p>
                <a class="button button-primary" href="{{ route('users.create') }}">Tambah pengguna</a>
            </div>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th scope="col">Nama</th>
                            <th scope="col">Email</th>
                            <th scope="col">Role</th>
                            <th scope="col">Status</th>
                            <th scope="col"><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td class="category-name">
                                    {{ $user->name }}
                                    @if ($user->is(auth()->user()))
                                        <span class="table-subtext">Akun Anda</span>
                                    @endif
                                </td>
                                <td>{{ $user->email }}</td>
                                <td>{{ match ($user->role) { 'admin' => 'Admin', 'warehouse' => 'Warehouse', default => 'Kasir' } }}</td>
                                <td>
                                    <span class="status-badge {{ $user->is_active ? 'status-good' : 'status-warning' }}">
                                        {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <a class="button button-secondary button-small"
                                           href="{{ route('users.edit', $user) }}">Ubah</a>
                                        @if ($user->is_active)
                                            <form action="{{ route('users.password-reset', $user) }}" method="POST">
                                                @csrf
                                                <button class="button button-secondary button-small" type="submit">
                                                    Kirim tautan atur sandi
                                                </button>
                                            </form>
                                            @if (! $user->is(auth()->user()))
                                                <form action="{{ route('users.deactivate', $user) }}" method="POST"
                                                      onsubmit="return confirm('Nonaktifkan akun ini?')">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button class="button button-danger button-small" type="submit">
                                                        Nonaktifkan
                                                    </button>
                                                </form>
                                            @endif
                                        @else
                                            <form action="{{ route('users.activate', $user) }}" method="POST">
                                                @csrf
                                                @method('PATCH')
                                                <button class="button button-secondary button-small" type="submit">
                                                    Aktifkan
                                                </button>
                                            </form>
                                        @endif
                                        @if (! $user->is(auth()->user()) && $user->stock_transactions_count === 0)
                                            <form action="{{ route('users.destroy', $user) }}" method="POST"
                                                  onsubmit="return confirm('Hapus akun ini secara permanen?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="button button-danger button-small" type="submit">Hapus</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($users->hasPages())
                <div class="pagination">{{ $users->links() }}</div>
            @endif
        @endif
    </section>
@endsection
