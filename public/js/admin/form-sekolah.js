/**
 * public/js/admin/form-sekolah.js
 * Impor langsung berkas data sekolah dan modal edit entri.
 */
document.addEventListener('DOMContentLoaded', function () {
    initImportForm();
    initEditModal();
    initDeleteModal();
    initBulkSelection();
    initBulkDeleteModal();
});

/* ── Impor Berkas ─────────────────────────────────────────── */
function initImportForm() {
    var form = document.getElementById('sekolah-import-form');
    if (!form) return;

    var fileInput = document.getElementById('berkas');
    var btnImport = document.getElementById('btn-import');
    var statusBox = document.getElementById('import-status');

    if (!fileInput || !btnImport || !statusBox) return;

    var filenameLabel = document.getElementById('berkas-filename');
    fileInput.addEventListener('change', function () {
        if (!filenameLabel) return;
        var selected = fileInput.files && fileInput.files[0];
        filenameLabel.textContent = selected ? selected.name : 'Belum ada berkas dipilih';
    });

    btnImport.addEventListener('click', submitImport);

    function submitImport() {
        var file = fileInput.files && fileInput.files[0];

        if (!file) {
            showStatus('error', 'Pilih berkas .xlsx atau .csv terlebih dahulu.');
            return;
        }

        var formData = new FormData();
        formData.append('berkas', file);
        formData.append(window.CSRF_TOKEN_NAME || 'csrf_test_name', window.CSRF_HASH || '');

        setBusy(true);
        showStatus('info', 'Mengimpor data, mohon tunggu...');

        fetch((window.BASE_URL || '/') + 'admin/sekolah/import', {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': window.CSRF_HASH || ''
            }
        })
            .then(function (response) {
                return response.json().then(function (payload) {
                    return { ok: response.ok, payload: payload };
                });
            })
            .then(function (result) {
                if (!result.ok || result.payload.status !== 'success') {
                    showStatus('error', result.payload.message || 'Impor gagal diproses.');
                    return;
                }

                var report = result.payload.report || {};
                showStatus(
                    'success',
                    result.payload.message || ('Impor selesai: ' + (report.imported || 0) + ' sekolah baru, ' + (report.updated || 0) + ' diperbarui.')
                );

                // Muat ulang agar tabel dan saran wilayah menampilkan data terbaru.
                window.setTimeout(function () {
                    window.location.reload();
                }, 1500);
            })
            .catch(function () {
                showStatus('error', 'Terjadi kesalahan jaringan. Coba lagi.');
            })
            .finally(function () {
                setBusy(false);
            });
    }

    function setBusy(busy) {
        btnImport.disabled = busy;
        btnImport.classList.toggle('opacity-50', busy);
    }

    function showStatus(type, message) {
        var styles = {
            info: 'border-outline-variant bg-surface-container-low text-on-surface',
            success: 'border-secondary-container bg-secondary-container/30 text-on-surface',
            error: 'border-error-container bg-error-container/30 text-on-error-container'
        };

        statusBox.className = 'mt-4 rounded-xl border px-4 py-3 text-body-sm ' + (styles[type] || styles.info);
        statusBox.textContent = message;
        statusBox.classList.remove('hidden');
    }
}

/* ── Modal Edit ───────────────────────────────────────────── */
function initEditModal() {
    var modal = document.getElementById('edit-sekolah-modal');
    var form = document.getElementById('edit-sekolah-form');

    if (!modal || !form) return;

    var fields = ['nama_sekolah', 'nama_provinsi', 'nama_kabupaten', 'nama_kecamatan', 'nama_desa', 'alamat_jalan'];

    document.querySelectorAll('.btn-edit-sekolah').forEach(function (button) {
        button.addEventListener('click', function () {
            var data = parseDataset(button);

            if (!data || !data.id) return;

            form.action = (window.BASE_URL || '/') + 'admin/sekolah/update/' + data.id;

            fields.forEach(function (field) {
                var input = document.getElementById('edit-' + field);
                if (input) input.value = data[field] || '';
            });

            var npsnLabel = document.getElementById('edit-sekolah-npsn');
            if (npsnLabel) npsnLabel.textContent = data.npsn || '-';

            openModal(modal);
        });
    });

    modal.querySelectorAll('.btn-close-edit').forEach(function (button) {
        button.addEventListener('click', function () {
            closeModal(modal);
        });
    });

    modal.addEventListener('click', function (event) {
        if (event.target === modal) closeModal(modal);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal.style.display === 'flex') {
            closeModal(modal);
        }
    });

    function parseDataset(button) {
        try {
            return JSON.parse(button.dataset.sekolah || '{}');
        } catch (error) {
            return null;
        }
    }
}

