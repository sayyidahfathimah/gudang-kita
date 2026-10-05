# Latihan Critique Exercise

Cuplikan latihan berikut adalah contoh; assessor dapat memberikan cuplikan lain saat defense.

```php
class OrderService {
    public function save(array $input): void {
        $db = new PDO($dsn, $user, $password);
        if ($input['qty'] > 0) {
            $db->query('UPDATE stocks SET current_stock = current_stock - ' . $input['qty']);
            mail($input['email'], 'Order', 'Selesai');
        }
    }
}
```

**Smell:** satu kelas mencampur validasi, koneksi database, perubahan stok, dan notifikasi (Long Method serta tanggung jawab bercampur). Query menyisipkan input langsung, tidak memakai transaksi, tidak mengunci stok, dan dapat membuat saldo negatif.

**Prinsip SOLID:** SRP dilanggar karena alasan perubahan validasi, penyimpanan, serta notifikasi berbeda. DIP dilanggar karena service membuat PDO sendiri dan bergantung pada detail infrastrukturnya.

**Arah refactor:** controller memvalidasi request; service menerima interface repository melalui constructor; repository memakai prepared statement, transaksi, dan `SELECT ... FOR UPDATE` untuk perubahan stok beserta ledger. Notifikasi dipisahkan dari operasi inti. Test service memakai fake repository; integration test membuktikan rollback dan penolakan oversell.

Analisis ini perlu disesuaikan dengan cuplikan yang benar-benar diberikan assessor saat technical defense.
