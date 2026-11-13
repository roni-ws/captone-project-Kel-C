<?= $this->extend('layouts/admin') ?>

<?= $this->section('title') ?>Pengaturan Frontend<?= $this->endSection() ?>
<?= $this->section('page-title') ?>Pengaturan Halaman Utama<?= $this->endSection() ?>

<?= $this->section('css') ?>
<link href="<?= base_url('vendor/quill/quill.snow.css') ?>" rel="stylesheet">
<style>
    .settings-section { scroll-margin-top: 84px; }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="max-w-7xl mx-auto w-full">

    <!-- ==========================================
         PAGE HEADER
         ========================================== -->
    <div class="flex justify-between items-center mb-md">
        <div>
            <h1 class="font-headline-md text-headline-md font-bold text-primary mb-1">Pengaturan Website</h1>
            <p class="text-on-surface-variant text-body-sm">Kelola isi konten halaman depan, alur pendaftaran, dan informasi kontak sekolah secara real-time.</p>
        </div>
    </div>

    <div class="flex flex-col lg:flex-row gap-6 lg:gap-8 items-start">

        <!-- ==========================================
             SIDEBAR NAVIGATION (STICKY)
             ========================================== -->
        <aside class="w-full lg:w-[280px] bg-surface-container-lowest border border-outline-variant/35 rounded-3xl p-5 shadow-sm lg:sticky lg:top-[84px] shrink-0">
            <h3 class="font-extrabold text-on-surface text-sm mb-4 px-2 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">settings</span>
                Pengaturan
            </h3>

            <nav id="settings-nav" class="flex flex-col gap-1 max-h-[60vh] overflow-y-auto custom-scrollbar pr-1">
                <a href="#tab-branding" class="settings-nav-item flex items-center gap-2.5 px-4 py-2.5 rounded-xl text-xs font-semibold bg-secondary-container text-on-secondary-container font-bold shadow-sm transition-all">
                    <span class="material-symbols-outlined text-base">workspace_premium</span>
                    Branding & Status
                </a>
                <a href="#tab-hero" class="settings-nav-item flex items-center gap-2.5 px-4 py-2.5 rounded-xl text-xs font-semibold text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high transition-all">
                    <span class="material-symbols-outlined text-base">image</span>
                    Hero Banner
                </a>
                <a href="#tab-galeri" class="settings-nav-item flex items-center gap-2.5 px-4 py-2.5 rounded-xl text-xs font-semibold text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high transition-all">
                    <span class="material-symbols-outlined text-base">photo_library</span>
                    Galeri Foto
                </a>
                <a href="#tab-alur" class="settings-nav-item flex items-center gap-2.5 px-4 py-2.5 rounded-xl text-xs font-semibold text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high transition-all">
                    <span class="material-symbols-outlined text-base">route</span>
                    Alur Pendaftaran
                </a>
                <a href="#tab-footer" class="settings-nav-item flex items-center gap-2.5 px-4 py-2.5 rounded-xl text-xs font-semibold text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high transition-all">
                    <span class="material-symbols-outlined text-base">contact_support</span>
                    Persyaratan & Footer
                </a>
                <a href="#tab-kontak" class="settings-nav-item flex items-center gap-2.5 px-4 py-2.5 rounded-xl text-xs font-semibold text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high transition-all">
                    <span class="material-symbols-outlined text-base">call</span>
                    Kontak & WhatsApp
                </a>
                <a href="#tab-kop" class="settings-nav-item flex items-center gap-2.5 px-4 py-2.5 rounded-xl text-xs font-semibold text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high transition-all">
                    <span class="material-symbols-outlined text-base">print</span>
                    Kop & Cetak Bukti
                </a>
            </nav>
        </aside>

        <!-- ==========================================
             CONTENT COLUMN
             ========================================== -->
        <div class="flex-1 w-full min-w-0">
            <form id="settings-form" action="<?= base_url('admin/settings/save') ?>" method="POST" enctype="multipart/form-data">
                <?= csrf_field() ?>

                <?= $this->include('components/admin/settings/tabs/umum') ?>
                <?= $this->include('components/admin/settings/tabs/galeri') ?>
                <?= $this->include('components/admin/settings/tabs/pendaftaran') ?>

                <!-- ==========================================
                     SUBMIT TOOLBAR (Sticky at Bottom)
                     ========================================== -->
                <div class="sticky bottom-6 mt-md p-4 rounded-2xl bg-surface border border-outline-variant/80 shadow-lg flex justify-between items-center backdrop-blur-md bg-opacity-95 z-10 animate-slide-up">
                    <span class="text-caption text-on-surface-variant hidden sm:inline flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[16px] text-primary">info</span> Pastikan data yang dimasukkan sudah benar sebelum disimpan.
                    </span>
                    <div class="flex gap-3 ml-auto w-full sm:w-auto">
                        <button type="submit" class="w-full sm:w-auto bg-primary text-on-primary font-semibold text-body-sm px-6 py-3 rounded-xl hover:opacity-90 shadow-md transition-all flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined text-[18px]">save</span> Simpan Perubahan
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    const BASE_URL = '<?= base_url() ?>';
    const CSRF_TOKEN = '<?= csrf_token() ?>';
    const CSRF_HASH = '<?= csrf_hash() ?>';
</script>
<script src="<?= base_url('vendor/quill/quill.min.js') ?>"></script>
<?= script_tag('js/admin/form-settings.js') ?>
<?= $this->endSection() ?>
