document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('lead-form');
    if (!form) {
        return;
    }

    var status = document.getElementById('form-status');
    var phone = form.querySelector('[name="phone"]');
    var submit = form.querySelector('[type="submit"]');

    form.querySelector('[name="form_started_at"]').value = Date.now();

    var operators = ['39', '50', '63', '66', '67', '68', '73', '91', '92', '93', '94', '95', '96', '97', '98', '99'];

    function formatPhone(value) {
        var digits = value.replace(/\D/g, '');

        if (digits.startsWith('380')) {
            digits = digits.slice(3);
        } else if (digits.startsWith('0')) {
            digits = digits.slice(1);
        }
        digits = digits.slice(0, 9);

        var out = '+380';
        if (digits.length) {
            out += '-' + digits.slice(0, 2);
        }
        if (digits.length > 2) {
            out += '-' + digits.slice(2, 4);
        }
        if (digits.length > 4) {
            out += '-' + digits.slice(4, 6);
        }
        if (digits.length > 6) {
            out += '-' + digits.slice(6, 9);
        }

        return out;
    }

    phone.addEventListener('focus', function () {
        if (!phone.value) {
            phone.value = '+380-';
        }
    });

    phone.addEventListener('input', function () {
        phone.value = formatPhone(phone.value);
    });

    phone.addEventListener('blur', function () {
        if (phone.value === '+380-' || phone.value === '+380') {
            phone.value = '';
        }
    });

    function validate() {
        var errors = {};
        var name = form.first_name.value.trim();
        var last = form.last_name.value.trim();
        var letters = /^[\p{L}’'\- ]+$/u;

        if (name.length < 2 || !letters.test(name)) {
            errors.first_name = 'Ім\'я може містити тільки літери.';
        }
        if (last && (last.length < 2 || !letters.test(last))) {
            errors.last_name = 'Прізвище може містити тільки літери.';
        }

        var digits = phone.value.replace(/\D/g, '');
        if (digits.length !== 12 || digits.slice(0, 3) !== '380' || operators.indexOf(digits.slice(3, 5)) === -1) {
            errors.phone = 'Вкажіть номер українського оператора.';
        }

        var email = form.email.value.trim();
        if (email && !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email)) {
            errors.email = 'Перевірте адресу пошти.';
        }

        return errors;
    }

    function showErrors(errors) {
        form.querySelectorAll('[data-error]').forEach(function (el) {
            var key = el.getAttribute('data-error');
            var text = errors[key];
            var field = form.querySelector('[name="' + key + '"]');

            el.textContent = Array.isArray(text) ? text[0] : (text || '');
            if (field) {
                field.classList.toggle('is-invalid', Boolean(text));
            }
        });
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        var errors = validate();
        showErrors(errors);
        if (Object.keys(errors).length) {
            return;
        }

        submit.disabled = true;
        status.textContent = 'Відправляємо…';
        status.className = 'alert alert-secondary';

        var payload = {};
        new FormData(form).forEach(function (value, key) {
            payload[key] = value;
        });
        payload.phone = '+' + phone.value.replace(/\D/g, '');

        fetch('/api/lead', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(payload)
        })
            .then(function (r) {
                return r.json().then(function (body) {
                    return { ok: r.ok, body: body };
                });
            })
            .then(function (result) {
                if (result.ok) {
                    form.reset();
                    form.querySelector('[name="form_started_at"]').value = Date.now();
                    showErrors({});
                    status.textContent = 'Дякуємо, заявку прийнято.';
                    status.className = 'alert alert-success';
                } else {
                    showErrors(result.body.errors || {});
                    status.textContent = 'Перевірте поля форми.';
                    status.className = 'alert alert-danger';
                }
            })
            .catch(function () {
                status.textContent = 'Не вдалося відправити. Спробуйте пізніше.';
                status.className = 'alert alert-danger';
            })
            .finally(function () {
                submit.disabled = false;
            });
    });
});
