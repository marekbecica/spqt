<?php

/**
 * @param string $configFile
 * @return array
 * @throws Exception
 */
function parseConfig(string $configFile): array {
    if (!file_exists($configFile)) {
        throw new Exception("File not found");
    }
    $file = fopen($configFile,'r');
    $result = [];
    while ($line = fgets($file)) {
        $line = trim($line);
        //filter out empty lines and lines with comments
        if (strlen($line) == 0 || preg_match('/^#/', $line) === 1) {
            continue;
        }
        $result = array_merge_recursive($result, parseLine($line));
    }
    fclose($file);
    return $result;
}

/**
 * @param string $line
 * @return array
 * @throws Exception
 */
function parseLine(string $line): array {
    $strArr = explode("=", $line);
    if (count($strArr) !== 2) {
        throw new Exception("Invalid format at (only one property and value allowed per line divided by '='): {$line}");
    }
    $prop = trim($strArr[0]);
    $keys = array_reverse(explode('.', $prop));
    $tmp = parseValue($strArr[1]);
    foreach ($keys as $key) {
        $tmp = [$key => $tmp];
    }
    return $tmp;
}

/**
 * @param string $value
 * @return bool|float|int|mixed|string
 */
function parseValue(string $value) {
    $value = trim($value);
    //For strings in double quotes
    if (preg_match('/^"(.*)"/', $value, $matches) === 1) {
        return $matches[1];
    }
    //For boolean values
    if (preg_match('/\b(true|false)\b/i', $value, $matches) === 1) {
        return boolval($matches[0]);
    }
    //For float and int
    if (is_numeric($value)) {
        if (strpos($value, '.') !== false) {
            return floatval($value);
        }
        return intval($value);
    }
    //for anything else return original string
    return $value;
}



var_dump(parseConfig(__DIR__ . "/config.txt"));