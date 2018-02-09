<?php
/**
 * User: darkfriend <hi@darkfriend.ru>
 * Date: 07.02.2018
 * Time: 22:41
 */

define("NO_KEEP_STATISTIC", true); // Не собираем стату по действиям AJAX
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
global $APPLICATION, $USER;
//error_reporting(E_ALL);
\Bitrix\Main\Loader::includeModule('iblock');
\Bitrix\Main\Loader::includeModule('highloadblock');

include_once __DIR__.'/HLHelpers.php';

if(!$USER->IsAdmin()) {
	echo 'Доступ запрещен!';
	die();
}

// config
$iblockID = 0; // IBLOCK_ID vilkaseo
$iblockType = ''; // IBLOCK_TYPE vilkaseo
$hlIblockID = 0; // ENTITY_ID Dev2funMultiDomainSeo

if(!$iblockID||!$iblockType||!$hlIblockID) {
	die('Вы не заполнили конфиг');
}

$page = 1;
if(!empty($_GET['page'])) {
	$page = $_GET['page'];
}

/**
 * @param int $iblockID
 * @param string $iblockType
 * @param int $page
 * @return CIBlockResult|int
 */
function darkQuery($iblockID,$iblockType,$page=1) {
	return CIBlockElement::GetList(
		[],
		[
			'ACTIVE' => 'Y',
			'IBLOCK_ID' => $iblockID,
			'IBLOCK_TYPE' => $iblockType,
		],
		false,
		[
			'nPageSize' => 50,
			'iNumPage' => $page,
		],
		[
			'IBLOCK_ID', 'ID', 'ACTIVE', 'IBLOCK_TYPE',
			'PROPERTY_*', 'NAME', 'CODE',
		]
	);
}

/**
 * @param CIBlockResult $oElements
 * @param int $hlIblockID
 */
function darkEach($oElements, $hlIblockID) {
	while ($oElement = $oElements->GetNextElement()) {
		$fields = $oElement->GetFields();
		$properties = $oElement->GetProperties();
		$url = $fields['NAME'];
		if(!preg_match("(http)",$url)) {
			$url = 'http://'.$url;
		}
		$arUrl = parse_url($url);
		$path = '';
		if(!empty($arUrl['path'])) {
			$path = $arUrl['path'];
		}
		$domain = '';
		if(!empty($arUrl['host'])) {
			$domain = $arUrl['host'];
		}

		$title = '';
		if(!empty($properties['VS_TITLE']['VALUE'])) {
			$title = $properties['VS_TITLE']['VALUE'];
		}

		$description = '';
		if(!empty($properties['VS_DESCRIPTION']['VALUE'])) {
			$description = $properties['VS_DESCRIPTION']['VALUE']['TEXT'];
		}

		$keywords = '';
		if(!empty($properties['VS_KEYWORDS']['VALUE'])) {
			$keywords = $properties['VS_KEYWORDS']['VALUE'];
		}
		if(!$path||!$title) continue;

		$hl = \Darkfriend\HLHelpers::getInstance();
		$res = $hl->addElement($hlIblockID,[
			'UF_TITLE' => $title,
			'UF_PATH' => $path,
			'UF_DOMAIN' => $domain,
			'UF_DESCRIPTION' => $description,
			'UF_KEYWORDS' => $keywords,
		]);
		if(!$res) {
			var_dump('ID='.$fields['ID'],\Darkfriend\HLHelpers::$LAST_ERROR);
			die();
		}
	}
}

$oElements = darkQuery($iblockID,$iblockType, $page);
$pages = $oElements->NavPageCount;

darkEach($oElements, $hlIblockID);

if($pages>$page) {
	$page++;
	$redirectUrl = $APPLICATION->GetCurPageParam('page='.$page,['page']);
	$redirectUrl = (CMain::IsHTTPS()?'https://':'http://').$_SERVER['HTTP_HOST'].$redirectUrl;
	header('Location: '.$redirectUrl);
}
die('миграция завершена');

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");