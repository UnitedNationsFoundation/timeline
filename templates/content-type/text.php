<?php
    $timeline_background                =   get_field('timeline_background');
    $timeline_text_background_color     =   get_field('timeline_text_background_color');
    $timeline_text_background_opacity   =   get_field('timeline_text_background_opacity');
    $timeline_background_color          =   get_field('timeline_background_color');
    $timeline_background_image          =   get_field('timeline_background_image');
    $timeline_background_video          =   get_field('timeline_background_video');
    $timeline_background_video_url      =   get_field('timeline_background_video_url');
    $timeline_overlay_color             =   get_field('timeline_overlay_color');
    $timeline_overlay_opacity           =   get_field('timeline_overlay_opacity');

    $timeline_text                      =   get_field('timeline_text');
    $timeline_text_position             =   get_field('timeline_text_position');
    $addclass                           =   ( get_field('timeline_overlay') )    ?   ' no-header-overlay':'';
?>
<div class="timeline-item-post<?php echo $addclass; ?>">
    <div class="background__cont" <?php if($timeline_background_image && $timeline_background == 'image') { ?>style="background-image: url(<?php echo $timeline_background_image['sizes']['background-size']; ?>);"<?php } elseif ($timeline_background_color) { ?>style="background-color: <?php echo $timeline_background_color; ?>;"<?php } ?>>
        <div class="overlay__box" style="<?php if($timeline_overlay_color) { ?>background-color: <?php echo $timeline_overlay_color; ?>;<?php } ?><?php if($timeline_overlay_opacity) { ?>opacity: 0.<?php echo $timeline_overlay_opacity; ?>;<?php } ?>"></div>
        <?php if ($timeline_background_video) { ?>
            <div class="video">
                <video autoplay muted loop><source src="<?php echo $timeline_background_video; ?>" type="video/mp4"></video>
            </div>
           <?php } elseif ($timeline_background_video_url) { ?>
            <div class="video">
                <?php echo wp_oembed_get( $timeline_background_video_url, array('controls' => 0, 'autoplay' => 1,'showinfo' => 0)) ?>
            </div>
        <?php } ?>
    </div>

    <div class="post__cont">
        <div class="text__box__row <?php echo $timeline_text_position; ?>">
            <div class="text__box <?php if($timeline_text_background_color) { ?>text__box--with-back<?php } ?>">
                <div class="text__box--back" style="<?php if($timeline_text_background_color) { ?>background-color: <?php echo $timeline_text_background_color; ?>;<?php } ?><?php if($timeline_text_background_opacity) { ?>opacity: 0.<?php echo $timeline_text_background_opacity; ?>;<?php } ?>"></div>
                <?php echo $timeline_text; ?>
            </div>
        </div>
    </div>
</div>
