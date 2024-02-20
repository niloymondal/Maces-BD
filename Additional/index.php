<?php

get_header();
?>
<div class="container">
    <div class="cms-content-row row gutters-grid justify-content-center">
        <div id="cms-content-area" class="<?php jessejane_content_css_class(); ?>">
            <?php if ( have_posts() ) { ?>
                <div class="cms-content-archive">
                    <?php    while ( have_posts() )
                        {
                            the_post();
                            
                            get_template_part( 'template-parts/content' );
                        }
                    ?>
                </div>
                <?php   
                    jessejane_posts_pagination();
                } else {
                    get_template_part( 'template-parts/content', 'none' );
                }
            ?>
        </div>
        <?php jessejane_sidebar(['inner_class' => 'pl-70 pl-tablet-0']); ?>
    </div>
</div>
<?php
get_footer();
