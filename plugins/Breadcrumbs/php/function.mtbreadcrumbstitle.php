<?php
function smarty_function_mtbreadcrumbstitle ( $args, $ctx ) {
    return $ctx->stash( 'breadcrumbslabel' );
}
?>