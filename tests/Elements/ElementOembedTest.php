<?php

namespace Dynamic\Elements\Oembed\Tests;

use Dynamic\Elements\Oembed\Elements\ElementOembed;
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
        $expected = DBField::create_field('HTMLText', '<p>External Content</p>')->Summary(20);
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
}
