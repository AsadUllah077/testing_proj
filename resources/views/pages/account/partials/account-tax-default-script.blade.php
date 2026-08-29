function normalizeTaxText(value) {
    return String(value || '')
        .replace(/&amp;/g, '&')
        .replace(/\s+/g, ' ')
        .trim()
        .toLowerCase();
}

function taxRateLabels(taxRate) {
    const rate = parseFloat(taxRate.tax_rate || 0);
    const rateText = Number.isInteger(rate) ? String(rate) : String(rate);
    const fixedRateText = rate.toFixed(2);

    return [
        taxRate.id,
        taxRate.name,
        taxRate.code,
        rateText,
        `${rateText}%`,
        fixedRateText,
        `${fixedRateText}%`,
        `${taxRate.name} (${rateText}%)`,
        `${taxRate.name} (${taxRate.tax_rate}%)`,
        `${taxRate.name} (${fixedRateText}%)`,
    ];
}

function taxRateIdByLabel() {
    const map = {};

    taxes.forEach(taxRate => {
        taxRateLabels(taxRate).forEach(label => {
            const normalizedLabel = normalizeTaxText(label);
            if (normalizedLabel) {
                map[normalizedLabel] = String(taxRate.id);
            }
        });
    });

    return map;
}

function defaultTaxIdForAccount(accountId) {
    const account = accounts.find(item => String(item.id) === String(accountId));

    if (!account) {
        return null;
    }

    const normalizedTaxCode = normalizeTaxText(account.tax_code);

    if (!normalizedTaxCode || normalizedTaxCode.includes('exempt') || normalizedTaxCode.includes('no tax')) {
        return '';
    }

    return taxRateIdByLabel()[normalizedTaxCode] || '';
}

function applyAccountDefaultTax(row) {
    const accountSelect = row.querySelector('select[name$="[account_id]"]');
    const taxSelect = row.querySelector('.tax-select, .item-tax, select[name$="[tax_id]"]');

    if (!accountSelect || !taxSelect) {
        return;
    }

    const accountId = String($(accountSelect).val() || accountSelect.value || '');

    const taxId = defaultTaxIdForAccount(accountId);

    if (taxId === null) {
        return;
    }

    const optionExists = !taxId || taxSelect.querySelector(`option[value="${taxId}"]`);

    if (optionExists) {
        $(taxSelect).val(taxId).trigger('change');
    }
}

function setupAccountDefaultTax(row) {
    const accountSelect = row.querySelector('select[name$="[account_id]"]');

    if (!accountSelect) {
        return;
    }

    accountSelect.addEventListener('change', function() {
        applyAccountDefaultTax(row);
    });

    $(accountSelect).on('select2:select select2:clear select2:unselect', function() {
        applyAccountDefaultTax(row);
    });
}
