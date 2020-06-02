<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<h2>Set your preferences</h2>
<?php echo fep_info_output(); ?>
<?php echo Fep_Form::init()->form_field_output( 'settings' ); ?>
