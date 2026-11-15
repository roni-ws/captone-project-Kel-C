<?= $this->extend('layouts/admin') ?>

<?= $this->section('title') ?>Data Sekolah Asal<?= $this->endSection() ?>
<?= $this->section('page-title') ?>Data Sekolah Asal<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$queryBase = array_filter([
    'q'        => $keyword !== '' ? $keyword : null,
    'sumber'   => $sumber !== '' ? $sumber : null,
    'per_page' => $perPage !== 25 ? $perPage : null,
], static fn ($value) => $value !== null);

$pageUrl = static fn (int $target): string =>
    base_url('admin/sekolah') . '?' . http_build_query(array_merge($queryBase, ['page' => $target]));

$totalPages = max(1, (int) ceil($total / $perPage));
$startPage  = max(1, $page - 2);
$endPage    = min($totalPages, $startPage + 4);
$startPage  = max(1, $endPage - 4);

?>

<div class="max-w-7xl mx-auto w-full">

    <!-- ================================
         HEADER
         ================================ -->
    <div class="mb-6">
        <h2 class="text-headline-md font-bold text-primary">Data Sekolah Asal</h2>
        <p class="text-body-sm text-on-surface-variant mt-1 max-w-3xl">
            Daftar sekolah SMP/MTs dari berbagai provinsi yang muncul sebagai saran pada formulir siswa.
        </p>
    </div>

    <?php if ($lastImport !== null): ?>
        <div class="mb-6 rounded-xl border border-secondary-container bg-secondary-container/30 px-4 py-3 text-body-sm text-on-surface">
            <span class="material-symbols-outlined text-[18px] align-middle mr-1">history</span>
            Impor terakhir: <strong><?= esc($lastImport['file'] ?? '-') ?></strong>
            pada <?= esc($lastImport['timestamp'] ?? '-') ?>
            — <?= number_format((int) ($lastImport['imported'] ?? 0), 0, ',', '.') ?> baru,
            <?= number_format((int) ($lastImport['updated'] ?? 0), 0, ',', '.') ?> diperbarui,
            <?= number_format((int) ($lastImport['skipped'] ?? 0), 0, ',', '.') ?> dilewati.
        </div>
    <?php endif; ?>

    <!-- ================================
         IMPOR BERKAS
         ================================ -->
    <div class="bg-surface border border-outline-variant rounded-2xl shadow-sm p-4 md:p-6 mb-6">
        <div class="flex items-start gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-primary-container text-on-primary-container flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined">upload_file</span>
            </div>
            <div>
                <h3 class="font-label-bold text-body-md text-on-surface">Impor Data Sekolah</h3>
                <p class="text-body-sm text-on-surface-variant mt-1">
                    Unggah template yang sudah diisi atau berkas resmi dari Data Induk Satuan Pendidikan
                    (.xlsx/.csv, maksimal <?= esc($maxUploadMb) ?> MB). Impor hanya menambah dan memperbarui
                    berdasarkan NPSN, tidak menghapus data.
                </p>
            </div>
        </div>

        <form id="sekolah-import-form" enctype="multipart/form-data" class="flex flex-col gap-3">
            <?= csrf_field() ?>
            <?php if (! $xlsxAvailable): ?>
                <div class="rounded-lg border border-tertiary-container bg-tertiary-container/20 px-3 py-2 text-body-sm text-on-surface">
                    <span class="material-symbols-outlined text-[18px] align-middle mr-1">info</span>
                    Ekstensi PHP <code>zip</code> belum aktif. Template diunduh sebagai CSV dan unggahan
                    <strong>.xlsx</strong> belum dapat dibaca. Gunakan berkas <strong>.csv</strong> dari Data Induk
                    atau aktifkan <code>extension=zip</code> pada php.ini.
                </div>
            <?php endif; ?>
            <label for="berkas" class="block text-body-sm font-label-bold text-on-surface">Berkas data sekolah</label>
            <div class="flex w-full items-stretch overflow-hidden rounded-lg border border-outline bg-surface">
                <label for="berkas"
                       class="flex cursor-pointer items-center gap-1 whitespace-nowrap bg-primary px-4 py-2 font-label-bold text-on-primary transition-colors hover:bg-primary/90">
                    <span class="material-symbols-outlined text-[18px]">folder_open</span>
                    Pilih Berkas
                </label>
                <span id="berkas-filename"
                      class="flex min-w-0 flex-1 items-center px-3 text-body-sm text-on-surface-variant truncate">
                    Belum ada berkas dipilih
                </span>
            </div>
            <input type="file" id="berkas" name="berkas" accept=".xlsx,.csv" class="sr-only">
            <div class="flex flex-wrap gap-2">
                <a href="<?= base_url('admin/sekolah/template') ?>"
                   class="flex items-center gap-1 border border-primary text-primary px-4 py-2 rounded-lg font-label-bold hover:bg-primary-container/30 transition-colors">
                    <span class="material-symbols-outlined text-[18px]">download</span>
                    Unduh Template
                </a>
                <button type="button" id="btn-import"
                        class="flex items-center gap-1 bg-primary text-on-primary px-4 py-2 rounded-lg font-label-bold hover:bg-primary/90 transition-colors">
                    <span class="material-symbols-outlined text-[18px]">upload</span>
                    Impor Sekarang
                </button>
            </div>
            <p class="text-caption text-on-surface-variant">
                Sumber data: <a href="<?= esc($portalUrl) ?>" target="_blank" rel="noopener noreferrer"
                   class="text-primary font-semibold hover:underline">Data Induk Satuan Pendidikan Kemendikdasmen</a>
                (SMP/MTs; wilayah mengikuti berkas Anda).
            </p>
        </form>

        <div id="import-status" class="hidden mt-4 rounded-xl border px-4 py-3 text-body-sm" role="status" aria-live="polite"></div>
    </div>

        <!-- ================================
         DAFTAR SEKOLAH
         ================================ -->
    <div class="bg-white rounded-2xl border border-outline-variant shadow-sm overflow-hidden">
        <form method="GET" action="<?= base_url('admin/sekolah') ?>" class="p-4 border-b border-outline-variant">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
                <div class="md:col-span-6 relative">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-xl">search</span>
                    <input type="search" name="q" value="<?= esc($keyword) ?>"
                           placeholder="Cari nama, NPSN, kecamatan, atau kabupaten..."
                           class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-outline-variant focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all text-body-sm outline-none bg-surface-container-lowest">
                </div>
                <div class="md:col-span-2">
                    <select name="sumber" aria-label="Filter sumber"
                            class="w-full px-3 py-2.5 rounded-xl border border-outline-variant focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all text-body-sm outline-none bg-surface-container-lowest">
                        <option value="">Semua sumber</option>
                        <option value="dapodik" <?= $sumber === 'dapodik' ? 'selected' : '' ?>>Data Induk</option>
                        <option value="admin" <?= $sumber === 'admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="manual" <?= $sumber === 'manual' ? 'selected' : '' ?>>Manual Siswa</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <select name="per_page" aria-label="Jumlah baris per halaman"
                            class="w-full px-3 py-2.5 rounded-xl border border-outline-variant focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all text-body-sm outline-none bg-surface-container-lowest">
                        <?php foreach ($perPageOptions as $option): ?>
                            <option value="<?= esc($option) ?>" <?= $perPage === $option ? 'selected' : '' ?>><?= esc($option) ?> baris</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <button type="submit"
                            class="w-full rounded-xl bg-primary text-on-primary px-4 py-2.5 font-semibold text-body-sm hover:opacity-90 transition-opacity shadow-sm">
                        Terapkan
                    </button>
                </div>
            </div>
        </form>

        <div id="bulk-toolbar-sekolah" class="hidden items-center justify-between gap-2 bg-primary-container/10 px-4 py-2 border-b border-primary-container/30">
            <span class="text-primary font-semibold text-body-sm">
                <span id="selected-sekolah-count">0</span> sekolah dipilih
            </span>
            <div class="flex gap-2 items-center">
                <button type="button" id="bulk-clear-sekolah-btn"
                        class="text-body-sm text-on-surface-variant hover:text-on-surface font-semibold px-2 py-1.5 transition-colors">
                    Batalkan
                </button>
                <button type="button" id="bulk-delete-sekolah-btn"
                        class="flex items-center gap-1 px-3 py-1.5 text-error font-semibold text-body-sm hover:bg-error-container/30 rounded-lg transition-colors">
                    <span class="material-symbols-outlined text-[18px]">delete_sweep</span> Hapus
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container-low border-b border-outline-variant">
                        <th class="p-3 w-10 text-center">
                            <input type="checkbox" id="select-all-sekolah" class="rounded border-outline-variant text-primary focus:ring-primary cursor-pointer" aria-label="Pilih semua sekolah di halaman ini">
                        </th>
                        <th class="p-3 font-semibold text-label-bold text-primary text-xs uppercase tracking-wider">Sekolah</th>
                        <th class="p-3 font-semibold text-label-bold text-primary text-xs uppercase tracking-wider">Wilayah</th>
                        <th class="p-3 font-semibold text-label-bold text-primary text-xs uppercase tracking-wider">Alamat</th>
                        <th class="p-3 font-semibold text-label-bold text-primary text-xs uppercase tracking-wider">Sumber</th>
                        <th class="p-3 font-semibold text-label-bold text-primary text-xs uppercase tracking-wider text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $avatarColors = [
                        'bg-primary-container text-on-primary-container',
                        'bg-secondary-container text-on-secondary-container',
                        'bg-tertiary-container text-on-tertiary-container',
                        'bg-surface-container-high text-primary',
                    ];
                    $sumberBadge = [
                        'dapodik' => ['badge-valid', 'verified', 'Data Induk'],
                        'admin'   => ['badge-pending', 'edit', 'Admin'],
                        'manual'  => ['badge-default', 'person', 'Manual Siswa'],
                    ];
                    ?>
                    <?php if ($rows === []): ?>
                        <tr>
                            <td colspan="6" class="p-8 text-center text-on-surface-variant">
                                Tidak ada data sekolah yang cocok dengan filter.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($rows as $row): ?>
                        <?php
                        $schoolName     = trim((string) ($row['nama_sekolah'] ?? '-'));
                        $initials       = mb_strtoupper(mb_substr((string) preg_replace('/\s+/u', '', $schoolName), 0, 2));
                        $initials       = $initials !== '' ? $initials : 'SK';
                        $avatar         = $avatarColors[abs((int) ($row['id'] ?? 0)) % count($avatarColors)];
                        $badge          = $sumberBadge[$row['sumber'] ?? ''] ?? ['badge-default', 'info', ucfirst((string) ($row['sumber'] ?? '-'))];
                        $npsn           = trim((string) ($row['npsn'] ?? ''));
                        $bentuk         = trim((string) ($row['bentuk_pendidikan'] ?? ''));
                        $subtitle       = trim(($npsn !== '' ? 'NPSN: ' . $npsn : '') . ($npsn !== '' && $bentuk !== '' ? ' · ' : '') . $bentuk);
                        ?>
                        <tr class="border-b border-outline-variant last:border-b-0 hover:bg-surface-container-low transition-colors">
                            <td class="p-3 text-center align-middle">
                                <input type="checkbox" class="row-select-sekolah rounded border-outline-variant text-primary focus:ring-primary cursor-pointer"
                                       value="<?= (int) $row['id'] ?>"
                                       data-usage="<?= (int) ($row['usage_count'] ?? 0) ?>"
                                       aria-label="Pilih <?= esc($row['nama_sekolah'] ?? 'sekolah') ?>">
                            </td>
                            <td class="p-3 text-body-sm">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs shrink-0 <?= esc($avatar) ?>">
                                        <?= esc($initials) ?>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-body-sm text-on-surface"><?= esc($schoolName) ?></p>
                                        <?php if ($subtitle !== ''): ?>
                                            <p class="text-caption text-on-surface-variant"><?= esc($subtitle) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="p-3 text-body-sm">
                                <p class="font-medium text-on-surface"><?= esc($row['nama_kecamatan'] ?? '-') ?></p>
                                <p class="text-caption text-on-surface-variant"><?= esc($row['nama_kabupaten'] ?? '-') ?></p>
                                <?php if (!empty($row['nama_provinsi'])): ?>
                                    <p class="text-caption text-on-surface-variant"><?= esc($row['nama_provinsi']) ?></p>
                                <?php endif; ?>
                            </td>
                            <td class="p-3 text-body-sm max-w-xs">
                                <p class="text-body-sm text-on-surface"><?= esc($row['alamat_jalan'] !== null && $row['alamat_jalan'] !== '' ? $row['alamat_jalan'] : '-') ?></p>
                                <?php if (!empty($row['nama_desa'])): ?>
                                    <p class="text-caption text-on-surface-variant">Desa/Kel. <?= esc($row['nama_desa']) ?></p>
                                <?php endif; ?>
                            </td>
                            <td class="p-3 text-body-sm">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold whitespace-nowrap <?= esc($badge[0]) ?>">
                                    <span class="material-symbols-outlined" style="font-size:14px"><?= esc($badge[1]) ?></span>
                                    <?= esc($badge[2]) ?>
                                </span>
                            </td>
                            <td class="p-3 text-body-sm">
                                <div class="flex items-center justify-center gap-0">
                                    <button type="button"
                                            class="btn-edit-sekolah p-1 text-primary hover:bg-primary-container/20 rounded-full transition-colors"
                                            title="Edit data sekolah"
                                            data-sekolah="<?= esc(json_encode([
                                                'id'             => (int) $row['id'],
                                                'nama_sekolah'   => $row['nama_sekolah'],
                                                'npsn'           => $row['npsn'] ?? '',
                                                'nama_kecamatan' => $row['nama_kecamatan'] ?? '',
                                                'nama_kabupaten' => $row['nama_kabupaten'] ?? '',
                                                'nama_provinsi'  => $row['nama_provinsi'] ?? '',
                                                'nama_desa'      => $row['nama_desa'] ?? '',
                                                'alamat_jalan'   => $row['alamat_jalan'] ?? '',
                                            ], JSON_UNESCAPED_UNICODE), 'attr') ?>">
                                        <span class="material-symbols-outlined" style="font-size:16px">edit</span>
                                    </button>

                                    <button type="button"
                                            class="btn-delete-sekolah p-1 text-error hover:bg-error-container/20 rounded-full transition-colors"
                                            title="Hapus sekolah"
                                            data-sekolah="<?= esc(json_encode([
                                                'id'    => (int) $row['id'],
                                                'nama'  => $row['nama_sekolah'],
                                                'usage' => (int) ($row['usage_count'] ?? 0),
                                            ], JSON_UNESCAPED_UNICODE), 'attr') ?>">
                                        <span class="material-symbols-outlined" style="font-size:16px">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="flex flex-col sm:flex-row items-center justify-between gap-3 px-4 py-3 border-t border-outline-variant bg-white">
                <p class="text-caption text-on-surface-variant">
                    Menampilkan <?= number_format(($page - 1) * $perPage + 1, 0, ',', '.') ?>–<?= number_format(min($page * $perPage, $total), 0, ',', '.') ?>
                    dari <?= number_format($total, 0, ',', '.') ?> sekolah
                </p>
                <nav class="flex items-center gap-1" aria-label="Navigasi halaman">
                    <a href="<?= esc($pageUrl(max(1, $page - 1))) ?>"
                       class="px-3 py-1.5 rounded-xl border border-outline-variant text-body-sm <?= $page <= 1 ? 'pointer-events-none opacity-40' : 'hover:bg-surface-container-low' ?>">
                        Sebelumnya
                    </a>
                    <?php for ($p = $startPage; $p <= $endPage; $p++): ?>
                        <a href="<?= esc($pageUrl($p)) ?>"
                           class="px-3 py-1.5 rounded-xl border text-body-sm <?= $p === $page ? 'bg-primary text-on-primary border-primary' : 'border-outline-variant hover:bg-surface-container-low' ?>">
                            <?= esc($p) ?>
                        </a>
                    <?php endfor; ?>
                    <a href="<?= esc($pageUrl(min($totalPages, $page + 1))) ?>"
                       class="px-3 py-1.5 rounded-xl border border-outline-variant text-body-sm <?= $page >= $totalPages ? 'pointer-events-none opacity-40' : 'hover:bg-surface-container-low' ?>">
                        Berikutnya
                    </a>
                </nav>
            </div>
        <?php endif; ?>
    </div>

