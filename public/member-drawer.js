(() => {
    const drawer = document.getElementById('member-drawer');
    if (!drawer) return;
    const form = document.getElementById('member-form');
    let opener;
    let initialValues = new URLSearchParams(new FormData(form)).toString();
    function openDrawer(event) {
        event?.preventDefault();
        opener = event?.currentTarget || document.querySelector('[data-open-member]');
        drawer.showModal();
        document.body.classList.add('member-drawer-open');
        const focusTarget = drawer.querySelector('[aria-invalid="true"]') || document.getElementById('member-errors') || document.getElementById('member-name');
        focusTarget?.focus();
    }
    function closeDrawer() {
        const changed = new URLSearchParams(new FormData(form)).toString() !== initialValues;
        if (changed && !window.confirm('Perubahan belum disimpan. Tutup formulir dan abaikan perubahan?')) return;
        form.reset();
        drawer.close();
        document.body.classList.remove('member-drawer-open');
        const url = new URL(window.location.href);
        url.searchParams.delete('edit');
        url.searchParams.delete('create');
        window.history.replaceState({}, '', url);
        (opener || document.querySelector('.member-toolbar a'))?.focus();
    }
    document.querySelectorAll('[data-open-member]').forEach(button => button.addEventListener('click', openDrawer));
    drawer.querySelectorAll('[data-close-member]').forEach(button => button.addEventListener('click', closeDrawer));
    drawer.addEventListener('cancel', event => { event.preventDefault(); closeDrawer(); });
    drawer.addEventListener('click', event => { if (event.target === drawer) closeDrawer(); });
    if (drawer.dataset.autoOpen === 'true') openDrawer();
})();
