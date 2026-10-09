<?php

namespace Dynamic\Elements\Oembed\Tests;

use Dynamic\Elements\Oembed\Elements\ElementOembed;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;
use nathancox\EmbedField\Model\EmbedObject;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\FieldType\DBField;

class ElementOembedTest extends SapphireTest
{
    protected $usesDatabase = true;

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
     * The iframe src is provider-supplied markup rendered in an unsandboxed iframe, so only
     * http(s) and protocol-relative values with a host may come back.
     *
     * @return array<string, array{0: string, 1: string|null}>
     */
    public static function embedSrcProvider(): array
    {
        return [
            'https' => ['https://www.youtube.com/embed/abc', 'https://www.youtube.com/embed/abc'],
            'http' => ['http://example.com/embed', 'http://example.com/embed'],
            'protocol relative' => ['//player.vimeo.com/video/1', '//player.vimeo.com/video/1'],
            'uppercase scheme' => ['HTTPS://example.com/embed', 'HTTPS://example.com/embed'],
            'javascript' => ['javascript:alert(document.domain)', null],
            'javascript with slashes' => ['javascript://%0aalert(document.domain)//', null],
            'mixed case javascript' => ['JaVaScRiPt:alert(1)', null],
            'leading space' => [' javascript:alert(1)', null],
            'leading control character' => ["\x01javascript:alert(1)", null],
            'tab inside scheme' => ["java\tscript:alert(1)", null],
            'newline inside scheme' => ["java\nscript:alert(1)", null],
            'entity encoded scheme' => ['java&#x73;cript:alert(1)', null],
            'data' => ['data:text/html,<script>alert(1)</script>', null],
            'vbscript' => ['vbscript:msgbox(1)', null],
            'file' => ['file:///etc/passwd', null],
            'relative path' => ['/embed/abc', null],
            'scheme without host' => ['https:/embed/abc', null],
            'host-less http' => ['http:///embed', null],
        ];
    }

    /**
     * @dataProvider embedSrcProvider
     */
    public function testGetEmbedURLOnlyAllowsHttpSources(string $src, ?string $expected)
    {
        $object = $this->createElementWithEmbedHtml(
            '<iframe src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8', false) . '"></iframe>'
        );

        $this->assertSame($expected, $object->getEmbedURL());
    }

    public function testGetEmbedURLIsNullWithoutAnIframe()
    {
        $object = $this->createElementWithEmbedHtml('<p>no player</p>');

        $this->assertNull($object->getEmbedURL());
    }

    /**
     * A script-capable iframe src in the provider markup never reaches the output; the
     * template renders no player.
     */
    public function testNoPlayerRenderedForScriptSchemeSrc()
    {
        $object = $this->createElementWithEmbedHtml('<iframe src="javascript://%0aalert(document.domain)//"></iframe>');

        $output = (string) $object->renderWith('Dynamic\\Elements\\Oembed\\Elements\\ElementOembed');

        $this->assertStringNotContainsString('<iframe', $output);
        $this->assertStringNotContainsString('javascript', $output);
    }

    public function testTemplateEscapesTheSrcAttribute()
    {
        $object = $this->createElementWithEmbedHtml(
            '<iframe src="https://example.com/v?a=%22+onload%3Dalert(1)"></iframe>'
        );

        $output = (string) $object->renderWith('Dynamic\\Elements\\Oembed\\Elements\\ElementOembed');

        $doc = new \DOMDocument();
        @$doc->loadHTML($output);
        $iframe = $doc->getElementsByTagName('iframe')->item(0);
        $this->assertNotNull($iframe);
        $this->assertFalse($iframe->hasAttribute('onload'));
    }

    /**
     * Links the fixture element to an EmbedObject holding the given provider markup. The
     * column is written directly so EmbedObject::validate() never fetches the URL.
     */
    private function createElementWithEmbedHtml(string $embedHtml): ElementOembed
    {
        $embed = EmbedObject::create();
        $embed->write();
        DB::prepared_query(
            'UPDATE "EmbedObject" SET "EmbedHTML" = ?, "SourceURL" = ? WHERE "ID" = ?',
            [$embedHtml, 'https://www.example.com/watch?v=1', $embed->ID]
        );

        $object = $this->objFromFixture(ElementOembed::class, 'one');
        $object->EmbedVideoID = $embed->ID;
        $object->write();

        return ElementOembed::get()->byID($object->ID);
    }
}