<!-- ==========================================
     MODAL EDIT SEKOLAH
     ========================================== -->
<div id="edit-sekolah-modal"
     class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 p-4"
     style="display: none; align-items: center; justify-content: center; z-index: 9999;"
     role="dialog" aria-modal="true" aria-labelledby="edit-sekolah-title">
    <div class="bg-white rounded-2xl shadow-2xl w-full"
         style="margin: auto; max-height: 90vh; display: flex; flex-direction: column; width: 100%; max-width: 720px;">
        <!-- Modal Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-outline-variant shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-primary/10 text-primary flex items-center justify-center">
                    <span class="material-symbols-outlined text-primary">school</span>
                </div>
                <div>
                    <h3 id="edit-sekolah-title" class="font-semibold text-body-md text-on-surface">Edit Data Sekolah</h3>
                    <p class="text-caption text-on-surface-variant">NPSN: <span id="edit-sekolah-npsn" class="font-semibold text-primary">-</span></p>
                </div>
            </div>
            <button type="button" class="btn-close-edit p-1.5 rounded-full hover:bg-surface-container transition-colors" aria-label="Tutup">
                <span class="material-symbols-outlined text-on-surface-variant">close</span>
            </button>
        </div>

        <!-- Modal Body -->
        <form id="edit-sekolah-form" method="POST" style="display: flex; flex-direction: column; overflow: hidden; margin: 0;">
            <?= csrf_field() ?>
            <div class="px-6 py-5 overflow-y-auto" style="flex: 1 1 auto; max-height: 65vh;">
                <?php $suggestions = $regionSuggestions ?? ['provinsi' => [], 'kabupaten' => [], 'kecamatan' => []]; ?>
                <?php $fieldClass = 'w-full px-sm py-3 border border-outline rounded-lg bg-surface font-body-md text-body-md text-on-surface focus:border-primary focus:ring-2 focus:ring-tertiary-fixed-dim/30 transition-all'; ?>

                <div class="space-y-md">
                    <!-- ======== IDENTITAS SEKOLAH ======== -->
                    <div class="space-y-md">
                        <h4 class="font-label-bold text-body-md text-primary pb-xs border-b border-surface-variant/30 flex items-center gap-xs">
                            <span class="material-symbols-outlined">school</span> Identitas Sekolah
                        </h4>

                        <div class="flex flex-col gap-xs">
                            <label class="font-label-bold text-body-sm text-on-surface" for="edit-nama_sekolah">Nama Sekolah <span class="text-error">*</span></label>
                            <input class="<?= $fieldClass ?>" id="edit-nama_sekolah" name="nama_sekolah" required maxlength="150" type="text" autocomplete="off"/>
                        </div>
                    </div>

                    <!-- ======== WILAYAH & ALAMAT ======== -->
                    <div class="space-y-md pt-sm border-t border-surface-variant/30">
                        <h4 class="font-label-bold text-body-md text-primary pb-xs border-b border-surface-variant/30 flex items-center gap-xs">
                            <span class="material-symbols-outlined">location_on</span> Wilayah &amp; Alamat
                        </h4>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                            <div class="flex flex-col gap-xs">
                                <label class="font-label-bold text-body-sm text-on-surface" for="edit-nama_provinsi">Provinsi</label>
                                <input class="<?= $fieldClass ?>" id="edit-nama_provinsi" name="nama_provinsi" maxlength="100" type="text"
                                       list="provinsi-suggestions" placeholder="Ketik atau pilih provinsi" autocomplete="off"/>
                                <datalist id="provinsi-suggestions">
                                    <?php foreach ($suggestions['provinsi'] as $provinsi): ?>
                                        <option value="<?= esc($provinsi) ?>"></option>
                                    <?php endforeach; ?>
                                </datalist>
                            </div>

                            <div class="flex flex-col gap-xs">
                                <label class="font-label-bold text-body-sm text-on-surface" for="edit-nama_kabupaten">Kabupaten / Kota</label>
                                <input class="<?= $fieldClass ?>" id="edit-nama_kabupaten" name="nama_kabupaten" maxlength="100" type="text"
                                       list="kabupaten-suggestions" placeholder="Ketik atau pilih kabupaten/kota" autocomplete="off"/>
                                <datalist id="kabupaten-suggestions">
                                    <?php foreach ($suggestions['kabupaten'] as $kabupaten): ?>
                                        <option value="<?= esc($kabupaten) ?>"></option>
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                            <div class="flex flex-col gap-xs">
                                <label class="font-label-bold text-body-sm text-on-surface" for="edit-nama_kecamatan">Kecamatan</label>
                                <input class="<?= $fieldClass ?>" id="edit-nama_kecamatan" name="nama_kecamatan" maxlength="100" type="text"
                                       list="kecamatan-suggestions" placeholder="Ketik atau pilih kecamatan" autocomplete="off"/>
                                <datalist id="kecamatan-suggestions">
                                    <?php foreach ($suggestions['kecamatan'] as $kecamatan): ?>
                                        <option value="<?= esc($kecamatan) ?>"></option>
                                    <?php endforeach; ?>
                                </datalist>
                            </div>

                            <div class="flex flex-col gap-xs">
                                <label class="font-label-bold text-body-sm text-on-surface" for="edit-nama_desa">Desa / Kelurahan</label>
                                <input class="<?= $fieldClass ?>" id="edit-nama_desa" name="nama_desa" maxlength="100" type="text" autocomplete="off"/>
                            </div>
                        </div>

                        <div class="flex flex-col gap-xs">
                            <label class="font-label-bold text-body-sm text-on-surface" for="edit-alamat_jalan">Alamat Jalan</label>
                            <textarea class="<?= $fieldClass ?> min-h-[80px]" id="edit-alamat_jalan" name="alamat_jalan" maxlength="500" rows="2"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="flex flex-col sm:flex-row justify-end items-center gap-sm px-6 pb-6 pt-4 border-t border-outline-variant/30 shrink-0 bg-white">
                <button type="button" class="btn-close-edit w-full sm:w-auto rounded-lg border border-outline text-on-surface-variant hover:bg-surface-container-high transition-colors flex items-center justify-center gap-xs font-label-bold text-body-sm text-center"
                        style="padding: 10px 24px !important; min-width: 120px;">
                    Batal
                </button>
                <button type="submit"
                        class="w-full sm:w-auto rounded-lg bg-primary text-on-primary font-label-bold text-body-sm hover:bg-primary-container transition-colors shadow-sm hover:shadow-md flex items-center justify-center gap-xs group"
                        style="padding: 10px 24px !important; min-width: 120px; cursor: pointer;">
                    Simpan Perubahan
                    <span class="material-symbols-outlined text-[18px]">check</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ==========================================
     MODAL HAPUS SEKOLAH
     ========================================== -->
