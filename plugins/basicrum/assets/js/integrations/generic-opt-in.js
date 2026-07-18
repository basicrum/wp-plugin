/*
 * Generic Basicrum opt-in callback snippet.
 *
 * Paste this into the consent tool's allow or grant callback. The Basicrum
 * consent loader must run before this callback.
 */
if ( 'function' === typeof window.OPT_IN_BASICRUM_LOADER_WRAPPER ) {
	window.OPT_IN_BASICRUM_LOADER_WRAPPER();
}
