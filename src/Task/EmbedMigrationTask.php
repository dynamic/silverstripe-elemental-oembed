<?php

namespace Dynamic\Elements\Oembed\Task;

use SilverStripe\Dev\BuildTask;
use SilverStripe\PolyExecution\PolyOutput;
use SilverStripe\Versioned\Versioned;
use Symfony\Component\Console\Input\InputInterface;
use Dynamic\Elements\Oembed\Elements\ElementOembed;

class EmbedMigrationTask extends BuildTask
{
    protected string $title = 'Embed Migration Task';

    protected static string $description = 'Migrate embed blocks from old gorricoe/linkable to nathancox/embedfield.';

    /**
     * @var string
     */
    private static string $segment = 'EmbedMigrationTask';

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $objects = ElementOembed::get();

        foreach ($objects as $object) {
            $object->write();
            $object->writeToStage(Versioned::DRAFT);
            $object->publishSingle();
        }

        return 0;
    }
}
