<?php

namespace Dynamic\Elements\Oembed\Tests;

use Dynamic\Elements\Oembed\Elements\ElementOembed;
use Fromholdio\EmbedField\Model\EmbedObject;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBField;

class ElementOembedTest extends SapphireTest
{
    /**
     * @var string
     */
    protected static $fixture_file = '../fixtures.yml';

    /**
     *
     */
    public function testGetCMSFields()
    {
        $object = $this->objFromFixture(ElementOembed::class, 'one');
        $fields = $object->getCMSFields();
        $this->assertInstanceOf(FieldList::class, $fields);
    }

    /**
     *
     */
    public function testGetSummary()
    {
        $object = $this->objFromFixture(ElementOembed::class, 'one');
        $expected = DBField::create_field('HTMLText', 'oEmbed Element')->Summary(20);
        $this->assertEquals($object->getSummary(), $expected);
    }

    /**
     *
     */
    public function testGetType()
    {
        $object = $this->objFromFixture(ElementOembed::class, 'one');
        $this->assertEquals($object->getType(), 'Media');
    }

    /**
     * Create an EmbedObject without touching the network: skipValidation keeps
     * EmbedObject::validate() from calling doRefresh().
     */
    private function createEmbedObject(string $sourceURL, ?string $embedHTML): EmbedObject
    {
        $embed = EmbedObject::create();
        $embed->SourceURL = $sourceURL;
        $embed->EmbedHTML = $embedHTML;
        $embed->write(false, false, false, false, true);
        return $embed;
    }

    /**
     * getEmbedURL() reads the iframe src off the linked EmbedObject, not a (removed)
     * EmbedHTML column of its own.
     */
    public function testGetEmbedURLFromLinkedEmbedObject()
    {
        $embedHTML = '<iframe width="200" height="150"'
            . ' src="https://www.youtube.com/embed/fQfWFNuhQls" frameborder="0" allowfullscreen></iframe>';
        $embed = $this->createEmbedObject(
            'https://www.youtube.com/watch?v=fQfWFNuhQls',
            $embedHTML
        );
        $this->assertTrue($embed->exists());

        $object = $this->objFromFixture(ElementOembed::class, 'one');
        $object->EmbedVideoID = $embed->ID;
        $object->write();

        $this->assertSame(
            'https://www.youtube.com/embed/fQfWFNuhQls',
            $object->getEmbedURL()
        );

        // reloaded from the database, as the template renders it
        $reloaded = ElementOembed::get()->byID($object->ID);
        $this->assertSame(
            'https://www.youtube.com/embed/fQfWFNuhQls',
            $reloaded->getEmbedURL()
        );
    }

    /**
     * An element with no EmbedVideo relation has no iframe src to return.
     */
    public function testGetEmbedURLWithoutEmbedVideo()
    {
        $object = $this->objFromFixture(ElementOembed::class, 'one');
        $this->assertFalse((bool)$object->EmbedVideoID);
        $this->assertNull($object->getEmbedURL());
    }

    /**
     * EmbedHTML that holds no iframe (a rich/link embed) yields no src rather than an error.
     */
    public function testGetEmbedURLWithoutIframe()
    {
        $embed = $this->createEmbedObject(
            'https://www.flickr.com/photos/dynamic/12345678/',
            '<blockquote>An embeddable photo</blockquote>'
        );

        $object = $this->objFromFixture(ElementOembed::class, 'one');
        $object->EmbedVideoID = $embed->ID;
        $object->write();

        $this->assertNull($object->getEmbedURL());
    }

    /**
     * A linked EmbedObject whose EmbedHTML is an empty string yields no src.
     */
    public function testGetEmbedURLEmptyEmbedHTML()
    {
        $embed = $this->createEmbedObject(
            'https://www.youtube.com/watch?v=fQfWFNuhQls',
            ''
        );

        $object = $this->objFromFixture(ElementOembed::class, 'one');
        $object->EmbedVideoID = $embed->ID;
        $object->write();

        $this->assertNull($object->getEmbedURL());
    }

    /**
     * Run onBeforeWrite()'s legacy migration against a stubbed provider lookup.
     *
     * @param bool $validationEnabled value of DataObject's global validation_enabled flag
     * @param array|null $providerData what the stubbed provider returns for the source URL
     */
    private function migrateLegacyElement(bool $validationEnabled, ?array $providerData): ElementOembed
    {
        StubEmbedObject::$data = $providerData;
        StubEmbedObject::$lookups = 0;
        Injector::inst()->load([EmbedObject::class => ['class' => StubEmbedObject::class]]);
        Config::modify()->set(ElementOembed::class, 'enable_migration', true);
        Config::modify()->set(DataObject::class, 'validation_enabled', $validationEnabled);

        $object = ElementOembed::create();
        $object->EmbedSourceURL = 'https://www.youtube.com/watch?v=fQfWFNuhQls';
        $object->EmbedTitle = 'Legacy title';
        $object->EmbedDescription = '<p>Legacy description</p>';
        $object->write();

        return ElementOembed::get()->byID($object->ID);
    }

    private function providerData(): array
    {
        return [
            'url' => 'https://www.youtube.com/watch?v=fQfWFNuhQls',
            'title' => 'A video',
            'type' => 'video',
            'embed' => [
                'html' => '<iframe src="https://www.youtube.com/embed/fQfWFNuhQls"></iframe>',
                'width' => 200,
                'height' => 150,
            ],
            'thumbnail' => ['url' => null, 'width' => null, 'height' => null],
            'provider' => ['url' => null, 'name' => null],
            'author' => ['url' => null, 'name' => null],
            'origin' => null,
            'webpage' => null,
        ];
    }

    /**
     * Validation off: nothing validates the new EmbedObject, so migration refreshes it itself,
     * exactly once, and links it.
     */
    public function testMigrationRefreshesAndLinksWithValidationOff()
    {
        $element = $this->migrateLegacyElement(false, $this->providerData());

        $this->assertSame(1, StubEmbedObject::$lookups, 'Provider is asked exactly once');
        $this->assertNotEmpty($element->EmbedVideoID);
        $this->assertSame(
            'https://www.youtube.com/embed/fQfWFNuhQls',
            $element->getEmbedURL()
        );
        $this->assertSame('Legacy title', $element->Title);
        $this->assertSame('<p>Legacy description</p>', $element->Content);
    }

    /**
     * Validation on: EmbedObject::validate() refreshes the new record itself, so migration
     * must not ask the provider a second time.
     */
    public function testMigrationDoesNotRefreshTwiceWithValidationOn()
    {
        $element = $this->migrateLegacyElement(true, $this->providerData());

        $this->assertSame(1, StubEmbedObject::$lookups, 'Provider is asked exactly once');
        $this->assertNotEmpty($element->EmbedVideoID);
        $this->assertSame(
            'https://www.youtube.com/embed/fQfWFNuhQls',
            $element->getEmbedURL()
        );
    }

    /**
     * Validation off and the provider lookup fails: a blank EmbedObject must not be linked,
     * or the element could never be migrated again. The element stays unlinked, and the
     * legacy title/description migration is deferred with it.
     */
    public function testFailedRefreshLeavesElementUnlinkedWithValidationOff()
    {
        $element = $this->migrateLegacyElement(false, null);

        $this->assertSame(1, StubEmbedObject::$lookups);
        $this->assertEmpty($element->EmbedVideoID);
        $this->assertNull($element->getEmbedURL());
        $this->assertEmpty($element->Title);
        $this->assertEmpty($element->Content);
    }
}
