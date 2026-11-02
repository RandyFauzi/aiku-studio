(() => {
    const nativeFetch = window.fetch.bind(window);
    window.fetch = async (input, init = {}) => {
        const url = new URL(input instanceof Request ? input.url : input, window.location.href);
        const sameOrigin = url.origin === window.location.origin;
        if (sameOrigin) {
            const headers = new Headers(input instanceof Request ? input.headers : undefined);
            new Headers(init.headers).forEach((value, key) => headers.set(key, value));
            headers.set('X-Requested-With', 'XMLHttpRequest');
            headers.set('Accept', 'application/json');
            headers.set('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]')?.content || '');
            init = { ...init, headers, credentials: 'same-origin' };
        }
        const response = await nativeFetch(input, init);
        if (sameOrigin && response.status === 401) window.location.assign('/login');
        if (sameOrigin && response.status === 419) {
            alert('Sesi telah berakhir. Muat ulang halaman lalu coba kembali.');
        }
        const method = (init.method || (input instanceof Request ? input.method : 'GET')).toUpperCase();
        if (sameOrigin && !['GET', 'HEAD'].includes(method) && !response.ok) {
            const data = await response.clone().json().catch(() => ({}));
            const message = Object.values(data.errors || {}).flat().join('\n') || data.message || data.error || 'Perubahan belum tersimpan.';
            if (window.Swal) window.Swal.fire('Gagal', message, 'error');
            throw new Error(message);
        }
        return response;
    };
})();
