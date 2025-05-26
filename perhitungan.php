<?php
require_once('includes/init.php');

$page = "Perhitungan";
require_once('template/header.php');

mysqli_query($koneksi, "TRUNCATE TABLE hasil;");

$kriterias = array();
$q1 = mysqli_query($koneksi, "SELECT * FROM kriteria ORDER BY kode_kriteria ASC");
while ($krit = mysqli_fetch_array($q1)) {
	$kriterias[$krit['id_kriteria']]['id_kriteria'] = $krit['id_kriteria'];
	$kriterias[$krit['id_kriteria']]['kode_kriteria'] = $krit['kode_kriteria'];
	$kriterias[$krit['id_kriteria']]['nama_kriteria'] = $krit['nama_kriteria'];
	$kriterias[$krit['id_kriteria']]['type'] = $krit['type'];
	$kriterias[$krit['id_kriteria']]['bobot'] = $krit['bobot'];
	$kriterias[$krit['id_kriteria']]['ada_pilihan'] = $krit['ada_pilihan'];
}

$alternatifs = array();
$q2 = mysqli_query($koneksi, "SELECT * FROM siswa");
while ($alt = mysqli_fetch_array($q2)) {
	$alternatifs[$alt['id_siswa']]['id_siswa'] = $alt['id_siswa'];
	$alternatifs[$alt['id_siswa']]['nama_siswa'] = $alt['nama_siswa'];
}
$jumlah_alternatif = count($alternatifs);

if ($jumlah_alternatif == 0) {
	echo "<script>
        alert('Data alternatif tidak ditemukan. Anda akan diarahkan ke dashboard.');
        window.location.href = '/dashboard.php'; // Ganti dengan URL dashboard kamu
    </script>";
	exit();
}

