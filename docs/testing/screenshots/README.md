# Bukti Responsif — 5 Oktober 2026

Screenshot diambil dari salinan Docker yang bersih dengan data seed. Browser Chrome diatur ke viewport CSS 360 × 900 untuk mobile dan 1280 × 900 untuk desktop. `document.documentElement.scrollWidth` sama dengan lebar viewport pada kelima halaman; tabel yang lebar dapat digeser di dalam panelnya.

| Halaman | Mobile 360 px | Desktop |
| --- | --- | --- |
| Login | [Gambar](login-mobile-360.png) | [Gambar](login-desktop.png) |
| Dashboard Admin | [Gambar](dashboard-mobile-360.png) | [Gambar](dashboard-desktop.png) |
| Daftar produk | [Gambar](products-mobile-360.png) | [Gambar](products-desktop.png) |
| Detail stok produk | [Gambar](detail-mobile-360.png) | [Gambar](detail-desktop.png) |
| Form tambah produk | [Gambar](form-mobile-360.png) | [Gambar](form-desktop.png) |

Pemeriksaan awal menemukan rute detail stok menghasilkan 500 dan tabel terlalu padat pada mobile. Routing dan CSS diperbaiki, lalu seluruh screenshot diambil ulang. Screenshot berisi akun Admin dari instalasi seed; tidak menampilkan kredensial.
