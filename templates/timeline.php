<?php include_once('popup.php'); ?>
<div class="timeline__container">
    <?php
        $mgr    =   new Timeline();
        $dates  =   $mgr->getDates();
        $preload_number_items   =   $mgr->getMaxLoadItems();

        if( is_array($dates) && count($dates) ):
    ?>
        <div class="timeline-dots-wrap">
            <div class="timeline__line"></div>
                <ul class="timeline-dots">
                    <?php foreach( $dates as $key => $date ): ?>
                        <li id="timeline-<?php echo $key; ?>" term="<?php echo $date->term_id; ?>"><button><?php echo $date->name; ?></button></li>
                    <?php endforeach; ?>
                </ul><!-- /timeline-dots -->
			<?php /*<div class="timeline-dots" data-activeitem=""></div>*/ ?>
		</div>

		<?php include('carousel-buttons.php'); ?>
            <div class="timeline-carousel__container">
    <?php
            foreach( $dates as $key => $date ):
                if( $key >= $preload_number_items ):
                    echo '<div class="timeline-'.$key.'" style="display:none;"><div class="loading centerDiv"><div class="spinner"><span class="ball-1"></span><span class="ball-2"></span><span class="ball-3"></span><span class="ball-4"></span><span class="ball-5"></span><span class="ball-6"></span><span class="ball-7"></span><span class="ball-8"></span></div></div></div><!-- /timeline-carousel timeline-'.$key.' -->';
                else:
					$query  =   $mgr->getByDate($date->term_id);
					if( $query->have_posts() ):

						$total_posts    =   $query->post_count;
						$addClass       =   ( $total_posts > 4 )    ?   ' multiple-owl-carousel' :   ( ($total_posts == 1) ? ' single-owl-carousel':'');

						echo '<div class="timeline-carousel loaded timeline-'.$key.$addClass.'" style="display:'.((!$key) ? '':'').';">';

						while( $query->have_posts() ):
							$query->the_post();
							$content_type   =   get_field('timeline_content_type');
							include('content-type/'.$content_type.'.php');
						endwhile;
						echo '</div> <!-- /timeline-carousel timeline-'.$key.' -->';
					endif;
					wp_reset_postdata();
				endif;
            endforeach;
    ?>
            </div><!-- /timeline-carousel__container -->
        </div><!-- /timeline-dots-wrap -->
    <?php
        endif;
    ?>

    <?php if( $title =  get_field('timeline_big_title', 'option') ): ?>
    <div class="timeline__title" style="background-image: url(<?php echo $title['sizes']['medium']; ?>);"></div>
    <?php endif; ?>

    <div class="timeline__buttons">
		<div class="button_box">
			<a href="#" class="timeline_open_popup">
				<?php if( $icon = get_field('timeline_popup_button_icon','option') ): ?><img src="<?php echo $icon['sizes']['thumbnail']; ?>" /><?php endif; ?>
				<span><?php the_field('timeline_popup_button_label','option'); ?></span>
			</a>
		</div><!-- /button_box -->
		<?php if( $download_file = get_field('timeline_download_file','option') ): ?>
        <div class="button_box">
			<a href="<?php echo $download_file; ?>" target="_blank">
				<?php if( $icon = get_field('timeline_download_button_icon','option') ): ?><img src="<?php echo $icon['sizes']['thumbnail']; ?>" /><?php endif; ?>
				<span><?php the_field('timeline_download_button_label','option'); ?></span>
			</a>
        </div><!-- /button_box -->
		<?php endif; ?>
    </div><!-- /timeline__buttons -->

</div><!-- /timeline__container -->
