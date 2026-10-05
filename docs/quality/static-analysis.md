# Hasil Static Analysis — 5 Oktober 2026

Alat: PHPStan 2.2.17, level 5, mencakup seluruh `app/`. Dependensi ada pada `composer.json` dan konfigurasi pada `phpstan.neon`.

```bash
vendor/bin/phpstan analyse --debug --no-progress --error-format=table
```

Hasil aktual: **0 error**, exit code 0. Output lengkap tersimpan di [phpstan-level5.txt](phpstan-level5.txt). Mode `--debug` digunakan karena sandbox pemeriksaan lokal tidak mengizinkan PHPStan membuka socket proses paralel; cakupan dan level analisis tetap sama.

Saat analisis pertama, PHPStan menemukan kesalahan parameter `redirect()` setelah goods receipt pada `PurchaseOrderController`. Panggilan diperbaiki menjadi `redirect('purchase', ['action' => 'view', 'id' => $id])`. Sesudah itu analisis level 5 lulus.

Bukti test pada source yang sama: PHPUnit Unit 29 test / 51 assertion dan DatabaseIntegration pada MySQL 8 terisolasi 9 test / 28 assertion, seluruhnya lulus. [Hasil test](../testing/results-2026-10-05.md) mencatat rincian. Analisis ulang perlu dijalankan setelah setiap perubahan kode menjelang release.
