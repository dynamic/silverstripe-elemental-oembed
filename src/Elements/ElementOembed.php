<?php

namespace Dynamic\Elements\Oembed\Elements;

use DNADesign\Elemental\Models\BaseElement;
use Sheadawson\Linkable\Forms\EmbeddedObjectField;
use Sheadawson\Linkable\Models\EmbeddedObject;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\FieldType\DBField;

class ElementOembed extends BaseElement
{
    /**
     * @var string
     */
    private static $icon = 'embed-icon';

    /**
     * @return string
     */
    private static $singular_name = 'oEmbed Element';

    /**
     * @return string
     */
    private static $plural_name = 'oEmbed Elements';

    /**
     * @var string
     */
    private static $table_name = 'ElementOembed';

    /**
     * @var array
     */
    private static $has_one = [
        'EmbeddedObject' => EmbeddedObject::class,
    ];

    /**
     * Set to false to prevent an in-line edit form from showing in an elemental area. Instead the element will be
     * clickable and a GridFieldDetailForm will be used.
     *
     * @config
     * @var bool
     */
    private static $inline_editable = false;

    /**
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->replaceField(
            'EmbeddedObjectID',
            EmbeddedObjectField::create('EmbeddedObject', 'Content from oEmbed URL', $this->EmbeddedObject())
        );

        return $fields;
    }

    /**
     * @return DBHTMLText
     */
    public function getSummary()
    {
        if ($this->EmbeddedObject()->ID) {
            return DBField::create_field('HTMLText', $this->EmbeddedObject->Title)->Summary(20);
        }

        return DBField::create_field('HTMLText', '<p>External Content</p>')->Summary(20);
    }

    /**
     * @return array
     */
    protected function provideBlockSchema()
    {
        $blockSchema = parent::provideBlockSchema();
        $blockSchema['content'] = $this->getSummary();
        return $blockSchema;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return _t(__CLASS__.'.BlockType', 'oEmbed');
    }
}
