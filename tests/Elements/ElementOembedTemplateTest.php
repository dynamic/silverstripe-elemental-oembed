<?php

namespace Dynamic\Elements\Oembed\Tests;

use Dynamic\Elements\Oembed\Elements\ElementOembed;
use Fromholdio\EmbedField\Model\EmbedObject;
use SilverStripe\Dev\SapphireTest;

/**
 * Covers the front-end template for {@link ElementOembed}, which must only render the
 * ratio/iframe player when an embed URL is actually available.
 *
 * @see https://github.com/dynamic/silverstripe-elemental-oembed/issues/45
 */
class ElementOembedTemplateTest extends SapphireTest
{
    /**
     * @var string
     */
    protected static $fixture_file = '../fixtures.yml';

    /**
     * The front-end template shipped by this module.
     *
     * @var string
     */
    private const TEMPLATE = 'Dynamic/Elements/Oembed/Elements/ElementOembed';

    /**
     * A sample iframe src used by the "embed exists" cases.
     *
     * @var string
     */
    private const EMBED_URL = 'https://www.youtube.com/embed/loZu3rTbLbQ';

    /**
     * Guards the precondition the empty-iframe bug is reported against: an element with no
     * linked EmbedObject renders with no embed URL.
     *
     * Scope note: on the current default branch `getEmbedURL()` reads a field that does not
     * exist on this element, so it returns null even when an EmbedObject *is* linked. This
     * therefore only pins the "no embed available" precondition, not the reason the URL is
     * missing; the linked-EmbedObject case is covered by the getEmbedURL() work in PR #44.
     */
    public function testGetEmbedURLIsNullForElementWithoutEmbedObject(): void
    {
        $element = $this->objFromFixture(ElementOembed::class, 'one');

        $this->assertNull($element->getEmbedURL());
    }

    /**
     * The reported bug: with no embed URL the template used to emit `<iframe src="">`, i.e. a
     * broken player frame. It must render its text content and no player instead.
     */
    public function testNoPlayerRenderedWhenEmbedUrlIsEmpty(): void
    {
        $element = $this->objFromFixture(ElementOembed::class, 'one');
        $element->ShowTitle = true;
        $element->EmbedTitle = 'A legacy embed title';
        $element->EmbedDescription = 'A legacy embed description';

        $output = $element->renderWith(self::TEMPLATE)->getValue();

        // The broken frame is gone entirely - not merely an empty attribute.
        $this->assertStringNotContainsString('<iframe', $output);
        $this->assertStringNotContainsString('src=', $output);
        $this->assertStringNotContainsString('ratio ratio-16x9', $output);

        // Everything the editor can still read keeps rendering.
        $this->assertStringContainsString('element__oembed__object', $output);
        $this->assertStringContainsString('<h2 class="element__title">oEmbed Element</h2>', $output);
        $this->assertStringContainsString('card-body', $output);
        $this->assertStringContainsString('A legacy embed title', $output);
        $this->assertStringContainsString('A legacy embed description', $output);
    }

    /**
     * The empty case must also hold for the default rendering path (forTemplate(), which the
     * theme uses) and for an element with no title at all.
     */
    public function testNoPlayerRenderedForUntitledElementWithoutEmbed(): void
    {
        $element = ElementOembed::create();

        $output = $element->forTemplate();

        $this->assertStringNotContainsString('<iframe', $output);
        $this->assertStringNotContainsString('src=', $output);
        $this->assertStringContainsString('element__oembed__object', $output);
    }

    /**
     * The player still renders when an embed URL is available.
     */
    public function testPlayerRenderedWhenEmbedUrlExists(): void
    {
        $element = $this->objFromFixture(ElementOembed::class, 'one');

        $output = $element->customise(['EmbedURL' => self::EMBED_URL])->forTemplate();

        $this->assertStringContainsString('ratio ratio-16x9', $output);
        $this->assertStringContainsString('<iframe', $output);
        $this->assertStringContainsString('src="' . self::EMBED_URL . '"', $output);
        $this->assertStringContainsString('card-img-top', $output);
    }

    /**
     * A script-capable iframe src in the provider markup never reaches the output; the
     * template falls back to its empty state.
     */
    public function testNoPlayerRenderedForScriptSchemeSrc(): void
    {
        $embed = EmbedObject::create();
        $embed->SourceURL = 'https://www.example.com/watch?v=1';
        $embed->EmbedHTML = '<iframe src="javascript://%0aalert(document.domain)//"></iframe>';
        $embed->write(false, false, false, false, true);

        $element = $this->objFromFixture(ElementOembed::class, 'one');
        $element->EmbedVideoID = $embed->ID;
        $element->write();

        $output = $element->forTemplate();

        $this->assertStringNotContainsString('<iframe', $output);
        $this->assertStringNotContainsString('javascript', $output);
    }
}
