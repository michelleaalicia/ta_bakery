// Formatter Rupiah untuk input dengan atribut data-rupiah.
// Angka jumlah, stok, jam kerja tidak terkena.
(function () {
    function digits(value) {
        return String(value == null ? '' : value).replace(/\D/g, '');
    }
    function format(input) {
        const value = input.value;
        const cursor = input.selectionStart == null ? value.length : input.selectionStart;
        const digitsBeforeCursor = digits(value.slice(0, cursor)).length;
        const formatted = digits(value).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        input.value = formatted;
        if (document.activeElement === input && input.setSelectionRange) {
            let i = 0, count = 0;
            while (i < formatted.length && count < digitsBeforeCursor) {
                if (/\d/.test(formatted[i])) count++;
                i++;
            }
            input.setSelectionRange(i, i);
        }
    }
    window.RupiahInput = { format, raw: digits };
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('input[data-rupiah]').forEach(function (input) {
            input.type = 'text';
            input.inputMode = 'numeric';
            format(input);
        });
        document.addEventListener('input', function (event) {
            if (event.target.matches('input[data-rupiah]')) format(event.target);
        });
        document.addEventListener('submit', function (event) {
            if (event.defaultPrevented) return;
            event.target.querySelectorAll('input[data-rupiah]').forEach(function (input) {
                input.value = digits(input.value);
            });
        });
    });
})();
