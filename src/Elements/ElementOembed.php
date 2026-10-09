<?php

namespace Dynamic\Elements\Oembed\Elements;

use DOMXPath;
use DOMDocument;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\DataObject;
use Fromholdio\EmbedField\Forms\EmbedField;
use DNADesign\Elemental\Models\BaseElement;
use Fromholdio\EmbedField\Model\EmbedObject;
use SilverStripe\Core\Config\Config;

class ElementOembed extends BaseElement
{
    /**
     * @var string
     */
    private static $singular_name = 'Media';

    /**
     * @var string
     */
    private static $plural_name = 'Media Elements';

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
     * @return void
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        if (Config::inst()->get(self::class, 'enable_migration')) {
            // if legacy EmbedSourceURL, create new EmbedObject
            if (!$this->EmbedVideoID && $this->EmbedSourceURL) {
                $embed = EmbedObject::create();
                $embed->SourceURL = $this->EmbedSourceURL;

                // Populate Type/EmbedHTML/dimensions from the source URL. EmbedObject::validate()
                // calls doRefresh() for a new record, but DataObject::validateWrite() skips
                // validation entirely when the global validation_enabled flag is off - refresh
                // ourselves in that case only, so the provider is never asked for the same data
                // twice per migrated element.
                $linkEmbed = true;
                if (!DataObject::config()->uninherited('validation_enabled')) {
                    $embed->doRefresh();

                    // With validation off nothing throws when the provider lookup fails, and
                    // doRefresh() leaves the record blank in that case (it clears SourceURL too).
                    // Linking a blank EmbedObject would be permanent: this block only runs while
                    // EmbedVideoID is empty, so the element would never be migrated again. Leave
                    // it unlinked instead so a later write retries (the legacy title and
                    // description migration below is deferred with it).
                    $linkEmbed = !empty($embed->EmbedHTML);
                }

                if (!$linkEmbed) {
                    return;
                }

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
     * isolate src from EmbedHTML for more control over iframe attributes
     *
     * The iframe comes from the linked EmbedObject's EmbedHTML, which is where that data lives
     * since the 5.x EmbedField refactor. An element with no linked EmbedObject has no iframe;
     * data left in the legacy columns is moved onto an EmbedObject by the `enable_migration`
     * step in onBeforeWrite(), not read here.
     *
     * @return string|null
     */
    public function getEmbedURL()
    {
        $embed = $this->EmbedVideo();

        if (!$embed || !$embed->exists()) {
            return null;
        }

        $html = $embed->EmbedHTML;

        if (!$html) {
            return null;
        }

        // Create a new DOM Document to hold our webpage structure
        $doc = new DOMDocument();

        // Load the HTML into the DOM Document
        @$doc->loadHTML($html);

        // Create a new XPath object
        $xpath = new DOMXPath($doc);

        // Query for the first iframe element
        $iframe = $xpath->query("//iframe")->item(0);

        if (!$iframe) {
            return null;
        }

        // Extract the src attribute value
        $src = static::normaliseEmbedSrc($iframe->getAttribute('src'));

        return $src ?: null;
    }

    /**
     * Returns the src as a browser resolves it, or null unless that is an http(s) URL with
     * a host or a protocol-relative URL.
     *
     * The src comes from provider-supplied markup and is rendered in an unsandboxed iframe, so
     * a javascript:, data:, vbscript: or file: value would run in the site origin. Leading and
     * trailing control characters and spaces are trimmed and tabs and newlines removed before
     * the scheme is read, as the URL parser does ("java\tscript:"). The returned string is the
     * one the check approved, so callers must use it rather than the original value. Entities
     * need no handling: the value comes from a parsed attribute.
     */
    public static function normaliseEmbedSrc(string $src): ?string
    {
        $candidate = str_replace(["\t", "\n", "\r"], '', trim($src, "\x00..\x20"));
        if ($candidate === '') {
            return null;
        }

        $protocolRelative = str_starts_with($candidate, '//');
        $parts = parse_url($protocolRelative ? 'https:' . $candidate : $candidate);
        if ($parts === false || empty($parts['host'])) {
            return null;
        }
        if (!$protocolRelative && !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)) {
            return null;
        }

        return $candidate;
    }
}
