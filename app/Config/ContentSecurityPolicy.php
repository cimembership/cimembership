<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Content Security Policy Config
 *
 * @see \CodeIgniter\HTTP\ContentSecurityPolicy
 */
class ContentSecurityPolicy extends BaseConfig
{
    /**
     * The `base-uri` directive restricts the URLs that can be used to specify the document base URL.
     *
     * @var array<string, bool>|string|null
     */
    public $baseURI;

    /**
     * The `child-src` directive governs the creation of nested browsing contexts as well
     * as Worker execution contexts.
     *
     * @var array<string, bool>|string
     */
    public array|string $childSrc = [
        'self' => true,
    ];

    /**
     * The `connect-src` directive restricts which URLs the protected resource can load using script interfaces.
     *
     * @var array<string, bool>|string
     */
    public array|string $connectSrc = [
        'self' => true,
    ];

    /**
     * The `default-src` directive sets a default source list for a number of directives.
     *
     * @var array<string, bool>|string
     */
    public array|string $defaultSrc = [
        'self' => true,
    ];

    /**
     * The `font-src` directive restricts from where the protected resource can load fonts.
     *
     * @var array<string, bool>|string|null
     */
    public $fontSrc;

    /**
     * The `form-action` directive restricts which URLs can be used as the action of HTML form elements.
     *
     * @var array<string, bool>|string|null
     */
    public $formAction;

    /**
     * The `frame-ancestors` directive indicates whether the user agent should allow embedding
     * the resource using a `frame`, `iframe`, `object`, `embed` or `applet` element,
     * or equivalent functionality in non-HTML resources.
     *
     * @var array<string, bool>|string|null
     */
    public $frameAncestors;

    /**
     * The `frame-src` directive restricts the URLs which may be loaded into child navigables.
     *
     * @var array<string, bool>|string|null
     */
    public $frameSrc;

    /**
     * The `img-src` directive restricts from where the protected resource can load images.
     *
     * @var array<string, bool>|string
     */
    public array|string $imageSrc = [
        'self' => true,
    ];

    /**
     * The `media-src` directive restricts from where the protected resource can load video,
     * audio, and associated text tracks.
     *
     * @var array<string, bool>|string|null
     */
    public $mediaSrc;

    /**
     * The `object-src` directive restricts from where the protected resource can load plugins.
     *
     * @var array<string, bool>|string
     */
    public array|string $objectSrc = [
        'self' => true,
    ];

    /**
     * The `plugin-types` directive restricts the set of plugins that can be invoked by the
     * protected resource by limiting the types of resources that can be embedded.
     *
     * @var array<string, bool>|string|null
     */
    public $pluginTypes;

    /**
     * The `script-src` directive restricts which scripts the protected resource can execute.
     *
     * @var array<string, bool>|string
     */
    public array|string $scriptSrc = [
        'self' => true,
    ];

    /**
     * The `style-src` directive restricts which styles the user may applies to the protected resource.
     *
     * @var array<string, bool>|string
     */
    public array|string $styleSrc = [
        'self' => true,
        'unsafe-inline' => true,
    ];

    /**
     * The `manifest-src` directive restricts the URLs from which application manifests may be loaded.
     *
     * @var array<string, bool>|string|null
     */
    public $manifestSrc;

    /**
     * The `script-src-elem` directive applies to all script requests and script blocks.
     *
     * @var array<string, bool>|string
     */
    public array|string $scriptSrcElem = [];

    /**
     * The `script-src-attr` directive applies to event handlers and, if present,
     * it will override the `script-src` directive for relevant checks.
     *
     * @var array<string, bool>|string
     */
    public array|string $scriptSrcAttr = [];

    /**
     * The `style-src-elem` directive governs the behaviour of styles except
     * for styles defined in inline attributes.
     *
     * @var array<string, bool>|string
     */
    public array|string $styleSrcElem = [];

    /**
     * The `style-src-attr` directive governs the behaviour of style attributes.
     *
     * @var array<string, bool>|string
     */
    public array|string $styleSrcAttr = [];

    /**
     * The `worker-src` directive restricts the URLs which may be loaded as a `Worker`,
     * `SharedWorker`, or `ServiceWorker`.
     *
     * @var array<string, bool>|string
     */
    public array|string $workerSrc = [];

    /**
     * The `sandbox` directive specifies an HTML sandbox policy that the user agent applies to the protected resource.
     *
     * @var array<string, bool>|string|null
     */
    public $sandbox;

    /**
     * The `report-uri` directive specifies a URL to which the user agent sends reports about policy violation.
     *
     * @var string|null
     */
    public ?string $reportURI = null;

    /**
     * The `report-to` directive specifies a named group in a Reporting API
     * endpoint to which the user agent sends reports about policy violation.
     */
    public ?string $reportTo = null;

    /**
     * Instructs user agents to rewrite URL schemes by changing HTTP to HTTPS.
     */
    public bool $upgradeInsecureRequests = false;

    /**
     * Set to `true` to make all directives report-only instead of enforced.
     */
    public bool $reportOnly = false;
}
