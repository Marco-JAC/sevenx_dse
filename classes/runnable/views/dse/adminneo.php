<?php
/**
 * The code of extension/sevenx_dse/modules/dse/adminneo.php, moved into a class (#207 stage 1). The file extension/sevenx_dse/modules/dse/adminneo.php is one call to it.
 * Guide: doc/bc/6.0/cli_cronjob_view_abstractions.md
 */
/*
 * The original header of extension/sevenx_dse/modules/dse/adminneo.php:
 *
 *
 * DSE AdminNeo endpoint — raw AdminNeo output, no eZ pagelayout.
 *
 * This view is loaded inside an <iframe> by dse/dashboard. It runs AdminNeo
 * directly under eZ Publish's authentication/access-control layer. AdminNeo
 * outputs a complete HTML document; eZExecution::setCleanExit() prevents the
 * eZ shutdown handler from treating the exit() as an error.
 *
 * DB credentials are read from site.ini by adminneo/adminneo-config.php,
 * which AdminNeo's Origin::create() loads automatically.
 *
 * @package sevenx_dse
 * @author  7x <info@se7enx.com>
 *
 */

namespace Exponential\View\Extension\SevenxDse\Dse
{

class Adminneo extends \Exponential\Runnable\ModuleView
{
    public function run( array $scope )
    {
        // the including function's variables ($Params, $Module, $cli, ...)
        foreach ( array_keys( $scope ) as $__name )
            if ( $__name !== 'this' && $__name !== 'scope' )
                ${$__name} = &$scope[$__name];
        unset( $__name );

        $adminNeoDir = realpath( dirname( $this->scriptFile() ) . '/../../adminneo' );

        // DB credentials and server config are read entirely from adminneo-config.php
        // by AdminNeo's Origin::create() — no URL parameters needed.
        // The login form pre-fills username + password from the config (Admin.php patch).
        // NEO_STATIC_URL tells link_files() to return direct static URLs for pre-compiled
        // assets in design/standard/{stylesheets,javascript,images}/adminneo/.
        // Rebuild with: php extension/sevenx_dse/adminneo/build-static-assets.php
        if ( !defined( 'NEO_STATIC_URL' ) ) {
            define( 'NEO_STATIC_URL', rtrim( \eZSys::wwwDir(), '/' ) . '/extension/sevenx_dse/design/standard/' );
        }

        // AdminNeo uses relative includes; chdir so they resolve correctly.
        chdir( $adminNeoDir );

        // Close eZ's session, restart under AdminNeo's session name (neo_sid).
        // eZ called session_start() early, defining PHP's SID constant — AdminNeo's
        // bootstrap skips its own session_start() when SID is defined. Without this
        // restart, session_regenerate_id() in auth.inc.php fails.
        session_write_close();
        session_cache_limiter( '' );
        session_name( 'neo_sid' );
        session_set_cookie_params( 0, preg_replace( '~\?.*~', '', $_SERVER['REQUEST_URI'] ), '', false, true );
        // Name the id explicitly: the one in AdminNeo's own cookie, or none (a new one). Under Exponential Velocity
        // the session layer keeps the closed session's id, so session_start() reopened Exponential's session under
        // the new name, and AdminNeo's logout would have ended the administrator's session too.
        session_id( self::sessionIdFromCookie( $_COOKIE, 'neo_sid' ) );
        session_start();

        // AdminNeo can run once per process: it defines constants from the request, declares driver functions and
        // switches the session name and cookie path above. Under Exponential Velocity a worker serves many requests,
        // so ask for this one to be replaced after it answers (the same as dse/dashboard does).
        self::retireWorkerAfterResponse();

        // AdminNeo ends its page by throwing \AdminNeo\EzExit instead of calling exit(). Catch only that: the
        // request then ends through the kernel's clean exit below, outside any try, because under Velocity
        // eZExecution::cleanExit() ends the request with an exception of its own that must not be caught here.
        // Uncaught, EzExit reached the kernel's exception handler: a 500 under Velocity and an error page appended
        // to AdminNeo's page under PHP-FPM (#610).
        \eZExecution::setCleanExit();
        self::includeAdminNeo( $adminNeoDir . '/index.php' );

        // AdminNeo printed a complete HTML document (or sent a redirect or a download), never the page layout.
        \eZExecution::cleanExit();
    }

    /**
     * Includes AdminNeo's entry script; returns true when AdminNeo ended its page with \AdminNeo\EzExit (its
     * exit()), false when the script ran to its end. Any other exception goes on to the caller.
     *
     * @param string $file the script (adminneo/index.php)
     * @return bool
     */
    public static function includeAdminNeo( $file )
    {
        try
        {
            include $file;
        }
        catch ( \AdminNeo\EzExit $e )
        {
            return true;
        }
        return false;
    }

    /**
     * The session id in cookie $name when it has the form PHP accepts, else '' (start a new session).
     *
     * @param array $cookies $_COOKIE
     * @param string $name the cookie
     * @return string
     */
    public static function sessionIdFromCookie( array $cookies, $name )
    {
        $id = isset( $cookies[$name] ) && is_string( $cookies[$name] ) ? $cookies[$name] : '';
        return preg_match( '/^[a-zA-Z0-9,-]{22,256}$/D', $id ) ? $id : '';
    }

    /**
     * Under Exponential Velocity, asks for the worker that serves this request to be replaced after it answers;
     * nothing elsewhere. Returns whether it asked.
     *
     * @return bool
     */
    public static function retireWorkerAfterResponse()
    {
        if ( class_exists( 'Q_WebServer_Pool', false ) && method_exists( 'Q_WebServer_Pool', 'retireAfterResponse' ) )
        {
            \Q_WebServer_Pool::retireAfterResponse( 'AdminNeo defines request constants and can run once per process' );
            return true;
        }
        return false;
    }
}

}
