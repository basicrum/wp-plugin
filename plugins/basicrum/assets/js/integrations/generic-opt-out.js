/*
 * Generic Basicrum opt-out callback snippet.
 *
 * Paste this into the consent tool's deny, expiry, or withdrawal callback. The
 * Basicrum consent loader must run before this callback.
 */
if ( 'function' === typeof window.OPT_OUT_BASICRUM_LOADER_WRAPPER ) {
	window.OPT_OUT_BASICRUM_LOADER_WRAPPER();
}
