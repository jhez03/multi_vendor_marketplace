import { Controller } from '@hotwired/stimulus';

/**
 * cart-count controller
 *
 * Connects to the cart count API and updates badge text.
 * Usage on any element:
 *   <span data-controller="cart-count" data-cart-count-url-value="/cart/count">0</span>
 */
export default class extends Controller {
    static values = { url: String };

    connect() {
        this.refresh();
    }

    async refresh() {
        try {
            const res  = await fetch(this.urlValue, { credentials: 'same-origin' });
            const data = await res.json();
            const count = data.count ?? 0;

            this.element.textContent = count;
            this.element.classList.toggle('hidden', count === 0);
        } catch {
            // Network error — leave existing text
        }
    }
}
