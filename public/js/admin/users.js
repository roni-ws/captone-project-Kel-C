/**
 * public/js/admin/users.js
 * JavaScript for the Admin "Manajemen User" page.
 * Handles: Modals (Add, Edit, Change Password, Delete) and DataTables.
 *
 * Requires global BASE_URL set inline from PHP.
 */

// ----------------------------------------------------
// Modal 1: Tambah Staff
// ----------------------------------------------------
function openAddModal() {
    const modal = document.getElementById('add-modal');
    modal.style.display = 'flex';
}
function closeAddModal() {
    document.getElementById('add-modal').style.display = 'none';
}

// ----------------------------------------------------
// Modal 2: Edit Staff
// ----------------------------------------------------
function openEditModal(id, username, email, role, isSelf) {
    const modal = document.getElementById('edit-modal');
    const form = document.getElementById('edit-form');
    
    form.action = BASE_URL + 'admin/users/edit/' + id;
    document.getElementById('edit-username').value = username;
    document.getElementById('edit-email').value = email;
    document.getElementById('edit-is-self').value = isSelf ? '1' : '0';

    // Reset password fields
    document.getElementById('password_lama').value = '';
    document.getElementById('password_baru').value = '';
    document.getElementById('password_konfirmasi').value = '';

    // Show/hide password lama
    const wrapperOldPass = document.getElementById('wrapper-password-lama');
    if (isSelf) {
        wrapperOldPass.classList.remove('hidden');
        document.getElementById('password_lama').required = false; // logic in controller handles if new password filled
    } else {
        wrapperOldPass.classList.add('hidden');
        document.getElementById('password_lama').required = false;
    }

    // Select role hanya dirender untuk admin; panitia memakai hidden input.
    const roleEl = document.getElementById('edit-role');
    if (roleEl) roleEl.value = role;

    modal.style.display = 'flex';
}
function closeEditModal() {
    document.getElementById('edit-modal').style.display = 'none';
}



// ----------------------------------------------------
// Modal 4: Konfirmasi Hapus
// ----------------------------------------------------
function confirmDelete(id, username) {
    const modal = document.getElementById('delete-modal');
    document.getElementById('delete-username').innerText = username;
    document.getElementById('confirm-delete-btn').href = BASE_URL + 'admin/users/delete/' + id;
    modal.style.display = 'flex';
}
function closeDeleteModal() {
    document.getElementById('delete-modal').style.display = 'none';
}

// ----------------------------------------------------
// DataTables & Filter Initialization
// ----------------------------------------------------
$(document).ready(function() {
    const table = $('#users-table').DataTable({
        responsive: true,
        dom: 'tp', // layout: table + pagination only, custom search bar is used
        pageLength: 10,
        language: {
            zeroRecords: 'Staff tidak ditemukan.',
            paginate: {
                next: '<span class="material-symbols-outlined text-[16px] flex items-center">chevron_right</span>',
                previous: '<span class="material-symbols-outlined text-[16px] flex items-center">chevron_left</span>'
            }
        },
        drawCallback: function(settings) {
            // Relocate DataTable pagination markup to custom footer wrapper
            const paginateHtml = $('.dataTables_paginate').html();
            if (paginateHtml) {
                $('#dt-paginate-wrapper').html(
                    '<div class="dt-custom-pagination flex items-center gap-1">' + paginateHtml + '</div>'
                );
                $('.dataTables_paginate').hide();
            } else {
                $('#dt-paginate-wrapper').html('');
            }

            // Make pagination style match material components
            $('#dt-paginate-wrapper span a').addClass('px-3 py-1.5 rounded-xl border border-outline-variant/60 hover:bg-surface-container-low text-body-sm font-semibold transition-all');
            $('#dt-paginate-wrapper span a.current').addClass('bg-primary text-on-primary border-primary hover:bg-primary').removeClass('hover:bg-surface-container-low');
            $('#dt-paginate-wrapper > div > a').addClass('p-1.5 rounded-xl border border-outline-variant/60 hover:bg-surface-container-low transition-all flex items-center');

            // Update information label
            const info = this.api().page.info();
            const label = (typeof STAFF_LABEL !== 'undefined') ? STAFF_LABEL : 'data staff';
            if (info.recordsTotal > 0) {
                $('#table-info-text').text(
                    `Menampilkan ${info.start + 1} sampai ${info.end} dari ${info.recordsTotal} ${label}`
                );
            } else {
                $('#table-info-text').text(`Menampilkan 0 ${label}`);
            }
        }
    });

    // Search action
    $('#custom-search').on('keyup', function() {
        table.search(this.value).draw();
    });

    // Filter Role action
    $('#role-filter').on('change', function() {
        const role = this.value;
        // Column indices: Role is column 3 (0-indexed)
        table.column(3).search(role ? '^' + role + '$' : '', true, false).draw();
    });

    // Reset Filter
    $('#reset-filter').on('click', function() {
        $('#custom-search').val('');
        $('#role-filter').val('');
        table.search('').column(3).search('').draw();
    });
});
