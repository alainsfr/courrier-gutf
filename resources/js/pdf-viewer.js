import * as pdfjsLib from 'pdfjs-dist';
import pdfWorker from 'pdfjs-dist/build/pdf.worker.min.mjs?url';

pdfjsLib.GlobalWorkerOptions.workerSrc = pdfWorker;

function base64ToUint8Array(base64) {
    const binary = atob(base64);
    const bytes = new Uint8Array(binary.length);
    for (let i = 0; i < binary.length; i += 1) {
        bytes[i] = binary.charCodeAt(i);
    }
    return bytes;
}

async function loadPdfBytes(url) {
    const embedUrl = new URL(url, window.location.origin);
    embedUrl.searchParams.set('embed', '1');

    const response = await fetch(embedUrl.toString(), {
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-PDF-Embed': '1',
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    if (!response.ok) {
        throw new Error(`Preview HTTP ${response.status}`);
    }

    const contentType = (response.headers.get('content-type') || '').toLowerCase();

    if (contentType.includes('application/json')) {
        const payload = await response.json();
        if (!payload?.data) {
            throw new Error('Preview JSON missing data');
        }
        return base64ToUint8Array(payload.data);
    }

    // Fallback si un proxy renvoie encore le binaire
    const buffer = await response.arrayBuffer();
    return new Uint8Array(buffer);
}

async function renderPdfInto(container, url) {
    const status = container.querySelector('[data-pdf-status]');
    const pagesRoot = container.querySelector('[data-pdf-pages]');

    if (!pagesRoot) {
        return;
    }

    const setStatus = (message, isError = false) => {
        if (!status) {
            return;
        }
        status.textContent = message || '';
        status.classList.toggle('hidden', !message);
        status.classList.toggle('text-rose-700', isError);
        status.classList.toggle('text-slate-500', !isError);
    };

    setStatus(status?.dataset.loadingLabel || 'Chargement…');
    pagesRoot.innerHTML = '';

    try {
        const data = await loadPdfBytes(url);
        const loadingTask = pdfjsLib.getDocument({ data });
        const pdf = await loadingTask.promise;
        const maxPages = pdf.numPages;

        setStatus('');

        for (let pageNumber = 1; pageNumber <= maxPages; pageNumber += 1) {
            const page = await pdf.getPage(pageNumber);
            const baseViewport = page.getViewport({ scale: 1 });
            const availableWidth = Math.max(pagesRoot.clientWidth || container.clientWidth || 800, 320);
            const scale = Math.min(availableWidth / baseViewport.width, 2.2);
            const viewport = page.getViewport({ scale });

            const pageWrap = document.createElement('div');
            pageWrap.className = 'mx-auto mb-3 w-fit max-w-full overflow-hidden rounded-lg bg-white shadow-sm';

            const canvas = document.createElement('canvas');
            canvas.className = 'block h-auto max-w-full';
            canvas.width = Math.floor(viewport.width);
            canvas.height = Math.floor(viewport.height);

            pageWrap.appendChild(canvas);
            pagesRoot.appendChild(pageWrap);

            await page.render({
                canvasContext: canvas.getContext('2d'),
                viewport,
            }).promise;
        }
    } catch (error) {
        console.error('PDF preview failed', error);
        setStatus(status?.dataset.errorLabel || 'Impossible d’afficher l’aperçu.', true);
    }
}

function initPdfViewers(root = document) {
    root.querySelectorAll('[data-pdf-viewer]').forEach((container) => {
        if (container.dataset.pdfBound === '1') {
            return;
        }

        const url = container.dataset.url;
        if (!url) {
            return;
        }

        container.dataset.pdfBound = '1';
        renderPdfInto(container, url);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initPdfViewers();
    initPreviewExpand();
});

function initPreviewExpand() {
    const viewer = document.getElementById('document-viewer');
    if (!viewer) {
        return;
    }

    const openBtn = viewer.querySelector('[data-preview-open]');
    const closeBtn = viewer.querySelector('[data-preview-close]');
    if (!openBtn) {
        return;
    }

    const expandedClasses = [
        'fixed', 'inset-0', 'z-[90]', 'm-0', 'h-screen', 'max-h-screen',
        'rounded-none', 'border-0', 'shadow-2xl',
    ];

    const setExpanded = (expanded) => {
        expandedClasses.forEach((cls) => viewer.classList.toggle(cls, expanded));
        viewer.classList.toggle('lg:col-span-2', !expanded);
        document.body.classList.toggle('overflow-hidden', expanded);
        openBtn.hidden = expanded;
        if (closeBtn) {
            closeBtn.hidden = !expanded;
        }
        viewer.setAttribute('data-preview-expanded', expanded ? '1' : '0');

        if (expanded) {
            viewer.scrollIntoView({ behavior: 'smooth', block: 'start' });
            const stage = viewer.querySelector('[data-preview-stage], [data-pdf-viewer], img');
            stage?.focus?.();
        }
    };

    openBtn.addEventListener('click', (event) => {
        event.preventDefault();
        setExpanded(true);
    });

    closeBtn?.addEventListener('click', (event) => {
        event.preventDefault();
        setExpanded(false);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && viewer.getAttribute('data-preview-expanded') === '1') {
            setExpanded(false);
        }
    });
}
