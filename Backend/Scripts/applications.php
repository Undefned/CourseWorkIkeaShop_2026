<?php
declare(strict_types=1);

function createApplication(): never
{
    $body = requestBody();
    $errors = [];
    $data = [];
    foreach (['name' => 100, 'phone' => 32, 'email' => 254, 'message' => 4000, 'type' => 20, 'object_type' => 20] as $key => $max) {
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
    if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Укажите корректный email.';
    }
    $data['type'] = $data['type'] ?: 'measurement';
    if (!in_array($data['type'], ['measurement', 'consultation', 'product', 'partner'], true)) {
        $errors['type'] = 'Неизвестный тип заявки.';
    }
    if ($data['object_type'] !== '' && !in_array($data['object_type'], ['apartment', 'house', 'commercial'], true)) {
        $errors['object_type'] = 'Неизвестный тип объекта.';
    }
    $area = $body['area'] ?? null;
    if ($area === '') {
        $area = null;
    }
    if ($area !== null && ((!is_string($area) && !is_int($area) && !is_float($area)) || !is_numeric($area) || (float) $area <= 0 || (float) $area > 100000)) {
        $errors['area'] = 'Площадь должна быть больше 0 и не больше 100000 м².';
    }
    $references = [];
    foreach (['product_id' => 'products', 'service_id' => 'services'] as $key => $table) {
        $value = $body[$key] ?? null;
        $references[$key] = null;
        if ($value !== null && $value !== '') {
            $id = is_scalar($value) && !is_bool($value) ? filter_var($value, FILTER_VALIDATE_INT) : false;
            if ($id === false || $id < 1 || !rows("SELECT id FROM $table WHERE id = :id AND is_active = TRUE", ['id' => $id])) {
                $errors[$key] = 'Выбранная позиция не найдена.';
            } else {
                $references[$key] = $id;
            }
        }
    }
    if ($data['type'] === 'product' && $references['product_id'] === null) {
        $errors['product_id'] = 'Укажите товар.';
    }
    if ($errors) {
        fail(422, 'Проверьте поля формы.', $errors);
    }
    $result = rows('INSERT INTO applications (name, phone, email, message, type, object_type, area, product_id, service_id)
        VALUES (:name, :phone, :email, :message, :type, :object_type, :area, :product_id, :service_id)
        RETURNING id, status, created_at', array_merge($data, $references, ['area' => $area]));
    respond(['data' => $result[0], 'message' => 'Заявка принята. Мы свяжемся с вами.'], 201);
}
