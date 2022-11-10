<?php

if( ! defined ('ABSPATH') ){
    exit;
}

function basicrum_display_settings_page(){

    if ( ! current_user_can( 'manage_options' ) ) 
    return;
 ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            
            <form action="options.php" method="post">
                <?php
                    settings_fields( 'basicrum_options' );
                    do_settings_sections( 'basicrum' );
                    submit_button();
                ?>
            </form>
    </div>
<?php
}

// Function that shows messages in admin panel 

function basicrum_admin_notices() {
    settings_errors();
}

add_action( 'admin_notices', 'basicrum_admin_notices' );