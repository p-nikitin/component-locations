<?php

use Bitrix\Main\Service\GeoIp;
use Bitrix\Main\Loader;
use Izifir\Core\App;
use Bitrix\Main\Data\Cache;
use Izifir\Core\City;

class SLocationsComponent extends CBitrixComponent
{
    private $fakeIp = false;
    
    /**
     * @param $arParams
     * @return array
     */
    public function onPrepareComponentParams($arParams): array
    {
        $arParams['CACHE_TIME'] = 2678400;
        $arParams['IBLOCK_ID'] = $arParams['IBLOCK_ID'] ?: false;
        return $arParams;
    }
    
    /**
     * @return mixed|void|null
     */
    public function executeComponent()
    {
        $cityId = $this->request->getQuery('city');

        // Получим IP адрес пользователя
        $this->arResult['REAL_IP'] = $this->getRealIp();
        if ($cityId) {
            $this->setCity($cityId);
            $uri = new \Bitrix\Main\Web\Uri($this->request->getRequestUri());
            $uri->deleteParams(['city']);
            LocalRedirect($uri->getUri());
        } else {
            $this->detectLocation();
        }
        $this->includeComponentTemplate();
    }
    
    /**
     * @return array|mixed
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    protected function getCityByIp()
    {
        Loader::includeModule('iblock');
        Loader::includeModule('sale');

        $result = [];
        $cache = Cache::createInstance();
        if ($cache->initCache($this->arParams['CACHE_TIME'], md5($this->arResult['REAL_IP']), 'izifir/locations')) {
            $vars = $cache->getVars();
            $result = $vars['result'];
        } elseif($cache->startDataCache()) {
            $cityName = false;
            if (!$this->isBot()) {
                // Пробуем определить город по IP адресу
                $httpClient = new \Bitrix\Main\Web\HttpClient();
                $data = $httpClient->get('http://ru.sxgeo.city/json/' . $this->arResult['REAL_IP']);
                $geoData = json_decode($data, true);
                $cityName = $geoData['city']['name_ru'];
            }

            // Если определить город не получилось, то будем искать город, определенный как основной
            if (empty($cityName) && $this->arParams['IBLOCK_ID']) {
                $cityElement = CIBlockElement::GetList(
                    [],
                    ['IBLOCK_ID' => $this->arParams['IBLOCK_ID'], '!PROPERTY_MAIN' => false],
                    false,
                    false,
                    ['ID', 'IBLOCK_ID', 'NAME']
                )->Fetch();
                $cityName = $cityElement['NAME'];
            }
            // Если город найден, определим ID местоположения для магазина
            if ($cityName) {
                $mainCity = City::getCity(['NAME' => $cityName])->GetNext();
                $defaultCity = City::getDefaultCity(false);

                if (!$mainCity['PROPERTY_LOCATION_ID_VALUE']) {
                    $saleCity = \Bitrix\Sale\Location\LocationTable::getList([
                        'filter' => ['NAME.NAME_UPPER' => mb_strtoupper($cityName)],
                        'select' => ['ID', 'NAME']
                    ])->fetch();
                }
                $result['CITY_NAME'] = $cityName;
                $result['LOCATION_ID'] = $mainCity['PROPERTY_LOCATION_ID_VALUE'] ?: $saleCity['ID'];
                $result['PHONE'] = $mainCity['PROPERTY_PHONE_VALUE'] ?: $defaultCity['PROPERTY_PHONE_VALUE'];
                $result['EMAIL'] = $mainCity['PROPERTY_EMAIL_VALUE'] ?: $defaultCity['PROPERTY_EMAIL_VALUE'];
                $result['ADDRESS'] = $mainCity['PROPERTY_ADDRESS_VALUE'] ?: $defaultCity['PROPERTY_ADDRESS_VALUE'];
                $result['INSTAGRAM_LINK'] = $mainCity['PROPERTY_INSTAGRAM_LINK_VALUE'] ?: $defaultCity['PROPERTY_INSTAGRAM_LINK_VALUE'];
                $result['VK_LINK'] = $mainCity['PROPERTY_VK_LINK_VALUE'] ?: $defaultCity['PROPERTY_VK_LINK_VALUE'];
                $result['WHATSAPP'] = $mainCity['PROPERTY_WHATSAPP_VALUE'] ?: $defaultCity['PROPERTY_WHATSAPP_VALUE'];

                $cache->endDataCache(['result' => $result]);
            } else {
                $cache->abortDataCache();
            }
        }
        return $result;
    }

    protected function detectLocation()
    {
        $currentCity = $_SESSION[App::SESSION_LOCATION_NAME];
        if (!$currentCity) {
            $currentCity = $this->getCityByIp();
            $_SESSION[App::SESSION_LOCATION_NAME] = $currentCity;
        }
        $this->arResult = array_merge($this->arResult, $currentCity);
    }

    protected function setCity($cityId)
    {
        Loader::includeModule('sale');

        if ($cityId) {
            $city = \Bitrix\Sale\Location\Search\Finder::find([
                'filter' => ['ID' => $cityId, 'NAME.LANGUAGE_ID' => LANGUAGE_ID],
                'select' => ['ID', 'NAME']
            ])->fetch();
            if ($city) {

                $mainCity = City::getCity(['NAME' => $city['SALE_LOCATION_LOCATION_NAME_NAME']])->GetNext();
                $defaultCity = City::getDefaultCity(false);

                $_SESSION[App::SESSION_LOCATION_NAME] = [
                    'CITY_NAME' => $city['SALE_LOCATION_LOCATION_NAME_NAME'],
                    'LOCATION_ID' => $cityId,
                    'PHONE' => $mainCity['PROPERTY_PHONE_VALUE'] ?: $defaultCity['PROPERTY_PHONE_VALUE'],
                    'EMAIL' => $mainCity['PROPERTY_EMAIL_VALUE'] ?: $defaultCity['PROPERTY_EMAIL_VALUE'],
                    'ADDRESS' => $mainCity['PROPERTY_ADDRESS_VALUE'] ?: $defaultCity['PROPERTY_ADDRESS_VALUE'],
                    'INSTAGRAM_LINK' => $mainCity['PROPERTY_INSTAGRAM_LINK_VALUE'] ?: $defaultCity['PROPERTY_INSTAGRAM_LINK_VALUE'],
                    'VK_LINK' => $mainCity['PROPERTY_VK_LINK_VALUE'] ?: $defaultCity['PROPERTY_VK_LINK_VALUE'],
                    'WHATSAPP' => $mainCity['PROPERTY_WHATSAPP_VALUE'] ?: $defaultCity['PROPERTY_WHATSAPP_VALUE'],
                ];
                $this->arResult = array_merge($this->arResult, $_SESSION[App::SESSION_LOCATION_NAME]);
            }
        }
    }

    /**
     * Возвращает реальный IP пользователя
     * @return false|mixed|string
     */
    protected function getRealIp()
    {
        // Предусматриваем возможность указать произвольный IP адрес
        $fakeIp = $this->request->getQuery('fake-ip');
        if ($fakeIp) {
            $this->fakeIp = true;
            return $fakeIp;
        }
        // А так вернем реальный IP пользователя
        return GeoIp\Manager::getRealIp();
    }

    protected function isBot()
    {
        return preg_match(
            "~(Google|Yahoo|Rambler|Bot|Yandex|Spider|Snoopy|Crawler|Finder|Mail|curl)~i",
            $_SERVER['HTTP_USER_AGENT']
        );
    }
}
