{* dse/dashboard. Before the editor opens (dse_gate) the dashboard of dse/gate.tpl, already rendered into neo_body.
   After it, AdminNeo's page in neo_body: the view wraps it in div.a4-dse-editor (#adminneo-root).

   AdminNeo's styles come from stylesheets/adminneo/adminneo-embedded.css (its own stylesheets, every rule scoped to
   .a4-dse-editor by adminneo/build-embedded-css.php, so they never reach the administration's menus and columns) and
   stylesheets/dse.css (the embedded layout, and AdminNeo's colors taken from the --a4-* tokens, so the editor follows
   the light and dark mode of admin4). Both are in design.ini [StylesheetSettings] BackendCSSFileList and packed with
   the other stylesheets. No inline CSS.

   AdminNeo's two scripts are loaded here, on this page only (they define global functions the rest of the
   administration does not need): external files, before neo_body, whose own small scripts call them. *}
{if is_set( $dse_gate )}
{$neo_body}
{else}
<script src={'javascript/adminneo/main.js'|ezdesign}></script>
<script src={'javascript/adminneo/jush.js'|ezdesign}></script>

<div class="a4-page a4-dse">
{$neo_body}
</div>
{/if}
