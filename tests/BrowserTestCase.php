<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * The base case for `tests/Browser`.
 *
 * It deliberately does not extend {@see TestCase}: that one calls
 * `withoutVite()`, which stubs the `@vite` directive so no script tag is
 * emitted at all. A Feature test wants exactly that — it asserts on the server
 * response and must not need a build. A browser test wants the opposite: with
 * the directive stubbed the browser receives markup with no bundle, renders a
 * blank page, and every assertion about the running application becomes
 * vacuous. Notably `assertNoSmoke()` still passes there, because a blank page
 * logs nothing.
 *
 * Browser tests therefore need a real `pnpm run build`.
 */
abstract class BrowserTestCase extends BaseTestCase
{
    //
}
