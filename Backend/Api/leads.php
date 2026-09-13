<?php

declare(strict_types=1);

require __DIR__ . '/lib/bootstrap.php';

requireMethod('POST');

/**
 * Consent: neither form has a real checkbox — the text under the submit
 * button ("Нажимая кнопку, вы соглашаетесь...") implies consent-by-
 * submission, so consent_given is recorded as TRUE below on that basis.
 * If a real checkbox is added later, read it here instead and reject the
 * request when it's unchecked.
 */

$body = requestBody();

$formType = is_string($body['form_type'] ?? null) ? trim($body['form_type']) : '';
if (!in_array($formType, ['contact_short', 'measurement_request'], true)) {
    fail(422, 'Проверьте поля формы.', ['form_type' => 'Неизвестный или отсутствующий тип формы.']);
}
$isMeasurement = $formType === 'measurement_request';

$errors = [];
$data = [];
foreach (['name' => 100, 'phone' => 32, 'email' => 254, 'message' => 4000] as $key => $max) {
    $value = $body[$key] ?? '';
    if (!is_string($value) || preg_match('//u', $value) !== 1 || preg_match_all('/./us', $value) > $max) {
        $errors[$key] = 'Некорректное значение или превышена допустимая длина.';
        $value = '';
    }
    $data[$key] = trim($value);
}

if ($data['name'] === '') {
    $errors['name'] = 'Укажите имя.';
}

$digits = preg_replace('/\D/', '', $data['phone']);
if (!preg_match('/^[+0-9()\s-]+$/', $data['phone']) || strlen($digits) < 10 || strlen($digits) > 15) {
    $errors['phone'] = 'Укажите телефон: от 10 до 15 цифр.';
}

// Email only exists on the measurement-request form.
if (!$isMeasurement) {
    $data['email'] = '';
} elseif ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Укажите корректный email.';
}

// Object type (chip group) only exists on the measurement-request form.
$projectTypeId = null;
if ($isMeasurement) {
    $typeSlug = is_string($body['project_type'] ?? null) ? trim($body['project_type']) : '';
    if ($typeSlug !== '') {
        $found = rows('SELECT id FROM project_types WHERE slug = :slug', ['slug' => $typeSlug]);
        if (!$found) {
            $errors['project_type'] = 'Неизвестный тип объекта.';
        } else {
            $projectTypeId = $found[0]['id'];
        }
    }
}

// Area only exists on the measurement-request form.
$area = $isMeasurement ? ($body['area'] ?? null) : null;
if ($area === '') {
    $area = null;
}
if ($area !== null) {
    $isNumericScalar = is_string($area) || is_int($area) || is_float($area);
    if (!$isNumericScalar || !is_numeric($area) || (float) $area <= 0 || (float) $area > 100000) {
        $errors['area'] = 'Площадь должна быть больше 0 и не больше 100000 м².';
    }
}

if ($errors) {
    fail(422, 'Проверьте поля формы.', $errors);
}

$result = rows(
    'INSERT INTO leads (form_type, name, phone, email, project_type_id, area_m2, message, source_page, consent_given)
     VALUES (:form_type, :name, :phone, :email, :project_type_id, :area_m2, :message, :source_page, TRUE)
     RETURNING id, status, created_at',
    [
        'form_type' => $formType,
        'name' => $data['name'],
        'phone' => $data['phone'],
        'email' => $data['email'] !== '' ? $data['email'] : null,
        'project_type_id' => $projectTypeId,
        'area_m2' => $area !== null ? (float) $area : null,
        'message' => $data['message'] !== '' ? $data['message'] : null,
        'source_page' => $isMeasurement ? 'request' : 'contacts',
    ]
);

respond(['data' => $result[0], 'message' => 'Заявка принята. Мы свяжемся с вами.'], 201);
