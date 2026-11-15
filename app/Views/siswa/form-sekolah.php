<?= $this->extend('layouts/siswa') ?>

<?= $this->section('title') ?>
Asal Sekolah (Langkah 2 dari 4)
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?= $this->include('components/siswa/form-stepper') ?>

<!-- Main Form Section -->
<div class="max-w-3xl mx-auto">
    <div class="mb-md">
        <h2 class="font-headline-sm text-headline-sm text-primary mb-xs">Informasi Sekolah Asal</h2>
        <p class="font-body-sm text-body-sm text-on-surface-variant">Tuliskan nama sekolah asal dan alamat lengkap sekolah asal Anda (SMP/MTs/Sederajat).</p>
    </div>

    <!-- Card Formulir -->
    <div class="bg-surface border border-outline-variant rounded-2xl shadow-sm p-md md:p-lg mb-md relative overflow-hidden">
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-primary to-tertiary-fixed-dim"></div>
        
        <form action="<?= base_url('siswa/sekolah') ?>" method="POST" class="space-y-md" novalidate>
            <?= csrf_field() ?>

            <!-- Nama Sekolah Asal -->
            <div class="flex flex-col gap-xs">
                <label class="font-label-bold text-body-sm text-on-surface" for="nama_sekolah">Nama Sekolah Asal <span class="text-error">*</span></label>
                <div class="relative z-10">
                    <span class="material-symbols-outlined absolute left-sm top-[14px] text-outline-variant pointer-events-none z-20" style="font-size: 20px;">school</span>
                    <input type="hidden" id="sekolah_id" name="sekolah_id" value="<?= esc(old('sekolah_id', $sekolah['id'] ?? '')) ?>">
                    <input type="hidden" id="npsn" name="npsn" value="<?= esc(old('npsn', $sekolah['npsn'] ?? '')) ?>">
                    <?php $oldNama = (string) old('nama_sekolah', $sekolah['nama_sekolah'] ?? ''); ?>
                    <?php $oldId   = (string) old('sekolah_id', $sekolah['id'] ?? ''); ?>
                    <input type="hidden" id="nama_sekolah_value" name="nama_sekolah" value="<?= esc($oldNama) ?>">
                    <select id="nama_sekolah" required class="w-full" placeholder="Ketik nama sekolah Anda..."></select>
                    <?php if ($oldNama !== ''): ?>
                        <script id="sekolah-awal" type="application/json"><?= json_encode([
                            'id'   => $oldId,
                            'text' => $oldNama,
                            'npsn' => (string) ($sekolah['npsn'] ?? ''),
                        ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
                    <?php endif; ?>
                </div>
                <p class="font-caption text-caption text-on-surface-variant">
                    Ketik minimal 3 karakter. Bila sekolah tidak ditemukan, lanjutkan mengetik nama sekolah secara lengkap.
                </p>
                <?php if (isset($errors['nama_sekolah'])): ?>
                    <span class="text-xs text-error mt-xs font-semibold"><?= esc($errors['nama_sekolah']) ?></span>
                <?php endif; ?>
            </div>

            <!-- Alamat Sekolah Asal -->
            <div class="flex flex-col gap-xs">
                <label class="font-label-bold text-body-sm text-on-surface" for="alamat_sekolah">Alamat Lengkap Sekolah Asal <span class="text-error">*</span></label>
                <div class="relative z-0">
                    <span class="material-symbols-outlined absolute left-sm top-4 text-outline-variant pointer-events-none" style="font-size: 20px;">location_on</span>
                    <textarea class="w-full pl-10 pr-sm py-3 border <?= isset($errors['alamat_sekolah']) ? 'border-error' : 'border-outline' ?> rounded-lg bg-surface font-body-md text-body-md text-on-surface placeholder:text-outline-variant focus:border-primary focus:ring-2 focus:ring-tertiary-fixed-dim/30 transition-all min-h-[100px]" 
                              id="alamat_sekolah" name="alamat_sekolah" placeholder="Nama jalan, kelurahan, kecamatan, kabupaten, dan provinsi sekolah asal" required><?= esc(old('alamat_sekolah', $sekolah['alamat_sekolah'] ?? '')) ?></textarea>
                </div>
                <p class="font-caption text-caption text-on-surface-variant">
                    Alamat terisi otomatis saat Anda memilih sekolah dari daftar. Silakan lengkapi atau perbaiki bila perlu.
                </p>
                <?php if (isset($errors['alamat_sekolah'])): ?>
                    <span class="text-xs text-error mt-xs font-semibold"><?= esc($errors['alamat_sekolah']) ?></span>
                <?php endif; ?>
            </div>

            <!-- Tombol Aksi -->
            <?= $this->include('components/siswa/form-actions') ?>
        </form>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    window.BASE_URL = '<?= base_url() ?>';
</script>
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<style>
    /* Penyesuaian TomSelect agar mirip input Tailwind Vibe */
    .ts-wrapper {
        border: 1px solid #c2c7ce;
        border-radius: 0.5rem;
        background-color: #f8f9fa;
        transition: all 0.2s;
    }
    .ts-wrapper.focus {
        border-color: #006493;
        box-shadow: 0 0 0 2px rgba(0, 100, 147, 0.3);
    }
    .ts-control {
        padding: 0.75rem 0.875rem 0.75rem 2.5rem !important;
        border: none !important;
        background: transparent !important;
        box-shadow: none !important;
        font-family: inherit !important;
        font-size: 0.875rem !important;
        line-height: 1.5 !important;
    }
    .ts-dropdown {
        border-radius: 0.5rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        margin-top: 4px;
        z-index: 50;
        font-family: inherit;
        font-size: 0.875rem;
    }
</style>
<?= script_tag('js/siswa/form-sekolah.js') ?>
<?= $this->endSection() ?>
