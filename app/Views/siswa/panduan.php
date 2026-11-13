<?= $this->extend('layouts/siswa') ?>

<?= $this->section('title') ?>
Panduan Pengisian Formulir
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Header -->
<section class="mb-lg">
    <h1 class="font-headline-md text-headline-md text-primary mb-xs">Panduan Pengisian Formulir</h1>
    <p class="font-body-sm text-body-sm text-on-surface-variant">
        Ikuti langkah berikut agar pendaftaran Anda selesai dengan lancar.
    </p>
</section>

<div class="space-y-md">
    <?php
    $steps = [
        [
            'num'   => '1',
            'title' => 'Biodata Diri',
            'icon'  => 'badge',
            'items' => [
                'Isi NIK dan No. KK sesuai Kartu Keluarga (16 digit).',
                'Nama lengkap ditulis sesuai dokumen resmi, tanpa gelar.',
                'Lengkapi tempat/tanggal lahir, jenis kelamin, dan jumlah saudara.',
                'No. Registrasi ditampilkan otomatis dan tidak dapat diubah.',
            ],
        ],
        [
            'num'   => '2',
            'title' => 'Asal Sekolah',
            'icon'  => 'school',
            'items' => [
                'Masukkan nama sekolah asal sesuai ijazah/SKL.',
                'Isi alamat sekolah lengkap.',
                'Gunakan data sekolah yang benar, bukan sekolah tujuan.',
            ],
        ],
        [
            'num'   => '3',
            'title' => 'Data Orang Tua',
            'icon'  => 'family_restroom',
            'items' => [
                'Isi nama dan pekerjaan ayah serta ibu kandung.',
                'Pastikan ejaan nama sesuai dokumen resmi.',
            ],
        ],
        [
            'num'   => '4',
            'title' => 'Kelengkapan Berkas',
            'icon'  => 'upload_file',
            'items' => [
                'Unggah scan Ijazah/SKL, Kartu Keluarga, KTP orang tua, dan pas foto.',
                'Format file: PDF/JPG/PNG, maksimal 2MB per file.',
                'Pastikan hasil scan terbaca jelas.',
            ],
        ],
    ];
    ?>

    <?php foreach ($steps as $step): ?>
    <div class="bg-surface border border-outline-variant rounded-2xl shadow-sm p-md relative overflow-hidden">
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-primary to-tertiary-fixed-dim"></div>
        <div class="flex items-start gap-sm">
            <div class="w-10 h-10 rounded-xl bg-primary-container text-on-primary-container flex items-center justify-center shrink-0 font-bold">
                <?= esc($step['num']) ?>
            </div>
            <div class="flex-grow">
                <h2 class="font-headline-sm text-headline-sm text-primary flex items-center gap-xs mb-sm">
                    <span class="material-symbols-outlined text-[20px]"><?= esc($step['icon']) ?></span>
                    <?= esc($step['title']) ?>
                </h2>
                <ul class="space-y-1.5 text-body-sm text-on-surface-variant">
                    <?php foreach ($step['items'] as $item): ?>
                        <li class="flex items-start gap-xs">
                            <span class="material-symbols-outlined text-[16px] text-emerald-600 shrink-0 mt-0.5">check_circle</span>
                            <span><?= esc($item) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Finalisasi & Cetak -->
    <div class="bg-emerald-50/70 border border-emerald-100 rounded-2xl p-md">
        <div class="flex items-center gap-xs text-emerald-800 font-bold text-xs uppercase tracking-wider mb-2">
            <span class="material-symbols-outlined text-[18px]">task_alt</span>
            Finalisasi &amp; Cetak Bukti
        </div>
        <ul class="space-y-1.5 text-body-sm text-slate-700">
            <li class="flex items-start gap-xs">
                <span class="material-symbols-outlined text-[16px] text-emerald-600 shrink-0 mt-0.5">check_circle</span>
                <span>Periksa seluruh data sebelum menekan tombol <strong>Finalisasi</strong>.</span>
            </li>
            <li class="flex items-start gap-xs">
                <span class="material-symbols-outlined text-[16px] text-emerald-600 shrink-0 mt-0.5">check_circle</span>
                <span>Setelah finalisasi, data tidak dapat diubah selama belum ada permintaan revisi.</span>
            </li>
            <li class="flex items-start gap-xs">
                <span class="material-symbols-outlined text-[16px] text-emerald-600 shrink-0 mt-0.5">check_circle</span>
                <span>Menu <strong>Cetak Bukti</strong> aktif setelah Anda melakukan finalisasi pendaftaran.</span>
            </li>
        </ul>
    </div>

    <!-- Help CTA -->
    <div class="bg-blue-50/70 border border-blue-100 rounded-2xl p-md flex flex-col sm:flex-row sm:items-center sm:justify-between gap-sm">
        <div class="flex items-start gap-xs">
            <span class="material-symbols-outlined text-blue-700 shrink-0">help</span>
            <p class="text-xs text-slate-700 leading-relaxed">
                Masih bingung atau menemukan kendala? Sampaikan lewat menu Pengaduan.
            </p>
        </div>
        <a href="<?= base_url('siswa/pengaduan') ?>"
           class="shrink-0 inline-flex items-center gap-1.5 bg-primary text-on-primary px-4 py-2.5 rounded-xl font-bold text-xs hover:brightness-110 transition-all shadow-sm">
            <span class="material-symbols-outlined text-[16px]">support_agent</span>
            Buka Pengaduan
        </a>
    </div>
</div>
<?= $this->endSection() ?>
