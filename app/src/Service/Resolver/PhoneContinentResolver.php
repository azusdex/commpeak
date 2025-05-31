<?php

namespace App\Service\Resolver;

class PhoneContinentResolver
{
    private array $prefix_to_continent = [];

    public function __construct(string $country_file_path)
    {
        $this->loadData($country_file_path);
    }

    private function loadData(string $file): void
    {
        $handle = fopen($file, 'r');
        while (($line = fgets($handle)) !== false) {
            if (str_starts_with($line, '#')) continue;
            $parts = explode("\t", $line);
            if (count($parts) >= 10) {
                $country_code = $parts[0];
                $continent = $parts[8];
                $prefix = $parts[12] ?? null;
                if ($prefix) {
                    $this->prefix_to_continent[$prefix] = $continent;
                }
            }
        }
        fclose($handle);
    }

    public function resolve(string $phone): ?string
    {
        foreach (array_keys($this->prefix_to_continent) as $prefix) {
            if (str_starts_with($phone, $prefix)) {
                return strtolower(trim($this->prefix_to_continent[$prefix]));
            }
        }

        return null;
    }
}