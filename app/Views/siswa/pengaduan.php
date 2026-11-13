<?= $this->extend('layouts/siswa') ?>

<?= $this->section('title') ?>
Pengaduan & Bantuan
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Header -->
<section class="mb-lg">
    <h1 class="font-headline-md text-headline-md text-primary mb-xs">Pengaduan &amp; Bantuan</h1>
    <p class="font-body-sm text-body-sm text-on-surface-variant">
        Sampaikan kendala atau pertanyaan Anda kepada Panitia Penyelenggara.
    </p>
</section>

<div class="space-y-md">
    <!-- Flash Error -->
    <?php if (!empty($errors)): ?>
        <div class="mb-md p-sm bg-red-50 border border-red-200 rounded-xl text-red-800 text-sm">
            <div class="flex items-center gap-xs font-bold mb-1">
                <span class="material-symbols-outlined text-[18px]">error</span>
                Periksa kembali pengaduan Anda
            </div>
            <ul class="list-disc list-inside space-y-0.5">
                <?php foreach ($errors as $err): ?>
                    <li><?= esc($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Info Card -->
    <div class="mb-md bg-blue-50/70 border border-blue-100 rounded-2xl p-md">
        <div class="flex items-center gap-xs text-blue-700 font-bold text-xs uppercase tracking-wider mb-1">
            <span class="material-symbols-outlined text-[18px]">info</span>
            Tentang Halaman Ini
        </div>
        <p class="text-xs text-slate-700 leading-relaxed">
            Gunakan formulir ini untuk melaporkan kendala teknis maupun pertanyaan seputar pendaftaran.
        </p>
    </div>

    <!-- Form Card -->
    <div class="bg-surface border border-outline-variant rounded-2xl shadow-sm p-md md:p-lg relative overflow-hidden">
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-primary to-tertiary-fixed-dim"></div>

        <form action="<?= base_url('siswa/pengaduan') ?>" method="POST" class="space-y-6" novalidate>
            <?= csrf_field() ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Category -->
                <div class="space-y-2">
                    <label for="category" class="text-[11px] font-bold text-slate-500 uppercase tracking-widest px-1">Kategori Keluhan</label>
                    <select name="category" id="category" required
                            class="w-full px-4 py-3 bg-slate-50 hover:bg-slate-100/50 border border-slate-200 rounded-2xl outline-none focus:bg-white focus:border-primary focus:ring-4 focus:ring-primary/5 transition-all text-xs font-semibold">
                        <?php $selected = old('category'); ?>
                        <option value="biodata"   <?= $selected === 'biodata'   ? 'selected' : '' ?>>Biodata Diri</option>
                        <option value="sekolah"   <?= $selected === 'sekolah'   ? 'selected' : '' ?>>Asal Sekolah</option>
                        <option value="orang_tua" <?= $selected === 'orang_tua' ? 'selected' : '' ?>>Data Orang Tua</option>
                        <option value="berkas"    <?= $selected === 'berkas'    ? 'selected' : '' ?>>Kelengkapan Berkas</option>
                        <option value="teknis"    <?= $selected === 'teknis'    ? 'selected' : '' ?>>Masalah Teknis Website</option>
                        <option value="lainnya"   <?= $selected === 'lainnya'   ? 'selected' : '' ?>>Pertanyaan Lainnya</option>
                    </select>
                </div>

                <!-- Subject -->
                <div class="space-y-2">
                    <label for="subject" class="text-[11px] font-bold text-slate-500 uppercase tracking-widest px-1">Subjek / Judul Pengaduan</label>
                    <input type="text" name="subject" id="subject" required
                           value="<?= esc(old('subject')) ?>"
                           placeholder="Contoh: Berkas ijazah tidak bisa diunggah"
                           class="w-full px-4 py-3 bg-slate-50 hover:bg-slate-100/50 border border-slate-200 rounded-2xl outline-none focus:bg-white focus:border-primary focus:ring-4 focus:ring-primary/5 transition-all text-xs font-semibold"/>
                </div>
            </div>

            <!-- Message -->
            <div class="space-y-2">
                <label for="message" class="text-[11px] font-bold text-slate-500 uppercase tracking-widest px-1">Pesan / Detail Kendala</label>
                <textarea name="message" id="message" rows="6" required
                          placeholder="Tuliskan sedetail mungkin kendala yang Anda alami (langkah yang sudah dicoba, pesan error, dsb)..."
                          class="w-full px-4 py-3 bg-slate-50 hover:bg-slate-100/50 border border-slate-200 rounded-2xl outline-none focus:bg-white focus:border-primary focus:ring-4 focus:ring-primary/5 transition-all text-xs font-semibold leading-relaxed"><?= esc(old('message')) ?></textarea>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-3 border-t border-slate-100 pt-6">
                <button type="submit"
                        class="bg-primary hover:brightness-110 text-on-primary px-5 py-3 rounded-2xl font-bold transition-all shadow-md hover:shadow-lg flex items-center gap-2 text-xs">
                    <span class="material-symbols-outlined text-xs">send</span>
                    Kirim Pengaduan
                </button>
                <a href="<?= base_url('siswa/dashboard') ?>"
                   class="border border-slate-200 text-slate-600 px-5 py-3 rounded-2xl font-bold hover:bg-slate-50 transition-all text-xs">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
