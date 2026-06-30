import { Controller } from '@hotwired/stimulus';

/*
 * Drives the EasyAdmin-style "Filter" modal on the Tag grid
 * (templates/gridview/tag/index.html.twig).
 *
 * The grid's per-column header filters are suppressed; instead a "Filter" button
 * next to "Add Tag" opens a self-contained modal (gv-modal classes, no Bootstrap
 * JS) listing the filterable fields. Each row has:
 *   - a checkbox that enables the filter;
 *   - a comparison <select> (UI-only, no name attribute) holding a gridview
 *     operator token (like / nlike / startwith / endwith / eq / neq);
 *   - a text input for the term;
 *   - a hidden carrier `myform[name]` — the ONLY submitted field.
 *
 * The modal wraps a GET <form> pointing at the grid index. On Apply we combine
 * the operator + term into the carrier as "<operator> <term>", a syntax
 * TextFilterApplier parses 1:1 with EasyAdmin's comparisons. On connect we parse
 * the carrier's server-rendered value back into the select + term (round-trip).
 *   - Apply → fill carriers, then native submit → ?myform[name]=like%20adv
 *   - Clear → close the modal only (per spec)
 * An unchecked or empty row disables its carrier, so it is not submitted and the
 * filter is dropped. The open state is the `gv-open` class on the modal (same
 * convention as the bundle's gridview-crud controller).
 */
export default class extends Controller {
    static targets = ['modal'];

    // Recognised operator tokens (must match the <option> values and the aliases
    // in TextFilterApplier). Anything else parses as the default "contains".
    static OPERATORS = ['like', 'nlike', 'startwith', 'endwith', 'eq', 'neq'];
    static DEFAULT_OP = 'like';

    connect() {
        // Seed each row from its carrier's server value, then reflect the checkbox
        // onto the controls' disabled state.
        this._rows().forEach((row) => {
            this._parseInto(row);
            this._syncRow(row);
        });
    }

    open(event) {
        if (event) event.preventDefault();
        this.modalTarget.classList.add('gv-open');
        this.modalTarget.removeAttribute('aria-hidden');
        this._onKey = (e) => { if (e.key === 'Escape') this.close(); };
        document.addEventListener('keydown', this._onKey);
    }

    close(event) {
        if (event) event.preventDefault();
        this.modalTarget.classList.remove('gv-open');
        this.modalTarget.setAttribute('aria-hidden', 'true');
        if (this._onKey) {
            document.removeEventListener('keydown', this._onKey);
            this._onKey = null;
        }
    }

    // Close only when the click lands on the overlay itself, not the dialog.
    backdropClose(event) {
        if (event.target === this.modalTarget) this.close();
    }

    // Checkbox toggled: enable/disable the paired controls.
    toggle(event) {
        const row = event.target.closest('[data-tag-filter-row]');
        if (row) this._syncRow(row);
    }

    // Form submit: build each carrier from "<operator> <term>" so the query
    // carries the EA-equivalent filter. Not preventing default → native GET.
    apply() {
        this._rows().forEach((row) => {
            const { checkbox, comparison, term, carrier } = this._controls(row);
            if (!carrier) return;

            const value = term ? term.value.trim() : '';
            const enabled = !!(checkbox && checkbox.checked) && value !== '';

            if (enabled) {
                const op = comparison ? comparison.value : this.constructor.DEFAULT_OP;
                carrier.value = `${op} ${value}`;
                carrier.disabled = false;
            } else {
                // Empty or unchecked → drop the param entirely (filter cleared).
                carrier.value = '';
                carrier.disabled = true;
            }
        });
    }

    _rows() {
        return Array.from(this.element.querySelectorAll('[data-tag-filter-row]'));
    }

    _controls(row) {
        return {
            checkbox: row.querySelector('[data-tag-filter-toggle]'),
            comparison: row.querySelector('[data-tag-filter-comparison]'),
            term: row.querySelector('[data-tag-filter-term]'),
            carrier: row.querySelector('[data-tag-filter-value]'),
        };
    }

    // Split a carrier value of the form "<operator> <term>" into the select +
    // term controls. A leading token that is not a known operator means the value
    // is a bare term → default "contains".
    _parseInto(row) {
        const { comparison, term, carrier } = this._controls(row);
        if (!carrier) return;

        const raw = (carrier.value || '').trim();
        let op = this.constructor.DEFAULT_OP;
        let rest = raw;

        const space = raw.indexOf(' ');
        if (space !== -1) {
            const token = raw.slice(0, space).toLowerCase();
            if (this.constructor.OPERATORS.includes(token)) {
                op = token;
                rest = raw.slice(space + 1).trim();
            }
        }

        if (comparison) comparison.value = op;
        if (term) term.value = rest;
    }

    _syncRow(row) {
        const { checkbox, comparison, term } = this._controls(row);
        const enabled = !!(checkbox && checkbox.checked);
        [comparison, term].forEach((el) => { if (el) el.disabled = !enabled; });
    }
}
