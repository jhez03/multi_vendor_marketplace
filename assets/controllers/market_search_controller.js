import { Controller } from '@hotwired/stimulus';

/**
 * market-search controller
 *
 * Wires the homepage search bar to perform live (debounced) search via
 * Turbo Drive navigation.  No manual fetch needed — we just update the URL
 * and let Turbo handle the page transition smoothly.
 *
 * Targets:
 *   input   — the <input type="search"> element
 *   clear   — optional clear button (shown when input has a value)
 *   spinner — optional loading indicator
 *
 * Usage in HTML:
 *   <div data-controller="market-search"
 *        data-market-search-url-value="/search">
 *     <input data-market-search-target="input"
 *            data-action="input->market-search#onInput keydown.enter->market-search#submit">
 *     <button data-market-search-target="clear"
 *             data-action="click->market-search#clear">✕</button>
 *   </div>
 */
export default class extends Controller {
    static targets = ['input', 'clear', 'spinner'];
    static values  = { url: { type: String, default: '/search' } };

    // Debounce timer handle
    #timer = null;

    connect() {
        this.#syncClear();
    }

    // ── Event handlers ────────────────────────────────────────────────────────

    /** Called on every keystroke; debounces 350 ms before navigating. */
    onInput() {
        this.#syncClear();
        clearTimeout(this.#timer);

        const val = this.inputTarget.value.trim();

        if (val.length === 0) {
            // If the user clears the field, navigate back to the search page
            // with no query so results disappear.
            this.#navigate('');
            return;
        }

        // Don't fire for very short queries until the user pauses typing
        if (val.length < 2) return;

        this.#showSpinner();
        this.#timer = setTimeout(() => this.#navigate(val), 350);
    }

    /** Called on Enter key or clicking the Search button. */
    submit(e) {
        e?.preventDefault();
        clearTimeout(this.#timer);
        const val = this.inputTarget.value.trim();
        this.#navigate(val);
    }

    /** Clears the input and resets results. */
    clear(e) {
        e?.preventDefault();
        this.inputTarget.value = '';
        this.inputTarget.focus();
        this.#syncClear();
        this.#navigate('');
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    #navigate(query) {
        const url = new URL(this.urlValue, window.location.origin);

        if (query) {
            url.searchParams.set('q', query);
        } else {
            url.searchParams.delete('q');
        }

        // Turbo Drive navigates without a full page reload, updating the URL
        // and swapping in the new <body> content automatically.
        if (window.Turbo) {
            window.Turbo.visit(url.toString(), { action: 'replace' });
        } else {
            window.location.href = url.toString();
        }
    }

    #syncClear() {
        if (!this.hasClearTarget) return;
        const hasValue = this.inputTarget.value.trim().length > 0;
        this.clearTarget.classList.toggle('hidden', !hasValue);
    }

    #showSpinner() {
        if (this.hasSpinnerTarget) {
            this.spinnerTarget.classList.remove('hidden');
        }
    }
}
