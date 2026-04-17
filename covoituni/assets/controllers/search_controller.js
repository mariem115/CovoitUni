import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['results', 'spinner', 'meta', 'form'];

    static values = {
        url: String,
        debounce: { type: Number, default: 300 },
    };

    connect() {
        this._timer = null;
        this._onInput = () => this.scheduleFetch();
        this.formTarget.querySelectorAll('[data-search-field]').forEach((el) => {
            el.addEventListener('input', this._onInput);
            el.addEventListener('change', this._onInput);
        });
    }

    disconnect() {
        if (this._timer) {
            clearTimeout(this._timer);
        }
        if (this.hasFormTarget) {
            this.formTarget.querySelectorAll('[data-search-field]').forEach((el) => {
                el.removeEventListener('input', this._onInput);
                el.removeEventListener('change', this._onInput);
            });
        }
    }

    scheduleFetch() {
        if (this._timer) {
            clearTimeout(this._timer);
        }
        this._timer = window.setTimeout(() => {
            this._timer = null;
            this.fetchResults();
        }, this.debounceValue);
    }

    async fetchResults() {
        const params = new URLSearchParams(new FormData(this.formTarget));
        params.set('format', 'json');
        params.delete('page');

        this.spinnerTarget.classList.remove('d-none');

        try {
            const res = await fetch(`${this.urlValue}?${params.toString()}`, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (!res.ok) {
                throw new Error(`HTTP ${res.status}`);
            }

            const data = await res.json();
            this.resultsTarget.innerHTML = data.html;
            if (this.hasMetaTarget && typeof data.meta === 'string') {
                this.metaTarget.textContent = data.meta;
            }
        } catch (e) {
            console.error(e);
        } finally {
            this.spinnerTarget.classList.add('d-none');
        }
    }
}
