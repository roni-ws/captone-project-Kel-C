<!-- ================================
     EDIT STUDENT MODAL
     ================================ -->
<div id="edit-modal"
     class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 p-4"
     style="display: none; align-items: center; justify-content: center; z-index: 9999;">
    <div class="bg-white rounded-2xl shadow-2xl w-full animate-in fade-in zoom-in-95 duration-200"
         style="margin: auto; max-height: 90vh; display: flex; flex-direction: column; width: 100%; max-width: 720px;">
        <!-- Modal Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-outline-variant shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-primary/10 text-primary flex items-center justify-center">
                    <span class="material-symbols-outlined text-primary">edit</span>
                </div>
                <div>
                    <h3 class="font-semibold text-body-md text-on-surface">Edit Data Calon Siswa</h3>
                    <p class="text-caption text-on-surface-variant">No. Registrasi: <span id="edit-no_registrasi" class="font-semibold text-primary">-</span></p>
                </div>
            </div>
            <button type="button" onclick="closeEditModal()" class="p-1.5 rounded-full hover:bg-surface-container transition-colors">
                <span class="material-symbols-outlined text-on-surface-variant">close</span>
            </button>
        </div>

        <!-- Modal Body -->
        <form id="edit-form" method="post" action="" novalidate style="display: flex; flex-direction: column; overflow: hidden; margin: 0;">
            <?= csrf_field() ?>
            <div class="px-6 py-5 overflow-y-auto custom-scrollbar" style="flex: 1 1 auto; max-height: 65vh;">
                <div class="space-y-md">

                    <!-- ======== AKUN LOGIN ======== -->
                    <div class="space-y-md">
                        <h4 class="font-label-bold text-body-md text-primary pb-xs border-b border-surface-variant/30 flex items-center gap-xs">
                            <span class="material-symbols-outlined">manage_accounts</span> Akun Login
                        </h4>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                            <div class="flex flex-col gap-xs">
                                <label class="font-label-bold text-body-sm text-on-surface" for="edit-username">Username <span class="text-error">*</span></label>
                                <input class="w-full px-sm py-3 border border-outline rounded-lg bg-surface font-body-md text-body-md text-on-surface focus:border-primary focus:ring-2 focus:ring-tertiary-fixed-dim/30 transition-all"
                                       id="edit-username" name="username" required type="text" autocomplete="off"/>
                                <p class="text-caption text-on-surface-variant">Huruf, angka, dan titik. Minimal 3 karakter.</p>
                            </div>

                            <div class="flex flex-col gap-xs">
                                <label class="font-label-bold text-body-sm text-on-surface">NISN</label>
                                <div class="w-full px-sm py-3 border border-outline-variant rounded-lg bg-surface-container-low font-body-md text-body-md text-on-surface-variant">
                                    <span id="edit-nisn">-</span>
                                </div>
                                <p class="text-caption text-on-surface-variant">NISN tidak dapat diubah.</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                            <div class="flex flex-col gap-xs">
                                <label class="font-label-bold text-body-sm text-on-surface" for="edit-password">Password Baru</label>
                                <input class="w-full px-sm py-3 border border-outline rounded-lg bg-surface font-body-md text-body-md text-on-surface focus:border-primary focus:ring-2 focus:ring-tertiary-fixed-dim/30 transition-all"
                                       id="edit-password" name="password" type="password" autocomplete="new-password"/>
                                <p class="text-caption text-on-surface-variant">Kosongkan jika tidak ingin mengubah password.</p>
                            </div>

                            <div class="flex flex-col gap-xs">
                                <label class="font-label-bold text-body-sm text-on-surface" for="edit-password_confirm">Konfirmasi Password</label>
                                <input class="w-full px-sm py-3 border border-outline rounded-lg bg-surface font-body-md text-body-md text-on-surface focus:border-primary focus:ring-2 focus:ring-tertiary-fixed-dim/30 transition-all"
                                       id="edit-password_confirm" name="password_confirm" type="password" autocomplete="new-password"/>
                                <p class="text-caption text-on-surface-variant">Wajib diisi jika password baru diisi.</p>
                            </div>
                        </div>
                    </div>

                    <!-- ======== BIODATA DIRI ======== -->
                    <div class="space-y-md pt-sm border-t border-surface-variant/30">
                        <h4 class="font-label-bold text-body-md text-primary mb-xs flex items-center gap-xs">
                            <span class="material-symbols-outlined">person</span> Biodata Diri
                        </h4>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                            <div class="flex flex-col gap-xs">
                                <label class="font-label-bold text-body-sm text-on-surface" for="edit-nama_lengkap">Nama Lengkap <span class="text-error">*</span></label>
                                <input class="w-full px-sm py-3 border border-outline rounded-lg bg-surface font-body-md text-body-md text-on-surface focus:border-primary focus:ring-2 focus:ring-tertiary-fixed-dim/30 transition-all"
                                       id="edit-nama_lengkap" name="nama_lengkap" required type="text"/>
                            </div>

                            <div class="flex flex-col gap-xs">
                                <label class="font-label-bold text-body-sm text-on-surface" for="edit-nik">NIK <span class="text-error">*</span></label>
                                <input class="w-full px-sm py-3 border border-outline rounded-lg bg-surface font-body-md text-body-md text-on-surface focus:border-primary focus:ring-2 focus:ring-tertiary-fixed-dim/30 transition-all"
                                       id="edit-nik" name="nik" pattern="\d{16}" maxlength="16" required type="text"/>
                            </div>

                            <div class="flex flex-col gap-xs">
                                <label class="font-label-bold text-body-sm text-on-surface" for="edit-no_kk">Nomor KK <span class="text-error">*</span></label>
                                <input class="w-full px-sm py-3 border border-outline rounded-lg bg-surface font-body-md text-body-md text-on-surface focus:border-primary focus:ring-2 focus:ring-tertiary-fixed-dim/30 transition-all"
                                       id="edit-no_kk" name="no_kk" pattern="\d{16}" maxlength="16" required type="text"/>
                            </div>

                            <div class="flex flex-col gap-xs">
                                <label class="font-label-bold text-body-sm text-on-surface" for="edit-tempat_lahir">Tempat Lahir <span class="text-error">*</span></label>
                                <input class="w-full px-sm py-3 border border-outline rounded-lg bg-surface font-body-md text-body-md text-on-surface focus:border-primary focus:ring-2 focus:ring-tertiary-fixed-dim/30 transition-all"
                                       id="edit-tempat_lahir" name="tempat_lahir" required type="text"/>
                            </div>

                            <div class="flex flex-col gap-xs">
                                <label class="font-label-bold text-body-sm text-on-surface" for="edit-tanggal_lahir">Tanggal Lahir <span class="text-error">*</span></label>
                                <input class="w-full px-sm py-3 border border-outline rounded-lg bg-surface font-body-md text-body-md text-on-surface focus:border-primary focus:ring-2 focus:ring-tertiary-fixed-dim/30 transition-all"
                                       id="edit-tanggal_lahir" name="tanggal_lahir" required type="date"/>
                            </div>

                            <div class="flex flex-col gap-xs">
                                <label class="font-label-bold text-body-sm text-on-surface" for="edit-no_hp">No. Handphone</label>
                                <input class="w-full px-sm py-3 border border-outline rounded-lg bg-surface font-body-md text-body-md text-on-surface focus:border-primary focus:ring-2 focus:ring-tertiary-fixed-dim/30 transition-all"
                                       id="edit-no_hp" name="no_hp" type="text"/>
                            </div>

                            <div class="flex flex-col gap-xs md:col-span-2">
                                <label class="font-label-bold text-body-sm text-on-surface">Jenis Kelamin <span class="text-error">*</span></label>
                                <div class="flex flex-col sm:flex-row gap-sm">
                                    <label class="relative flex-1 cursor-pointer group">
                                        <input class="peer sr-only" name="jenis_kelamin" type="radio" value="L"/>
                                        <div class="w-full px-md py-3 border border-outline rounded-lg flex items-center justify-center gap-sm bg-surface transition-all peer-checked:border-primary peer-checked:bg-surface-container-low group-hover:border-primary">
                                            <span class="material-symbols-outlined text-outline-variant peer-checked:text-primary transition-colors">male</span>
                                            <span class="font-label-bold text-body-sm text-on-surface-variant peer-checked:text-primary transition-colors">Laki-laki</span>
                                        </div>
                                    </label>
                                    <label class="relative flex-1 cursor-pointer group">
                                        <input class="peer sr-only" name="jenis_kelamin" type="radio" value="P"/>
                                        <div class="w-full px-md py-3 border border-outline rounded-lg flex items-center justify-center gap-sm bg-surface transition-all peer-checked:border-primary peer-checked:bg-surface-container-low group-hover:border-primary">
                                            <span class="material-symbols-outlined text-outline-variant peer-checked:text-primary transition-colors">female</span>
                                            <span class="font-label-bold text-body-sm text-on-surface-variant peer-checked:text-primary transition-colors">Perempuan</span>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div class="flex flex-col gap-xs">
                                <label class="font-label-bold text-body-sm text-on-surface" for="edit-anak_ke">Anak Ke- <span class="text-error">*</span></label>
                                <input class="w-full px-sm py-3 border border-outline rounded-lg bg-surface font-body-md text-body-md text-on-surface focus:border-primary focus:ring-2 focus:ring-tertiary-fixed-dim/30 transition-all"
                                       id="edit-anak_ke" name="anak_ke" min="1" required type="number"/>
                            </div>

                            <div class="flex flex-col gap-xs">
                                <label class="font-label-bold text-body-sm text-on-surface" for="edit-jumlah_saudara">Jumlah Saudara <span class="text-error">*</span></label>
                                <input class="w-full px-sm py-3 border border-outline rounded-lg bg-surface font-body-md text-body-md text-on-surface focus:border-primary focus:ring-2 focus:ring-tertiary-fixed-dim/30 transition-all"
                                       id="edit-jumlah_saudara" name="jumlah_saudara" min="0" required type="number"/>
                            </div>
                        </div>
                    </div>

                    <!-- ======== ALAMAT ======== -->
                    <div class="space-y-md pt-sm border-t border-surface-variant/30">
                        <h4 class="font-label-bold text-body-md text-primary mb-xs flex items-center gap-xs">
                            <span class="material-symbols-outlined">home</span> Alamat Domisili
                        </h4>

                        <div class="flex flex-col gap-xs">
                            <label class="font-label-bold text-body-sm text-on-surface" for="edit-alamat_desa">Desa <span class="text-error">*</span></label>
                            <input class="w-full px-sm py-3 border border-outline rounded-lg bg-surface font-body-md text-body-md text-on-surface focus:border-primary focus:ring-2 focus:ring-tertiary-fixed-dim/30 transition-all"
                                   id="edit-alamat_desa" name="alamat_desa" required type="text"/>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                            <div class="flex flex-col gap-xs">
                                <label class="font-label-bold text-body-sm text-on-surface" for="edit-alamat_kecamatan">Kecamatan <span class="text-error">*</span></label>
                                <input class="w-full px-sm py-3 border border-outline rounded-lg bg-surface font-body-md text-body-md text-on-surface focus:border-primary focus:ring-2 focus:ring-tertiary-fixed-dim/30 transition-all"
                                       id="edit-alamat_kecamatan" name="alamat_kecamatan" required type="text"/>
                            </div>

                            <div class="flex flex-col gap-xs">
                                <label class="font-label-bold text-body-sm text-on-surface" for="edit-alamat_kabupaten">Kabupaten / Kota <span class="text-error">*</span></label>
                                <input class="w-full px-sm py-3 border border-outline rounded-lg bg-surface font-body-md text-body-md text-on-surface focus:border-primary focus:ring-2 focus:ring-tertiary-fixed-dim/30 transition-all"
                                       id="edit-alamat_kabupaten" name="alamat_kabupaten" required type="text"/>
                            </div>
                        </div>
                    </div>

                    <!-- ======== ASAL SEKOLAH ======== -->
                    <div class="space-y-md pt-sm border-t border-surface-variant/30">
                        <h4 class="font-label-bold text-body-md text-primary mb-xs flex items-center gap-xs">
                            <span class="material-symbols-outlined">school</span> Asal Sekolah
                        </h4>

                        <div class="flex flex-col gap-xs">
                            <label class="font-label-bold text-body-sm text-on-surface" for="edit-nama_sekolah">Nama Sekolah Asal <span class="text-error">*</span></label>
                            <input type="hidden" id="edit-sekolah_id" name="sekolah_id" value="">
                            <input type="hidden" id="edit-npsn" name="npsn" value="">
                            <input type="hidden" id="edit-nama_sekolah_value" name="nama_sekolah" value="">
                            <select id="edit-nama_sekolah" class="w-full" placeholder="Ketik nama sekolah..."></select>
                        </div>

                        <div class="flex flex-col gap-xs">
                            <label class="font-label-bold text-body-sm text-on-surface" for="edit-alamat_sekolah">Alamat Sekolah Asal <span class="text-error">*</span></label>
                            <textarea class="w-full px-sm py-3 border border-outline rounded-lg bg-surface font-body-md text-body-md text-on-surface focus:border-primary focus:ring-2 focus:ring-tertiary-fixed-dim/30 transition-all min-h-[80px]"
                                      id="edit-alamat_sekolah" name="alamat_sekolah" required></textarea>
                        </div>
                    </div>

                    <!-- ======== DATA ORANG TUA ======== -->
                    <div class="space-y-md pt-sm border-t border-surface-variant/30">
                        <h4 class="font-label-bold text-body-md text-primary mb-xs flex items-center gap-xs">
                            <span class="material-symbols-outlined">family_history</span> Data Orang Tua
                        </h4>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                            <div class="flex flex-col gap-xs">
                                <label class="font-label-bold text-body-sm text-on-surface" for="edit-nama_ayah">Nama Ayah <span class="text-error">*</span></label>
                                <input class="w-full px-sm py-3 border border-outline rounded-lg bg-surface font-body-md text-body-md text-on-surface focus:border-primary focus:ring-2 focus:ring-tertiary-fixed-dim/30 transition-all"
                                       id="edit-nama_ayah" name="nama_ayah" required type="text"/>
                            </div>

                            <div class="flex flex-col gap-xs">
                                <label class="font-label-bold text-body-sm text-on-surface" for="edit-pekerjaan_ayah">Pekerjaan Ayah <span class="text-error">*</span></label>
                                <input class="w-full px-sm py-3 border border-outline rounded-lg bg-surface font-body-md text-body-md text-on-surface focus:border-primary focus:ring-2 focus:ring-tertiary-fixed-dim/30 transition-all"
                                       id="edit-pekerjaan_ayah" name="pekerjaan_ayah" required type="text"/>
                            </div>

                            <div class="flex flex-col gap-xs">
                                <label class="font-label-bold text-body-sm text-on-surface" for="edit-nama_ibu">Nama Ibu <span class="text-error">*</span></label>
                                <input class="w-full px-sm py-3 border border-outline rounded-lg bg-surface font-body-md text-body-md text-on-surface focus:border-primary focus:ring-2 focus:ring-tertiary-fixed-dim/30 transition-all"
                                       id="edit-nama_ibu" name="nama_ibu" required type="text"/>
                            </div>

                            <div class="flex flex-col gap-xs">
                                <label class="font-label-bold text-body-sm text-on-surface" for="edit-pekerjaan_ibu">Pekerjaan Ibu <span class="text-error">*</span></label>
                                <input class="w-full px-sm py-3 border border-outline rounded-lg bg-surface font-body-md text-body-md text-on-surface focus:border-primary focus:ring-2 focus:ring-tertiary-fixed-dim/30 transition-all"
                                       id="edit-pekerjaan_ibu" name="pekerjaan_ibu" required type="text"/>
                            </div>
                        </div>
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
