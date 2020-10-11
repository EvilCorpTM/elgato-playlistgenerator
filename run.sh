#!/bin/sh
php -f fetchData.php
php channelsToM3U.php > playlist.m3u8
echo "done, furk the payware"