/* ── Picker sekolah (Tom Select remote /api/sekolah/search) ── */
function createSekolahPicker(selectId, excludeIdsFn) {
    var selectEl = document.getElementById(selectId.replace('#', ''));
    if (typeof TomSelect === 'undefined' || !selectEl) return null;

    return new TomSelect(selectId, {
        valueField: 'id',
        labelField: 'text',
        searchField: 'text',
        create: false,
        maxOptions: 30,
        preload: false,
        openOnFocus: true,
        loadThrottle: 300,
        placeholder: 'Ketik nama sekolah...',
        load: function (query, callback) {
            if (!query || query.trim().length < 3) {
                callback();
                return;
            }

            var base = window.BASE_URL || '/';
            fetch(base + 'api/sekolah/search?q=' + encodeURIComponent(query), {
                headers: { 'Accept': 'application/json' }
            })
                .then(function (response) { return response.ok ? response.json() : []; })
                .then(function (json) {
                    var items    = Array.isArray(json) ? json : [];
                    var excluded = (typeof excludeIdsFn === 'function' ? excludeIdsFn() : []) || [];
                    callback(items.filter(function (item) {
                        return excluded.indexOf(Number(item.id)) === -1;
                    }));
                })
                .catch(function () { callback(); });
        },
        render: {
            option: function (data, escape) {
                var label  = data.text || data.value || '';
                var detail = [data.kecamatan, data.kabupaten].filter(Boolean).join(' - ');
                var html   = '<div class="py-2 px-2 leading-tight">';
                html += '<div class="font-semibold text-on-surface">' + escape(label) + '</div>';
                if (detail) {
                    html += '<div class="text-xs text-on-surface-variant mt-0.5">' + escape(detail) + '</div>';
                }
                if (data.npsn) {
                    html += '<div class="text-[11px] text-outline mt-0.5">NPSN ' + escape(String(data.npsn)) + '</div>';
                }
                return html + '</div>';
            },
            item: function (data, escape) {
                return '<div>' + escape(data.text || data.value || '') + '</div>';
            },
            no_results: function (data, escape) {
                return '<div class="no-results p-2 text-on-surface-variant italic">Sekolah tidak ditemukan.</div>';
            }
        }
    });
}

