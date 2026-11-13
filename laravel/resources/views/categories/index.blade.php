@extends('layouts.app')

@section('title', 'Kategori Barang')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">MASTER DATA</p>
            <h1>Kategori barang</h1>
            <p class="muted">Kelola pengelompokan barang di inventory.</p>
        </div>
        @if (auth()->user()->isAdmin())
            <a class="button button-primary" href="{{ route('categories.create') }}">Tambah kategori</a>
        @endif
    </div>

    <section class="panel" aria-label="Daftar kategori">
        <div class="panel-heading">
            <h2>Daftar kategori</h2>
            <span class="count">{{ $categories->total() }} kategori</span>
        </div>

        <form class="search-form" method="GET" action="{{ route('categories.index') }}">
            <label class="sr-only" for="category-search">Cari kategori</label>
            <input id="category-search" name="search" type="search" value="{{ $search }}"
                   placeholder="Cari nama atau deskripsi kategori..." maxlength="255">
            <button class="button button-secondary" type="submit">Cari</button>
            @if ($search)
                <a class="button button-secondary" href="{{ route('categories.index') }}">Reset</a>
            @endif
        </form>

        @if ($categories->isEmpty())
            <div class="empty-state">
                <h3>{{ $search ? 'Kategori tidak ditemukan' : 'Belum ada kategori' }}</h3>
                <p class="muted">{{ $search ? 'Coba kata kunci yang berbeda.' : 'Tambahkan kategori pertama untuk mulai mengelompokkan barang.' }}</p>
                @if (! $search && auth()->user()->isAdmin())
                    <a class="button button-primary" href="{{ route('categories.create') }}">Tambah kategori</a>
                @endif
            </div>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th scope="col">Nama kategori</th>
                            <th scope="col">Deskripsi</th>
                            <th scope="col">Diperbarui</th>
                            @if (auth()->user()->isAdmin())
                                <th scope="col"><span class="sr-only">Aksi</span></th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($categories as $category)
                            <tr>
                                <td class="category-name">{{ $category->name }}</td>
                                <td class="description-cell">{{ $category->description ?: '—' }}</td>
                                <td class="muted">{{ $category->updated_at->format('d M Y') }}</td>
                                @if (auth()->user()->isAdmin())
                                    <td>
                                        <div class="table-actions">
                                            <a class="button button-secondary button-small"
                                               href="{{ route('categories.edit', $category) }}">Ubah</a>
                                            <form action="{{ route('categories.destroy', $category) }}" method="POST"
                                                  onsubmit="return confirm('Hapus kategori ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="button button-danger button-small" type="submit">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($categories->hasPages())
                <div class="pagination">{{ $categories->links() }}</div>
            @endif
        @endif
    </section>
@endsection
