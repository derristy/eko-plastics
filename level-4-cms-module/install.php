<?php

/**
 * Готовит компонент к работе: генерирует классы модели из схемы,
 * создаёт таблицу и регистрирует сниппет.
 *
 * Запуск из корня сайта:  php install.php
 */

$root = __DIR__;
while (!is_file($root . '/config.core.php') && dirname($root) !== $root) {
    $root = dirname($root);
}

if (!is_file($root . '/config.core.php')) {
    exit("не нашёл config.core.php, запустите скрипт внутри сайта на MODX\n");
}

require_once $root . '/config.core.php';
require_once MODX_CORE_PATH . 'vendor/autoload.php';

$modx = new MODX\Revolution\modX();
$modx->initialize('mgr');
$modx->getService('error', 'error.modError');
$modx->setLogTarget('ECHO');

$component = MODX_CORE_PATH . 'components/ekoform/';

if (!is_dir($component)) {
    exit("нет каталога $component, скопируйте core/components/ekoform в core/components\n");
}

$manager = $modx->getManager();
$generator = $manager->getGenerator();

// генератор портит уже существующие классы, поэтому чистим перед генерацией
$generated = $component . 'model/ekoform/';
if (is_dir($generated)) {
    foreach (glob($generated . '{,mysql/}*.php', GLOB_BRACE) as $old) {
        unlink($old);
    }
}

$generator->parseSchema(
    $component . 'model/schema/ekoform.mysql.schema.xml',
    $component . 'model/'
);
echo "классы модели сгенерированы\n";

// xPDO 3 не подхватывает классы пакета автоматически, подключаем явно
foreach ([$component . 'model/ekoform/EkoFormLead.php', $component . 'model/ekoform/mysql/EkoFormLead.php'] as $classFile) {
    if (is_file($classFile)) {
        require_once $classFile;
    }
}

$modx->addPackage('ekoform', $component . 'model/', null, 'ekoform\\');

if ($manager->createObjectContainer(\ekoform\EkoFormLead::class)) {
    echo "таблица ekoform_leads создана\n";
} else {
    echo "таблица уже существует\n";
}

$namespace = $modx->getObject(MODX\Revolution\modNamespace::class, ['name' => 'ekoform']);
if (!$namespace) {
    $namespace = $modx->newObject(MODX\Revolution\modNamespace::class);
    $namespace->set('name', 'ekoform');
}
$namespace->set('path', '{core_path}components/ekoform/');
$namespace->set('assets_path', '{assets_path}components/ekoform/');
$namespace->save();
echo "namespace ekoform зарегистрирован\n";

$snippet = $modx->getObject(MODX\Revolution\modSnippet::class, ['name' => 'ekoForm']);
if (!$snippet) {
    $snippet = $modx->newObject(MODX\Revolution\modSnippet::class);
    $snippet->set('name', 'ekoForm');
}
$snippet->set('description', 'Форма зворотного зв\'язку з передачею заявки в SalesDrive');
$snippet->set('snippet', file_get_contents($component . 'snippet.ekoform.php'));
$snippet->save();
echo "сниппет ekoForm зарегистрирован\n";

$chunk = $modx->getObject(MODX\Revolution\modChunk::class, ['name' => 'ekoFormTpl']);
if (!$chunk) {
    $chunk = $modx->newObject(MODX\Revolution\modChunk::class);
    $chunk->set('name', 'ekoFormTpl');
}
$chunk->set('description', 'Розмітка форми зворотного зв\'язку');
$chunk->set('snippet', file_get_contents($component . 'elements/ekoFormTpl.tpl'));
$chunk->save();
echo "чанк ekoFormTpl зарегистрирован\n";

foreach (['ekoform.api_url' => 'https://derristy.salesdrive.me', 'ekoform.api_key' => ''] as $key => $value) {
    $setting = $modx->getObject(MODX\Revolution\modSystemSetting::class, ['key' => $key]);
    if ($setting) {
        continue;
    }
    $setting = $modx->newObject(MODX\Revolution\modSystemSetting::class);
    $setting->fromArray([
        'key' => $key,
        'value' => $value,
        'xtype' => 'textfield',
        'namespace' => 'core',
        'area' => 'ekoform',
    ], '', true, true);
    $setting->save();
    echo "настройка $key добавлена\n";
}

$modx->getCacheManager()->refresh();
echo "готово, кеш очищен\n";
