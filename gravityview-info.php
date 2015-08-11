<?php

/**
 * To hide the add, you can add a filter:
 *
 * `add_filter('hide_gravityview_promotion-gravity-forms-constant-contact', '__return_true');`
 *
 * @var boolean
 */
$hide_promo = apply_filters( 'hide_gravityview_promotion-gravity-forms-constant-contact', class_exists('GravityView_Plugin') );

if( $hide_promo ) { return; }

?>

<div class="hr-divider"></div>

<style type="text/css">
	#kws_gravityview_info {
		float: left;
		width: 95%;
		border: 1px solid #ccc;
		padding: 10px 2.3%;
		color: #333;
		margin: 0;
		margin-bottom: 10px;
		background: #fff;
		text-align: center;
		<?php echo isset($_GET['viewinstructions']) ? 'display:none;' : ''; ?>
	}
	#kws_gravityview_info div.aligncenter {
		max-width: 700px;
		padding-top: 10px;
		margin: 0 auto;
		float: none;
	}
	#kws_gravityview_info * {
		text-align: left;
	}
	.rtl #kws_gravityview_info * {
		text-align: right;
	}
	#kws_gravityview_info h3 {
		font-size: 1.2em;
		line-height: 1.5em;
		font-weight: normal;
		color: #444;
	}
	#kws_gravityview_info p, #kws_gravityview_info li {
		font-size: 1.1em;
	}
	#kws_gravityview_info ul.ul-square li {
		list-style: square!important;
	}
	#kws_gravityview_info .email {
		padding: 5px;
		font-size: 15px;
		line-height: 20px;
		margin-bottom: 10px;
	}
	#kws_gravityview_info .submit {
		margin-top: 0;
		text-align:center;
	}
	#kws_gravityview_info .button-primary {
		display:block;
		margin: 5px auto;
		width: 50%;
		min-width: 261px;
		text-align: center;
	}
	#kws_gravityview_info img {
		max-width: 100%;
		margin: 0 auto 10px;
		display: block;
		text-align: center;
	}
</style>

<div id="kws_gravityview_info">
	<div class="aligncenter">
		<a href="https://gravityview.co/pricing/?utm_source=plugin&amp;utm_medium=settings&amp;utm_content=logolink&amp;utm_campaign=gravity-forms-constant-contact" title="<?php esc_attr_e( 'Go to the GravityView Website', 'gravity-forms-constant-contact' ); ?>" class="aligncenter"><img src= "<?php echo plugins_url( '/images/GravityView-612x187.jpg', GFConstantContact::get_file() ); ?>" alt="GravityView Logo" width="306" height="93" /></a>
		<h2><?php esc_html_e('GravityView is the best way to display Gravity Forms entries.', 'gravity-forms-constant-contact'); ?></h2>

		<?php


		echo '<h3>'. sprintf( esc_html__('Do you have form data you want to show on your website? Have you ever copied and pasted entries into your site? That&rsquo;s illogical! %sGravityView%s is here.', 'gravity-forms-constant-contact' ), '<a href="https://gravityview.co/pricing/?utm_source=plugin&amp;utm_medium=settings&amp;utm_content=subheadinglink&amp;utm_campaign=gravity-forms-constant-contact">', '</a>' ) .'</h3>';

		?>

		<ul class="ul-square">
			<li><?php esc_html_e('Drag & drop interface', 'gravity-forms-constant-contact'); ?></li>
			<li><?php esc_html_e('Different layout types - display entries as a table or profiles', 'gravity-forms-constant-contact'); ?></li>
			<li><?php esc_html_e('Preset templates make it easy to get started', 'gravity-forms-constant-contact'); ?></li>
			<li><?php esc_html_e('Great support', 'gravity-forms-constant-contact'); ?></li>
			<li><a href="https://gravityview.co/extensions/?utm_source=plugin&amp;utm_medium=settings&amp;utm_content=extensionslink&amp;utm_campaign=gravity-forms-constant-contact"><?php esc_html_e('Lots of powerful extensions', 'gravity-forms-constant-contact'); ?></a></li>
			<li><?php esc_html_e('30 day money-back Guarantee', 'gravity-forms-constant-contact' ); ?></li>
		</ul>

		<p class="submit"><a href="https://gravityview.co/pricing/?utm_source=plugin&amp;utm_medium=settings&amp;utm_content=buttonlink&amp;utm_campaign=gravity-forms-constant-contact" class="button button-hero button-primary"><?php esc_html_e('Try GravityView Today!', 'gravity-forms-constant-contact'); ?></a></p>

		<?php

		echo wpautop( '<small>'.sprintf( esc_html__('By the way, you may have heard about the Gravity Forms Directory plugin. That\'s by us, too, but it wasn\'t good enough, so we re-wrote it from the ground up to be more simple and way more powerful. Trust us: you&rsquo;ll %slove%s GravityView.', 'gravity-forms-constant-contact'),  '<em>', '</em>' ) .'</small>' );

		?>
	</div>
</div>

<div class="clear"></div>