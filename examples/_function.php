<?php

if (!function_exists('getEnvVarValue')) {
    function getEnvVarValue(string $name): string
    {
        if ('' === $envvar = (string) getenv($name)) {
            exit(sprintf('No env var with name "%s" found.', $name));
        }

        return $envvar;
    }
}