<?= $this->extend('layouts/admin') ?>

<?= $this->section('title') ?>Manajemen User<?= $this->endSection() ?>
<?= $this->section('page-title') ?>Manajemen User<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="max-w-[1200px] mx-auto">

    <!-- ================================
         PAGE HEADER
         ================================ -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-headline-md font-bold text-primary"><?= $isAdmin ? 'Kelola Akun Staff' : 'Akun Saya' ?></h2>
            <p class="text-body-sm text-on-surface-variant mt-1">
                <?php if ($isAdmin): ?>
                    Kelola hak akses administrator dan panitia penerimaan siswa baru secara terpusat.
                    Total <strong class="text-on-surface"><?= count($staffs) ?></strong> akun staff terdaftar.
                <?php else: ?>
                    Perbarui username, email, dan kata sandi akun Anda sendiri. Anda tidak dapat melihat atau mengubah akun staff lain.
                <?php endif; ?>
            </p>
        </div>
        <?php if ($isAdmin): ?>
            <div>
                <button onclick="openAddModal()"
                        class="w-full sm:w-auto flex items-center justify-center gap-2 bg-primary text-on-primary font-semibold text-body-sm px-5 py-3 rounded-xl hover:opacity-90 transition-opacity shadow-md">
                    <span class="material-symbols-outlined text-[18px]">person_add</span> Tambah Staff Baru
                </button>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($isAdmin): ?>
    <!-- ================================
         FILTER BAR
         ================================ -->
    <div class="filter-bar bg-white rounded-2xl border border-outline-variant shadow-sm p-4 mb-4">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
            <!-- Search -->
            <div class="md:col-span-8 relative">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-xl">search</span>
                <input id="custom-search" type="text"
                       placeholder="Cari nama staff, username, atau email..."
                       class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-outline-variant focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all text-body-sm outline-none bg-surface-container-lowest" />
            </div>
            <!-- Role Filter -->
            <div class="md:col-span-3">
                <select id="role-filter"
                        class="w-full px-3 py-2.5 rounded-xl border border-outline-variant focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all text-body-sm outline-none bg-surface-container-lowest">
                    <option value="">Semua Peran (Role)</option>
                    <option value="ADMIN">ADMIN</option>
                    <option value="PANITIA">PANITIA</option>
                </select>
            </div>
            <!-- Reset -->
            <div class="md:col-span-1 flex justify-end">
                <button id="reset-filter" title="Reset filter"
                        class="w-full md:w-auto p-2.5 text-primary border border-outline-variant rounded-xl hover:bg-surface-container transition-colors flex justify-center items-center">
                    <span class="material-symbols-outlined">filter_list_off</span>
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ================================
         DATA TABLE
         ================================ -->
    <div class="bg-white rounded-2xl border border-outline-variant shadow-sm overflow-hidden">
        <div class="overflow-x-auto custom-scrollbar">
            <table id="users-table" class="w-full text-left border-collapse min-w-[800px]" style="width:100%">
                <thead>
                    <tr class="bg-surface-container-low border-b border-outline-variant">
                        <th class="p-3 font-semibold text-label-bold text-primary text-xs uppercase tracking-wider w-16">No</th>
                        <th class="p-3 font-semibold text-label-bold text-primary text-xs uppercase tracking-wider">Nama Staff (Username)</th>
                        <th class="p-3 font-semibold text-label-bold text-primary text-xs uppercase tracking-wider">Email Resmi</th>
                        <th class="p-3 font-semibold text-label-bold text-primary text-xs uppercase tracking-wider w-36">Peran (Role)</th>
                        <th class="p-3 font-semibold text-label-bold text-primary text-xs uppercase tracking-wider w-44">Tgl Dibuat</th>
                        <th class="p-3 font-semibold text-label-bold text-primary text-xs uppercase tracking-wider text-center w-40">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($staffs)): ?>
                        <?php foreach ($staffs as $i => $staff): ?>
                        <?php
                            $isSelf = ($authUserId == $staff['id']);
                            $username = esc($staff['username']);
                            $email = esc($staff['email']);
                            $roleName = strtoupper($staff['role']);
                            $inisial = strtoupper(mb_substr($username, 0, 2));

                            $roleClass = match($roleName) {
                                'ADMIN'       => 'bg-tertiary-container text-on-tertiary-container',
                                'PANITIA'     => 'bg-primary-container text-on-primary-container',
                                default       => 'bg-surface-container text-on-surface-variant border border-outline-variant/30',
                            };
                            $roleIcon = match($roleName) {
                                'ADMIN'       => 'admin_panel_settings',
                                'PANITIA'     => 'assignment_ind',
                                default       => 'person',
                            };

                            // Avatar colors
                            $avatarColors = ['bg-primary-container text-on-primary-container', 'bg-secondary-container text-on-secondary-container', 'bg-tertiary-container text-on-tertiary-container', 'bg-surface-container-high text-primary'];
                            $avatarClass  = $avatarColors[$i % count($avatarColors)];
                        ?>
                        <tr class="border-b border-outline-variant hover:bg-surface-container-low transition-colors">
                            <!-- No -->
                            <td class="p-3 text-body-sm text-on-surface-variant">
                                <?= $i + 1 ?>
                            </td>
                            <!-- Username -->
                            <td class="p-3">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-full <?= $avatarClass ?> flex items-center justify-center font-bold text-xs shrink-0">
                                        <?= $inisial ?>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-body-sm text-on-surface truncate">
                                            <?= $username ?>
                                            <?php if ($isSelf): ?>
                                                <span class="ml-1 text-[10px] font-bold bg-secondary text-on-secondary px-1.5 py-0.5 rounded-full uppercase">Anda</span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <!-- Email -->
                            <td class="p-3 text-body-sm text-on-surface">
                                <?= $email ?>
                            </td>
                            <!-- Role Badge -->
                            <td class="p-3">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold whitespace-nowrap <?= $roleClass ?>">
                                    <span class="material-symbols-outlined text-[14px]"><?= $roleIcon ?></span>
                                    <?= $roleName ?>
                                </span>
                            </td>
                            <!-- Created At -->
                            <td class="p-3 text-body-sm text-on-surface-variant whitespace-nowrap">
                                <?= date('d M Y, H:i', strtotime($staff['created_at'])) ?>
                            </td>
                            <!-- Actions -->
                            <td class="p-3 text-center">
                                <div class="flex items-center justify-center gap-0">
                                    <!-- Edit Akun (data & kata sandi) -->
                                    <button onclick="openEditModal(<?= (int) $staff['id'] ?>, '<?= esc($username, 'js') ?>', '<?= esc($email, 'js') ?>', '<?= esc($staff['role'], 'js') ?>', <?= $isSelf ? 'true' : 'false' ?>)"
                                            class="p-1 text-primary hover:bg-primary-container/20 rounded-full transition-colors"
                                            title="Ubah Data Akun &amp; Kata Sandi">
                                        <span class="material-symbols-outlined" style="font-size:16px">manage_accounts</span>
                                    </button>
                                    
                                    <!-- Delete Staff: hanya admin, dan tidak untuk akun sendiri -->
                                    <?php if ($isAdmin): ?>
                                        <?php if ($isSelf): ?>
                                            <button class="p-1 text-outline opacity-40 cursor-not-allowed rounded-full"
                                                    title="Tidak dapat menghapus akun Anda sendiri" disabled>
                                                <span class="material-symbols-outlined" style="font-size:16px">delete</span>
                                            </button>
                                        <?php else: ?>
                                            <button onclick="confirmDelete(<?= (int) $staff['id'] ?>, '<?= esc($username, 'js') ?>')"
                                                    class="p-1 text-error hover:bg-error-container/20 rounded-full transition-colors"
                                                    title="Hapus Akun Staff">
                                                <span class="material-symbols-outlined" style="font-size:16px">delete</span>
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="py-16 text-center text-on-surface-variant text-body-sm">
                                <span class="material-symbols-outlined text-5xl block mb-3 text-outline">group_off</span>
                                Belum ada data staff terdaftar.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Table Footer Info -->
        <div class="px-4 py-3 border-t border-outline-variant bg-surface-container-lowest flex items-center justify-between flex-wrap gap-2">
            <span class="text-caption text-on-surface-variant" id="table-info-text">
                Menampilkan <?= count($staffs) ?> <?= $isAdmin ? 'data staff' : 'data akun' ?>
            </span>
            <div id="dt-paginate-wrapper"></div>
        </div>
    </div>

</div>

<?php if ($isAdmin): ?>
    <?= $this->include('components/admin/users/modals/add_modal') ?>
<?php endif; ?>

<?= $this->include('components/admin/users/modals/edit_modal') ?>

<?php if ($isAdmin): ?>
    <?= $this->include('components/admin/users/modals/delete_modal') ?>
<?php endif; ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    const BASE_URL = '<?= base_url() ?>';
    const STAFF_LABEL = '<?= $isAdmin ? 'data staff' : 'data akun' ?>';
    const CURRENT_USER_ID = <?= (int) ($authUserId ?? 0) ?>;
</script>
<?= script_tag('js/admin/users.js') ?>
<?= $this->endSection() ?>
