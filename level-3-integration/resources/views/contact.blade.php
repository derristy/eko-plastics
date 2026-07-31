<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Зворотний зв'язок</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-body p-4 p-md-5">
                    <h1 class="h3 mb-1">Зворотний зв'язок</h1>
                    <p class="text-secondary mb-4">Заявка потрапляє в SalesDrive, контакт — у Діловод.</p>

                    <form id="lead-form" novalidate>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="first-name">Ім'я</label>
                                <input class="form-control" type="text" id="first-name" name="first_name" placeholder="Іван" required>
                                <div class="invalid-feedback" data-error="first_name"></div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="last-name">Прізвище</label>
                                <input class="form-control" type="text" id="last-name" name="last_name" placeholder="Петренко">
                                <div class="invalid-feedback" data-error="last_name"></div>
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="email">Email</label>
                                <input class="form-control" type="email" id="email" name="email" placeholder="ivan@example.com">
                                <div class="invalid-feedback" data-error="email"></div>
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="phone">Телефон</label>
                                <input class="form-control" type="tel" id="phone" name="phone" placeholder="+380-XX-XX-XX-XXX" required>
                                <div class="invalid-feedback" data-error="phone"></div>
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="message">Повідомлення</label>
                                <textarea class="form-control" id="message" name="message" rows="4" placeholder="Ваше питання"></textarea>
                                <div class="invalid-feedback" data-error="message"></div>
                            </div>
                        </div>

                        <div class="position-absolute invisible" aria-hidden="true">
                            <label>Company<input type="text" name="company" tabindex="-1" autocomplete="off"></label>
                        </div>
                        <input type="hidden" name="form_started_at" value="">

                        <button class="btn btn-dark w-100 mt-4 py-2" type="submit">Надіслати</button>
                        <div class="mt-3" id="form-status"></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="/assets/js/form.js"></script>
</body>
</html>
