import { Controller } from '@hotwired/stimulus';

/**
 * char-counter controller
 *
 * Displays a live character count for a textarea or input.
 *
 * Targets:
 *   input   — the textarea/input to count characters in
 *   count   — the <span> that displays the current count
 *
 * Values:
 *   max (Number) — the maximum character limit (used for colour feedback)
 *
 * Usage:
 *   <div data-controller="char-counter" data-char-counter-max-value="2000">
 *     <textarea data-char-counter-target="input"
 *               data-action="input->char-counter#update"></textarea>
 *     <span data-char-counter-target="count"></span> / 2000
 *   </div>
 */
export default class extends Controller {
    static targets = ['input', 'count'];
    static values  = { max: { type: Number, default: 0 } };

    connect() {
        this.update();
    }

    update() {
        const len = this.inputTarget.value.length;
        this.countTarget.textContent = len;

        // Visual feedback: turn orange near the limit, red over it
        if (this.maxValue > 0) {
            const ratio = len / this.maxValue;
            if (ratio >= 1) {
                this.countTarget.style.color = '#f09080';   // over limit
            } else if (ratio >= 0.9) {
                this.countTarget.style.color = '#f0a832';   // near limit
            } else {
                this.countTarget.style.color = '';          // normal
            }
        }
    }
}
