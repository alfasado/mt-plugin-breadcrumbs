package Breadcrumbs::Plugin;

use strict;

sub _hdlr_breadcrumbs {
    my ( $ctx, $args, $cond ) = @_;
    my @breadcrumbs;
    my $glue = $args->{ glue };
    my $blog = $ctx->stash( 'blog' );
    my $category;
    my $folder;
    my $archive_type = $ctx->{ archive_type } || $ctx->{ current_archive_type } || '';
    require MT::Template::Tags::Category;
    require MT::Template::Tags::Archive;
    if ( $archive_type =~ /^(?:Individual|Page)$/ ) {
        my $entry = $ctx->stash( 'entry' );
        unshift( @breadcrumbs, { breadcrumbstype  => $entry->class,
                                 breadcrumbslabel => $entry->title,
                                 breadcrumbslink  => $entry->permalink } );
        $category = $entry->category;
    } elsif ( $archive_type =~ /Category/ ) {
        $category = $ctx->stash( 'archive_category' );
    } elsif ( $archive_type =~ /Folder/ ) {
        $category = $ctx->stash( 'archive_category' );
    } elsif ( $archive_type =~ /(?:Yearly|Monthly|Weekly|Daily|Author)/ ) {
        my $breadcrumbslabel = MT::Template::Tags::Archive::_hdlr_archive_title( $ctx, $args );
        if ( lc( MT->current_language || 'en_us') =~ /^j[ap]$/ ) {
            $breadcrumbslabel =~ s/&#24180;/\x{5E74}/
        }
        unshift( @breadcrumbs, { breadcrumbstype  => lc ( $archive_type ),
                                 breadcrumbslabel => $breadcrumbslabel,
                                 breadcrumbslink  => MT::Template::Tags::Archive::_hdlr_archive_link( $ctx, $args ) } );
    } else {
        return '';
    }
    if ( defined $category ) {
        while ( $category ) {
            local $ctx->{ __stash }{ archive_category } = $category;
            local $ctx->{ __stash }{ category } = $category;
            my $params;
            $params->{ type } = 'Category';
            $params->{ with_index } = $args->{ with_index };
            my $category_count = MT::Template::Tags::Category::_hdlr_category_count( $ctx, $params );
            my $category_link;
            if ( $category_count ) {
                $category_link = MT::Template::Tags::Archive::_hdlr_archive_link( $ctx, $params );
            }
            unshift ( @breadcrumbs,
                    { breadcrumbstype => $category->class,
                      breadcrumbslabel => $category->label,
                      breadcrumbslink => $category_link } );
            $category = $category->parent_category;
        }
    }
    if ( $blog ) {
        unshift ( @breadcrumbs, { breadcrumbstype => 'blog',
                                  breadcrumbslabel => $blog->name,
                                  breadcrumbslink => $blog->site_url } );
        if ( $blog->is_blog ) {
            unshift ( @breadcrumbs,
                    { breadcrumbstype => 'website',
                      breadcrumbslabel => $blog->website->name,
                      breadcrumbslink => $blog->website->site_url } );
        }
    }
    my $builder = $ctx->stash( 'builder' );
    my $tokens = $ctx->stash( 'tokens' );
    my $i = 0;
    my $vars = $ctx->{ __stash }{ vars } ||= {};
    my $res = '';
    foreach my $breadcrumb ( @breadcrumbs ) {
        local $vars->{ __first__ }   = !$i;
        local $vars->{ __last__ }    = ! defined $breadcrumbs[ $i + 1 ];
        local $vars->{ __odd__ }     = ( $i % 2 ) == 0;
        local $vars->{ __even__ }    = ( $i % 2 ) == 1;
        local $vars->{ __counter__ } = $i + 1;
        local $vars->{ __type__ }    = $breadcrumb->{ breadcrumbstype };
        local $ctx->{ __stash }{ breadcrumbslabel } = $breadcrumb->{ breadcrumbslabel };
        local $ctx->{ __stash }{ breadcrumbslink }  = $breadcrumb->{ breadcrumbslink };
        my $out = $builder->build( $ctx, $tokens, $cond );
        return $ctx->error( $builder->errstr ) unless defined $out;
        $res .= $out;
        $res .= $glue if $glue && defined $breadcrumbs[ $i + 1 ];
        $i++;
    }
    return $res;
}

sub _hdlr_breadcrumbs_label {
    my ( $ctx, $args ) = @_;
    return $ctx->stash( 'breadcrumbslabel' );
}

sub _hdlr_breadcrumbs_link {
    my ( $ctx, $args ) = @_;
    return $ctx->stash( 'breadcrumbslink' );
}

1;