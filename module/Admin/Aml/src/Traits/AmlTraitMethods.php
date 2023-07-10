<?php

declare(strict_types=1);

namespace Aml\Traits;

trait AmlTraitMethods
{

    /////////////////////////////////////
    ////////// Private Methods //////////
    /////////////////////////////////////

    private function arrayDiff(
        array $array1,
        array $array2,
        string $key,
        bool $reverse = false
    ): array {
        $return = [];
        if (! empty($key))
        {
            $arr1 = $reverse === true ? $array2 : $array1;
            $arr2 = $reverse === true ? $array1 : $array2;
            $col1 = array_column($arr1, $key);
            $col2 = array_column($arr2, $key);
            $return = array_diff($col1, $col2);
            sort($return);
        }
        return $return;
    }

    private function arrayIntersect(
        array $array1,
        array $array2,
        string $key1,
        string $key2,
        bool $reverse = false
    ): array {
        $return = [];
        if (! empty($key1) && ! empty($key2))
        {
            $arr1 = $reverse === true ? $array2 : $array1;
            $arr2 = $reverse === true ? $array1 : $array2;
            $col11 = array_column($arr1, $key1);
            $col12 = array_column($arr1, $key2);
            $col21 = array_column($arr2, $key1);
            $col22 = array_column($arr2, $key2);
            $intersection = array_intersect($col11, $col21);
            for ($i = 0; $i < count($col21); $i++)
            {
                if (
                    in_array($col21[$i], $intersection)
                ) {
                    $key = array_search($col21[$i], $col11);
                    if(
                        $key !== false
                        && $col22[$i] != $col12[$key]
                    ) {
                        $return[] = $col21[$i];
                    }
                }
            }
        }
        return $return;
    }

    private function arraySortUniqueByKey(array $array, string $key): array
    {
        $return = [];
        if (! empty($array) && ! empty($key)) {
            $col = array_unique(array_column($array, $key));
            if (! empty($col)) {
                $unique = array_intersect_key($array, $col);
                if (! empty($unique)) {
                    $unique_col = array_column($unique, $key);
                    array_multisort($unique_col, SORT_ASC, SORT_STRING, $unique);
                    $return = $unique;
                }
            }
        }
        return $return;
    }

    private function csv2Array(
        string $data,
        bool $with_header = false,
        string $delimiter = ',',
        string $enclosure = '"'
    ): array {
        $return = [];
        if (! empty($data)) {
            $lines = explode(PHP_EOL, $data);
            foreach ($lines as $line) {
                $return[] = str_getcsv($line, $delimiter, $enclosure);
            }
            if (! $with_header) {
                $header = array_shift($return);
            }
        }
        return $return;
    }

    private function csv2Json(string $data): array
    {
        $return = [];
        if (! empty($data)) {
            $array = $this->csv2Array($data, true);
            if (! empty($array)) {
                $header = array_shift($array);
                foreach ($array as $items) {
                    $row = [];
                    $i = 0;
                    foreach ($items as $value) {
                        $row[$header[$i]] = $value;
                        $i++;
                    }
                    $return[] = $row;
                }
            }
        }
        return $return;
    }

    private function sanitizeBirthdate(?string $input): ?string
    {
        $return = null;
        if (! empty($input))
        {
            $pattern1 = "/\d{4}\-\d{2}\-\d{2}/u";
            $pattern2 = "/(\d{1,4})\D{0,1}?/u";
            preg_match($pattern1, $input, $match);
            if (! empty($match[0]))
            {
                $return = $match[0];
            } else {
                $match = null;
                preg_match_all($pattern2, $input, $match);
                if (
                    ! empty($match[0])
                    && count($match[0]) >= 3
                    && (
                        strlen($match[0][0]) == 4
                    )
                ) {
                    $mktime = mktime(
                        (int) 0,
                        (int) 0,
                        (int) 0,
                        (int) $match[0][1],
                        (int) $match[0][2],
                        (int) $match[0][0]
                    );
                    $return = date('Y-m-d', $mktime);
                }
            }
        }
        return $return;
    }

    private function preSanitizeFullname(?string $input): ?string
    {
        $return = null;
        if (! empty($input))
        {
            $pattern = "/\(\s*\b.*\b\s*\)/u";
            $input = $this->sanitize(preg_replace($pattern, '', $input));
            if (! empty($input))
            {
                $return = $input;
            }
        }
        return $return;
    }

    private function sanitizeFullname(?string $input): ?string
    {
        $return = null;
        if (! empty($input))
        {
            $pattern = "/\b(?:bc|bs|bsc|csc|doc|dr|ing|jd|jr|judr|llm|ma|mba|md|med|mgr|miss|mr|mrs|ms|mudr|phd|phdr)\b/iu";
            $input = $this->sanitize(preg_replace($pattern, '', $input));
            if (! empty($input))
            {
                $return = $input;
            }
        }
        return $return;
    }

    private function sanitize(?string $input): string
    {
        $input = preg_replace(["/\x{00a0}/miu", "/\s+/miu"], [' ', ' '], (string) $input);
        return (string) trim($input);
    }
}
