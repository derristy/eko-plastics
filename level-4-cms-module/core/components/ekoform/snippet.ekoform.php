<?php

/**
 * ekoForm — форма зворотного зв'язку.
 * Валідує дані, зберігає заявку через xPDO і передає її в SalesDrive.
 *
 * Параметри: &tpl, &successTpl, &minSeconds
 */

/** @var modX $modx */
/** @var array $scriptProperties */

$component = MODX_CORE_PATH . 'components/ekoform/';
// xPDO 3 не подхватывает классы пакета автоматически, подключаем явно
foreach ([$component . 'model/ekoform/EkoFormLead.php', $component . 'model/ekoform/mysql/EkoFormLead.php'] as $classFile) {
    if (is_file($classFile)) {
        require_once $classFile;
    }
}
$modx->addPackage('ekoform', $component . 'model/', null, 'ekoform\\');
$modx->lexicon->load('ekoform:default');

$tpl = $modx->getOption('tpl', $scriptProperties, 'ekoFormTpl');
$successTpl = $modx->getOption('successTpl', $scriptProperties, '');
$minSeconds = (int) $modx->getOption('minSeconds', $scriptProperties, 3);

$operators = ['39', '50', '63', '66', '67', '68', '73', '91', '92', '93', '94', '95', '96', '97', '98', '99'];

$values = [
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'phone' => '',
    'message' => '',
];
$errors = [];
$success = false;

$render = function (array $values, array $errors, bool $success) use ($modx, $tpl, $successTpl) {
    if ($success && $successTpl) {
        return $modx->getChunk($successTpl);
    }

    $placeholders = $values;
    foreach (array_keys($values) as $field) {
        $placeholders['error_' . $field] = $errors[$field] ?? '';
    }
    $placeholders['error_form'] = $errors['form'] ?? '';
    $placeholders['success'] = $success ? $modx->lexicon('ekoform.sent') : '';
    $placeholders['started_at'] = time();

    return $modx->getChunk($tpl, $placeholders);
};

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !isset($_POST['ekoform'])) {
    return $render($values, $errors, false);
}

foreach (array_keys($values) as $field) {
    $values[$field] = trim((string) ($_POST[$field] ?? ''));
}

// пастка для ботів: поле приховане, людина його не заповнює
if (trim((string) ($_POST['company'] ?? '')) !== '') {
    $modx->log(modX::LOG_LEVEL_WARN, '[ekoForm] заявку відхилено: заповнено приховане поле', '', '', __FILE__, __LINE__);

    return $render($values, ['form' => $modx->lexicon('ekoform.rejected')], false);
}

// форма, заповнена швидше за кілька секунд, надіслана не людиною
$startedAt = (int) ($_POST['started_at'] ?? 0);
if ($startedAt > 0 && (time() - $startedAt) < $minSeconds) {
    $modx->log(modX::LOG_LEVEL_WARN, '[ekoForm] заявку відхилено: форму заповнено надто швидко', '', '', __FILE__, __LINE__);

    return $render($values, ['form' => $modx->lexicon('ekoform.rejected')], false);
}

$letters = '/^[\p{L}\x{2019}\'\- ]+$/u';

if (mb_strlen($values['first_name']) < 2 || !preg_match($letters, $values['first_name'])) {
    $errors['first_name'] = $modx->lexicon('ekoform.error_first_name');
}

if ($values['last_name'] !== '' && !preg_match($letters, $values['last_name'])) {
    $errors['last_name'] = $modx->lexicon('ekoform.error_last_name');
}

$phone = preg_replace('/[^\d]/', '', $values['phone']);
if (str_starts_with($phone, '0')) {
    $phone = '38' . $phone;
}
if (strlen($phone) !== 12 || !str_starts_with($phone, '380') || !in_array(substr($phone, 3, 2), $operators, true)) {
    $errors['phone'] = $modx->lexicon('ekoform.error_phone');
} else {
    $values['phone'] = '+' . $phone;
}

if ($values['email'] !== '' && !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = $modx->lexicon('ekoform.error_email');
}

if (mb_strlen($values['message']) > 2000) {
    $errors['message'] = $modx->lexicon('ekoform.error_message');
}

if ($errors) {
    return $render($values, $errors, false);
}

/** @var \ekoform\EkoFormLead $lead */
$lead = $modx->newObject(\ekoform\EkoFormLead::class);
$lead->fromArray([
    'first_name' => $values['first_name'],
    'last_name' => $values['last_name'],
    'email' => $values['email'],
    'phone' => $values['phone'],
    'message' => $values['message'],
    'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
    'createdon' => date('Y-m-d H:i:s'),
]);

if (!$lead->save()) {
    $modx->log(modX::LOG_LEVEL_ERROR, '[ekoForm] не вдалося зберегти заявку', '', '', __FILE__, __LINE__);

    return $render($values, ['form' => $modx->lexicon('ekoform.error_save')], false);
}

$apiUrl = rtrim((string) $modx->getOption('ekoform.api_url', null, ''), '/');
$apiKey = (string) $modx->getOption('ekoform.api_key', null, '');

if ($apiUrl === '' || $apiKey === '') {
    $lead->set('crm_status', 'skipped');
    $lead->save();
    $modx->log(modX::LOG_LEVEL_WARN, '[ekoForm] CRM не налаштована, заявку збережено локально', '', '', __FILE__, __LINE__);

    return $render($values, [], true);
}

$payload = json_encode([
    'getResultData' => '1',
    'fName' => $lead->get('first_name'),
    'lName' => $lead->get('last_name'),
    'phone' => $lead->get('phone'),
    'email' => $lead->get('email'),
    'comment' => $lead->get('message'),
    'externalId' => 'modx-' . $lead->get('id'),
], JSON_UNESCAPED_UNICODE);

$ch = curl_init($apiUrl . '/handler/');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'X-Api-Key: ' . $apiKey],
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 20,
]);
$body = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

$answer = json_decode((string) $body, true);

if ($curlError !== '' || $status >= 400 || empty($answer['success'])) {
    $reason = $curlError !== '' ? $curlError : 'HTTP ' . $status . ' ' . (string) $body;
    $lead->set('crm_status', 'failed');
    $lead->set('crm_error', mb_substr($reason, 0, 500));
    $lead->save();
    $modx->log(modX::LOG_LEVEL_ERROR, '[ekoForm] SalesDrive не прийняв заявку #' . $lead->get('id') . ': ' . $reason, '', '', __FILE__, __LINE__);

    // заявка вже збережена, тому користувачу показуємо успіх
    return $render($values, [], true);
}

$lead->set('crm_status', 'sent');
$lead->set('crm_order_id', (string) ($answer['data']['orderId'] ?? ''));
$lead->save();

$modx->log(modX::LOG_LEVEL_INFO, '[ekoForm] заявку #' . $lead->get('id') . ' передано в SalesDrive, order ' . $lead->get('crm_order_id'), '', '', __FILE__, __LINE__);

return $render($values, [], true);
