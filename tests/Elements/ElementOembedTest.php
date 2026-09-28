<?php

namespace Dynamic\Elements\Oembed\Tests;

use Dynamic\Elements\Oembed\Elements\ElementOembed;
use Fromholdio\EmbedField\Model\EmbedObject;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;
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
     * An element with no EmbedVideo relation has no iframe src to return, even with the legacy
     * column declared.
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
}
