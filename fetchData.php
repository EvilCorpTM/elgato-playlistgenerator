<?php
// by cat6e

$channels = array();

/*
Astra 19.2E https://www.satindex.de/transponderv-1.php
Astra 28.2E https://www.satindex.de/transponderv-3.php
... (and the other URLs from satindex.de
*/
include("config.php");


function preg_first($re, $text) {
	preg_match_all($re, $text, $matches, PREG_SET_ORDER, 0);
	return $matches[0];
}

function getTransponders($url) {
	$db = array();
	$html=file_get_contents($url);
	$html = trim(preg_replace('/\s\s+/', ' ', $html));
	$html=substr($html, strpos($html, '</div><div class="freq_kopf" >'));
	$html=substr($html, strpos($html, 'FEC</div>')+strlen('FEC</div>'));
	$html=explode('"clear: both;"', $html);
	unset($html[0]);
	$re = '/<div class="tp_tab_a1" >(\s?\d+\.)<\/div> <div class="tp_tab_a2" >([0-9.]+)<\/div> <div.*><a.*>([0-9.]*)<\/a><\/div><div class="tp_tab_a1" >(H|V)<\/div> <div class="tp_tab_a2" >([0-9.]+)<\/div> <div class="tp_tab_a1" >(\d+\/\d+)<\/div> <div class="tp_tab_a2" >(DVB-S\d?)<\/div> <div class="tp_tab_a2" >([0-9A-Za-z]+)<\/div> <div class="tp_tab_a1" >([0-9.]*)<\/div> <div class="tp_tab_a3" ><img.*><\/div> <div class="tp_tab_a1" >(\d+)<\/div> <div class="tp_tab_a4" >(.*)<\/div>/im';
	foreach($html as $transponder) {
		$transponderM=preg_first($re, $transponder);
		unset($transponderM[0]);
		$transponder=array(
			"transponder" => intval($transponderM[2]),
			"tsid" => intval($transponderM[10]),
			"freq" => intval($transponderM[3]),
			"pol" => $transponderM[4],
			"rate" => intval($transponderM[5]),
			"fec" => $transponderM[6],
			"type" => $transponderM[7],
			"modulation" => $transponderM[8],
		);
		$db[intval($transponder["freq"])] = $transponder;
	}
	return $db;
}
function processChannel($channelLink) {
	$pageLink = "https://www.satindex.de".$channelLink;
        $pageCache = "cache/".urlencode(base64_encode($pageLink));
        if (!file_exists($pageCache)) { file_put_contents($pageCache, file_get_contents($pageLink)); }
        $html = file_get_contents($pageCache);
	$pmtID = preg_first('/class="cell_l"\s+>PMT Pid:<\/div><div\s+class="cell_r"\s+>(\d+)<\/div>/m', $html)[1];
	return array($pmtID);
}
function processTransponder($transponder) {
	global $channels;

	$pageLink = "https://www.satindex.de/frequenz/".$transponder["freq"];
	$pageCache = "cache/".urlencode(base64_encode($pageLink));
	if (!file_exists($pageCache)) { file_put_contents($pageCache, file_get_contents($pageLink)); }
	$html = file_get_contents($pageCache);
	$html = trim(preg_replace('/\s\s+/', ' ', $html));
	$html = substr($html, strpos($html, '<div class="tvra"'));
	$html = substr($html, 0, strpos($html, '"tp_info_end"'));

	echo "Transponder:\t".$transponder["transponder"]."\n\n";

	$html = explode('"clear: both;"', $html);
	$re = '/<div class="freq_color_[01]" > <div class="col freqx_1" ><a href="(.*)" title=.*>(.*)<\/a><\/div> <div class="col freqx_2" ><img.*><\/div> <div class="col freqx_2" ><img .*><\/div> <div class="col freqx_2" ><img.*><\/div> <div class="col freqx_3" >(.*)<\/div> <div class="col freqx_4" >(\d*)<\/div> <div class="col freqx_4" >(.*)<\/div> <div class="col freqx_5" >(.*)<\/div> <div class="col freqx_4" >(.*)<\/div> <div class="col freqx_4" >(.*)<\/div> <div class="col2 freqx_6" >.*<\/div>/im';
	unset($html[count($html) - 1]);
	foreach($html as $channel) {
		if (strpos("freq_color_", $channel) === false) {
			$matchCount = preg_match_all($re, $channel, $channelM, PREG_SET_ORDER, 0);
			if ($matchCount  != 1) {
				continue;
			}
			$channelM = $channelM[0];
			unset($channelM[0]);
			$pidsSlice = array_slice($channelM, 4);
			$pids=array();
			foreach($pidsSlice as $pid) {
				if (intval($pid) != 0) {
					if ((strpos("|", $pid) === false)) {
						$multiKultiPIDs_xD = explode('|', $pid);
						foreach($multiKultiPIDs_xD as $pidpid) {
							array_push($pids, intval(preg_replace( '/[^0-9]/', '', $pidpid)));
						}
					} else {
						array_push($pids, intval(preg_replace( '/[^0-9]/', '', $pid)));
					}
				}
			}
			$pids=array_merge($pids, processChannel($channelM[1]));
			$channel=array(
				"link" => $channelM[1],
				"name" => $channelM[2],
				"kategorie" => $channelM[3],
				"transponder" => $transponder,
				"pids" => $pids,
			);
			print_r($channel);
			array_push($channels, $channel);
		}
	}
}


$transponder = getTransponders($transponderDB);
foreach($transponder as $t) {
	processTransponder($t);
}
file_put_contents("channels.json", json_encode($channels));
?>
