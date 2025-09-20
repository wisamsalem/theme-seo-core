<?php
$pid   = get_the_ID();
$title = \ThemeSeoCore\Modules\Titles\TitleGenerator::for_post( $pid );
$desc  = \ThemeSeoCore\Modules\Meta\MetaGenerator::for_post( $pid );
$can   = get_post_meta( $pid, '_tsc_canonical', true );

echo '<p><strong>' . esc_html__( 'Preview', 'theme-seo-core' ) . '</strong></p>';
echo '<div style="border:1px solid #ccd0d4;padding:8px;border-radius:4px;margin-bottom:8px">';
echo '<div style="color:#1a0dab;font-size:18px;line-height:1.2;margin-bottom:4px;">' . esc_html( $title ) . '</div>';
echo '<div style="color:#4d5156;font-size:13px;">' . esc_html( $desc ) . '</div>';
if ( $can ) {
	echo '<div style="color:#006621;font-size:12px;margin-top:4px;">' . esc_html( $can ) . '</div>';
}
echo '</div>';

?>
<p>
	<label for="tsc_title"><strong><?php esc_html_e( 'SEO Title (override)', 'theme-seo-core' ); ?></strong></label><br>
	<input type="text" id="tsc_title" name="tsc_title" class="widefat" value="<?php echo esc_attr( get_post_meta( $pid, '_tsc_title', true ) ); ?>">
</p>
<p>
	<label for="tsc_desc"><strong><?php esc_html_e( 'Meta Description (override)', 'theme-seo-core' ); ?></strong></label><br>
	<textarea id="tsc_desc" name="tsc_desc" class="widefat" rows="3"><?php echo esc_textarea( get_post_meta( $pid, '_tsc_desc', true ) ); ?></textarea>
</p>
<p>
	<label for="tsc_canonical"><strong><?php esc_html_e( 'Canonical URL (override)', 'theme-seo-core' ); ?></strong></label><br>
	<input type="url"