/* ── Modal Hapus (reassign pendaftar) ─────────────────────── */
function initDeleteModal() {
    var modal = document.getElementById('delete-sekolah-modal');
    if (!modal) return;

    var form         = document.getElementById('delete-sekolah-form');
    var namaEl       = document.getElementById('delete-sekolah-nama');
    var usageBlock   = document.getElementById('delete-usage-block');
    var usageCountEl = document.getElementById('delete-usage-count');
    var unusedNote   = document.getElementById('delete-unused-note');
    var movePicker   = document.getElementById('delete-move-picker');
    var moveError    = document.getElementById('delete-move-error');
    var radios       = modal.querySelectorAll('input[name="reassign_action"]');
    var currentId    = null;
    var targetSelect = null;

    targetSelect = createSekolahPicker('#reassign-target', function () {
        return currentId === null ? [] : [Number(currentId)];
    });

    if (targetSelect) targetSelect.disable();

    function syncMovePicker() {
        var moveSelected = false;
        radios.forEach(function (radio) {
            if (radio.checked && radio.value === 'move') moveSelected = true;
        });

        if (targetSelect) {
            if (moveSelected) {
                targetSelect.enable();
            } else {
                targetSelect.disable();
            }
        }

        movePicker.classList.toggle('opacity-50', !moveSelected);
        movePicker.classList.toggle('pointer-events-none', !moveSelected);
    }

    radios.forEach(function (radio) {
        radio.addEventListener('change', syncMovePicker);
    });

    document.querySelectorAll('.btn-delete-sekolah').forEach(function (button) {
        button.addEventListener('click', function () {
            var data = parseDeleteDataset(button);
            if (!data || !data.id) return;

            currentId = data.id;
            form.action = (window.BASE_URL || '/') + 'admin/sekolah/delete/' + data.id;
            namaEl.textContent = data.nama || '-';

            var usage = Number(data.usage || 0);
            if (usage > 0) {
                usageCountEl.textContent = usage;
                usageBlock.classList.remove('hidden');
                unusedNote.classList.add('hidden');
            } else {
                usageBlock.classList.add('hidden');
                unusedNote.classList.remove('hidden');
            }

            radios.forEach(function (radio) {
                radio.checked = radio.value === 'default';
            });

            if (targetSelect) targetSelect.clear(true);
            moveError.classList.add('hidden');
            syncMovePicker();
            openModal(modal);
        });
    });

    form.addEventListener('submit', function (event) {
        if (usageBlock.classList.contains('hidden')) return; // Belum dipakai pendaftar.

        var moveSelected = false;
        radios.forEach(function (radio) {
            if (radio.checked && radio.value === 'move') moveSelected = true;
        });

        if (moveSelected) {
            var value = targetSelect ? targetSelect.getValue() : '';
            if (!value) {
                event.preventDefault();
                moveError.classList.remove('hidden');
                return;
            }
        }

        moveError.classList.add('hidden');
    });

    modal.querySelectorAll('.btn-close-delete').forEach(function (button) {
        button.addEventListener('click', function () {
            closeModal(modal);
        });
    });

    modal.addEventListener('click', function (event) {
        if (event.target === modal) closeModal(modal);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal.style.display === 'flex') {
            closeModal(modal);
        }
    });

    function parseDeleteDataset(button) {
        try {
            return JSON.parse(button.getAttribute('data-sekolah') || '{}');
        } catch (error) {
            return null;
        }
    }
}

/* ── Bulk Selection (checkbox) ────────────────────────────── */
function initBulkSelection() {
    var selectAll = document.getElementById('select-all-sekolah');
    var toolbar   = document.getElementById('bulk-toolbar-sekolah');
    var countEl   = document.getElementById('selected-sekolah-count');
    var clearBtn  = document.getElementById('bulk-clear-sekolah-btn');
    if (!toolbar || !countEl) return;

    function rowCheckboxes() {
        return Array.prototype.slice.call(document.querySelectorAll('.row-select-sekolah'));
    }

    function updateSelection() {
        var boxes    = rowCheckboxes();
        var selected = boxes.filter(function (cb) { return cb.checked; });
        countEl.textContent = selected.length;

        if (selected.length > 0) {
            toolbar.classList.remove('hidden');
            toolbar.classList.add('flex');
        } else {
            toolbar.classList.add('hidden');
            toolbar.classList.remove('flex');
        }

        if (selectAll) {
            selectAll.checked       = selected.length > 0 && selected.length === boxes.length;
            selectAll.indeterminate = selected.length > 0 && selected.length < boxes.length;
        }
    }

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            rowCheckboxes().forEach(function (cb) { cb.checked = selectAll.checked; });
            updateSelection();
        });
    }

    document.addEventListener('change', function (event) {
        if (event.target
            && event.target.classList
            && event.target.classList.contains('row-select-sekolah')) {
            updateSelection();
        }
    });

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            rowCheckboxes().forEach(function (cb) { cb.checked = false; });
            updateSelection();
        });
    }
}

