<?php

/*
 * The data layer sits at the action boundary and nowhere else. None of these
 * three invariants can fail behaviourally: a data object that grows a Request
 * dependency or a toArray() still passes every feature test, and only stops
 * working once someone reuses it from the console or wires it straight into a
 * page. That is why they are asserted structurally.
 */

arch('data objects are final and readonly')
    ->expect('App\Data')
    ->toBeFinal()
    ->toBeReadonly();

/*
 * InitializeSuperAdminData is built by a console command from configuration,
 * and PruneOrphanImagesResult is returned to one. An HTTP dependency here
 * would make those callers impossible.
 */
arch('data objects stay HTTP-agnostic')
    ->expect('App\Data')
    ->not->toUse('Illuminate\Http');

/*
 * Presenters own every payload that crosses to Inertia. A toArray() here would
 * open a second, competing path to the same place.
 */
arch('data objects do not serialize themselves')
    ->expect('App\Data')
    ->not->toHaveMethod('toArray');
