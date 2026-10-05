# Bukti Responsif — 5–6 Oktober 2026

Screenshot diambil dari salinan Docker yang bersih dengan data seed. Browser Chrome diatur ke viewport CSS 360 × 900 untuk mobile dan 1280 × 900 untuk desktop. Pada lima halaman awal, `document.documentElement.scrollWidth` sama dengan lebar viewport. Screenshot order tambahan menunjukkan tabel yang dapat digeser di dalam panelnya; lebar dokumen pada empat keadaan order itu belum diukur otomatis.

| Halaman | Mobile 360 px | Desktop |
| --- | --- | --- |
| Login | [Gambar](login-mobile-360.png) | [Gambar](login-desktop.png) |
| Dashboard Admin | [Gambar](dashboard-mobile-360.png) | [Gambar](dashboard-desktop.png) |
| Daftar produk | [Gambar](products-mobile-360.png) | [Gambar](products-desktop.png) |
| Detail stok produk | [Gambar](detail-mobile-360.png) | [Gambar](detail-desktop.png) |
| Form tambah produk | [Gambar](form-mobile-360.png) | [Gambar](form-desktop.png) |
| Daftar Purchase Order | [Gambar](purchase-list-mobile-360.png) | [Gambar](purchase-list-desktop.png) |
| Purchase Order hasil kosong | [Gambar](purchase-empty-mobile-360.png) | [Gambar](purchase-empty-desktop.png) |
| Daftar Sales Order | [Gambar](sales-list-mobile-360.png) | [Gambar](sales-list-desktop.png) |
| Sales Order hasil kosong | [Gambar](sales-empty-mobile-360.png) | [Gambar](sales-empty-desktop.png) |

Pemeriksaan awal menemukan rute detail stok menghasilkan 500 dan tabel terlalu padat pada mobile. Routing dan CSS diperbaiki, lalu seluruh screenshot diambil ulang. Screenshot berisi akun Admin dari instalasi seed; tidak menampilkan kredensial.

Delapan screenshot order diambil pada 6 Oktober dari database acceptance terpisah dengan [script capture](../../../tests/capture_order_screenshots.py). HTML autentikasi sementara tidak disimpan dalam repository; hanya gambar hasilnya yang disimpan. Tabel order pada 360 px digeser di dalam panel.