/* ── Modal Hapus Massal ────────────────────────────────────── */
function initBulkDeleteModal() {
    var modal = document.getElementById('bulk-delete-sekolah-modal');
    if (!modal) return;

    var form         = document.getElementById('bulk-delete-sekolah-form');
    var idsBox       = document.getElementById('bulk-ids-container');
    var deleteBtn    = document.getElementById('bulk-delete-sekolah-btn');
    var countEl      = document.getElementById('bulk-delete-count');
    var usageBlock   = document.getElementById('bulk-usage-block');
    var usageCountEl = document.getElementById('bulk-usage-count');
    var unusedNote   = document.getElementById('bulk-unused-note');
    var movePicker   = document.getElementById('bulk-move-picker');
    var moveError    = document.getElementById('bulk-move-error');
    var radios       = modal.querySelectorAll('input[name="reassign_action"]');

    function selectedRows() {
        return Array.prototype.slice.call(document.querySelectorAll('.row-select-sekolah:checked'));
    }

    var targetSelect = createSekolahPicker('#bulk-reassign-target', function () {
        return selectedRows().map(function (cb) { return Number(cb.value); });
    });

    if (targetSelect) targetSelect.disable();

    function syncMovePicker() {
        var moveSelected = false;
        radios.forEach(function (radio) {
            if (radio.checked && radio.value === 'move') moveSelected = true;
        });

        if (targetSelect) {
            if (moveSelected) {
                targetSelect.enable();
            } else {
                targetSelect.disable();
            }
        }

        movePicker.classList.toggle('opacity-50', !moveSelected);
        movePicker.classList.toggle('pointer-events-none', !moveSelected);
    }

    radios.forEach(function (radio) {
        radio.addEventListener('change', syncMovePicker);
    });

    if (deleteBtn) {
        deleteBtn.addEventListener('click', function () {
            var rows = selectedRows();

            if (rows.length === 0) {
                if (typeof showToast === 'function') {
                    showToast('Pilih minimal satu sekolah.', 'error');
                }
                return;
            }

            // Siapkan daftar ID dan ringkasan pemakaian pendaftar.
            idsBox.innerHTML = '';
            var usageSum = 0;
            rows.forEach(function (cb) {
                var input   = document.createElement('input');
                input.type  = 'hidden';
                input.name  = 'ids[]';
                input.value = cb.value;
                idsBox.appendChild(input);
                usageSum += Number(cb.getAttribute('data-usage') || 0);
            });
            countEl.textContent = rows.length;

            if (usageSum > 0) {
                usageCountEl.textContent = usageSum;
                usageBlock.classList.remove('hidden');
                unusedNote.classList.add('hidden');
            } else {
                usageBlock.classList.add('hidden');
                unusedNote.classList.remove('hidden');
            }

            radios.forEach(function (radio) {
                radio.checked = radio.value === 'default';
            });
            if (targetSelect) targetSelect.clear(true);
            moveError.classList.add('hidden');
            syncMovePicker();
            openModal(modal);
        });
    }

    form.addEventListener('submit', function (event) {
        if (usageBlock.classList.contains('hidden')) return; // Tidak ada pemakaian, langsung hapus.

        var moveSelected = false;
        radios.forEach(function (radio) {
            if (radio.checked && radio.value === 'move') moveSelected = true;
        });

        if (moveSelected) {
            var value = targetSelect ? targetSelect.getValue() : '';
            if (!value) {
                event.preventDefault();
                moveError.classList.remove('hidden');
                return;
            }
        }

        moveError.classList.add('hidden');
    });

    modal.querySelectorAll('.btn-close-bulk-delete').forEach(function (button) {
        button.addEventListener('click', function () {
            closeModal(modal);
        });
    });

    modal.addEventListener('click', function (event) {
        if (event.target === modal) closeModal(modal);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal.style.display === 'flex') {
            closeModal(modal);
        }
    });
}

function openModal(modal) {
    modal.style.display = 'flex';
}

function closeModal(modal) {
    modal.style.display = 'none';
}
