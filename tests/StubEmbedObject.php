<?php

namespace Dynamic\Elements\Oembed\Tests;

use Fromholdio\EmbedField\Model\EmbedObject;

/**
 * EmbedObject whose provider lookup is canned, so migration tests never touch the network.
 */
class StubEmbedObject extends EmbedObject
{
    /** @var array|null */
    public static $data;

    /** @var int */
    public static $lookups = 0;

    public static function retrieve_data_from_url(string $sourceURL): ?array
    {
        static::$lookups++;
        return static::$data;
    }
}
