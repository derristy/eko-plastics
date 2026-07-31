<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-body p-4 p-md-5">
                    <h1 class="h3 mb-4">Зворотний зв'язок</h1>

                    [[+success:notempty=`<div class="alert alert-success">[[+success]]</div>`]]
                    [[+error_form:notempty=`<div class="alert alert-danger">[[+error_form]]</div>`]]

                    <form method="post">
                        <input type="hidden" name="ekoform" value="1">
                        <input type="hidden" name="started_at" value="[[+started_at]]">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="first_name">Ім'я</label>
                                <input class="form-control[[+error_first_name:notempty=` is-invalid`]]" type="text" id="first_name" name="first_name" value="[[+first_name]]" required>
                                <div class="invalid-feedback">[[+error_first_name]]</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="last_name">Прізвище</label>
                                <input class="form-control[[+error_last_name:notempty=` is-invalid`]]" type="text" id="last_name" name="last_name" value="[[+last_name]]">
                                <div class="invalid-feedback">[[+error_last_name]]</div>
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="email">Email</label>
                                <input class="form-control[[+error_email:notempty=` is-invalid`]]" type="email" id="email" name="email" value="[[+email]]">
                                <div class="invalid-feedback">[[+error_email]]</div>
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="phone">Телефон</label>
                                <input class="form-control[[+error_phone:notempty=` is-invalid`]]" type="tel" id="phone" name="phone" value="[[+phone]]" placeholder="+380XXXXXXXXX" required>
                                <div class="invalid-feedback">[[+error_phone]]</div>
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="message">Повідомлення</label>
                                <textarea class="form-control[[+error_message:notempty=` is-invalid`]]" id="message" name="message" rows="4">[[+message]]</textarea>
                                <div class="invalid-feedback">[[+error_message]]</div>
                            </div>
                        </div>

                        <div class="position-absolute invisible" aria-hidden="true">
                            <label>Company<input type="text" name="company" tabindex="-1" autocomplete="off"></label>
                        </div>

                        <button class="btn btn-dark w-100 mt-4 py-2" type="submit">Надіслати</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
