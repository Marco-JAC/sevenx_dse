#!/usr/bin/env php
<?php
/**
 * Builds design/standard/stylesheets/adminneo/adminneo-embedded.css: AdminNeo's own stylesheets (default-blue.css,
 * jush.css and their dark counterparts), with every rule scoped to the element that holds the embedded editor
 * (.a4-dse-editor), so they style the editor only and never the administration around it (its menus, columns and
 * forms). The dark stylesheets apply under html[data-a4-theme="dark"], the administration's own dark mode, instead of
 * the browser's color scheme.
 *
 *   :root, html and body  become the editor element itself
 *   .js .x, .rtl .x ...   become .a4-dse-editor.js .x (the editor element carries these classes)
 *   any other selector    gets the editor element in front: .a4-dse-editor <selector>
 *   @media and @supports  are kept, with their rules scoped; other at-rules are copied unchanged
 *
 * The colors of the embedded editor follow the administration's tokens: design/standard/stylesheets/dse.css, loaded
 * after this file, maps AdminNeo's variables (--body-bg, --link-text ...) to the --a4-* tokens.
 *
 * Run from the extension directory after rebuilding AdminNeo's stylesheets (build-static-assets.php):
 *   php adminneo/build-embedded-css.php
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package sevenx_dse
 */

namespace AdminNeo;

const DSE_EMBED_ROOT = '.a4-dse-editor';
const DSE_EMBED_DARK_ROOT = 'html[data-a4-theme="dark"] .a4-dse-editor';

/** The position of the brace that closes the block opened at $open, minding strings and comments. */
function dse_css_block_end( string $css, int $open ): int
{
    $depth = 0;
    $length = strlen( $css );
    for ( $i = $open; $i < $length; $i++ )
    {
        $c = $css[$i];
        if ( $c === '"' || $c === "'" )
        {
            $i = dse_css_string_end( $css, $i );
            continue;
        }
        if ( $c === '{' )
            $depth++;
        else if ( $c === '}' && --$depth === 0 )
            return $i;
    }
    throw new \RuntimeException( 'Unbalanced braces at offset ' . $open );
}

function dse_css_string_end( string $css, int $start ): int
{
    $quote = $css[$start];
    $length = strlen( $css );
    for ( $i = $start + 1; $i < $length; $i++ )
    {
        if ( $css[$i] === '\\' )
            $i++;
        else if ( $css[$i] === $quote )
            return $i;
    }
    return $length - 1;
}

/** The selectors of a selector list, split at the commas outside parentheses, brackets and strings. */
function dse_css_split_selectors( string $list ): array
{
    $parts = array();
    $depth = 0;
    $current = '';
    $length = strlen( $list );
    for ( $i = 0; $i < $length; $i++ )
    {
        $c = $list[$i];
        if ( $c === '"' || $c === "'" )
        {
            $end = dse_css_string_end( $list, $i );
            $current .= substr( $list, $i, $end - $i + 1 );
            $i = $end;
            continue;
        }
        if ( $c === '(' || $c === '[' )
            $depth++;
        else if ( $c === ')' || $c === ']' )
            $depth--;
        if ( $c === ',' && $depth === 0 )
        {
            $parts[] = trim( $current );
            $current = '';
            continue;
        }
        $current .= $c;
    }
    if ( trim( $current ) !== '' )
        $parts[] = trim( $current );
    return $parts;
}

function dse_css_scope_selector( string $selector, string $root ): string
{
    // :root, html and body: the editor element
    if ( preg_match( '/^(?::root|html|body)(?=$|[\s.:#\[>+~])(.*)$/s', $selector, $m ) )
        return $root . $m[1];
    // the state classes AdminNeo puts on <body> are on the editor element
    if ( preg_match( '/^\.(?:js|nojs|rtl|ltr)(?=$|[\s.:#\[>+~])/', $selector ) )
        return $root . $selector;
    return $root . ' ' . $selector;
}

function dse_css_scope( string $css, string $root ): string
{
    $css = preg_replace( '~/\*.*?\*/~s', '', $css );
    $out = '';
    $length = strlen( $css );
    $i = 0;
    while ( $i < $length )
    {
        $open = strpos( $css, '{', $i );
        if ( $open === false )
            break;
        $prelude = trim( substr( $css, $i, $open - $i ) );
        $close = dse_css_block_end( $css, $open );
        $body = substr( $css, $open + 1, $close - $open - 1 );
        $i = $close + 1;
        // a statement at-rule (@charset, @import) before the block is copied as it is
        while ( $prelude !== '' && $prelude[0] === '@' && strpos( $prelude, ';' ) !== false )
        {
            $semi = strpos( $prelude, ';' );
            $out .= substr( $prelude, 0, $semi + 1 );
            $prelude = trim( substr( $prelude, $semi + 1 ) );
        }
        if ( preg_match( '~^@(media|supports|container|layer)\b~i', $prelude ) )
            $out .= $prelude . '{' . dse_css_scope( $body, $root ) . '}';
        else if ( $prelude !== '' && $prelude[0] === '@' )
            $out .= $prelude . '{' . $body . '}';
        else
        {
            $selectors = array();
            foreach ( dse_css_split_selectors( $prelude ) as $selector )
                $selectors[] = dse_css_scope_selector( $selector, $root );
            $out .= implode( ',', $selectors ) . '{' . trim( $body ) . '}';
        }
        $out .= "\n";
    }
    return $out;
}

if ( PHP_SAPI === 'cli' && realpath( $_SERVER['SCRIPT_FILENAME'] ?? '' ) === realpath( __FILE__ ) )
{
    $dir = dirname( __DIR__ ) . '/design/standard/stylesheets/adminneo';
    $sources = array(
        'default-blue.css'      => DSE_EMBED_ROOT,
        'jush.css'              => DSE_EMBED_ROOT,
        'default-blue-dark.css' => DSE_EMBED_DARK_ROOT,
        'jush-dark.css'         => DSE_EMBED_DARK_ROOT,
    );
    $out = "/* Generated by adminneo/build-embedded-css.php from " . implode( ', ', array_keys( $sources ) )
        . ": AdminNeo's styles scoped to the embedded editor (.a4-dse-editor). Do not edit; rebuild. */\n";
    foreach ( $sources as $file => $root )
    {
        $css = file_get_contents( $dir . '/' . $file );
        if ( $css === false )
        {
            fwrite( STDERR, "FAIL cannot read $file\n" );
            exit( 1 );
        }
        $out .= "/* $file */\n" . dse_css_scope( $css, $root );
    }
    if ( file_put_contents( $dir . '/adminneo-embedded.css', $out ) === false )
    {
        fwrite( STDERR, "FAIL cannot write adminneo-embedded.css\n" );
        exit( 1 );
    }
    echo "PASS wrote design/standard/stylesheets/adminneo/adminneo-embedded.css (" . strlen( $out ) . " bytes)\n";
}
