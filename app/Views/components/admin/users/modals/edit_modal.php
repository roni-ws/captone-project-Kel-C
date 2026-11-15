<!-- ==========================================
     MODAL 2: EDIT STAFF
     ========================================== -->
<div id="edit-modal"
     class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 p-4"
     style="display: none; align-items: center; justify-content: center; z-index: 9999;"
     role="dialog" aria-modal="true" aria-labelledby="edit-modal-title">
    <div class="bg-white rounded-2xl shadow-2xl w-full"
         style="margin: auto; max-height: 90vh; display: flex; flex-direction: column; width: 100%; max-width: 720px;">
        <!-- Modal Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-outline-variant shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-primary/10 text-primary flex items-center justify-center">
                    <span class="material-symbols-outlined text-primary">manage_accounts</span>
                </div>
                <div>
                    <h3 id="edit-modal-title" class="font-semibold text-body-md text-on-surface"><?= ($isAdmin ?? false) ? 'Ubah Data Staff' : 'Ubah Data Akun' ?></h3>
                    <p class="text-caption text-on-surface-variant"><?= ($isAdmin ?? false) ? 'Perbarui identitas, peran, dan kata sandi' : 'Perbarui identitas dan kata sandi akun Anda' ?></p>
                </div>
            </div>
            <button type="button" onclick="closeEditModal()" class="p-1.5 rounded-full hover:bg-surface-container transition-colors" aria-label="Tutup">
                <span class="material-symbols-outlined text-on-surface-variant">close</span>
            </button>
        </div>

        <!-- Modal Body -->
        <form id="edit-form" method="post" action="" style="display: flex; flex-direction: column; overflow: hidden; margin: 0;">
            <?= csrf_field() ?>
            <?php $fieldClass = 'w-full px-sm py-3 border border-outline rounded-lg bg-surface font-body-md text-body-md text-on-surface focus:border-primary focus:ring-2 focus:ring-tertiary-fixed-dim/30 transition-all'; ?>
            <div class="px-6 py-5 overflow-y-auto" style="flex: 1 1 auto; max-height: 65vh;">
                <input type="hidden" name="is_self" id="edit-is-self" value="0">

                <div class="space-y-md">
                    <!-- ======== IDENTITAS AKUN ======== -->
                    <div class="space-y-md">
                        <h4 class="font-label-bold text-body-md text-primary pb-xs border-b border-surface-variant/30 flex items-center gap-xs">
                            <span class="material-symbols-outlined">manage_accounts</span> Identitas Akun
                        </h4>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                            <div class="flex flex-col gap-xs">
                                <label class="font-label-bold text-body-sm text-on-surface" for="edit-username">Username <span class="text-error">*</span></label>
                                <input type="text" name="username" id="edit-username" required minlength="3" maxlength="30" pattern="^[a-zA-Z0-9_]+$"
                                       class="<?= $fieldClass ?>" autocomplete="off" />
                                <p class="text-caption text-on-surface-variant">Huruf, angka, dan garis bawah. Minimal 3 karakter.</p>
                            </div>

                            <div class="flex flex-col gap-xs">
                                <label class="font-label-bold text-body-sm text-on-surface" for="edit-email">Alamat Email <span class="text-error">*</span></label>
                                <input type="email" name="email" id="edit-email" required
                                       class="<?= $fieldClass ?>" autocomplete="off" />
                            </div>
                        </div>

                        <div class="flex flex-col gap-xs">
                            <label for="edit-role" class="font-label-bold text-body-sm text-on-surface">Peran / Akses Sistem</label>
                            <?php if ($isAdmin ?? false): ?>
                                <select name="role" id="edit-role" required class="<?= $fieldClass ?>">
                                    <option value="panitia">Panitia — Akses terbatas ke data pendaftar</option>
                                    <option value="admin">Admin — Akses penuh pengaturan &amp; user</option>
                                </select>
                            <?php else: ?>
                                <input type="hidden" name="role" value="<?= ROLE_PANITIA ?>">
                                <div class="w-full px-sm py-3 border border-outline-variant rounded-lg bg-surface-container text-body-md text-on-surface-variant">
                                    Panitia &mdash; hanya dapat mengubah akun sendiri
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- ======== UBAH KATA SANDI ======== -->
                    <div class="space-y-md pt-sm border-t border-surface-variant/30">
                        <h4 class="font-label-bold text-body-md text-primary pb-xs border-b border-surface-variant/30 flex items-center gap-xs">
                            <span class="material-symbols-outlined">lock</span> Ubah Kata Sandi
                        </h4>

                        <div id="wrapper-password-lama" class="flex flex-col gap-xs hidden">
                            <label for="password_lama" class="font-label-bold text-body-sm text-on-surface">Kata Sandi Lama</label>
                            <input type="password" name="password_lama" id="password_lama"
                                   class="<?= $fieldClass ?>" placeholder="Wajib diisi jika mengganti sandi" autocomplete="current-password" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                            <div class="flex flex-col gap-xs">
                                <label for="password_baru" class="font-label-bold text-body-sm text-on-surface">Kata Sandi Baru</label>
                                <input type="password" name="password_baru" id="password_baru" minlength="6"
                                       class="<?= $fieldClass ?>" placeholder="Minimal 6 karakter" autocomplete="new-password" />
                            </div>
                            <div class="flex flex-col gap-xs">
                                <label for="password_konfirmasi" class="font-label-bold text-body-sm text-on-surface">Konfirmasi Kata Sandi Baru</label>
                                <input type="password" name="password_konfirmasi" id="password_konfirmasi" minlength="6"
                                       class="<?= $fieldClass ?>" placeholder="Ulangi sandi baru" autocomplete="new-password" />
                            </div>
                        </div>
                        <p class="text-caption text-on-surface-variant">* Kosongkan kedua kolom jika tidak ingin mengubah kata sandi.</p>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="flex flex-col sm:flex-row justify-end items-center gap-sm px-6 pb-6 pt-4 border-t border-outline-variant/30 shrink-0 bg-white">
                <button type="button" onclick="closeEditModal()"
                        class="w-full sm:w-auto rounded-lg border border-outline text-on-surface-variant hover:bg-surface-container-high transition-colors flex items-center justify-center gap-xs font-label-bold text-body-sm text-center"
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
