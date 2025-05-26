<?php
require_once('includes/init.php');

$user_role = get_role();
if ($user_role == 'admin' || $user_role == 'user') {
?>

	<html>

	<head>
		<title>Sistem Pendukung Keputusan Metode Mabac dan Entropy</title>
	</head>

	<body onload="window.print();">

		<div style="width:100%;margin:0 auto;text-align:center;"> <br>
			<table>
				<tr>
					<td width="10%">
						<img style="border-radius:50%; margin-top: 10px; width: 140px; height: 140px" src="images/smk.jpg" alt="logo">
					</td>
					<td style="text-align: center; font-size:20pt">YAYASAN MA'HADUL ISLAM AL BASHRIYYAH<br>
						<span style="font-size:24pt"><b>SMK SULTAN AGUNG</span></b> <br>
						<span style="font-size: 12pt"> Program Keahlian : Manajemen Perkantoran </span> <br>
						<span style="font-size: 12pt"> SK.NO.421/395-Disdik. NSS : 402020220233 NPSN : 69754542</span><br>
						<span style="font-size: 12pt">Jl. Raya Dago Km. 08 Desa Dago Kecamatan Parung Panjang Kab. Bogor Jawa Barat 16360</span>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<hr style="border: solid 2px #000">
					</td>
				</tr>
				<tr>
					<td colspan="2" style="text-align: center; font-weight: bold; font-size: 14pt">LAPORAN HASIL PERHITUNGAN PEMILIHAN SISWA BERPRESTASI</td>
				</tr>
				<tr>
					<td colspan="2">
			</table>
			<h3> </h3>
			<table width="100%" cellspacing="0" cellpadding="5" border="1">
				<thead>
					<tr align="center">
						<th>Nama Siswa</th>
						<th>Jurusan</th>
						<th>Kelas</th>
						<th>Nomor Kelas</th>
						<th>Nilai</th>
						<th width="15%">Rank</th>
					</tr>
				</thead>
				<tbody>
					<?php
					$no = 0;
					$query = mysqli_query($koneksi, "SELECT * FROM hasil JOIN siswa ON hasil.id_siswa=siswa.id_siswa ORDER BY hasil.nilai_hasil DESC");
					while ($data = mysqli_fetch_array($query)) {
						$no++;
					?>
						<tr align="center">
							<td align="left"><?= $data['nama_siswa'] ?></td>
							<td align="left"><?= $data['jurusan'] ?></td>
							<td align="left"><?= $data['kelas'] ?></td>
							<td align="left"><?= $data['nomor_kelas'] ?></td>
							<td><?= $data['nilai_hasil'] ?></td>
							<td><?= $no; ?></td>
						</tr>
					<?php
					}
					?>
				</tbody>
			</table>
			<tr>
				<td colspan="6">
					<br><br>
					<table width="100%">
						<tr>
							<td width="8%"></td>
							<td width="29%">
								<br>
								Mengetahui,
								<p> Wali Kelas.................</p>
								<br>
								<br><br><br>
								<u>..........................</u>
							</td>
							<td width="8%"></td>
							<td width="29%">
								<br>
								Bogor,..................
								<p> Kepala Sekolah SMK Sultan Agung</p>
								<br>
								<br><br><br>
								<u>Maya Ummi Azizatus Sa'adah, S.Pd., M.M.</u>
							</td>
						</tr>
					</table>

				</td>
			</tr>
		</div>

	</body>

	</html>

<?php
} else {
	header('Location: login.php');
}
?>