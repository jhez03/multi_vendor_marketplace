import { Controller } from '@hotwired/stimulus';

/**
 * variant-manager controller
 * Manages dynamic add/remove of product variant rows.
 * Each row tracks: sku, price, stock, attributes (key=value pairs).
 */
export default class extends Controller {
    static targets = ['list', 'template', 'count'];

    connect() {
        this.index = this.listTarget.querySelectorAll('[data-variant-row]').length;
        this._updateCount();
    }

    add(e) {
        e.preventDefault();
        const tpl  = this.templateTarget.content.cloneNode(true);
        const row  = tpl.querySelector('[data-variant-row]');

        // Replace placeholder index __INDEX__ with actual index
        row.innerHTML = row.innerHTML.replaceAll('__INDEX__', this.index);
        this.index++;

        this.listTarget.appendChild(tpl);
        this._updateCount();

        // Focus first input of the new row
        const first = this.listTarget.lastElementChild?.querySelector('input');
        first?.focus();
    }

    remove(e) {
        e.preventDefault();
        const row = e.currentTarget.closest('[data-variant-row]');
        row?.remove();
        this._updateCount();
    }

    _updateCount() {
        if (this.hasCountTarget) {
            const n = this.listTarget.querySelectorAll('[data-variant-row]').length;
            this.countTarget.textContent = n;
        }
    }
}
