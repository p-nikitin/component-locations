<?php
/**
 * @var $arResult array
 * @var $templateFolder string
 */
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
$this->setFrameMode(true);
\Bitrix\Main\UI\Extension::load(['izifir.locations']);
?>

<?php $frame = $this->createFrame()->begin('Ищем вас...'); ?>
<div class="header__location" data-move="header-location" id="header-location">
    <div class="location">
        <button class="location__link js-header-location-title">
            <span class="location__label js-header-location-label"><?= $arResult['CITY_NAME'] ?></span>
        </button>
    </div>
</div>
<script>
    new BX.Izifir.Locations('header-location', {
        popupUrl: '<?= $templateFolder ?>/popup.php'
    });
</script>
<?php $frame->end(); ?>
