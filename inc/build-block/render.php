<?php
/**
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 */
?>
<p <?php echo get_block_wrapper_attributes(); ?>>
  <div><?php echo(time()); echo(do_shortcode("[display_lightning_upsells]")); ?></div>
</p>
