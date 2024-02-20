<?php

get_header();
$classes = ['cms-content-container'];
if ( class_exists('\Elementor\Plugin') && \Elementor\Plugin::$instance->documents->get( get_the_ID() )->is_built_with_elementor() ) {
    $classes[] = 'elementor-container';
    $is_built_with_elementor = true;
} else {
    $classes[] = 'container';
    $is_built_with_elementor = false;
}

?>
    <div class="<?php echo jessejane_nice_class($classes);?>">
        <?php 
            if ( $is_built_with_elementor ) { 
                while ( have_posts() )
                {
                    the_post();
                    the_content();
                }
            } else {
        ?>
            <div class="row cms-content-row gutters-grid justify-content-center">
                <div id="cms-content-area" class="<?php jessejane_content_css_class(['content_col'=> 'page_content_col','sidebar_pos' => 'page_sidebar_pos']); ?>">
                    <?php
                        while ( have_posts() )
                        {
                            the_post();
                            get_template_part( 'template-parts/content', 'page' );
                            if ( comments_open() || get_comments_number() )
                            {
                                comments_template();
                            }
                        }
                    ?>
                </div>
                <?php jessejane_sidebar(['content_col'=> 'page_content_col', 'sidebar_pos' => 'page_sidebar_pos', 'inner_class' => 'pl-70 pl-tablet-0']); ?>
            </div>
        <?php } ?>
    </div>
<?php
get_footer();