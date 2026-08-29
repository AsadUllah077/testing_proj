@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const amountPkr = document.getElementById('amount_pkr');
            const amountUsd = document.getElementById('amount_usd');
            const exchangeRate = document.getElementById('exchange_rate');
            const chargeCurrency = document.getElementById('charge_currency');

            if (!amountPkr || !amountUsd || !exchangeRate || !chargeCurrency) {
                return;
            }

            const numericValue = (input) => {
                const value = Number.parseFloat(input.value);

                return Number.isFinite(value) ? value : null;
            };

            const convertedValue = (value) => {
                return Number.isFinite(value) ? value.toFixed(2) : '';
            };

            const recalculate = (sourceCurrency) => {
                const rate = numericValue(exchangeRate);
                chargeCurrency.value = sourceCurrency;

                if (!rate || rate <= 0) {
                    return;
                }

                if (sourceCurrency === 'USD') {
                    const usd = numericValue(amountUsd);
                    amountPkr.value = usd === null ? '' : convertedValue(usd * rate);

                    return;
                }

                const pkr = numericValue(amountPkr);
                amountUsd.value = pkr === null ? '' : convertedValue(pkr / rate);
            };

            amountPkr.addEventListener('input', () => recalculate('PKR'));
            amountUsd.addEventListener('input', () => recalculate('USD'));
            exchangeRate.addEventListener('input', () => recalculate(chargeCurrency.value || 'PKR'));
        });
    </script>
@endpush
