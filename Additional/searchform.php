<?php

$search_placeholder = jessejane_get_opts('search_field_placeholder', esc_html__('Search&hellip;', 'jessejane'));
?>
<form role="search" method="get" class="cms-search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<input type="search" class="cms-search-field" placeholder="<?php echo esc_attr( $search_placeholder ); ?>" value="<?php echo get_search_query(); ?>" name="s" />
	<button type="submit" class="cms-search-submit" value="<?php echo esc_attr_x( 'Search', 'submit button', 'jessejane' ); ?>"></button>
</form>