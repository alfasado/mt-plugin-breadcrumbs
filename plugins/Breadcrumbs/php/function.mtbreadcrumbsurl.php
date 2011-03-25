<?php
function smarty_function_mtbreadcrumbsurl ( $args, $ctx ) {
    require_once( 'function.mtbreadcrumbslink.php' );
    return smarty_function_mtbreadcrumbslink( $args, $ctx );
}
?>