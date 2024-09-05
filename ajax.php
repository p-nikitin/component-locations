<?php

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Loader;

class SLocationSearchController extends Controller
{
    public function configureActions()
    {
        return [
            'search' => ['prefilters' => []]
        ];
    }

    public function searchAction($q)
    {
        Loader::includeModule('sale');
        CBitrixComponent::includeComponentClass('bitrix:sale.location.selector.search');

        $q = htmlspecialchars(trim($q));

        if ($q) {
            $result = \Bitrix\Sale\Location\Search\Finder::find([
                'filter' => ['=PHRASE' => $q, 'NAME.LANGUAGE_ID' => LANGUAGE_ID, 'TYPE_ID' => 5],
                'select' => ['ID', 'NAME.NAME', 'TYPE_ID'],
                'limit' => 10
            ])->fetchAll();

            return $result;
        }
        return [];
    }
}