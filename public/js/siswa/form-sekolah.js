/**
 * public/js/siswa/form-sekolah.js
 * Autocomplete sekolah asal dan validasi form.
 *
 * Nilai yang dikirim ke server:
 * - nama_sekolah : nama teks sekolah (bukan ID)
 * - sekolah_id   : ID master_sekolah bila dipilih dari daftar
 * - npsn         : NPSN sekolah bila dipilih dari daftar
 *
 * Server tetap memverifikasi ulang ketiga nilai tersebut.
 */
document.addEventListener('DOMContentLoaded', function () {
    const form            = document.querySelector('form');
    const select          = document.getElementById('nama_sekolah');
    const hiddenName      = document.getElementById('nama_sekolah_value');
    const hiddenId        = document.getElementById('sekolah_id');
    const hiddenNpsn      = document.getElementById('npsn');
    const alamatSekolah   = document.getElementById('alamat_sekolah');

    const baseUrl = (function () {
        const configured = window.BASE_URL || '/';
        return configured.endsWith('/') ? configured : configured + '/';
    })();

    if (form) {
        form.addEventListener('submit', function (event) {
            const errors = [];
            const nama = hiddenName ? hiddenName.value.trim() : '';

            if (nama.length < 3) {
                errors.push('Nama sekolah asal minimal 3 karakter.');
                select?.classList.add('border-error');
            } else {
                select?.classList.remove('border-error');
            }

            if (alamatSekolah && alamatSekolah.value.trim().length < 5) {
                errors.push('Alamat sekolah asal minimal 5 karakter.');
                alamatSekolah.classList.add('border-error');
            } else if (alamatSekolah) {
                alamatSekolah.classList.remove('border-error');
            }

            if (errors.length) {
                event.preventDefault();
                if (typeof showFormAlert === 'function') {
                    showFormAlert(form, errors);
                }
            }
        });
    }

    if (!select || typeof TomSelect === 'undefined') {
        return;
    }

    const control = new TomSelect(select, {
        valueField: 'id',
        labelField: 'text',
        searchField: 'text',
        create: true,
        maxItems: 1,
        maxOptions: 30,
        preload: false,
        openOnFocus: true,
        loadThrottle: 300,
        placeholder: 'Ketik nama sekolah Anda...',
        load: function (query, callback) {
            if (query.trim().length < 3) {
                callback();
                return;
            }

            fetch(baseUrl + 'api/sekolah/search?q=' + encodeURIComponent(query), {
                headers: { 'Accept': 'application/json' },
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Permintaan gagal');
                    }
                    return response.json();
                })
                .then(function (json) {
                    callback(Array.isArray(json) ? json : []);
                })
                .catch(function () {
                    callback();
                });
        },
        onChange: function (value) {
            if (!value) {
                hiddenName.value = '';
                hiddenId.value   = '';
                hiddenNpsn.value = '';
                return;
            }

            const option = this.options[value];

            if (option && option.record_id) {
                hiddenName.value = option.text || option.value || '';
                hiddenId.value   = option.record_id;
                hiddenNpsn.value = option.npsn || '';

                if (option.alamat_lengkap && alamatSekolah && alamatSekolah.value.trim() === '') {
                    alamatSekolah.value = option.alamat_lengkap;
                }
            } else {
                // Sekolah baru yang diketik manual: tanpa ID dan NPSN.
                hiddenName.value = value;
                hiddenId.value   = '';
                hiddenNpsn.value = '';
            }
        },
        render: {
            option: function (data, escape) {
                const label  = data.text || data.value || '';
                const detail = [data.kecamatan, data.kabupaten].filter(Boolean).join(' · ');

                let html = '<div class="py-2 px-2 leading-tight">';
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
            option_create: function (data, escape) {
                return '<div class="create p-2 cursor-pointer bg-primary/10 text-primary font-semibold rounded-md mx-1 my-1 hover:bg-primary hover:text-white transition-colors">'
                    + 'Tambahkan sekolah baru "<strong>' + escape(data.input) + '</strong>"'
                    + '</div>';
            },
            no_results: function (data, escape) {
                return '<div class="no-results p-2 text-on-surface-variant italic">'
                    + 'Sekolah tidak ditemukan pada data induk. Ketik nama lengkap lalu pilih opsi menambahkan sekolah baru.'
                    + '</div>';
            },
        },
    });

    applyInitialValue(control);
});

/**
 * Menampilkan sekolah yang tersimpan sebelumnya sebagai pilihan aktif.
 */
function applyInitialValue(control) {
    const script = document.getElementById('sekolah-awal');

    if (!script) {
        return;
    }

    let initial;

    try {
        initial = JSON.parse(script.textContent || '{}');
    } catch (error) {
        return;
    }

    const text = (initial.text || '').trim();

    if (text === '') {
        return;
    }

    const value = String(initial.id || text);

    control.addOption({
        id:        value,
        record_id: initial.id ? Number(initial.id) : 0,
        text:      text,
        npsn:      initial.npsn || '',
    });

    control.setValue(value, true);
}
