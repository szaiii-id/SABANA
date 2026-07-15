# SABANA (Sarana Bantuan Anak Banua)

SABANA merupakan Aplikasi E-Government yang dikhususkan untuk wilayah Kalimantan Selatan (Banua). Aplikasi ini dibangun untuk mewujudkan transparansi dalam penyaluran bantuan sosial kepada masyarakat, baik melalui transfer langsung maupun melalui kantor pemerintah terdekat.

Aplikasi ini hadir sebagai solusi tanggap atas banyaknya kasus bantuan sosial yang salah sasaran. Dengan mengintegrasikan sistem cerdas dan metode evaluasi terstruktur, proses verifikasi di dalam SABANA menjadi lebih akurat, transparan, dan tepat sasaran. Hal ini sangat krusial guna menghindari kesalahan penerima bantuan sosial, baik yang disengaja maupun tidak disengaja.

## Arsitektur Sistem

Sistem ini dirancang menggunakan pendekatan **RESTful API** dengan pemisahan portal secara tegas antara portal manajemen untuk Admin dan portal pelayanan untuk Warga. 

### Struktur Backend (PHP & Laravel)
Backend mengadopsi pola desain berlapis untuk menjaga kode tetap bersih, dapat diuji, dan mudah dikembangkan:
* **Migration & Model:** Representasi struktur basis data relasional dan operasi pemetaan objek.
* **Repository Interface & Repository:** Lapisan abstraksi data untuk memisahkan logika kueri basis data dari logika bisnis utama.
* **Service:** Menangani seluruh inti logika bisnis aplikasi, termasuk kalkulasi SPK SMART dan komunikasi ke *engine* AI.
* **Request:** Lapisan khusus untuk memvalidasi dan mengamankan data yang masuk (*input validation*).
* **Resource:** Mengonversi objek dan model data menjadi representasi JSON terstandarisasi untuk *response* API.
* **Controller:** Menerima permintaan HTTP dari klien, mengarahkannya ke *Service*, dan mengembalikan *Resource* sebagai respon.

### Struktur Frontend (Vue.js & TypeScript)
Struktur frontend dibagi secara modular untuk memaksimalkan penggunaan ulang kode (*reusability*) dan kemudahan pemeliharaan:
* **Types:** Definisi struktur data statis menggunakan TypeScript untuk memastikan integritas dan keamanan data.
* **API & Service:** Konfigurasi *endpoint* dan modul pengelola komunikasi HTTP dengan Backend RESTful API.
* **Pages:** Komponen tingkat tampilan (*view-level*) yang dipetakan ke dalam sistem *routing* aplikasi.
* **Composable:** Kumpulan fungsi logika bisnis reaktif (Vue Composition API) yang dapat digunakan berulang kali lintas komponen.
* **Component:** Potongan elemen antarmuka pengguna (UI) independen seperti tombol, tabel, atau formulir.

## FITUR APLIKASI SABANA

### 1. Keamanan & Akses (General)
* **Sistem Autentikasi Terpusat:** Mengamankan jalur masuk (login) ke dalam sistem.
* **User Management & Role-Based Access Control (RBAC):** Pembagian hak akses yang ketat antara Super Admin, Admin, dan Warga.
* **Sistem Keamanan Berbasis Token:** Menjaga sesi login dan pertukaran data antar endpoint tetap terlindungi.

### 2. Modul Admin
* **Manajemen Program Bantuan:** Membuat, mengedit, dan mengelola daftar program bansos yang sedang berjalan.
* **Sistem Verifikasi Berjenjang:** Admin memiliki otoritas penuh untuk meninjau pengajuan dan memberikan status Setujui, Revisi, atau Tolak.
* **Sistem Pendukung Keputusan (SPK) Metode SMART:** Terintegrasi dengan algoritma penghitungan skor SMART untuk memberikan rekomendasi kelayakan secara objektif kepada admin.
* **Verifikasi Cerdas (AI / Deep Learning):** Memanfaatkan teknologi OCR (Optical Character Recognition) dan NLP (Natural Language Processing) untuk mengekstraksi dan memverifikasi data dokumen warga secara otomatis.
* **Modul Penyaluran Bantuan:** Memproses eksekusi penyaluran bantuan kepada warga yang telah dinyatakan valid.
* **Evaluasi Berkala (6 Bulan):** Sistem peninjauan kembali kondisi warga setelah 6 bulan menerima bantuan untuk memastikan efektivitas program.
* **Sistem Logging (Catatan Aktivitas):** Seluruh aktivitas admin direkam dalam sistem log untuk kebutuhan transparansi dan audit pelacakan.
* **Pendaftaran Warga via Admin:** Memfasilitasi pendaftaran akun dan pengajuan bantuan bagi warga yang tidak memiliki perangkat keras (dilakukan oleh Admin).
* **Manajemen Akun Admin:** Pengelolaan data admin sistem. (Catatan: Akun Super Admin tidak dapat dibuat lewat UI, melainkan di-inject langsung melalui database tingkat server).
* **Profil & Keamanan Admin:** Fitur pembaruan data profil dan reset password.

