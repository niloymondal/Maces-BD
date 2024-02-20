<?php

?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta name="description" content="<?php echo get_bloginfo('name').' - '.get_bloginfo('description');?>">
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="theme-color">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="//gmpg.org/xfn/11">
    <link rel="apple-touch-icon" href="<?php echo esc_url(get_template_directory_uri() . '/assets/images/apple-touch-icon.png'); ?>">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
    <?php 
        wp_body_open(); 
        jessejane_page_loading();
    ?>
    <div id="cms-page" class="cms-page">
        <div class="cms-header-wraps">
            <?php 
                jessejane_header_top();
                jessejane_header_layout();
            ?>
        </div>
        <?php  jessejane_page_title_layout();  ?>
        <div id="cms-main" class="<?php jessejane_main_css_classes(); ?>">
