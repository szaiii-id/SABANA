<h2>Laporan Aspirasi Masuk</h2>
<p>Halo Admin SABANA, ada pesan baru dari warga dengan detail berikut</p>

<table style="width: 100%; border-collapse: collapse;">
    <tr>
        <td style="width: 150px; font-weight: bold;">Nama Pengirim</td>
        <td>{{ $data['nama'] }}</td>
    </tr>
    <tr>
        <td style="font-weight: bold;">Email</td>
        <td>{{ $data['email'] }}</td>
    </tr>
    <tr>
        <td style="font-weight: bold;">Kategori</td>
        <td>{{ $data['subjek'] }}</td>
    </tr>
    <tr>
        <td style="font-weight: bold; vertical-align: top;">Isi Aspirasi</td>
        <td>{{ $data['pesan'] }}</td>
    </tr>
</table>

<p>Mohon segera ditindaklanjuti melalui sistem administrasi.</p>