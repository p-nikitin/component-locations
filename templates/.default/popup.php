<?php

use Izifir\Core\Helpers\Iblock;

const NO_KEEP_STATISTIC = true;
const NO_AGENT_STATISTIC = true;
const NO_AGENT_CHECK = true;
const NOT_CHECK_PERMISSIONS = true;

require_once($_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/main/include/prolog_before.php');

\Bitrix\Main\Loader::includeModule('iblock');

$cityIterator = CIBlockElement::GetList(
    ['SORT' => 'asc'],
    [
        'IBLOCK_ID' => Iblock::getIblockIdByCode('dictionaries_city'),
        'ACTIVE' => 'Y'
    ],
    false,
    false,
    ['ID', 'IBLOCK_ID', 'NAME', 'PROPERTY_LOCATION_ID']
);
$defaultCityList = [];
while ($city = $cityIterator->GetNext()) {
    $defaultCityList[] = $city;
}

?>

<button class="modal-location__close js-header-location-close" aria-label="Закрыть окно"></button>
<div class="modal-location__head">
    <div class="modal-location-title">
        <div class="modal-location-title__label">Выбрать город</div>
    </div>
    <div class="modal-location__form">
        <form action="" class="modal-location-form">
            <input class="input js-header-location-input" type="text" placeholder="Введите город">
            <button class="submit">
                <svg class="i-icon i-search">
                    <use xlink:href="#i-search"></use>
                </svg>
            </button>
        </form>
    </div>
</div>
<div class="modal-location__body">
    <?php if (!empty($defaultCityList)) : ?>
        <ul class="modal-location-list js-header-location-default-list">
            <?php foreach ($defaultCityList as $city) : ?>
                <li class="modal-location-list__item">
                <a href="?city=<?= $city['PROPERTY_LOCATION_ID_VALUE'] ?>" class="modal-location-list__link">
                    <span><?= $city['NAME'] ?></span>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    <?php endif ?>
    <ul class="modal-location-list js-header-location-search-list" style="display: none;">
        <li class="modal-location-list__item">
            <span>Поиск...</span>
        </li>
    </ul>
</div>
