<?php

namespace Dynamic\Elements\Oembed\Task;

use SilverStripe\Dev\BuildTask;
use SilverStripe\Versioned\Versioned;
use Dynamic\Elements\Oembed\Elements\ElementOembed;

class EmbedMigrationTask extends BuildTask
{
     /**
     * @var string
     */
    protected $title = 'Embed Migration Task';

    /**
     * @var string
     */
    protected $description = 'Migrate embed blocks from old gorricoe/linkable to nathancox/embedfield.';

    /**
     * @var string
     */
    private static string $segment = 'EmbedMigrationTask';

    /**
     * @param $request
     * @return void
     */
    public function run($request): void
    {
        $objects = ElementOembed::get();

        foreach ($objects as $object) {
            $object->write();
            $object->writeToStage(Versioned::DRAFT);
            $object->publishSingle();
        }
    }
}
