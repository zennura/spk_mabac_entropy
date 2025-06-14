<?php require_once('includes/init.php'); ?>


<?php
$page = "Siswa";
require_once('template/header.php');

?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
	<h1 class="h3 mb-0 text-gray-800"><i class="fas fa-fw fa-users"></i> Data Siswa</h1>

	<a href="tambah-alternatif.php" class="btn btn-success"> <i class="fa fa-plus"></i> Tambah Data Siswa </a>
</div>

<?php
$status = isset($_GET['status']) ? $_GET['status'] : '';
$msg = '';
switch ($status):
	case 'sukses-baru':
		$msg = 'Data berhasil disimpan';
		break;
	case 'sukses-hapus':
		$msg = 'Data behasil dihapus';
		break;
	case 'sukses-edit':
		$msg = 'Data behasil diupdate';
		break;
endswitch;

if ($msg):
	echo '<div class="alert alert-info">' . $msg . '</div>';
endif;
?>

<div class="card shadow mb-4">
	<!-- /.card-header -->
	<div class="card-header py-3">
		<h6 class="m-0 font-weight-bold text-success"><i class="fa fa-table"></i> Daftar Data Siswa</h6>
	</div>

	<div class="card-body">
		<div class="table-responsive">
			<table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
				<thead class="bg-success text-white">
					<tr align="center">
						<th width="5%">No</th>
						<th>Nama Siswa </th>
						<th>Jurusan</th>
						<th>Kelas</th>
						<th>Nomor Kelas</th?>
						<th width="15%">Aksi</th>
					</tr>
				</thead>
				<tbody>
					<?php
					// $no=0;
					// $query = mysqli_query($koneksi,"SELECT * FROM hasil JOIN siswa ON hasil.id_siswa=siswa.id_siswa ORDER BY hasil.nilai_hasil DESC");
					// while($data = mysqli_fetch_array($query)){
					// $no++;
					$no = 0;
					$query = "SELECT * FROM siswa";
					$querytipe = mysqli_query($koneksi, $query);
					while ($data = mysqli_fetch_array($querytipe)) {
						$no++;
					?>
						<tr align="center">
							<td><?php echo $no; ?></td>
							<td align="left"><?php echo $data['nama_siswa']; ?></td>
							<td align="center"><?php echo $data['jurusan']; ?></td>
							<td align="center"><?php echo $data['kelas']; ?></td>
							<td align="center"><?php echo $data['nomor_kelas']; ?></td>
							<td>
								<div class="btn-group" role="group">
									<a data-toggle="tooltip" data-placement="bottom" title="Edit Data" href="edit-alternatif.php?id=<?php echo $data['id_siswa']; ?>" class="btn btn-warning btn-sm"><i class="fa fa-edit"></i></a>
									<a data-toggle="tooltip" data-placement="bottom" title="Hapus Data" href="hapus-alternatif.php?id=<?php echo $data['id_siswa']; ?>" onclick="return confirm ('Apakah anda yakin untuk meghapus data ini')" class="btn btn-danger btn-sm"><i class="fa fa-trash"></i></a>
								</div>
							</td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
<footer class="footer">©Copyright 2024 SMK SULTAN AGUNG PARUNG PANJANG</footer>
<?php
require_once('template/footer.php');
?>