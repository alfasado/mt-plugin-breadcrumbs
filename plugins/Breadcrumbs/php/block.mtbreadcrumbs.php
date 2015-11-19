<?php
function smarty_block_mtbreadcrumbs( $args, $content, &$ctx, &$repeat ) {
    $localvars = array( 'breadcrumbs', 'breadcrumb', '__breadcrumbs_counter', '__breadcrumbs_count' );
    if (! isset( $content ) ) {
        $ctx->localize( $localvars );
        $breadcrumbs = array();
        $category = NULL;
        $archive_type = $ctx->stash( 'current_archive_type' );
        $blog = $ctx->stash( 'blog' );
        if ( $archive_type === 'Individual' || $archive_type === 'Page' ) {
            $entry = $ctx->stash( 'entry' );
            $app = $ctx->stash( 'bootstrapper' );
            array_unshift( $breadcrumbs,
                           array( 'breadcrumbstype'  => $entry->class,
                                  'breadcrumbslabel' => $entry->title,
                                  'breadcrumbslink'  => $ctx->mt->db()->entry_link( $entry->id, $archive_type, $args ),
                           ) );
            $category = $entry->category();
        } elseif ( strpos( $archive_type, 'Category' ) !== false) {
            $category = $ctx->stash( 'category' );
        } elseif ( strpos($archive_type, 'Folder') !== false) {
            $category = $ctx->stash( 'category' );
        } elseif ( preg_match( '/(?:Yearly|Monthly|Weekly|Daily|Author)/', $archive_type ) ) {
            require_once 'function.mtarchivelink.php';
            require_once 'function.mtarchivetitle.php';
            $breadcrumbslabel = smarty_function_mtarchivetitle( $args, $ctx );
            $lang = strtolower($blog && $blog->blog_language ? $blog->blog_language
                                                             : $ctx->mt->config('DefaultLanguage'));
            if ( $lang === 'ja' || $lang === 'jp' ) {
                $breadcrumbslabel = str_replace('&#24180;', "\xE5\xB9\xB4", $breadcrumbslabel);
            }
            array_unshift( $breadcrumbs,
                           array( 'breadcrumbstype'  => strtolower( $archive_type ),
                                  'breadcrumbslabel' => $breadcrumbslabel,
                                  'breadcrumbslink'  => smarty_function_mtarchivelink( $args, $ctx ),
                           ) );
        } else {
            $ctx->restore( $localvars );
            $repeat = FALSE;
            return '';
        }
        if ( isset ( $category ) ) {
            while( $category ) {
                $category_link = $ctx->mt->db()->category_link( $category->id );
                if ( $args[ 'with_index' ] && $category_link && preg_match( '/\/(#.*)*$/', $category_link ) ) {
                    $index = $ctx->mt->config( 'IndexBasename' );
                    $ext = $blog->blog_file_extension;
                    if ( $ext ) $ext = '.' . $ext;
                    $index .= $ext;
                    $category_link = preg_replace( '/\/(#.*)?$/', "/$index\$1", $category_link );
                }
                array_unshift( $breadcrumbs,
                               array( 'breadcrumbstype'  => $category->class,
                                      'breadcrumbslabel' => $category->label,
                                      'breadcrumbslink'  => $category_link,
                               ) );
                $category = __breadcrumbs_catgory_parent( $ctx, $category );
            }
        }
        array_unshift( $breadcrumbs,
                       array( 'breadcrumbstype'  => $blog->class,
                              'breadcrumbslabel' => $blog->name,
                              'breadcrumbslink'  => $blog->site_url(),
                       ) );
        if ( $blog->class === 'blog' ) {
            if ( $website = $blog->website() ) {
                array_unshift( $breadcrumbs,
                               array( 'breadcrumbstype'  => $website->class,
                                      'breadcrumbslabel' => $website->name,
                                      'breadcrumbslink'  => $website->site_url(),
                               ) );
            }
        }
        $ctx->stash( 'breadcrumbs', $breadcrumbs );
        $ctx->stash( '__breadcrumbs_count', count( $breadcrumbs ) );
        $ctx->stash( '__breadcrumbs_counter', 0 );
    } else {
        $breadcrumbs = $ctx->stash( 'breadcrumbs' );
        if ( isset( $breadcrumbs ) ) {
            $glue = $args[ 'glue' ];
            $count_breadcrumbs = $ctx->stash( '__breadcrumbs_count' );
            $counter = $ctx->stash( '__breadcrumbs_counter' );
            if ( $counter < $count_breadcrumbs ) {
                $breadcrumb = $breadcrumbs[ $counter ];
                $ctx->stash( '__breadcrumbs_counter', $counter + 1 );
                $ctx->stash( 'breadcrumbslabel', $breadcrumb[ 'breadcrumbslabel' ] );
                $ctx->stash( 'breadcrumbslink', $breadcrumb[ 'breadcrumbslink' ] );
                $count = $counter + 1;
                $ctx->__stash[ 'vars' ][ '__counter__' ] = $count;
                $ctx->__stash[ 'vars' ][ '__type__' ]    = $breadcrumb[ 'breadcrumbstype' ];
                $ctx->__stash[ 'vars' ][ '__odd__' ]     = ( $count % 2 ) == 1;
                $ctx->__stash[ 'vars' ][ '__even__' ]    = ( $count % 2 ) == 0;
                $ctx->__stash[ 'vars' ][ '__first__' ]   = $count == 1;
                $ctx->__stash[ 'vars' ][ '__last__' ]    = ( $count == $count_breadcrumbs );
                $repeat = TRUE;
            } else {
                $repeat = FALSE;
            }
            if ( ( $counter > 1 ) && $glue && (! empty( $content ) ) ) {
                 $content = $glue . $content;
            }
            if ( $counter > 0 ) {
                return $content;
            }
        }
    }
    if (! $repeat ) {
        $ctx->restore( $localvars );
    }
}
function __breadcrumbs_catgory_parent ( $ctx, $category ) {
    if ( $category_id = $category->parent ) {
        if ( $category->class == 'category' ) {
            return $ctx->mt->db()->fetch_category( $category_id );
        } else {
            return $ctx->mt->db()->fetch_folder( $category_id );
        }
    } else {
        return NULL;
    }
}
?>