<?php
namespace TYPO3\Fluid\Tests\Functional\View;

/*
 * This file is part of the TYPO3.Fluid package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Http\Uri;
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Tests\FunctionalTestCase;
use TYPO3\Fluid\Tests\Functional\View\Fixtures\View\StandaloneView;

/**
 * Testcase for Standalone View
 */
class StandaloneViewTest extends FunctionalTestCase
{
    /**
     * @var string
     */
    protected $standaloneViewNonce = '42';

    /**
     * Every testcase should run *twice*. First, it is run in *uncached* way, second,
     * it is run *cached*. To make sure that the first run is always uncached, the
     * $standaloneViewNonce is initialized to some random value which is used inside
     * an overridden version of StandaloneView::createIdentifierForFile.
     */
    public function runBare()
    {
        $this->standaloneViewNonce = uniqid();
        parent::runBare();
        $numberOfAssertions = $this->getNumAssertions();
        parent::runBare();
        $this->addToAssertionCount($numberOfAssertions);
    }

    /**
     * @test
     */
    public function inlineTemplateIsEvaluatedCorrectly()
    {
        $httpRequest = Request::create(new Uri('http://localhost'));
        $actionRequest = new ActionRequest($httpRequest);

        $standaloneView = new StandaloneView($actionRequest, $this->standaloneViewNonce);
        $standaloneView->assign('foo', 'bar');
        $standaloneView->setTemplateSource('This is my cool {foo} template!');

        $expected = 'This is my cool bar template!';
        $actual = $standaloneView->render();
        $this->assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function renderSectionIsEvaluatedCorrectly()
    {
        $httpRequest = Request::create(new Uri('http://localhost'));
        $actionRequest = new ActionRequest($httpRequest);

        $standaloneView = new StandaloneView($actionRequest, $this->standaloneViewNonce);
        $standaloneView->assign('foo', 'bar');
        $standaloneView->setTemplateSource('Around stuff... <f:section name="innerSection">test {foo}</f:section> after it');

        $expected = 'test bar';
        $actual = $standaloneView->renderSection('innerSection');
        $this->assertSame($expected, $actual);
    }

    /**
     * @test
     * @expectedException \TYPO3\Fluid\View\Exception\InvalidTemplateResourceException
     */
    public function renderThrowsExceptionIfNeitherTemplateSourceNorTemplatePathAndFilenameAreSpecified()
    {
        $httpRequest = Request::create(new Uri('http://localhost'));
        $actionRequest = new ActionRequest($httpRequest);

        $standaloneView = new StandaloneView($actionRequest, $this->standaloneViewNonce);
        $standaloneView->render();
    }

    /**
     * @test
     * @expectedException \TYPO3\Fluid\View\Exception\InvalidTemplateResourceException
     */
    public function renderThrowsExceptionSpecifiedTemplatePathAndFilenameDoesNotExist()
    {
        $httpRequest = Request::create(new Uri('http://localhost'));
        $actionRequest = new ActionRequest($httpRequest);

        $standaloneView = new StandaloneView($actionRequest, $this->standaloneViewNonce);
        $standaloneView->setTemplatePathAndFilename(__DIR__ . '/Fixtures/NonExistingTemplate.txt');
        $standaloneView->render();
    }

    /**
     * @test
     * @expectedException \TYPO3\Fluid\View\Exception\InvalidTemplateResourceException
     */
    public function renderThrowsExceptionIfSpecifiedTemplatePathAndFilenamePointsToADirectory()
    {
        $request = Request::create(new Uri('http://localhost'));
        $actionRequest = new ActionRequest($request);

        $standaloneView = new StandaloneView($actionRequest, $this->standaloneViewNonce);
        $standaloneView->setTemplatePathAndFilename(__DIR__ . '/Fixtures');
        $standaloneView->render();
    }

    /**
     * @test
     */
    public function templatePathAndFilenameIsLoaded()
    {
        $httpRequest = Request::create(new Uri('http://localhost'));
        $actionRequest = new ActionRequest($httpRequest);

        $standaloneView = new StandaloneView($actionRequest, $this->standaloneViewNonce);
        $standaloneView->assign('name', 'Karsten');
        $standaloneView->assign('name', 'Robert');
        $standaloneView->setTemplatePathAndFilename(__DIR__ . '/Fixtures/TestTemplate.txt');

        $expected = 'This is a test template. Hello Robert.';
        $actual = $standaloneView->render();
        $this->assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function variablesAreEscapedByDefault()
    {
        $standaloneView = new StandaloneView(null, $this->standaloneViewNonce);
        $standaloneView->assign('name', 'Sebastian <script>alert("dangerous");</script>');
        $standaloneView->setTemplateSource('Hello {name}.');

        $expected = 'Hello Sebastian &lt;script&gt;alert(&quot;dangerous&quot;);&lt;/script&gt;.';
        $actual = $standaloneView->render();
        $this->assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function variablesAreNotEscapedIfEscapingIsDisabled()
    {
        $standaloneView = new StandaloneView(null, $this->standaloneViewNonce);
        $standaloneView->assign('name', 'Sebastian <script>alert("dangerous");</script>');
        $standaloneView->setTemplateSource('{escapingEnabled=false}Hello {name}.');

        $expected = 'Hello Sebastian <script>alert("dangerous");</script>.';
        $actual = $standaloneView->render();
        $this->assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function partialWithDefaultLocationIsUsedIfNoPartialPathIsSetExplicitly()
    {
        $httpRequest = Request::create(new Uri('http://localhost'));
        $actionRequest = new ActionRequest($httpRequest);
        $actionRequest->setFormat('txt');

        $standaloneView = new StandaloneView($actionRequest, $this->standaloneViewNonce);
        $standaloneView->setTemplatePathAndFilename(__DIR__ . '/Fixtures/TestTemplateWithPartial.txt');

        $expected = 'This is a test template. Hello Robert.';
        $actual = $standaloneView->render();
        $this->assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function explicitPartialPathIsUsed()
    {
        $httpRequest = Request::create(new Uri('http://localhost'));
        $actionRequest = new ActionRequest($httpRequest);
        $actionRequest->setFormat('txt');

        $standaloneView = new StandaloneView($actionRequest, $this->standaloneViewNonce);
        $standaloneView->setTemplatePathAndFilename(__DIR__ . '/Fixtures/TestTemplateWithPartial.txt');
        $standaloneView->setPartialRootPath(__DIR__ . '/Fixtures/SpecialPartialsDirectory');

        $expected = 'This is a test template. Hello Karsten.';
        $actual = $standaloneView->render();
        $this->assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function layoutWithDefaultLocationIsUsedIfNoLayoutPathIsSetExplicitly()
    {
        $httpRequest = Request::create(new Uri('http://localhost'));
        $actionRequest = new ActionRequest($httpRequest);
        $actionRequest->setFormat('txt');

        $standaloneView = new StandaloneView($actionRequest, $this->standaloneViewNonce);
        $standaloneView->setTemplatePathAndFilename(__DIR__ . '/Fixtures/TestTemplateWithLayout.txt');

        $expected = 'Hey HEY HO';
        $actual = $standaloneView->render();
        $this->assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function explicitLayoutPathIsUsed()
    {
        $httpRequest = Request::create(new Uri('http://localhost'));
        $actionRequest = new ActionRequest($httpRequest);
        $actionRequest->setFormat('txt');
        $standaloneView = new StandaloneView($actionRequest, $this->standaloneViewNonce);
        $standaloneView->setTemplatePathAndFilename(__DIR__ . '/Fixtures/TestTemplateWithLayout.txt');
        $standaloneView->setLayoutRootPath(__DIR__ . '/Fixtures/SpecialLayouts');

        $expected = 'Hey -- overridden -- HEY HO';
        $actual = $standaloneView->render();
        $this->assertSame($expected, $actual);
    }

    /**
     * @test
     * @expectedException \TYPO3\Fluid\Core\Parser\Exception
     */
    public function viewThrowsExceptionWhenUnknownViewHelperIsCalled()
    {
        $httpRequest = Request::create(new Uri('http://localhost'));
        $actionRequest = new ActionRequest($httpRequest);
        $actionRequest->setFormat('txt');
        $standaloneView = new Fixtures\View\StandaloneView($actionRequest, $this->standaloneViewNonce);
        $standaloneView->setTemplatePathAndFilename(__DIR__ . '/Fixtures/TestTemplateWithUnknownViewHelper.txt');
        $standaloneView->setLayoutRootPath(__DIR__ . '/Fixtures/SpecialLayouts');

        $standaloneView->render();
    }

    /**
     * @test
     */
    public function xmlNamespacesCanBeIgnored()
    {
        $httpRequest = Request::create(new Uri('http://localhost'));
        $actionRequest = new ActionRequest($httpRequest);
        $actionRequest->setFormat('txt');
        $standaloneView = new Fixtures\View\StandaloneView($actionRequest, $this->standaloneViewNonce);
        $standaloneView->setTemplatePathAndFilename(__DIR__ . '/Fixtures/TestTemplateWithCustomNamespaces.txt');
        $standaloneView->setLayoutRootPath(__DIR__ . '/Fixtures/SpecialLayouts');

        $expected = '<foo:bar /><bar:foo></bar:foo><foo.bar:baz />foobar';
        $actual = $standaloneView->render();
        $this->assertSame($expected, $actual);
    }
}
