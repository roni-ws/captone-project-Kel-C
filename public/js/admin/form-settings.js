/**
 * public/js/admin/form-settings.js
 * JavaScript for the Admin "Pengaturan Website" page.
 * Handles section navigation (sticky sidebar + scrollspy), image previews,
 * announcement modals, and Quill Editor synchronization.
 */

// Styling states for the settings sidebar navigation items.
const SETTINGS_NAV_ACTIVE = ['bg-secondary-container', 'text-on-secondary-container', 'font-bold', 'shadow-sm'];
const SETTINGS_NAV_INACTIVE = ['text-on-surface-variant', 'hover:text-on-surface', 'hover:bg-surface-container-high'];

/**
 * Find the nearest scrollable ancestor of an element.
 * Layout admin memakai div overflow-y-auto, bukan window, sebagai container scroll.
 */
function findScrollContainer(element) {
    let node = element ? element.parentElement : null;

    while (node && node !== document.body) {
        const style = window.getComputedStyle(node);
        if (/(auto|scroll)/.test(style.overflowY)) {
            return node;
        }
        node = node.parentElement;
    }

    return document.scrollingElement || document.documentElement;
}

/**
 * Preview uploaded images in the file browser
 */
function previewImage(input, previewId, placeholderId) {
    const file = input.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = document.getElementById(previewId);
            img.src = e.target.result;
            img.classList.remove('hidden');
            
            const placeholder = document.getElementById(placeholderId);
            if (placeholder) {
                placeholder.classList.add('hidden');
            }
        }
        reader.readAsDataURL(file);
    }
}

// =========================================================================
// MODAL HANDLERS FOR ANNOUNCEMENTS
// =========================================================================
function openAnnouncementModal() {
    const modal = document.getElementById('modal-announcement');
    modal.classList.remove('hidden');
    setTimeout(() => {
        modal.classList.add('opacity-100');
        modal.querySelector('.bg-surface').classList.remove('scale-95');
        modal.querySelector('.bg-surface').classList.add('scale-100');
    }, 10);
}

function closeAnnouncementModal() {
    const modal = document.getElementById('modal-announcement');
    modal.classList.remove('opacity-100');
    modal.querySelector('.bg-surface').classList.add('scale-95');
    modal.querySelector('.bg-surface').classList.remove('scale-100');
    setTimeout(() => {
        modal.classList.add('hidden');
        // Reset input values
        document.getElementById('judul').value = '';
        document.getElementById('konten').value = '';
    }, 300);
}

function confirmDeleteAnnouncement(id) {
    if (confirm('Apakah Anda yakin ingin menghapus pengumuman pendaftaran ini secara permanen? Calon siswa tidak akan melihat pengumuman ini lagi.')) {
        fetch(BASE_URL + 'admin/settings/announcement/delete/' + id, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: new URLSearchParams({ [CSRF_TOKEN]: CSRF_HASH })
        }).then(function (r) { return r.json(); })
          .then(function (data) {
              location.reload();
          });
    }
}

// =========================================================================
// SETTINGS SECTION NAVIGATION (STICKY SIDEBAR + SCROLLSPY)
// =========================================================================
function initSettingsSectionNav() {
    const nav = document.getElementById('settings-nav');
    const form = document.getElementById('settings-form');
    if (!nav || !form) return;

    const items = Array.from(nav.querySelectorAll('.settings-nav-item'));
    const entries = items
        .map((item) => ({ item, section: document.querySelector(item.getAttribute('href')) }))
        .filter((entry) => entry.section !== null);

    if (entries.length === 0) return;

    const scrollRoot = findScrollContainer(form);
    const isRootDocument = scrollRoot === document.scrollingElement || scrollRoot === document.documentElement;
    const scrollTarget = isRootDocument ? window : scrollRoot;
    const OFFSET = 96; // tinggi navbar sticky + jarak nyaman
    let activeHref = null;
    let isProgrammaticScroll = false;
    let ticking = false;

    const getScrollTop = () => (isRootDocument ? window.scrollY : scrollRoot.scrollTop);
    const getRootTop = () => (isRootDocument ? 0 : scrollRoot.getBoundingClientRect().top);
    const getSectionTop = (section) => section.getBoundingClientRect().top - getRootTop() + getScrollTop();

    function setActive(href) {
        if (href === activeHref) return;
        activeHref = href;

        entries.forEach(({ item }) => {
            const isActive = item.getAttribute('href') === href;
            SETTINGS_NAV_ACTIVE.forEach((className) => item.classList.toggle(className, isActive));
            SETTINGS_NAV_INACTIVE.forEach((className) => item.classList.toggle(className, !isActive));
        });
    }

    function isNearBottom() {
        if (isRootDocument) {
            return window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 40;
        }
        return scrollRoot.scrollTop + scrollRoot.clientHeight >= scrollRoot.scrollHeight - 40;
    }

    function updateActiveByScroll() {
        ticking = false;
        if (isProgrammaticScroll) return;

        if (isNearBottom()) {
            setActive(entries[entries.length - 1].item.getAttribute('href'));
            return;
        }

        const position = getScrollTop() + OFFSET;
        let currentHref = entries[0].item.getAttribute('href');

        entries.forEach((entry) => {
            if (getSectionTop(entry.section) <= position) {
                currentHref = entry.item.getAttribute('href');
            }
        });

        setActive(currentHref);
    }

    function requestUpdate() {
        if (ticking) return;
        ticking = true;
        window.requestAnimationFrame(updateActiveByScroll);
    }

    items.forEach((item) => {
        item.addEventListener('click', (event) => {
            const href = item.getAttribute('href');
            const target = href ? document.querySelector(href) : null;
            if (!target) return;

            event.preventDefault();
            isProgrammaticScroll = true;
            setActive(href);
            target.scrollIntoView({ behavior: 'smooth', block: 'start', inline: 'nearest' });

            if (window.history && window.history.replaceState) {
                window.history.replaceState(null, '', href);
            }

            window.setTimeout(() => {
                isProgrammaticScroll = false;
                requestUpdate();
            }, 450);
        });
    });

    scrollTarget.addEventListener('scroll', requestUpdate, { passive: true });
    window.addEventListener('resize', requestUpdate, { passive: true });

    // Dukung tautan langsung seperti /admin/settings#tab-kop.
    const initialHref = window.location.hash && entries.some((entry) => entry.item.getAttribute('href') === window.location.hash)
        ? window.location.hash
        : entries[0].item.getAttribute('href');

    const initialEntry = entries.find((entry) => entry.item.getAttribute('href') === initialHref);
    if (initialEntry) {
        initialEntry.section.scrollIntoView({ block: 'start' });
    }
    setActive(initialHref);
    updateActiveByScroll();
}

document.addEventListener('DOMContentLoaded', () => {
    // Sticky sidebar navigation with scrollspy
    initSettingsSectionNav();

    // Init Quill editor for Kop Surat
    var quill = new Quill('#quill-editor', {
        theme: 'snow',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline'],
                [{ 'header': [1, 2, 3, false] }],
                [{ 'align': [] }],
                ['link', 'clean']
            ]
        }
    });

    // Sync Quill content to hidden textarea on form submit
    document.querySelector('#settings-form').onsubmit = function() {
        document.querySelector('#kop-content-hidden').value = quill.root.innerHTML;
    };
});
