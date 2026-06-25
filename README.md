# Point of Sales (POS) API

Aplikasi Point of Sales (POS) API sederhana berbasis Laravel 13 untuk mengelola produk dan melakukan checkout transaksi dengan aman.

---

## 🛠️ Persyaratan Sistem
Sebelum memulai, pastikan perangkat Anda memenuhi persyaratan berikut:
*   PHP `>= 8.2`
*   Composer
*   Database Engine (MySQL / MariaDB / PostgreSQL / SQLite)

---

## 🚀 Panduan Setup & Instalasi

Ikuti langkah-langkah di bawah ini untuk menjalankan aplikasi secara lokal:

1.  **Clone Repository**
    ```bash
    git clone https://gitlab.com/test9753426/point-of-sales-api.git
    cd point-of-sales-api
    ```

2.  **Instalasi Dependensi**
    ```bash
    composer install
    ```

3.  **Salin File Environment**
    ```bash
    cp .env.example .env
    ```

4.  **Konfigurasi Database**
    Buka file `.env` yang baru saja disalin dan sesuaikan konfigurasi koneksi database Anda:
    ```env
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=pos_api
    DB_USERNAME=root
    DB_PASSWORD=
    ```

5.  **Generate Application Key**
    ```bash
    php artisan key:generate
    ```

6.  **Jalankan Migrasi Database**
    ```bash
    php artisan migrate
    ```

7.  **Jalankan Server Lokal**
    ```bash
    php artisan serve
    ```
    Aplikasi kini dapat diakses di `http://127.0.0.1:8000`.

---

## 🧪 Menjalankan Unit & Feature Test

Aplikasi ini dilengkapi dengan pengujian menyeluruh (*automated testing*) menggunakan PHPUnit/Pest. Untuk menjalankan seluruh pengujian:

```bash
php artisan test
```

---

## 📖 Dokumentasi API

Seluruh dokumentasi API POS ini didokumentasikan secara otomatis menggunakan **Scramble**.

Ketika server lokal Anda berjalan (`php artisan serve`), Anda dapat langsung mengakses dokumentasi interaktif (OpenAPI/Swagger UI) dengan membuka tautan ini pada browser:
👉 **`http://127.0.0.1:8000/`** (Root URL)

### Ringkasan Endpoint

#### 1. Produk (`/api/products`)
*   `GET /api/products` - Mendapatkan daftar semua produk (Paginasi tersedia).
*   `POST /api/products` - Menambahkan produk baru.
*   `GET /api/products/{id}` - Menampilkan detail produk tertentu.
*   `PUT /api/products/{id}` - Memperbarui informasi produk.
*   `DELETE /api/products/{id}` - Menghapus produk secara halus (*Soft Delete*).

#### 2. Transaksi (`/api/transactions`)
*   `GET /api/transactions` - Mendapatkan riwayat daftar transaksi (Paginasi tersedia).
*   `POST /api/transactions` - Melakukan checkout pembelian beberapa produk sekaligus.
*   `GET /api/transactions/{id}` - Menampilkan detail transaksi beserta rincian item produk yang dibeli.

---

## 📐 Keputusan Teknis & Arsitektur

Aplikasi ini dirancang dengan mematuhi standar pengembangan modern Laravel dan berfokus pada keandalan data (*data integrity*). Berikut adalah beberapa keputusan teknis utama yang diambil:

### 1. Pola Desain: Repository Pattern & Service Layer
Untuk memisahkan tanggung jawab kode (*Separation of Concerns*), kami tidak menulis logika bisnis langsung di Controller atau Model.
*   **Repository Layer (`app/Repositories`)**: Bertanggung jawab penuh atas akses dan query ke database. Membungkus Eloquent ORM sehingga memudahkan jika suatu saat kita ingin mengganti penyimpanan data tanpa merusak logika bisnis.
*   **Service Layer (`app/Services`)**: Menampung logika bisnis utama (misalnya: memproses detail checkout, validasi stok, menghitung harga total transaksi).
*   **Controller Layer (`app/Http/Controllers`)**: Hanya berperan menerima HTTP Request, memvalidasi input via *Form Request*, memanggil Service, dan mengembalikan JSON Response via *Resource*.

### 2. Integritas Data Transaksi (Atomic Operations)
Pada proses checkout, terjadi pembaruan data di beberapa tabel sekaligus (mengurangi stok produk, mencatat transaksi, menyimpan rincian item transaksi). 
*   Kami membungkus proses ini dalam **Database Transaction (`DB::transaction`)**. 
*   Apabila salah satu proses gagal (misalnya terjadi kesalahan sistem di tengah jalan atau stok tidak mencukupi untuk salah satu item), maka seluruh operasi yang sempat berjalan akan dibatalkan otomatis (*atomic rollback*), memastikan database tidak berakhir dalam kondisi tidak konsisten (*partial save*).

### 3. Pencegahan Race Condition dengan Pessimistic Locking
Pada sistem Point of Sales, stok produk sering kali diperebutkan oleh banyak transaksi bersamaan (*concurrent checkout*).
*   Kami mengimplementasikan **Pessimistic Locking (`lockForUpdate()`)** pada repositori produk ketika melakukan pengecekan stok saat checkout.
*   Hal ini memastikan baris database produk tersebut "dikunci" untuk sementara hingga transaksi checkout selesai, mencegah terjadinya kesalahan penghitungan stok (*race condition*).

### 4. Custom Exception & Response Consistency
Semua respons JSON API dirancang seragam melalui Trait `ApiResponse`. Format sukses:
```json
{
  "success": true,
  "message": "Action successful",
  "data": { ... }
}
```
Ketika terjadi kegagalan seperti stok tidak mencukupi, aplikasi melempar exception khusus `InsufficientStockException` yang ditangkap secara global di `bootstrap/app.php` dan dikembalikan dalam bentuk respons JSON terstruktur dengan HTTP Code `422 Unprocessable Content`.

### 5. Struktur Respons Paginasi yang Lebih Datar (*Flat Structure*)
Secara default, Laravel API Resources membungkus data terpaginasi di dalam objek nested `data.data`. Untuk meningkatkan kenyamanan integrasi di sisi klien (*frontend/mobile*), kami melakukan penyesuaian pada `ApiResponse` trait:
*   Struktur paginasi di-*flatten* (diratakan) sehingga array data produk atau transaksi langsung berada pada *key* utama `data`.
*   Meta informasi paginasi (`meta`) dan tautan navigasi (`links`) diletakkan sejajar pada root respons JSON bersama `success` dan `message`.

Contoh format respons paginasi:
```json
{
  "success": true,
  "message": "Success",
  "data": [
    { "id": 1, "name": "Kopi Susu", ... }
  ],
  "links": { "first": "...", "last": "...", ... },
  "meta": { "current_page": 1, "from": 1, "last_page": 1, ... }
}
```

