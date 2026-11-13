<!-- ==========================================
     MODAL 2: EDIT STAFF
     ========================================== -->
<div id="edit-modal"
     class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 p-4"
     style="display: none; align-items: center; justify-content: center; z-index: 9999;">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md animate-in fade-in zoom-in-95 duration-200"
         style="margin: auto; max-height: 90vh; display: flex; flex-direction: column; width: 100%; max-width: 450px;">
        <!-- Modal Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-outline-variant shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-primary/10 text-primary flex items-center justify-center">
                    <span class="material-symbols-outlined text-primary">edit</span>
                </div>
                <div>
                    <h3 class="font-semibold text-body-md text-on-surface"><?= ($isAdmin ?? false) ? 'Ubah Data Staff' : 'Ubah Data Akun' ?></h3>
                    <p class="text-caption text-on-surface-variant"><?= ($isAdmin ?? false) ? 'Perbarui identitas, peran, dan kata sandi' : 'Perbarui identitas dan kata sandi akun Anda' ?></p>
                </div>
            </div>
            <button onclick="closeEditModal()" class="p-1.5 rounded-full hover:bg-surface-container transition-colors">
                <span class="material-symbols-outlined text-on-surface-variant">close</span>
            </button>
        </div>

        <!-- Modal Body -->
        <form id="edit-form" method="post" action="" style="display: flex; flex-direction: column; overflow: hidden; margin: 0;">
            <?= csrf_field() ?>
            <div class="px-6 py-5 space-y-4 overflow-y-auto custom-scrollbar" style="flex: 1 1 auto; max-height: 60vh;">
                <input type="hidden" name="is_self" id="edit-is-self" value="0">
                <!-- Username -->
                <div>
                    <label class="block text-body-sm font-semibold text-on-surface mb-1.5">Username</label>
                    <input type="text" name="username" id="edit-username" required minlength="3" maxlength="30" pattern="^[a-zA-Z0-9_]+$"
                           class="w-full px-3 py-2.5 rounded-xl border border-outline-variant focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all text-body-sm outline-none bg-surface-container-lowest" />
                </div>
                <!-- Email -->
                <div>
                    <label class="block text-body-sm font-semibold text-on-surface mb-1.5">Alamat Email</label>
                    <input type="email" name="email" id="edit-email" required
                           class="w-full px-3 py-2.5 rounded-xl border border-outline-variant focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all text-body-sm outline-none bg-surface-container-lowest" />
                </div>
                <!-- Role -->
                <div>
                    <label for="edit-role" class="block text-body-sm font-semibold text-on-surface mb-1.5">Peran / Akses Sistem</label>
                    <?php if ($isAdmin ?? false): ?>
                        <select name="role" id="edit-role" required
                                class="w-full px-3 py-2.5 rounded-xl border border-outline-variant focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all text-body-sm outline-none bg-surface-container-lowest">
                            <option value="panitia">Panitia (Akses Terbatas ke Pendaftar)</option>
                            <option value="admin">Admin (Akses Penuh Pengaturan & User)</option>
                        </select>
                    <?php else: ?>
                        <input type="hidden" name="role" value="<?= ROLE_PANITIA ?>">
                        <div class="w-full px-3 py-2.5 rounded-xl border border-outline-variant bg-surface-container text-body-sm text-on-surface-variant">
                            Panitia &mdash; hanya dapat mengubah akun sendiri
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Password change section -->
                <div class="pt-4 border-t border-outline-variant/30">
                    <h4 class="font-semibold text-body-sm text-primary flex items-center gap-2 mb-3">
                        <span class="material-symbols-outlined text-[18px]">lock</span>
                        Ubah Kata Sandi
                    </h4>
                    
                    <div id="wrapper-password-lama" class="flex flex-col gap-1 mb-3 hidden">
                        <label for="password_lama" class="text-body-sm font-semibold text-on-surface">Kata Sandi Lama</label>
                        <input type="password" name="password_lama" id="password_lama"
                               class="w-full px-3 py-2.5 rounded-xl border border-outline-variant focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all text-body-sm outline-none bg-surface-container-lowest"
                               placeholder="Wajib diisi jika ganti sandi" />
                    </div>

                    <div class="flex flex-col gap-3">
                        <div class="flex flex-col gap-1">
                            <label for="password_baru" class="text-body-sm font-semibold text-on-surface">Kata Sandi Baru</label>
                            <input type="password" name="password_baru" id="password_baru" minlength="6"
                                   class="w-full px-3 py-2.5 rounded-xl border border-outline-variant focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all text-body-sm outline-none bg-surface-container-lowest"
                                   placeholder="Minimal 6 karakter" />
                        </div>
                        <div class="flex flex-col gap-1">
                            <label for="password_konfirmasi" class="text-body-sm font-semibold text-on-surface">Konfirmasi Kata Sandi Baru</label>
                            <input type="password" name="password_konfirmasi" id="password_konfirmasi" minlength="6"
                                   class="w-full px-3 py-2.5 rounded-xl border border-outline-variant focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all text-body-sm outline-none bg-surface-container-lowest"
                                   placeholder="Ulangi sandi baru" />
                        </div>
                    </div>
                    <p class="text-[10px] text-on-surface-variant mt-2 leading-tight">
                        * Kosongkan jika tidak ingin mengubah kata sandi.
                    </p>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="flex gap-2 px-6 pb-6 pt-3 border-t border-outline-variant/30 shrink-0">
                <button type="button" onclick="closeEditModal()"
                        class="flex-1 py-2.5 border border-outline-variant rounded-xl text-body-sm font-semibold text-on-surface hover:bg-surface-container transition-colors">
                    Batal
                </button>
                <button type="submit"
                        class="flex-1 py-2.5 bg-primary text-on-primary rounded-xl text-body-sm font-semibold hover:opacity-90 transition-opacity">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