### 3. Modul Warga
* **Katalog Program Aktif:** Menampilkan daftar program bantuan sosial yang saat ini sedang dibuka oleh pemerintah.
* **Pengajuan Bantuan Mandiri:** Warga dapat mendaftar dan mengisi formulir pengajuan langsung dari akun masing-masing.
* **Riwayat & Pelacakan (History):** Fitur untuk melacak sudah sampai tahap mana proses pengajuan yang dikirimkan.
* **Cetak Dokumen Digital:** Fitur untuk mengunduh dan mencetak Surat Registrasi serta Bukti Penyaluran Bantuan secara resmi.
* **Lapor & Aspirasi:** Kanal komunikasi dan pengaduan dua arah yang terintegrasi langsung dengan WhatsApp (WA) dan Email.
* **Profil & Reset PIN:** Mengelola informasi pribadi warga dan pembaruan PIN keamanan.


## Stack Teknologi

Aplikasi SABANA dibangun menggunakan ekosistem teknologi modern yang terisolasi dan tangguh untuk memastikan performa tinggi, keamanan maksimal, dan skalabilitas jangka panjang. 

### Backend & Server
* **PHP & Laravel:** Framework utama backend untuk pengelolaan logika bisnis, sistem routing, dan API.
* **FrankenPHP:** Web server modern berkinerja sangat tinggi (high-performance) khusus untuk aplikasi PHP.

### Frontend
* **Vue.js & TypeScript (TS):** Framework antarmuka pengguna yang reaktif, dipadukan dengan TypeScript untuk keamanan pengetikan kode (static typing) dan meminimalisir bug.
* **Nginx:** Web server tangguh yang dikonfigurasi khusus untuk menyajikan (serve) file statis frontend dengan cepat kepada pengguna.

### Artificial Intelligence (AI) & Engine
* **Python:** Lingkungan utama untuk menjalankan model Deep Learning.
* **OCR (Optical Character Recognition):** Teknologi ekstraksi teks cerdas untuk membaca dokumen warga (KTP, Kartu Keluarga, atau dokumen pendukung lainnya) secara otomatis.
* **NLP (Natural Language Processing):** Pemrosesan bahasa alami yang bertugas memvalidasi, mengklasifikasi, dan mencocokkan kesesuaian data teks pada dokumen pengajuan.

### Database, Search & Caching
* **PostgreSQL:** Sistem manajemen basis data relasional (RDBMS) utama yang sangat tangguh untuk integritas data.
* **Elasticsearch:** Mesin analitik dan pencarian terdistribusi untuk mengeksekusi kueri pencarian data warga atau program bansos berskala besar dalam hitungan milidetik.
* **Redis:** Sistem penyimpanan in-memory yang digunakan untuk caching data yang sering diakses dan manajemen antrean (job queue).

### Infrastruktur & Deployment
* **Docker:** Teknologi containerization untuk membungkus seluruh layanan aplikasi (dari database hingga AI) dalam lingkungan yang terisolasi, memastikan sistem berjalan konsisten di lingkungan lokal (development) maupun server produksi (production).

### Integrasi Pihak Ketiga (Third-Party Services)
* **Fonnte (WhatsApp Gateway):** Terintegrasi langsung untuk mengirimkan notifikasi status pengajuan dan menerima pesan pelaporan atau aspirasi warga melalui WhatsApp.
* **Brevo (Email Gateway):** Layanan pengiriman email transaksional untuk mengirimkan tanda terima, pembaruan status resmi, dan tautan reset password.
