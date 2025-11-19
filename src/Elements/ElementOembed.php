<?php

namespace Dynamic\Elements\Oembed\Elements;

use DOMXPath;
use DOMDocument;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\FieldType\DBField;
use Fromholdio\EmbedField\Forms\EmbedField;
use DNADesign\Elemental\Models\BaseElement;
use Fromholdio\EmbedField\Model\EmbedObject;
use SilverStripe\Core\Config\Config;

class ElementOembed extends BaseElement
{
    /**
     * @var string
     */
    private static $icon = 'font-icon-block-media';

    /**
     * @var string
     */
    private static $table_name = 'ElementOembed';

    /**
     * @var bool
     */
    private static $enable_migration = false;

    /**
     * @return array
     */
    private static $db = [
        'Content' => 'HTMLText',
        'EmbedTitle' => 'Varchar(255)',
        'EmbedDescription' => 'HTMLText',
        'EmbedSourceURL' => 'Varchar(255)',
    ];

    private static $has_one = [
        'EmbedVideo' => EmbedObject::class,
    ];

    /**
     * @var array
     */
    private static $inline_editable = false;

    /**
     * @param $includerelations
     * @return array
     */
    public function fieldLabels($includerelations = true)
    {
        $labels = parent::fieldLabels($includerelations);
        //$labels['EmbeddedObject'] = _t(__CLASS__ . '.EmbeddedObjectLabel', 'Content from oEmbed URL');

        return $labels;
    }

    /**
     * @return FieldList
     */
    public function getCMSFields()
    {
        $this->beforeUpdateCMSFields(function (FieldList $fields) {

            $fields->removeByName([
                'EmbedVideoID',
            ]);
            
            // Embed video
            $embedVideo = EmbedField::create('EmbedVideoID', 'Embed video');

            $fields->addFieldToTab(
                'Root.Main',
                $embedVideo,
                'Content'
            );

            if (Config::inst()->get(self::class, 'enable_migration')) {
                $legacy_title = $fields->dataFieldByName('EmbedTitle')
                    ->setTitle('Legacy Title');
                $legacy_source = $fields->dataFieldByName('EmbedSourceURL')
                    ->setTitle('Legacy Source URL');
                $legacy_description = $fields->dataFieldByName('EmbedDescription')
                    ->setTitle('Legacy Description');

                $fields->insertAfter(
                    'Content',
                    $legacy_description
                );
            
                $fields->insertAfter(
                    'Content',
                    $legacy_source
                );

                $fields->insertAfter(
                    'Content',
                    $legacy_title
                );
            } else {
                $fields->removeByName([
                    'EmbedTitle',
                    'EmbedDescription',
                    'EmbedSourceURL',
                ]);
            }
        });

        return parent::getCMSFields();
    }

    /**
     * @return string
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        if (Config::inst()->get(self::class, 'enable_migration')) {
            // if legacy EmbedSourceURL, create new EmbedObject
            if (!$this->EmbedVideoID && $this->EmbedSourceURL) {
                $embed = EmbedObject::create();
                $embed->SourceURL = $this->EmbedSourceURL;
                $embed->write();

                $this->EmbedVideoID = $embed->ID;

                // migrate legacy title and description
                if (!$this->Title && $this->EmbedTitle) {
                    $this->Title = $this->EmbedTitle;
                }

                if (!$this->Content && $this->EmbedDescription) {
                    $this->Content = $this->EmbedDescription;
                }
            }
        }
    }

    /**
     * @return DBHTMLText
     */
    public function getSummary()
    {
        if ($this->Title) {
            return DBField::create_field('HTMLText', $this->dbObject('Title'))->Summary(20);
        }

        return DBField::create_field('HTMLText', 'Embeded Content')->Summary(20);
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
        return _t(__CLASS__ . '.BlockType', 'Media');
    }

    /**
     * isolate src from EmbedHTML for more control over iframe attributes
     *
     * @return void
     */
    public function getEmbedURL()
    {
        if ($this->EmbedHTML) {
            $html = $this->EmbedHTML;

            // Create a new DOM Document to hold our webpage structure
            $doc = new DOMDocument();

            // Load the HTML into the DOM Document
            @$doc->loadHTML($html);

            // Create a new XPath object
            $xpath = new DOMXPath($doc);

            // Query for the first iframe element
            $iframe = $xpath->query("//iframe")->item(0);

            if ($iframe) {
                // Extract the src attribute value
                if ($src = $iframe->getAttribute('src')) {
                    return $src;
                }
            } else {
                return null;
            }
        }
    }
}
