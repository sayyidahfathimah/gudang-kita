# ADR 001 — Batas Layer Aplikasi

- **Status:** Diterima
- **Konteks:** Controller yang mengurus HTTP sekaligus query dan aturan transaksi sulit diuji dan berisiko membocorkan detail database.
- **Keputusan:** Controller menangani HTTP, sesi, CSRF, dan izin; Service memeriksa aturan use case; Repository menjalankan SQL melalui PDO. Use case Sales Order menerima repository melalui kontrak.
- **Konsekuensi:** Aturan dapat diuji tanpa MySQL lewat fake repository. Beberapa controller lama masih membuat repository konkret secara langsung sehingga injeksi dependensi belum konsisten di seluruh aplikasi.
