<?php

if ( post_password_required() ) {
    return;
}
$comments_number = absint( get_comments_number() );
$post_comments_form_on = jessejane_get_theme_opt( 'post_comments_form_on', true );
$wrap_class = 'comments-area cms-no-comments';
if(have_comments()) $wrap_class = 'comments-area';

if(is_page()) $wrap_class .= ' cms-page-comment';

if($post_comments_form_on) : ?>
    <div id="comments" class="<?php echo esc_attr($wrap_class);?>">
        <?php
        // You can start editing here -- including this comment!
        if ( have_comments() ) : ?>
            <div class="comment-list-wrap">
                <h3 class="comments-title"><?php
                    printf(
                        /* translators: 1: Number of comments, 2: Post title. */
                        _nx(
                            '%1$s Comment',
                            '%1$s Comments',
                            $comments_number,
                            'comments title',
                            'jessejane'
                        ),
                        number_format_i18n( $comments_number )
                    );
                ?></h3>
                <ol class="commentlist">
                    <?php
                        wp_list_comments( array(
                            'style'      => 'ul',
                            'short_ping' => true,
                            'callback'   => 'jessejane_comment_list'
                        ) );
                    ?>
                </ol>
                <nav class="navigation comments-pagination mt-40 empty-none"><?php 
                    //the_comments_navigation(); 
                    paginate_comments_links([
                        'prev_text' => jessejane_pagination_prev_text(),
                        'next_text' => jessejane_pagination_next_text()
                    ]); 
                ?></nav>
            </div>
            <?php if ( ! comments_open() ) : ?>
                <div class="no-comments"><?php esc_html_e( 'Comments are closed.', 'jessejane' ); ?></div>
            <?php
            endif;

        endif; // Check for have_comments().
        comment_form(jessejane_comment_form_args());
    ?>
    </div>
<?php endif;