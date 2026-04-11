// assets/controllers/checkout_controller.js
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
  static values = {
    stripeKey: String,
    stripeAction: String,
    paypalAction: String,
    subtotal: String,
  };

  connect() {
    this.stripe = null;
    this.elements = null;
    this.cardEl = null;
    this.paypalRendered = false;

    this.initStripe();
    this.wireMethodToggle();
    this.wireStripeButton();
    this.renderPaypalButtons();
  }

  disconnect() {
    this.destroyStripe();
  }

  // ── Stripe ─────────────────────────────────────────────

  initStripe() {
    const mountTarget = document.getElementById("stripe-card-element");
    if (!mountTarget) return;

    if (mountTarget.dataset.stripeMounted === "1") return;

    this.stripe = Stripe(this.stripeKeyValue);
    this.elements = this.stripe.elements();

    this.cardEl = this.elements.create("card", {
      style: {
        base: {
          color: "#e8e4dc",
          fontFamily: '"DM Sans", sans-serif',
          fontSize: "14px",
          "::placeholder": { color: "#4a4844" },
          iconColor: "#9c9790",
        },
        invalid: { color: "#f09080", iconColor: "#f09080" },
      },
    });

    this.cardEl.mount("#stripe-card-element");
    mountTarget.dataset.stripeMounted = "1";

    this.cardEl.on("change", (event) => {
      const errDiv = document.getElementById("stripe-card-errors");
      if (!errDiv) return;

      if (event.error) {
        errDiv.textContent = event.error.message;
        errDiv.classList.remove("hidden");
      } else {
        errDiv.classList.add("hidden");
      }
    });
  }

  destroyStripe() {
    const mountTarget = document.getElementById("stripe-card-element");
    if (!mountTarget) return;

    if (this.cardEl) {
      this.cardEl.destroy();
      this.cardEl = null;
      mountTarget.dataset.stripeMounted = "0";
      mountTarget.innerHTML = "";
    }
  }

  // ── Payment method toggle ─────────────────────────────

  wireMethodToggle() {
    const radios = document.querySelectorAll(
      'input[name="payment_method_ui"]'
    );
    if (!radios.length) return;

    const stripeWrap = document.getElementById("stripe-element-wrap");
    const stripeBtn = document.getElementById("stripe-pay-btn");
    const paypalWrap = document.getElementById("paypal-button-wrap");
    const methodLabels = document.querySelectorAll(".method-label");

    radios.forEach((radio) => {
      radio.addEventListener("change", () => {
        methodLabels.forEach((l) => {
          l.style.borderColor = "";
          l.style.background = "";
          const dot = l.querySelector("span:last-child");
          if (dot) {
            dot.style.borderColor = "";
            dot.style.background = "";
          }
        });

        const parent = radio
          .closest(".method-card")
          .querySelector(".method-label");

        parent.style.borderColor = "rgba(34,191,163,0.6)";
        parent.style.background = "rgba(34,191,163,0.04)";

        const dot = parent.querySelector("span:last-child");
        if (dot) {
          dot.style.borderColor = "#22bfa3";
          dot.style.background = "rgba(34,191,163,0.25)";
        }

        if (radio.value === "stripe") {
          stripeWrap?.classList.remove("hidden");
          stripeBtn?.classList.remove("hidden");
          paypalWrap?.classList.add("hidden");
        } else {
          stripeWrap?.classList.add("hidden");
          stripeBtn?.classList.add("hidden");
          paypalWrap?.classList.remove("hidden");
        }
      });
    });
  }

  // ── Stripe Button ─────────────────────────────────────

  wireStripeButton() {
    const btn = document.getElementById("stripe-pay-btn");
    if (!btn) return;

    btn.addEventListener("click", async () => {
      const form = document.getElementById("checkout-form");
      if (!form || !form.reportValidity()) return;

      btn.disabled = true;
      btn.textContent = "Processing…";

      document.getElementById("provider-field").value = "stripe";

      const formData = new FormData(form);
      const response = await fetch(this.stripeActionValue, {
        method: "POST",
        body: formData,
        credentials: "same-origin",
      });

      if (!response.ok) {
        btn.disabled = false;
        btn.textContent = `Pay ₱${this.subtotalValue} with card`;
        return;
      }

      const html = await response.text();
      const doc = new DOMParser().parseFromString(html, "text/html");

      const clientSecret =
        doc.getElementById("stripe-client-secret")?.value;
      const returnUrl =
        doc.getElementById("stripe-return-url")?.value;

      if (!clientSecret) {
        window.location.href = response.url;
        return;
      }

      const { error } = await this.stripe.confirmCardPayment(
        clientSecret,
        {
          payment_method: { card: this.cardEl },
        }
      );

      if (error) {
        const errDiv = document.getElementById("stripe-card-errors");
        if (errDiv) {
          errDiv.textContent = error.message;
          errDiv.classList.remove("hidden");
        }

        btn.disabled = false;
        btn.textContent = `Pay ₱${this.subtotalValue} with card`;
      } else {
        window.location.href = returnUrl || "/payment/stripe/return";
      }
    });
  }

  // ── PayPal ────────────────────────────────────────────

  renderPaypalButtons() {
    if (typeof paypal === "undefined") return;
    if (!document.getElementById("paypal-button-container")) return;
    if (this.paypalRendered) return;

    this.paypalRendered = true;

    paypal
      .Buttons({
        style: {
          shape: "rect",
          color: "gold",
          layout: "vertical",
          label: "paypal",
        },
        createOrder: async () => {
          const form = document.getElementById("checkout-form");
          if (!form || !form.reportValidity()) {
            return Promise.reject("Invalid form");
          }

          document.getElementById("provider-field").value = "paypal";

          const formData = new FormData(form);
          const response = await fetch(this.paypalActionValue, {
            method: "POST",
            body: formData,
            credentials: "same-origin",
          });

          const data = await response.json();
          return data.paypalOrderId;
        },
        onApprove: (data) => {
          window.location.href =
            "/payment/paypal/return?token=" + data.orderID;
        },
      })
      .render("#paypal-button-container");
  }
}
