<?php

$heading = jessejane_get_theme_opt('heading_404_page','');
$subheading = jessejane_get_theme_opt('subheading_404_page', '');
$content_404_page = jessejane_get_theme_opt( 'content_404_page', esc_html__('The webpage you are looking for is not here!', 'jessejane'));
$btn_text_404_page = jessejane_get_theme_opt( 'btn_text_404_page', esc_html__('Back To Home', 'jessejane') );
get_header();
?>
    <div class="container cms-content-container">
        <div id="cms-content-area" class="cms-content-area cms-404-content-area text-center row align-items-center">
            <div class="col-12">
                <div class="cms-heading text-200 lh-1 text-accent"><?php echo esc_html__('404','jessejane') ?></div>
                <div class="cms-heading text-75 text-tablet-extra-45 text-mobile-25 empty-none"><?php echo esc_html($heading); ?></div>
                <div class="cms-heading text-45 text-tablet-extra-30 text-mobile-20 empty-none"><?php echo esc_html($subheading); ?></div>
                <div class="cms-heading text-18 text-tablet-30 mb-20"><?php 
                    printf('%s', $content_404_page);
                ?></div>
                <div class="mt-20">
                    <a class="btn btn-fill btn-accent text-white btn-hover-primary text-hover-white" href="<?php echo esc_url(home_url('/')); ?>">
                        <span class="cms-btn-content">
                            <span class="cms-btn-icon"><i class="cmsi-arrow-left rtl-flip"></i></span>
                            <span class="cms-btn-text"><?php 
                                printf('%s', $btn_text_404_page);
                            ?></span>
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </div>
<?php
get_footer();