$matriks_x = array();
foreach ($kriterias as $kriteria) :
	foreach ($alternatifs as $alternatif) :

		$id_siswa = $alternatif['id_siswa'];
		$id_kriteria = $kriteria['id_kriteria'];

		if ($kriteria['ada_pilihan'] == 1) {
			$q4 = mysqli_query($koneksi, "SELECT sub_kriteria.nilai_sub_kriteria FROM penilaian 
			JOIN sub_kriteria WHERE penilaian.nilai_penilaian=sub_kriteria.id_sub_kriteria AND penilaian.id_siswa='$alternatif[id_siswa]' 
			AND penilaian.id_kriteria='$kriteria[id_kriteria]'");
			$data = mysqli_fetch_array($q4);
			$nilai = $data['nilai_sub_kriteria'] ?? false;
		} else {
			$q4 = mysqli_query($koneksi, "SELECT nilai_penilaian FROM penilaian WHERE id_siswa='$alternatif[id_siswa]' 
			AND id_kriteria='$kriteria[id_kriteria]'");
			$data = mysqli_fetch_array($q4);
			$nilai = $data['nilai_penilaian'] ?? false;
		}
		if (!$nilai) {
			echo "<script>
				alert('Terdapat Alternatif yang belum dinilai.');
				window.location.href = 'list-penilaian.php'; // Ganti dengan URL dashboard kamu
			</script>";
			exit();
		}

		$matriks_x[$id_kriteria][$id_siswa] = $nilai;
	endforeach;
endforeach;

//Data awal entropy
$datan1 = array();
$data_max_xij = [];
$data_min_xij = [];
foreach ($alternatifs as $k => $v) {
	foreach ($kriterias as $k1 => $v1) {
		$id_siswa = $v['id_siswa'];
		$id_kriteria = $v1['id_kriteria'];
		$datan1[$k][$k1] = $matriks_x[$id_kriteria][$id_siswa];
		$nilai = $datan1[$k][$k1];
		$data_max_xij[$k1] = isset($data_max_xij[$k1])
			? max($data_max_xij[$k1], $nilai)
			: $nilai;
		$data_min_xij[$k1] = isset($data_min_xij[$k1])
			? min($data_min_xij[$k1], $nilai)
			: $nilai;
	}
}

//Normalisasi data awal
$datan2 = array();
$datan2sum = array();
foreach ($alternatifs as $k => $v) {
	foreach ($kriterias as $k1 => $v1) {
		$hasil = $datan1[$k][$k1] / $data_max_xij[$k1];
		$datan2[$k][$k1] = round($hasil, 2);
		if (!isset($datan2sum[$k1])) {
			$datan2sum[$k1] = 0;
		}
		$datan2sum[$k1] += $datan2[$k][$k1];
	}
}

//Nilai matriks Aij
$datan3 = array();
foreach ($alternatifs as $k => $v) {
	foreach ($kriterias as $k1 => $v1) {
		$hasil = $datan2[$k][$k1] / $datan2sum[$k1];
		$datan3[$k][$k1] = round($hasil, 2);
	}
}

//Nilai entropy tiap kriteria
$datan4 = array();
$datan4sum = array();
foreach ($alternatifs as $k => $v) {
	foreach ($kriterias as $k1 => $v1) {
		$hasil = $datan3[$k][$k1] * log($datan3[$k][$k1]);
		$datan4[$k][$k1] = round($hasil, 6);
		if (!isset($datan4sum[$k1])) {
			$datan4sum[$k1] = 0;
		}
		$datan4sum[$k1] += $datan4[$k][$k1];
	}
}

//Mencari Nilai Ej dan Dispersi Tiap Kriteria
$datan5 = array();
$datan6 = array();
$datan6sum = 0;
foreach ($kriterias as $k1 => $v1) {
	$datan5[$k1] = round((-1 / log($jumlah_alternatif)) * $datan4sum[$k1], 6);
	$datan6[$k1] = round(1 - $datan5[$k1], 6);
	$datan6sum += $datan6[$k1];
}

//Normalisasi Dispersi
$datan7 = array();
foreach ($kriterias as $k1 => $v1) {
	$datan7[$k1] = round($datan6[$k1] / $datan6sum, 6);
}

//Normalisasi matriks keputusan awal
$datan8 = array();
foreach ($alternatifs as $k => $v) {
	foreach ($kriterias as $k1 => $v1) {
		$datan8[$k][$k1] =  round(($datan1[$k][$k1] - $data_min_xij[$k1]) / ($data_max_xij[$k1] - $data_min_xij[$k1]), 2);
	}
}

//Menghitung nilai bobot
$datan9 = array();
foreach ($alternatifs as $k => $v) {
	foreach ($kriterias as $k1 => $v1) {
		$datan9[$k][$k1] =  $datan7[$k1] * ($datan8[$k][$k1] + 1);
	}
}

//Aproksimasi perbatasan (G)
$datan10 = array();
foreach ($alternatifs as $k => $v) {
	foreach ($kriterias as $k1 => $v1) {
		if (!isset($datan10[$k1])) {
			$datan10[$k1] = 1;
		}
		$datan10[$k1] *= $datan9[$k][$k1];
	}
}
foreach ($kriterias as $k1 => $v1) {
	$datan10[$k1] = round(pow($datan10[$k1], (1 / $jumlah_alternatif)), 6);
}

//Menghitung jarak alternatif
$datan11 = array();
$datan12 = array();
foreach ($alternatifs as $k => $v) {
	$datan12[$k]['id_siswa'] = $v['id_siswa'];
	$datan12[$k]['nama_siswa'] = $v['nama_siswa'];
	foreach ($kriterias as $k1 => $v1) {
		$datan11[$k][$k1] = $datan9[$k][$k1] - $datan10[$k1];
		if (!isset($datan12[$k]['nilai'])) {
			$datan12[$k]['nilai'] = 0;
		}
		$datan12[$k]['nilai'] += $datan11[$k][$k1];
	}
}


?>
<div class="card shadow mb-4">
	<!-- /.card-header -->
	<div class="card-header py-3">
		<h6 class="m-0 font-weight-bold text-success"><i class="fa fa-table"></i> Data Awal Entropy</h6>
	</div>

	<div class="card-body">
		<div class="table-responsive">
			<table class="table table-bordered" width="100%" cellspacing="0">
				<thead class="bg-success text-white">
					<tr align="center">
						<th width="5%" rowspan="2">No</th>
						<th>Nama Siswa</th>
						<?php foreach ($kriterias as $kriteria) : ?>
							<th><?= $kriteria['kode_kriteria'] ?></th>
						<?php endforeach ?>
					</tr>
				</thead>
				<tbody>
					<?php
					$no = 1;
					foreach ($alternatifs as $alternatif) : ?>
						<tr align="center">
							<td><?= $no; ?></td>
							<td align="left"><?= $alternatif['nama_siswa'] ?></td>
							<?php
							foreach ($kriterias as $kriteria) :
								$id_siswa = $alternatif['id_siswa'];
								$id_kriteria = $kriteria['id_kriteria'];
								echo '<td>';
								echo $matriks_x[$id_kriteria][$id_siswa];
								echo '</td>';
							endforeach
							?>
						</tr>
					<?php
						$no++;
					endforeach
					?>
					<tr align="center">
						<td></td>
						<td align="left">Max Xij</td>
						<?php foreach ($data_max_xij as $k => $v) {
							echo  '<td>' . $v . '</td>';
						} ?>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
</div>
<div class="card shadow mb-4">
	<!-- /.card-header -->
	<div class="card-header py-3">
		<h6 class="m-0 font-weight-bold text-success"><i class="fa fa-table"></i>Normalisasi Data Awal</h6>
	</div>

	<div class="card-body">
		<div class="table-responsive">
			<table class="table table-bordered" width="100%" cellspacing="0">
				<thead class="bg-success text-white">
					<tr align="center">
						<th width="5%" rowspan="2">No</th>
						<th>Nama Siswa</th>
						<?php foreach ($kriterias as $kriteria) : ?>
							<th><?= $kriteria['kode_kriteria'] ?></th>
						<?php endforeach ?>
					</tr>
				</thead>
				<tbody>
					<?php
					$no = 1;
					foreach ($alternatifs as $k => $v) : ?>
						<tr align="center">
							<td><?= $no; ?></td>
							<td align="left"><?= $v['nama_siswa'] ?></td>
							<?php
							foreach ($kriterias as $k1 => $v1) :
								echo '<td>';
								echo $datan2[$k][$k1];
								echo '</td>';
							endforeach
							?>
						</tr>
					<?php
						$no++;
					endforeach
					?>
					<tr align="center">
						<td></td>
						<td align="left">TOTAL</td>
						<?php foreach ($datan2sum as $k => $v) {
							echo  '<td>' . $v . '</td>';
						} ?>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
</div>
<div class="card shadow mb-4">
	<!-- /.card-header -->
	<div class="card-header py-3">
		<h6 class="m-0 font-weight-bold text-success"><i class="fa fa-table"></i>Nilai Matriks Aij</h6>
	</div>

	<div class="card-body">
		<div class="table-responsive">
			<table class="table table-bordered" width="100%" cellspacing="0">
				<thead class="bg-success text-white">
					<tr align="center">
						<th width="5%" rowspan="2">No</th>
						<th>Nama Siswa</th>
						<?php foreach ($kriterias as $kriteria) : ?>
							<th><?= $kriteria['kode_kriteria'] ?></th>
						<?php endforeach ?>
					</tr>
				</thead>
				<tbody>
					<?php
					$no = 1;
					foreach ($alternatifs as $k => $v) : ?>
						<tr align="center">
							<td><?= $no; ?></td>
							<td align="left"><?= $v['nama_siswa'] ?></td>
							<?php
							foreach ($kriterias as $k1 => $v1) :
								echo '<td>';
								echo $datan3[$k][$k1];
								echo '</td>';
							endforeach
							?>
						</tr>
					<?php
						$no++;
					endforeach
					?>
				</tbody>
			</table>
		</div>
	</div>
</div>
<div class="card shadow mb-4">
	<!-- /.card-header -->
	<div class="card-header py-3">
		<h6 class="m-0 font-weight-bold text-success"><i class="fa fa-table"></i>Nilai Entropy Tiap Kriteria (Ej)</h6>
	</div>

	<div class="card-body">
		<div class="table-responsive">
			<table class="table table-bordered" width="100%" cellspacing="0">
				<thead class="bg-success text-white">
					<tr align="center">
						<th width="5%" rowspan="2">No</th>
						<th>Nama Siswa</th>
						<?php foreach ($kriterias as $kriteria) : ?>
							<th><?= $kriteria['kode_kriteria'] ?></th>
						<?php endforeach ?>
					</tr>
				</thead>
				<tbody>
					<?php
					$no = 1;
					foreach ($alternatifs as $k => $v) : ?>
						<tr align="center">
							<td><?= $no; ?></td>
							<td align="left"><?= $v['nama_siswa'] ?></td>
							<?php
							foreach ($kriterias as $k1 => $v1) :
								echo '<td>';
								echo $datan4[$k][$k1];
								echo '</td>';
							endforeach
							?>
						</tr>
					<?php
						$no++;
					endforeach
					?>
					<tr align="center">
						<td></td>
						<td align="left">TOTAL</td>
						<?php foreach ($datan4sum as $k => $v) {
							echo  '<td>' . $v . '</td>';
						} ?>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
</div>
<div class="row mb-4">
	<div class="col-md-4">
		<div class="card shadow">
			<div class="card-body">
				<div class="table-responsive">
					<table class="table table-bordered" width="100%" cellspacing="0">
						<thead class="bg-success text-white">
							<tr align="center">
								<th colspan="2">Mencari Ej</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($datan5 as $k => $v) : ?>
								<tr align="center">
									<td>E<?= $k ?></td>
									<td><?= $v ?></td>
								</tr>
							<?php endforeach ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
	<div class="col-md-4">
		<div class="card shadow">
			<div class="card-body">
				<div class="table-responsive">
					<table class="table table-bordered" width="100%" cellspacing="0">
						<thead class="bg-success text-white">
							<tr align="center">
								<th colspan="2">Dispersi Tiap Kriteria</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($datan6 as $k => $v) : ?>
								<tr align="center">
									<td>D<?= $k ?></td>
									<td><?= $v ?></td>
								</tr>
							<?php endforeach ?>
							<tr align="center">
								<td>TOTAL</td>
								<td><?= $datan6sum ?></td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>

	<div class="col-md-4">
		<div class="card shadow">
			<div class="card-body">
				<div class="table-responsive">
					<table class="table table-bordered" width="100%" cellspacing="0">
						<thead class="bg-success text-white">
							<tr align="center">
								<th colspan="2">Normalisasi Dispersi</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($datan7 as $k => $v) : ?>
								<tr align="center">
									<td>W<?= $k ?></td>
									<td><?= $v ?></td>
								</tr>
							<?php endforeach ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>
<div class="card shadow mb-4">
	<!-- /.card-header -->
	<div class="card-header py-3">
		<h6 class="m-0 font-weight-bold text-success"><i class="fa fa-table"></i>Data Nilai Bobot Entropy</h6>
	</div>

	<div class="card-body">
		<div class="table-responsive">
			<table class="table table-bordered" width="100%" cellspacing="0">
				<thead class="bg-success text-white">
					<tr align="center">
						<th>Bobot</th>
						<th>Jenis</th>
						<th>Nama Kriteria</th>
						<th>Kode Kriteria</th>
					</tr>
				</thead>
				<tbody>
					<?php $k = 0;
					foreach ($kriterias as $k => $v) : ?>
						<tr align="center">
							<td><?= $datan7[$k] ?></td>
							<td><?= $v['type'] ?></td>
							<td><?= $v['nama_kriteria'] ?></td>
							<td><?= $v['kode_kriteria'] ?></td>
						</tr>
					<?php endforeach ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
<div class="card shadow mb-4">
	<!-- /.card-header -->
	<div class="card-header py-3">
		<h6 class="m-0 font-weight-bold text-success"><i class="fa fa-table"></i> Data Awal Mabac</h6>
	</div>

	<div class="card-body">
		<div class="table-responsive">
			<table class="table table-bordered" width="100%" cellspacing="0">
				<thead class="bg-success text-white">
					<tr align="center">
						<th width="5%" rowspan="2">No</th>
						<th>Nama Siswa</th>
						<?php foreach ($kriterias as $kriteria) : ?>
							<th><?= $kriteria['kode_kriteria'] ?></th>
						<?php endforeach ?>
					</tr>
				</thead>
				<tbody>
					<?php
					$no = 1;
					foreach ($alternatifs as $alternatif) : ?>
						<tr align="center">
							<td><?= $no; ?></td>
							<td align="left"><?= $alternatif['nama_siswa'] ?></td>
							<?php
							foreach ($kriterias as $kriteria) :
								$id_siswa = $alternatif['id_siswa'];
								$id_kriteria = $kriteria['id_kriteria'];
								echo '<td>';
								echo $matriks_x[$id_kriteria][$id_siswa];
								echo '</td>';
							endforeach
							?>
						</tr>
					<?php
						$no++;
					endforeach
					?>
					<tr align="center">
						<td></td>
						<td align="left">Max Xij</td>
						<?php foreach ($data_max_xij as $k => $v) {
							echo  '<td>' . $v . '</td>';
						} ?>
					</tr>
					<tr align="center">
						<td></td>
						<td align="left">Min Xij</td>
						<?php foreach ($data_min_xij as $k => $v) {
							echo  '<td>' . $v . '</td>';
						} ?>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
</div>
<div class="card shadow mb-4">
	<!-- /.card-header -->
	<div class="card-header py-3">
		<h6 class="m-0 font-weight-bold text-success"><i class="fa fa-table"></i>Normalisasi Matriks Keputusan Awal</h6>
	</div>

	<div class="card-body">
		<div class="table-responsive">
			<table class="table table-bordered" width="100%" cellspacing="0">
				<thead class="bg-success text-white">
					<tr align="center">
						<th width="5%" rowspan="2">No</th>
						<th>Nama Siswa</th>
						<?php foreach ($kriterias as $kriteria) : ?>
							<th><?= $kriteria['kode_kriteria'] ?></th>
						<?php endforeach ?>
					</tr>
				</thead>
				<tbody>
					<?php
					$no = 1;
					foreach ($alternatifs as $k => $v) : ?>
						<tr align="center">
							<td><?= $no; ?></td>
							<td align="left"><?= $v['nama_siswa'] ?></td>
							<?php
							foreach ($kriterias as $k1 => $v1) :
								echo '<td>';
								echo $datan8[$k][$k1];
								echo '</td>';
							endforeach
							?>
						</tr>
					<?php
						$no++;
					endforeach
					?>
				</tbody>
			</table>
		</div>
	</div>
</div>
<div class="card shadow mb-4">
	<!-- /.card-header -->
	<div class="card-header py-3">
		<h6 class="m-0 font-weight-bold text-success"><i class="fa fa-table"></i>Menghitung Nilai Bobot</h6>
	</div>

	<div class="card-body">
		<div class="table-responsive">
			<table class="table table-bordered" width="100%" cellspacing="0">
				<thead class="bg-success text-white">
					<tr align="center">
						<th width="5%" rowspan="2">No</th>
						<th>Nama Siswa</th>
						<?php foreach ($kriterias as $kriteria) : ?>
							<th><?= $kriteria['kode_kriteria'] ?></th>
						<?php endforeach ?>
					</tr>
				</thead>
				<tbody>
					<?php
					$no = 1;
					foreach ($alternatifs as $k => $v) : ?>
						<tr align="center">
							<td><?= $no; ?></td>
							<td align="left"><?= $v['nama_siswa'] ?></td>
							<?php
							foreach ($kriterias as $k1 => $v1) :
								echo '<td>';
								echo $datan9[$k][$k1];
								echo '</td>';
							endforeach
							?>
						</tr>
					<?php
						$no++;
					endforeach
					?>
				</tbody>
			</table>
		</div>
	</div>
</div>
<div class="card shadow mb-4">
	<!-- /.card-header -->
	<div class="card-header py-3">
		<h6 class="m-0 font-weight-bold text-success"><i class="fa fa-table"></i>Aproksimasi Perbatasan (G)</h6>
	</div>

	<div class="card-body">
		<div class="table-responsive">
			<table class="table table-bordered" width="100%" cellspacing="0">
				<thead class="bg-success text-white">
					<tr align="center">
						<th></th>
						<?php foreach ($kriterias as $k => $v) : ?>
							<th>G<?= $k ?></th>
						<?php endforeach ?>
					</tr>
				</thead>
				<tbody>
					<tr align="center">
						<td>G</td>
						<?php $k1 = 0;
						foreach ($kriterias as $k1 => $v1) : ?>
							<td><?= $datan10[$k1] ?></td>
						<?php endforeach ?>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
</div>
<div class="card shadow mb-4">
	<!-- /.card-header -->
	<div class="card-header py-3">
		<h6 class="m-0 font-weight-bold text-success"><i class="fa fa-table"></i>Menghitung Jarak Alternatif</h6>
	</div>

	<div class="card-body">
		<div class="table-responsive">
			<table class="table table-bordered" width="100%" cellspacing="0">
				<thead class="bg-success text-white">
					<tr align="center">
						<th width="5%" rowspan="2">No</th>
						<th>Nama Siswa</th>
						<?php foreach ($kriterias as $kriteria) : ?>
							<th><?= $kriteria['kode_kriteria'] ?></th>
						<?php endforeach ?>
					</tr>
				</thead>
				<tbody>
					<?php
					$no = 1;
					$k = 0;
					foreach ($alternatifs as $k => $v) : ?>
						<tr align="center">
							<td><?= $no; ?></td>
							<td align="left"><?= $v['nama_siswa'] ?></td>
							<?php
							$k1 = 0;
							foreach ($kriterias as $k1 => $v1) :
								echo '<td>';
								echo $datan11[$k][$k1];
								echo '</td>';
							endforeach
							?>
						</tr>
					<?php
						$no++;
					endforeach
					?>
				</tbody>
			</table>
		</div>
	</div>
</div>
<?php


mysqli_query($koneksi, "DELETE FROM hasil");
foreach ($datan12 as $key => $data) {
	$id_siswa = $data['id_siswa'];
	$nilai = $data['nilai'];
	$query = "INSERT INTO hasil (id_siswa, nilai_hasil)
              VALUES ('$id_siswa', '$nilai')";
	mysqli_query($koneksi, $query);
}

usort($datan12, function ($a, $b) {
	return $b['nilai'] <=> $a['nilai'];
});

mysqli_query($koneksi, "DELETE FROM hasil");
foreach ($datan12 as $key => $data) {
	$id_siswa = $data['id_siswa'];
	$nilai = $data['nilai'];
	$query = "INSERT INTO hasil (id_siswa, nilai_hasil)
              VALUES ('$id_siswa', '$nilai')";
	mysqli_query($koneksi, $query);
}
?>
<div class="card shadow">
	<div class="card-body">
		<div class="table-responsive">
			<table class="table table-bordered" width="100%" cellspacing="0">
				<thead class="bg-success text-white">
					<tr align="center">
						<th colspan="3">Peringkat Alternatif</th>
					</tr>
				</thead>
				<tbody>
					<?php
					$no = 1; 
					foreach ($datan12 as $k => $v) : ?>
						<tr align="center">
							<td><?= $no; ?></td>
							<td><?= $v['nama_siswa'] ?></td>
							<td><?= $v['nilai'] ?></td>
						</tr>
					<?php
						$no++;
					endforeach
					?>
				</tbody>
			</table>
		</div>
	</div>
</div>
<?php
require_once('template/footer.php');

?>