<div id="delete-sekolah-modal"
     class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 p-4"
     style="display: none; align-items: center; justify-content: center; z-index: 9999;"
     role="dialog" aria-modal="true" aria-labelledby="delete-sekolah-title">
    <div class="bg-white rounded-2xl shadow-2xl w-full"
         style="margin: auto; max-height: 90vh; display: flex; flex-direction: column; width: 100%; max-width: 560px;">
        <!-- Modal Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-outline-variant shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-error-container flex items-center justify-center">
                    <span class="material-symbols-outlined text-on-error-container">delete_forever</span>
                </div>
                <div>
                    <h3 id="delete-sekolah-title" class="font-semibold text-body-md text-on-surface">Hapus Data Sekolah</h3>
                    <p class="text-caption text-on-surface-variant">Tindakan ini tidak dapat dibatalkan</p>
                </div>
            </div>
            <button type="button" class="btn-close-delete p-1.5 rounded-full hover:bg-surface-container transition-colors" aria-label="Tutup">
                <span class="material-symbols-outlined text-on-surface-variant">close</span>
            </button>
        </div>

        <!-- Modal Body -->
        <form id="delete-sekolah-form" method="POST" action="" style="display: flex; flex-direction: column; overflow: hidden; margin: 0;">
            <?= csrf_field() ?>
            <div class="px-6 py-5 overflow-y-auto" style="flex: 1 1 auto;">
                <p class="text-body-sm text-on-surface">
                    Anda akan menghapus <strong id="delete-sekolah-nama" class="text-primary">-</strong> dari master data sekolah.
                </p>

                <div id="delete-usage-block" class="mt-4 rounded-xl border border-tertiary-container bg-tertiary-container/20 px-4 py-3">
                    <p class="text-body-sm text-on-surface">
                        Sekolah ini dipakai oleh <strong id="delete-usage-count" class="text-primary">0</strong> pendaftar.
                        Pilih apa yang terjadi pada data mereka:
                    </p>

                    <div class="mt-3 space-y-2">
                        <label class="flex items-start gap-3 p-3 rounded-xl border border-outline-variant bg-surface hover:bg-surface-container-low transition-colors cursor-pointer">
                            <input type="radio" name="reassign_action" value="default" checked
                                   class="mt-1 rounded border-outline-variant text-primary focus:ring-primary cursor-pointer" />
                            <span>
                                <span class="block font-semibold text-body-sm text-on-surface">Ubah menjadi "Belum Diisi"</span>
                                <span class="block text-caption text-on-surface-variant mt-0.5">Pendaftar kembali diminta memilih sekolah asalnya.</span>
                            </span>
                        </label>

                        <label class="flex items-start gap-3 p-3 rounded-xl border border-outline-variant bg-surface hover:bg-surface-container-low transition-colors cursor-pointer">
                            <input type="radio" name="reassign_action" value="move"
                                   class="mt-1 rounded border-outline-variant text-primary focus:ring-primary cursor-pointer" />
                            <span>
                                <span class="block font-semibold text-body-sm text-on-surface">Pindahkan ke sekolah lain</span>
                                <span class="block text-caption text-on-surface-variant mt-0.5">Alamat sekolah pendaftar ikut disesuaikan ke sekolah tujuan.</span>
                            </span>
                        </label>
                    </div>

                    <div id="delete-move-picker" class="mt-3">
                        <label class="font-label-bold text-body-sm text-on-surface block mb-1" for="reassign-target">Sekolah Tujuan</label>
                        <select id="reassign-target" name="reassign_sekolah_id" placeholder="Ketik nama sekolah..."></select>
                        <p id="delete-move-error" class="hidden mt-2 text-caption text-error font-semibold">Pilih sekolah tujuan terlebih dahulu.</p>
                    </div>
                </div>

                <p id="delete-unused-note" class="hidden mt-4 text-body-sm text-on-surface-variant">
                    Sekolah ini belum dipakai pendaftar apa pun, sehingga dapat langsung dihapus.
                </p>
            </div>

            <!-- Modal Footer -->
            <div class="flex flex-col sm:flex-row justify-end items-center gap-sm px-6 pb-6 pt-4 border-t border-outline-variant/30 shrink-0 bg-white">
                <button type="button" class="btn-close-delete w-full sm:w-auto rounded-lg border border-outline text-on-surface-variant hover:bg-surface-container-high transition-colors flex items-center justify-center gap-xs font-label-bold text-body-sm text-center"
                        style="padding: 10px 24px !important; min-width: 120px;">
                    Batal
                </button>
                <button type="submit"
                        class="w-full sm:w-auto rounded-lg bg-error text-on-error font-label-bold text-body-sm hover:opacity-90 transition-opacity shadow-sm flex items-center justify-center gap-xs"
                        style="padding: 10px 24px !important; min-width: 120px; cursor: pointer;">
                    Hapus Sekolah
                    <span class="material-symbols-outlined text-[18px]">delete</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ==========================================
     MODAL HAPUS MASSAL SEKOLAH
     ========================================== -->
