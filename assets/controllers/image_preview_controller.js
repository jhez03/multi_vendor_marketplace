import { Controller } from '@hotwired/stimulus';

/**
 * image-preview controller
 * Shows a live thumbnail when a file input changes.
 * data-image-preview-target="input"   — the <input type="file">
 * data-image-preview-target="preview" — the <img> to display
 * data-image-preview-target="empty"   — placeholder shown when no image
 */
export default class extends Controller {
    static targets = ['input', 'preview', 'empty'];

    show() {
        const file = this.inputTarget.files?.[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = (e) => {
            this.previewTarget.src = e.target.result;
            this.previewTarget.classList.remove('hidden');
            if (this.hasEmptyTarget) this.emptyTarget.classList.add('hidden');
        };
        reader.readAsDataURL(file);
    }

    clear(e) {
        e.preventDefault();
        this.inputTarget.value = '';
        this.previewTarget.src = '';
        this.previewTarget.classList.add('hidden');
        if (this.hasEmptyTarget) this.emptyTarget.classList.remove('hidden');
    }
}