<div id="bulk-delete-sekolah-modal"
     class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 p-4"
     style="display: none; align-items: center; justify-content: center; z-index: 9999;"
     role="dialog" aria-modal="true" aria-labelledby="bulk-delete-sekolah-title">
    <div class="bg-white rounded-2xl shadow-2xl w-full"
         style="margin: auto; max-height: 90vh; display: flex; flex-direction: column; width: 100%; max-width: 560px;">
        <!-- Modal Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-outline-variant shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-error-container flex items-center justify-center">
                    <span class="material-symbols-outlined text-on-error-container">delete_sweep</span>
                </div>
                <div>
                    <h3 id="bulk-delete-sekolah-title" class="font-semibold text-body-md text-on-surface">Hapus Sekolah Terpilih</h3>
                    <p class="text-caption text-on-surface-variant">Tindakan ini tidak dapat dibatalkan</p>
                </div>
            </div>
            <button type="button" class="btn-close-bulk-delete p-1.5 rounded-full hover:bg-surface-container transition-colors" aria-label="Tutup">
                <span class="material-symbols-outlined text-on-surface-variant">close</span>
            </button>
        </div>

        <!-- Modal Body -->
        <form id="bulk-delete-sekolah-form" method="POST" action="<?= base_url('admin/sekolah/bulk-delete') ?>" style="display: flex; flex-direction: column; overflow: hidden; margin: 0;">
            <?= csrf_field() ?>
            <div id="bulk-ids-container"></div>
            <div class="px-6 py-5 overflow-y-auto" style="flex: 1 1 auto;">
                <p class="text-body-sm text-on-surface">
                    Anda akan menghapus <strong id="bulk-delete-count" class="text-primary">0</strong> sekolah dari master data.
                </p>

                <div id="bulk-usage-block" class="mt-4 rounded-xl border border-tertiary-container bg-tertiary-container/20 px-4 py-3">
                    <p class="text-body-sm text-on-surface">
                        Sekolah yang dipilih dipakai oleh <strong id="bulk-usage-count" class="text-primary">0</strong> pendaftar.
                        Pilih apa yang terjadi pada data mereka:
                    </p>

                    <div class="mt-3 space-y-2">
                        <label class="flex items-start gap-3 p-3 rounded-xl border border-outline-variant bg-surface hover:bg-surface-container-low transition-colors cursor-pointer">
                            <input type="radio" name="reassign_action" value="default" checked
                                   class="mt-1 rounded border-outline-variant text-primary focus:ring-primary cursor-pointer" />
                            <span>
                                <span class="block font-semibold text-body-sm text-on-surface">Ubah menjadi "Belum Diisi"</span>
                                <span class="block text-caption text-on-surface-variant mt-0.5">Pendaftar kembali diminta memilih sekolah asalnya.</span>
                            </span>
                        </label>

                        <label class="flex items-start gap-3 p-3 rounded-xl border border-outline-variant bg-surface hover:bg-surface-container-low transition-colors cursor-pointer">
                            <input type="radio" name="reassign_action" value="move"
                                   class="mt-1 rounded border-outline-variant text-primary focus:ring-primary cursor-pointer" />
                            <span>
                                <span class="block font-semibold text-body-sm text-on-surface">Pindahkan ke sekolah lain</span>
                                <span class="block text-caption text-on-surface-variant mt-0.5">Alamat sekolah pendaftar ikut disesuaikan ke sekolah tujuan.</span>
                            </span>
                        </label>
                    </div>

                    <div id="bulk-move-picker" class="mt-3">
                        <label class="font-label-bold text-body-sm text-on-surface block mb-1" for="bulk-reassign-target">Sekolah Tujuan</label>
                        <select id="bulk-reassign-target" name="reassign_sekolah_id" placeholder="Ketik nama sekolah..."></select>
                        <p id="bulk-move-error" class="hidden mt-2 text-caption text-error font-semibold">Pilih sekolah tujuan terlebih dahulu.</p>
                    </div>
                </div>

                <p id="bulk-unused-note" class="hidden mt-4 text-body-sm text-on-surface-variant">
                    Tidak ada sekolah yang dipilih yang dipakai pendaftar, sehingga dapat langsung dihapus.
                </p>
            </div>

            <!-- Modal Footer -->
            <div class="flex flex-col sm:flex-row justify-end items-center gap-sm px-6 pb-6 pt-4 border-t border-outline-variant/30 shrink-0 bg-white">
                <button type="button" class="btn-close-bulk-delete w-full sm:w-auto rounded-lg border border-outline text-on-surface-variant hover:bg-surface-container-high transition-colors flex items-center justify-center gap-xs font-label-bold text-body-sm text-center"
                        style="padding: 10px 24px !important; min-width: 120px;">
                    Batal
                </button>
                <button type="submit"
                        class="w-full sm:w-auto rounded-lg bg-error text-on-error font-label-bold text-body-sm hover:opacity-90 transition-opacity shadow-sm flex items-center justify-center gap-xs"
                        style="padding: 10px 24px !important; min-width: 120px; cursor: pointer;">
                    Hapus Semua
                    <span class="material-symbols-outlined text-[18px]">delete</span>
                </button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<?= script_tag('js/admin/form-sekolah.js') ?>
<?= $this->endSection() ?>
